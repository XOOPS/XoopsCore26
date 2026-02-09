# XOOPS Copilot Instructions — Reusable Template

<!--
  HOW TO USE THIS TEMPLATE:
  1. Copy this file to your XOOPS module's `.github/copilot-instructions.md`
  2. Replace the {PLACEHOLDERS} in "About This Repository" and "Project Layout"
  3. Adjust "Build & Test" commands to match your composer.json scripts
  4. Remove or add sections as needed for your module
  5. Delete this comment block
-->

## About This Repository

{MODULE_NAME} is a XOOPS module that {SHORT_DESCRIPTION}.

## Project Layout

```
{ADJUST THIS TREE TO MATCH YOUR MODULE}
class/                    # Module classes
include/                  # Include files (functions, common setup)
language/                 # Language files (english/, etc.)
templates/                # Smarty templates
sql/                      # SQL schema files
admin/                    # Admin panel pages
docs/                     # Documentation
```

## Build & Test

```bash
composer install          # Install dependencies
composer test             # Run PHPUnit tests
composer lint             # Check code style (PSR-12)
composer fix              # Auto-fix code style issues
```

## PHP Compatibility

Code must run on PHP 7.4 through 8.5. Do not use features exclusive to PHP 8.0+ (named arguments, match expressions, union type hints in signatures, enums, fibers, readonly properties, intersection types, `never` return type, first-class callable syntax).

## XOOPS Coding Conventions

- Follow PSR-12 coding standard.
- Every source file begins with the XOOPS copyright header block:
  ```php
  <?php
  /*
   You may not change or alter any portion of this comment or credits
   of supporting developers from this source code or any supporting source code
   which is considered copyrighted (c) material of the original comment or credit authors.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
   */
  ```
- Class docblocks include `@category`, `@package`, `@author`, `@copyright`, `@license`, and `@link` tags.
- Use `self::` for class constants (not `static::`). PHPStan level max cannot resolve late static binding on constants and reports `mixed`.
- Prefer `\Throwable` in catch blocks over `\Exception` to cover both exceptions and errors on PHP 7+.
- Use `trigger_error()` with `E_USER_WARNING` for non-fatal failures. Use `basename()` in error messages to avoid exposing server paths.
- Suppress PHP-native warnings with `@` only when a subsequent `=== false` check and explicit `trigger_error()` provide a cleaner error path.

## XOOPS Compatibility Layer

XOOPS has two major generations with different APIs. Code must support both:

- **XOOPS 2.6+**: Use `class_exists('Xoops', false)` to detect. Access via `\Xoops::getInstance()`.
- **XOOPS 2.5.x**: Fall back to globals (`$GLOBALS['xoopsModule']`, `$GLOBALS['xoopsConfig']`) and helper functions (`xoops_getHandler()`, `xoops_getModuleHandler()`).
- Never assume XOOPS is present at runtime — libraries (like XMF) may be used in standalone contexts.
- Use the `class_exists()` check with `false` as the second parameter to avoid triggering autoload.
- For module helpers, use `Xmf\Module\Helper::getHelper($dirname)` which handles the 2.5/2.6 detection internally.

## Security Practices

- All user input must be filtered. Use `Xmf\Request::getVar()` or `Xmf\FilterInput::clean()` — never access `$_GET`, `$_POST`, or `$_REQUEST` directly.
- Escape all output with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` or use Smarty auto-escaping in templates.
- Use parameterized queries via XOOPS database handlers — never concatenate user input into SQL.
- Pass `['allowed_classes' => false]` to any `unserialize()` calls to prevent PHP Object Injection.
- Validate file paths with `realpath()` and boundary checks to prevent directory traversal.
- When generating PHP code (config files, caches), use `var_export()` — never string interpolation with user data.
- For file operations, follow the defensive pattern: exists -> size check -> readable check -> read -> verify not false.

## Testing Guidelines

- Test classes extend `\PHPUnit\Framework\TestCase`.
- Tests must be fully isolated — no XOOPS installation required.
- Name test methods `test{MethodName}` or `test{MethodName}{Scenario}`.
- Use `try/finally` for temp file cleanup so files are removed even when assertions fail.
- Assert return values before using them (e.g., `$this->assertNotFalse($fh)` after `fopen()`).
- Suppress expected warnings with `@` in test calls (e.g., `@ClassName::methodThatTriggersError()`).

## Pull Request Checklist

1. Code follows PSR-12 and passes linting.
2. Static analysis passes with no new errors.
3. Tests pass on all supported PHP versions (7.4-8.5).
4. New public methods have PHPDoc with `@param`, `@return`, and `@throws` tags.
5. New functionality has corresponding unit tests.
6. Changes are documented in the changelog.
7. No hardcoded encoding strings — use class constants or `_CHARSET`.
8. No direct superglobal access — use `Xmf\Request` or equivalent.
