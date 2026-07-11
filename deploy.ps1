param(
    [string]$message = "update"
)

$ErrorActionPreference = "Stop"

Write-Host "=== Pushing to GitHub ===" -ForegroundColor Cyan
git add -A
git commit -m $message
git push origin master

Write-Host "=== Pulling on server ===" -ForegroundColor Cyan
ssh hkweb "cd /var/www/flarum/packages/flarum-anonymous && git pull origin master"

Write-Host "=== Updating composer + clearing cache ===" -ForegroundColor Cyan
ssh hkweb "cd /var/www/flarum && COMPOSER_ALLOW_SUPERUSER=1 composer update teacherli07/flarum-anonymous --no-interaction && php flarum cache:clear && php flarum info"

Write-Host "=== Done ===" -ForegroundColor Green
