# Task Completion Checklist

## Before Marking a Task Complete

### 1. Code Quality
- [ ] Code follows project conventions (see `code_style_conventions.md`)
- [ ] No debug/console output left in code
- [ ] Methods have appropriate visibility
- [ ] Type hints added where applicable

### 2. Testing
- [ ] Run tests: `./vendor/bin/phpunit`
- [ ] All existing tests pass
- [ ] New functionality has test coverage (if applicable)
- [ ] Edge cases considered

### 3. Documentation
- [ ] PHPDoc updated for changed/new methods
- [ ] README.md updated if public API changed
- [ ] CHANGELOG.md updated for significant changes

### 4. Git
- [ ] Changes committed with descriptive message
- [ ] No unintended files included

## Common Pitfalls
- ClickHouse operations are eventually consistent (use `usleep()` in tests if needed)
- Migrations use raw SQL, not Schema builder
