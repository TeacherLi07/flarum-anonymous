# flarum-anonymous v2 — 共享凭据 + Actor 委托架构

> 将 Flarum 论坛改造为匿名社区。
> 用户对外身份为 7 位随机字符串 ("饼干"/Biscuit)。
> 核心机制：Account 认证后将 Session 的 `user_id` 替换为 Biscuit-User 的 ID，
> Flarum 原生用户体系透明处理后续所有行为。

---

## 一、架构对比

### v1: 覆盖层

```
Session → User (phone, UUID) ──→ Post.biscuit_string (字符串)
   │
   └── ~15 个 extend/override 点覆盖 Flarum 原生行为
```

**问题**：每个覆盖点都是潜在泄露点。每新增一个 Flarum 功能（通知/搜索/@提及/扩展）都需额外覆盖。

### v2: Actor 委托

```
Session → Account (phone)
              │
              ├── 登录时 session->put('user_id', biscuitUserId)
              │
              └── 1:N → Biscuit-User (Flarum User)
                            │
                            └── Flarum 原生处理所有后续行为
                                    (Post/Discussion/@mention/通知/搜索/统计/头像...)
```

**优势**：Flarum 用户系统完整复用，零覆盖点，零泄露。

---

## 二、数据模型

### 2.1 Account 表（新建，与 users 分离）

```sql
account_biscuits:
  id              INT UNSIGNED AUTO_INCREMENT PK
  account_user_id INT UNSIGNED (FK → users.id, Account User)
  biscuit_user_id INT UNSIGNED (FK → users.id, Biscuit-User)
  biscuit_string  VARCHAR(7) NOT NULL               -- 对外显示字符串
  note            TEXT NULL                         -- 备注 (仅 Account 可见)
  is_active       TINYINT(1) DEFAULT 1              -- 当前活跃的 Biscuit 会话
  is_frozen       TINYINT(1) DEFAULT 0              -- 是否冻结
  created_at      TIMESTAMP
  deleted_at      TIMESTAMP NULL                    -- 软删除
```

### 2.2 两个 User 角色

| 属性 | Account User | Biscuit-User |
|------|-------------|-------------|
| `username` | Phone hash (内部) | **biscuit_string** (对外) |
| `email` | 真实邮箱 | `{biscuit_string}@anonymous.local` |
| `phone` | 真实手机号 | NULL |
| `password` | 用户设置的密码 | 随机 (不可直接登录) |
| `is_anonymous_account` | true | false |
| 能否登录 | ✓ (仅 phone/email) | ✗ |
| 能否发帖 | ✗ | ✓ |
| 头像 | 无意义 | 自动生成 identicon |
| 数据可见性 | 仅自己+管理员 | 公开 |

### 2.3 无需修改的 Flarum 原生数据

- `posts.user_id` → Biscuit-User ID
- `discussions.user_id` → Biscuit-User ID
- `users.comment_count` → 自动统计
- `users.discussion_count` → 自动统计
- `users.join_time` → Biscuit 出生时间
- `users.avatar_url` → 自动生成的 identicon

**没有任何覆盖层**，Flarum 原生统计和显示全部生效。

---

## 三、认证流程

### 3.1 注册

```
POST /api/register
  phone: "13800138000"
  verificationCode: "123456"
  password: "test123456"

1. 创建 Account User (Flarum User)
   - username = substr(SHA1(phone), 0, 30)
   - phone = 13800138000
   - password = test123456
   - is_anonymous_account = true

2. 生成第一个 Biscuit-User
   - username = 随机 7 位 [A-Za-z0-9]
   - email = {username}@anonymous.local
   - password = random_bytes(32)
   - is_anonymous_account = false

3. 创建 account_biscuits 关联
4. 登录 session → user_id = Biscuit-User ID
```

SMS Mock 同 v1：任意手机号 + 验证码通过。修改 `RegisterWithPhone` listener（监听 `UserSaving` 事件）。

### 3.2 登录

```
POST /login (或 /api/token)
  identification: "13800138000" | "test@test.com"
  password: "test123456"

1. Flarum 原生 LoginUserHandler → 认证 Account User
2. SessionAuthenticator::logIn → session(user_id = accountId)
3. LoggedIn 事件 → 我们的 listener:

   $defaultBiscuit = AccountBiscuit::where('account_user_id', $event->user->id)
       ->where('is_active', true)
       ->whereNull('deleted_at')
       ->first();

   session()->put('account_id', $event->user->id);      // 保留真实身份
   session()->put('user_id', $defaultBiscuit->biscuit_user_id); // 覆盖

4. InjectActorReference → 读取 session('user_id') = Biscuit-User → actor = Biscuit-User
5. 所有下游逻辑透明使用 Biscuit-User
```

