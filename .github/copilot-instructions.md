# XoopsCore (2.6) — Copilot Instructions

<!-- Generic XOOPS conventions: see .github/xoops-copilot-template.md for reuse in other repos -->


## About This Repository

XoopsCore is the XOOPS 2.6 CMS framework. It provides a modern, PSR-4 autoloaded architecture with the Xoops\ and Xmf\ namespaces, a kernel layer for legacy compatibility, and a full module system for building dynamic web applications.

## Project Layout

```
xoops_lib/Xoops/            # Modern OOP library (namespace: Xoops\)
xoops_lib/Xmf/              # XMF utility library (namespace: Xmf\)
xoops_lib/Xoops.php          # Main bootstrap
xoops_lib/vendor/            # Composer dependencies
htdocs/kernel/               # Legacy kernel classes
htdocs/class/                # Legacy class library
htdocs/include/              # Legacy include files
htdocs/modules/              # Built-in and installable modules
htdocs/themes/               # Theme files
htdocs/install/              # Installation wizard
console/                     # CLI commands
tests/unit/                  # PHPUnit tests
.github/workflows/ci.yml    # GitHub Actions: tests, coverage
```

## Build & Test

```bash
cp composer.json.dist composer.json   # Create composer config
composer install                       # Install dependencies
xoops_lib/vendor/bin/phpunit          # Run PHPUnit tests
```

Composer vendors install to `xoops_lib/vendor/` (non-standard path). PHPUnit config is in `phpunit.xml.dist` with four test suites (legacyclass, kernel, xmflib, xoopslib).

## PHP Compatibility

Code must run on PHP 7.2 through 8.5. Do not use language features introduced after PHP 7.2, including but not limited to PHP 7.3+ (trailing commas in function calls, flexible heredoc/nowdoc), PHP 7.4+ (typed properties, arrow functions, null coalescing assignment `??=`, numeric literal separators, spread operator in arrays), and PHP 8.0+ (named arguments, match expressions, union type hints in signatures, enums, fibers, readonly properties, intersection types, `never` return type, first-class callable syntax, constructor promotion). CI tests all versions in the matrix.

## Coding Conventions

- Follow PSR-12 coding standard (custom variant PSR2-XOOPS defined in `phpcs.xml.dist`).
- Every source file begins with the XOOPS copyright header block.
- Class docblocks include `@category`, `@package`, `@author`, `@copyright`, `@license`, and `@link` tags.
- Use `self::` for class constants (not `static::`). PHPStan level max cannot resolve late static binding on constants and reports `mixed`.
- Prefer `\Throwable` in catch blocks over `\Exception` to cover both exceptions and errors.
- Use `trigger_error()` with `E_USER_WARNING` for non-fatal failures. Use `basename()` in error messages to avoid exposing server paths.

## XOOPS 2.6 Architecture

- **PSR-4 Autoloading**: `Xoops\` maps to `xoops_lib/Xoops/`, `Xmf\` maps to `xoops_lib/Xmf/`.
- **Bootstrap**: `Xoops::getInstance()` provides the main application object.
- **Module system**: Modules reside in `htdocs/modules/` with standardized directory layout.
- **Legacy kernel**: `htdocs/kernel/` contains backward-compatible ORM and handler classes.
- **Template engine**: Smarty-based with templates in `htdocs/themes/` and module `templates/`.

## XOOPS Compatibility Layer

XOOPS 2.6 uses `\Xoops::getInstance()` for core access. Libraries check `class_exists('Xoops', false)` to detect 2.6+ and fall back to XOOPS 2.5 globals (`$GLOBALS['xoopsModule']`, `xoops_getHandler()`) when needed.

## Security Practices

- All user input must be filtered. Use `Xmf\Request::getVar()` or `Xmf\FilterInput::clean()` — never access `$_GET`, `$_POST`, or `$_REQUEST` directly.
- Escape all output with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` or use Smarty auto-escaping.
- Use parameterized queries via XOOPS database handlers — never concatenate user input into SQL.
- Pass `['allowed_classes' => false]` to any `unserialize()` calls to prevent PHP Object Injection.
- Validate file paths with `realpath()` and boundary checks to prevent directory traversal.

## Testing Guidelines

- Test classes extend `\PHPUnit\Framework\TestCase`.
- Tests are organized in `tests/unit/` mirroring source structure.
- Tests must be fully isolated — no XOOPS installation required for unit tests.
- Name test methods `test{MethodName}` or `test{MethodName}{Scenario}`.

## Pull Request Checklist

1. Code follows PSR-12 and passes code style checks.
2. Static analysis passes with no new errors beyond the baseline.
3. Tests pass on all supported PHP versions (7.2-8.5).
4. New public methods have PHPDoc with `@param`, `@return`, and `@throws` tags.
5. New functionality has corresponding unit tests in `tests/unit/`.
6. Changes are documented in the changelog.
7. No direct superglobal access — use `Xmf\Request` or equivalent.
