# Post-Migration TODO List

## Immediate Tasks

- [ ] Run test suite to verify all functionality works correctly
  ```bash
  ./vendor/bin/phpunit
  ```

- [ ] Verify all ClickHouse operations in production/staging environment
  - [ ] SELECT queries
  - [ ] INSERT operations
  - [ ] UPDATE operations (ALTER TABLE UPDATE)
  - [ ] DELETE operations (ALTER TABLE DELETE)
  - [ ] JOIN queries
  - [ ] Aggregation queries

## Code Quality Improvements

### Fix PHP 8.3 Deprecation Warnings

The following files have implicit nullable parameters that should be explicitly marked:

- [ ] `src/ClickhouseBuilder/Query/BaseBuilder.php`
  - Lines: 303, 359, 496, 574, 592, 609, 626, 643, 660, 1558, 1657, 1689, 1748, 1782, 1795
  - Change `$param = null` to `?Type $param = null`

- [ ] `src/ClickhouseBuilder/Query/Traits/SampleComponentCompiler.php`
  - Line: 17

- [ ] `src/ClickhouseBuilder/Query/Limit.php`
  - Line: 35

- [ ] `src/ClickhouseBuilder/Query/From.php`
  - Line: 111 (multiple parameters)

- [ ] `src/ClickhouseBuilder/Query/JoinClause.php`
  - Line: 277

### Code Modernization (Optional)

- [ ] Update to use PHP 8.3 typed properties where applicable
- [ ] Replace array type hints with more specific types where possible
- [ ] Add PHPStan or Psalm for static analysis
- [ ] Update to use constructor property promotion
- [ ] Replace Enum library with native PHP 8.1 enums (breaking change)

## Documentation

- [ ] Update README.md to reflect vendor code internalization
- [ ] Document any custom modifications made to the migrated code
- [ ] Add CONTRIBUTING.md with guidelines for modifying the internal packages
- [ ] Add PHPDoc blocks for any undocumented public methods

## Dependency Management

- [ ] Monitor `myclabs/php-enum` for updates or consider migrating to native PHP enums
- [ ] Monitor `guzzlehttp/guzzle` for security updates
- [ ] Monitor `smi2/phpclickhouse` for updates
- [ ] Set up automated dependency checking (e.g., Dependabot)

## Testing

- [ ] Add unit tests for migrated ClickhouseBuilder code
- [ ] Add integration tests for ClickhouseClient code
- [ ] Verify edge cases work correctly:
  - [ ] Complex JOIN queries
  - [ ] Subqueries
  - [ ] ARRAY JOIN operations
  - [ ] PREWHERE clauses
  - [ ] SAMPLE clauses
  - [ ] FORMAT clauses

## Long-term Maintenance

- [ ] Consider refactoring to remove unused Laravel integration code in `src/ClickhouseBuilder/Integrations/`
- [ ] Evaluate if all ClickhouseClient features are needed
- [ ] Set up CI/CD pipeline to run tests on pull requests
- [ ] Create versioning strategy for internal packages
- [ ] Document upgrade path from old Tinderbox packages for external users

## Known Issues

- PHP 8.3 deprecation warnings for implicit nullable parameters (cosmetic, doesn't affect functionality)
- None critical at this time

## Notes

- The `functions.php` file provides global helper functions:
  - `tp()` - tap function for debugging
  - `array_flatten()` - flatten multi-dimensional arrays
  - `raw()` - create raw SQL expressions
  - `into_memory_table()` - create temporary in-memory tables
  - `file_from()` - create file objects for bulk operations

- Dependencies now explicitly managed:
  - `myclabs/php-enum` - for enum functionality
  - `guzzlehttp/guzzle` - for HTTP client in ClickhouseClient

## Migration Verification Checklist

- [x] Vendor packages removed from composer.json
- [x] Source code copied to src/ directory
- [x] Namespaces updated throughout
- [x] Autoloader configured in composer.json
- [x] `composer dump-autoload` executed
- [x] `composer update` executed successfully
- [x] Classes load without fatal errors
- [ ] All existing tests pass
- [ ] Integration tests pass
- [ ] Documentation updated