**限制**：仅 phone/email 登录（同 v1）。通过 `RestrictLoginIdentification` 中间件实现。

### 3.3 切换饼干

```
POST /api/session/acting
  biscuit_user_id: N

1. AccountBiscuit Policy 验证: 该 biscuit 属于当前 account
2. session->put('user_id', N)
3. session->put('account_biscuit_id', N)
4. 返回新的 Biscuit-User 信息
```

前端 `BiscuitSelector` 组件（同 v1 设计）调用此端点。切换后无需页面刷新——Mithril SPA 自动识别新 actor。

### 3.4 同讨论智能选择

发帖时自动选择本次讨论上一次使用的 Biscuit（同 v1）。

实现方式：前端在 `ComposerBody` 中读取讨论 ID，查找 `PostComposer.lastBiscuit[discussionId]` (localStorage)，无则使用默认活跃 Biscuit。

---

## 四、Biscuit 管理

### 4.1 管理页面 (`/biscuits`)

访问逻辑同 v1：
- 未登录 → 重定向首页
- 登录后 → 读取 `session('account_id')` → 展示该 Account 的所有 Biscuits
- 管理员可附加 `?account_id=N` 参数审计

### 4.2 操作

| 操作 | 实现 |
|------|------|
| 领取新饼干 | 创建 Biscuit-User → account_biscuits INSERT |
| 设置默认 | `UPDATE account_biscuits SET is_active = 1` (取消其他) |
| 编辑备注 | `UPDATE account_biscuits SET note = ?` |
| 冻结 | `UPDATE account_biscuits SET is_frozen = 1` |
| 解冻 | `UPDATE account_biscuits SET is_frozen = 0` |
| 删除 | `DELETE FROM account_biscuits` (软删除) |

### 4.3 槽位获取

完全同 v1 确认的模型：
- 双条件同时满足：`slots = 1 + min(⌊days/slot_days⌋, ⌊posts/slot_posts⌋)`
- 封顶 `slot_max`
- 管理员配置阈值 (Settings)
- 冻结触发：`needsFreeze → FreezeModal`

### 4.4 删除默认饼干

- 若有其他活跃 Biscuit → 自动激活创建最早的
- 若无 → 自动创建新 Biscuit-User

---

## 五、API 端点

### 5.1 Account/Biscuit 管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/account/biscuits` | 当前 Account 的 Biscuit 列表 |
| POST | `/api/account/biscuits` | 领取新 Biscuit |
| PATCH | `/api/account/biscuits/{id}` | 更新备注/活跃/冻结 |
| DELETE | `/api/account/biscuits/{id}` | 删除 (软删除) |
| PATCH | `/api/account/biscuits/batch/freeze` | 批量冻结 |
| POST | `/api/session/acting` | 切换当前活跃 Biscuit |

