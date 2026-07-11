<?php

namespace TeacherLi07\Anonymous\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

class AccountBiscuitSerializer extends AbstractSerializer
{
    protected $type = 'account-biscuits';

    protected function getDefaultAttributes($biscuit): array
    {
        $actor = $this->getActor();

        $attrs = [
            'biscuitString' => $biscuit->biscuit_string,
            'isActive'      => (bool) $biscuit->is_active,
            'isFrozen'      => (bool) $biscuit->is_frozen,
            'canEdit'       => $this->canEdit($actor, $biscuit),
            'canDelete'     => $this->canEdit($actor, $biscuit),
            'createdAt'     => $this->formatDate($biscuit->created_at),
        ];

        $accountUserId = session('account_id');

        if ($accountUserId && (int) $accountUserId === (int) $biscuit->account_user_id) {
            $attrs['note'] = $biscuit->note;
        }

        if ($actor->isAdmin()) {
            $attrs['note'] = $biscuit->note;
            $attrs['accountUserId'] = $biscuit->account_user_id;
        }

        return $attrs;
    }

    protected function canEdit($actor, $biscuit): bool
    {
        if ($actor->isGuest()) {
            return false;
        }

        $accountUserId = session('account_id');

        if ($accountUserId && (int) $biscuit->account_user_id === (int) $accountUserId) {
            return true;
        }

        return $actor->isAdmin();
    }
}
