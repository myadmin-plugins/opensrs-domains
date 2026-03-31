# MyAdmin OpenSRS Domains Plugin

Composer package `detain/myadmin-opensrs-domains` — OpenSRS domain registration, renewal, transfer, and management plugin for the MyAdmin control panel.

## Commands

```bash
composer install                # install PHP deps
composer test                   # run PHPUnit tests
vendor/bin/phpunit              # run tests directly
vendor/bin/phpunit --filter PluginTest  # run single test class
```

## Architecture

**Namespace**: `Detain\MyAdminOpenSRS\` → `src/` · **Tests**: `Detain\MyAdminOpenSRS\Tests\` → `tests/`

**Core classes**:
- `src/Plugin.php` — Symfony EventDispatcher hooks: `domains.load_addons`, `domains.activate`, `domains.settings`, `function.requirements`. Static methods `getAddon()`, `getActivate()`, `getSettings()`, `getRequirements()`, `activate_domain()`, `doAddonEnable()`, `doAddonDisable()`
- `src/OpenSRS.php` — Domain API wrapper. Static methods: `request()`, `xmlRequest()`, `getCookieRaw()`, `getNameserversRaw()`, `createNameserverRaw()`, `deleteNameserverRaw()`, `transferCheck()`, `lookupGetDomain()`, `lookupDomain()`, `checkDomainAvailable()`, `lookupDomainPrice()`, `lock()`, `whoisPrivacy()`, `listDomainsByExpireyDate()`, `redeemDomain()`, `searchDomain()`, `ackEvent()`, `pollEvent()`, `getEventTypes()`, `response_to_array()`. Instance method: `loadDomainInfo()`
- `src/openSRS_loader.php` — Helper functions: `processOpenSRS()`, `array2object()`, `object2array()`, `convertArray2Formatted()`, `convertFormatted2array()`, `array_filter_recursive()`
- `src/openSRS_config.php` — Constants: `OSRS_USERNAME`, `OSRS_KEY`, `OSRS_HOST`, `OSRS_SSL_PORT`, `OSRS_PROTOCOL`, `OSRS_VERSION`, `CRYPT_TYPE`. Loads `include/config/config.settings.php` for `OPENSRS_USERNAME`/`OPENSRS_KEY`

**CLI scripts** (`bin/`): 60+ PHP scripts organized by API domain:
- `bin/lookup/` — `lookupDomain.php`, `lookupGetDomain.php`, `lookupGetPrice.php`, `lookupLookupDomain.php`, `lookupGetBalance.php`, `lookupNameSuggest.php`, `premiumDomain.php`, `suggestDomain.php`, etc.
- `bin/provisioning/` — `provActivate.php`, `provRenew.php`, `provSWregister.php`, `provUpdateContacts.php`, etc.
- `bin/transfer/` — `transCheck.php`, `transCancel.php`, `transProcess.php`, `transSendPass.php`, etc.
- `bin/nameserver/` — `nsCreate.php`, `nsDelete.php`, `nsGet.php`, `nsModify.php`, `nsRegistryAdd.php`, `nsRegistryCheck.php`
- `bin/dnszone/` — `dnsCreate.php`, `dnsDelete.php`, `dnsGet.php`, `dnsSet.php`, `dnsReset.php`, `dnsForce.php`
- `bin/authentication/` — `authChangeOwnership.php`, `authChangePassword.php`, `authSendAuthcode.php`
- `bin/cookie/` — `cookieSet.php`, `cookieDelete.php`, `cookieUpdate.php`, `cookieQuit.php`
- `bin/forwarding/` — `fwdCreate.php`, `fwdDelete.php`, `fwdGet.php`, `fwdSet.php`
- `bin/subreseller/` — `subresCreate.php`, `subresGet.php`, `subresModify.php`, `subresPay.php`
- `bin/subuser/` — `subuserAdd.php`, `subuserDelete.php`, `subuserGet.php`, `subuserModify.php`
- `bin/bulkchange/` — `bulkChange.php`, `bulkSubmit.php`, `bulkTransfer.php`
- `bin/personalnames/` — `persDelete.php`, `persQuery.php`, `persSUregister.php`, `persUpdate.php`

All `bin/` scripts follow the same pattern:
```php
require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php';
use opensrs\Request;
$callstring = json_encode(['func' => 'funcName', 'attributes' => [...]]);
$request = new Request();
$osrsHandler = $request->process('json', $callstring);
```

**Tests** (`tests/`):
- `tests/bootstrap.php` — Defines stub functions (`myadmin_log`, `get_module_settings`, `get_service`, `run_event`, etc.) and constants (`OPENSRS_USERNAME`, `OPENSRS_KEY`, `OPENSRS_PRIVACY_COST`)
- `tests/OpenSRSTest.php` — Tests `OpenSRS` class structure, `response_to_array()`, `getEventTypes()`, method signatures
- `tests/PluginTest.php` — Tests `Plugin` hooks, method signatures, static properties
- `tests/LoaderFunctionsTest.php` — Tests `array2object()`, `object2array()`, `convertArray2Formatted()`, `convertFormatted2array()`, `array_filter_recursive()`
- `tests/samples/` — XML event samples: `XML.DOMAIN.CREATED.txt`, `XML.DOMAIN.DELETED.txt`, `XML.DOMAIN.EXPIRED.txt`, `XML.DOMAIN.REGISTERED.txt`, `XML.DOMAIN.RENEWED.txt`, `XML.TRANSFER.STATUS_CHANGE.txt`, `XML.ORDER.STATUS_CHANGE.txt`, etc.

**Config** (`phpunit.xml.dist`): Bootstrap `tests/bootstrap.php`, coverage on `src/`

## Dependencies

- `php` >= 7.4 with `ext-soap`, `ext-libxml`, `ext-simplexml`, `ext-mbstring`
- `detain/osrs-toolkit-php` >= 3.4.3 — provides `opensrs\Request`, `opensrs\Exception`, `opensrs\APIException`
- `symfony/event-dispatcher` ^5.0||^6.0||^7.0
- `detain/myadmin-plugin-installer` dev-master
- Dev: `phpunit/phpunit` ^9.6

## Conventions

- API calls use `opensrs\Request::process('json', $callstring)` — never call OpenSRS API directly
- All `OpenSRS` static methods use `StatisticClient::tick()` / `StatisticClient::report()` for metrics when available
- Plugin hooks registered via `Plugin::getHooks()` returning `[event => [class, method]]` pairs
- Constants `OPENSRS_USERNAME`, `OPENSRS_PASSWORD`, `OPENSRS_KEY` must be defined before loading `src/openSRS_config.php`
- Commit messages: lowercase, descriptive
- Test stubs defined in `tests/bootstrap.php` — add new stubs there when testing methods that call MyAdmin framework functions

## CI

- `.travis.yml` — Legacy Travis CI config (PHP 5.4–7.1)
- `.scrutinizer.yml` — Scrutinizer CI with coverage
- `.codeclimate.yml` — Code Climate analysis
- `.bettercodehub.yml` — BetterCodeHub PHP config

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