### 5.2 SMS (同 v1)

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/sms/send` | 发送验证码 (Mock) |

### 5.3 公共端点

**无需新增**。Biscuit-User 就是普通 Flarum User，所有公共端点透明支持：

- `GET /api/users/{id}` → Biscuit-User 信息
- `GET /api/discussions?filter[author]={username}` → 该 Biscuit 的讨论
- `GET /api/posts?filter[author]={username}` → 该 Biscuit 的回帖
- `/u/{username}` → Flarum 原生用户主页

---

## 六、前端

### 6.1 需要保留的 v1 组件

| 组件 | v2 修改 |
|------|---------|
| `BiscuitManagerPage` | 数据源改为 `/api/account/biscuits` |
| `BiscuitList` | 无变化 |
| `BiscuitListItem` | 无变化 |
| `BiscuitSelector` | `onchange` 调用 `POST /api/session/acting` |
| `FreezeBiscuitModal` | 无变化 |
| `BiscuitProfilePage` | **删除** — 使用 Flarum 原生 `/u/:username` |
| `BiscuitLabel` | **删除** — 使用 Flarum 原生 `username` helper |
| `BiscuitIdenticon` | 保留 — 作为 Biscuit-User 的头像生成逻辑 |
| `PhoneRegisterForm` | 移至注册页的 extend |

### 6.2 需要删除的 v1 覆盖

以下全部删除，Flarum 原生处理：

```diff
- override(username, ...)                         // 不需要，Biscuit 就是 User
- override(PostUser.prototype, 'linkChildren', ...) // 不需要
- override(PostUser.prototype, 'userViewItems', ...)// 不需要
- override(DiscussionListItem.prototype, 'view', ...) // 不需要
- override(SignUpModal.prototype, 'fields', ...)  // 保留注册改造
- override(LogInModal.prototype, 'fields', ...)   // 不需要
- extend(PostUser.prototype, 'view', ...)          // 不需要
- extend(ComposerBody.prototype, 'headerItems', ...)// 保留 (BiscuitSelector)
- replaceBiscuitVdom 方法                          // 删除
```

### 6.3 需要保留的后端代码

| 组件 | v2 修改 |
|------|---------|
| `RestrictLoginIdentification` | 保留，无变化 |
| `RegisterWithPhone` | 修改：创建 Account User + Biscuit-User |
| `CreateInitialBiscuit` | 修改：创建 Biscuit-User + account_biscuits 关联 |
| `SlotManager` | 保留，无变化 |
| `BiscuitGenerator` | 保留，无变化 |
| `SmsService` | 保留，Mock 模式 |
| `BiscuitPolicy` | 修改为 AccountBiscuitPolicy |

### 6.4 需要删除的后端代码

```diff
- AddBiscuitToPost listener          // posts 不再有 biscuit_string
- InjectBiscuitToPostData middleware  // 不需要
- BiscuitFilterGambit                 // 使用原生 author filter
- ListDiscussionsByBiscuitController  // 使用原生 User 讨论列表
- UserSerializer 扩展 (displayName, biscuitSlots) // 不需要
- BasicUserSerializer 扩展 (displayName)  // 不需要
- DiscussionSerializer 扩展 (biscuitString, lastPostedBiscuitString) // 不需要
- PostSerializer 扩展 (biscuitString, biscuitIsDeleted, biscuitIsFrozen) // 不需要
- ForumSerializer needBiscuitFreeze  // 保留
- ForumSerializer canManageBiscuits  // 保留
```

---

## 七、数据迁移 (v1 → v2)

```
1. 为每个 v1 用户创建 Account User (若无)
   - 已有 phone 的 → 直接作为 Account
   - 无 phone 的 → 将现有 User 转为 Account (is_anonymous_account = true)

2. 为每个 v1 用户的每个 Biscuit 创建 Biscuit-User:
   - username = biscuit_string
   - email = {biscuit_string}@anonymous.local
   - password = random
   - created_at = biscuits.created_at

3. 创建 account_biscuits 关联 (is_active = biscuits.is_default, is_frozen, note, deleted_at)

4. 迁移 posts:
   - posts.biscuit_string IS NOT NULL → posts.user_id = Biscuit-User.id
   - posts.biscuit_string IS NULL → posts.user_id = Account User.id (旧帖子归 Account)

5. 迁移 discussions (first post 同理)

6. 更新统计: php flarum cache:clear + refresh comment/discussion counts

7. 清理: ALTER TABLE posts DROP COLUMN biscuit_string
          ALTER TABLE discussions DROP COLUMN biscuit_string (如有)
```

---

## 八、保留的 v1 确认细节

| 细节 | 确认 | v2 位置 |
|------|------|---------|
| 饼干 7 位 [A-Za-z0-9] | ✓ 同 v1 | BiscuitGenerator |
| 唯一性：`biscuit_string_lower` UNIQUE | ✓ 同 v1 | account_biscuits 表 |
| 软删除 | ✓ 同 v1 | account_biscuits.deleted_at |
| `is_frozen` 布尔独立列 | ✓ 同 v1 | account_biscuits.is_frozen |
| 双条件槽位模型 | ✓ 同 v1 | SlotManager |
| 管理员配置阈值 | ✓ 同 v1 | Settings + serializeToForum |
| SMS Mock | ✓ 同 v1 | RegisterWithPhone |
| 仅 phone/email 登录 | ✓ 同 v1 | RestrictLoginIdentification |
| `/u/:username` 非管理员重定向 | **简化**：`/u/{account}` → 重定向, `/u/{biscuit}` → Flarum 原生 | Frontend content callback |
| Identicon 头像 (5×5 镜像网格, crispEdges) | ✓ 同 v1 | BiscuitIdenticon → 作为 Biscuit-User 的 avatar_url 生成 |
| 槽位最低 1，冻结触发后端 | ✓ 同 v1 | |
| 删除默认→自动激活/创建 | ✓ 同 v1 | |
| 注册自动生成 UUID username | **简化**：username = 手机号哈希 |

---

## 九、文件结构 (v2)

```
flarum-anonymous/
├── composer.json
├── extend.php
├── migrations/
│   ├── 2026_01_01_000001_create_account_biscuits_table.php
│   ├── 2026_01_01_000002_add_phone_to_users.php
│   ├── 2026_01_01_000003_add_anonymous_flags_to_users.php
│   └── 2026_01_01_000004_migrate_v1_to_v2.php
├── src/
│   ├── Api/Controller/
│   │   ├── ListAccountBiscuitsController.php
│   │   ├── ShowAccountBiscuitController.php
│   │   ├── CreateAccountBiscuitController.php
│   │   ├── UpdateAccountBiscuitController.php
│   │   ├── DeleteAccountBiscuitController.php
│   │   ├── BatchFreezeAccountBiscuitsController.php
│   │   ├── SwitchActingBiscuitController.php
│   │   └── SendSmsCodeController.php
│   ├── Api/Serializer/
│   │   └── AccountBiscuitSerializer.php
│   ├── Auth/SmsService.php
│   ├── Access/AccountBiscuitPolicy.php
│   ├── Listener/
│   │   ├── RegisterWithPhone.php      (UserSaving)
│   │   ├── CreateInitialBiscuit.php   (Registered)
│   │   └── SetActingBiscuitOnLogin.php (LoggedIn)
│   ├── Middleware/
│   │   └── RestrictLoginIdentification.php
│   ├── AccountBiscuit.php
│   ├── BiscuitGenerator.php
│   └── SlotManager.php
├── js/
│   ├── admin.js / forum.js
│   ├── src/admin/index.js
│   └── src/forum/
│       ├── index.js              (~50 行，仅注册 + 初始设置)
│       ├── components/
│       │   ├── BiscuitManagerPage.js
│       │   ├── BiscuitList.js
│       │   ├── BiscuitListItem.js
│       │   ├── BiscuitSelector.js
│       │   ├── FreezeBiscuitModal.js
│       │   ├── BiscuitIdenticon.js  (保留，同 v1)
│       │   └── PhoneRegisterForm.js
│       └── models/
│           └── AccountBiscuit.js
├── less/forum.less
└── locale/zh.yml + en.yml
```

## 十、页面行为矩阵（同 v1 确认）

| 页面 | 游客 | 登录用户 | 管理员 |
|------|------|----------|--------|
| `/` | 讨论列表，作者=饼干名+头像 | 同游客 | 同游客 |
| `/d/:id` | 帖子作者=饼干名+头像 (Flarum 原生) | 同游客 + BiscuitSelector | 同游客 |
| `/u/:biscuit_string` | Flarum 原生用户页 (该饼干的讨论+回帖) | 同游客 | 同游客 |
| `/u/:account_username` | 重定向首页 | 重定向首页 | 正常 (审计) |
| `/biscuits` | 重定向首页 | Biscuit 管理 | 可查任意用户 |
| `/b/:string` | **重定向到 `/u/:string`** | 同左 | 同左 |

---

## 十一、实施顺序

| 步骤 | 内容 |
|------|------|
| 1 | 新建 AccountBiscuit model + migration |
| 2 | Session 切换: SwitchActingBiscuitController + SetActingBiscuitOnLogin |
| 3 | Account Biscuit CRUD API (6 controllers) |
| 4 | 注册改造: RegisterWithPhone + CreateInitialBiscuit (Biscuit-User 创建) |
| 5 | SMS Mock + RestrictLogin (同 v1, 几乎不变) |
| 6 | 前端: AccountBiscuit model + BiscuitSelector + Switch |
| 7 | 前端: BiscuitManagerPage (数据源改为 account biscuits) |
| 8 | 前端: 注册登录页改造 |
| 9 | 前端: Identicon 设为 Biscuit-User 默认头像 |
| 10 | 数据迁移 v1→v2 |
| 11 | 清理 v1 遗留代码 |
| 12 | 测试 |
