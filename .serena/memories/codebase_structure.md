# Codebase Structure

```
laravel-clickhouse-eloquent/
├── src/                          # Main source code
│   ├── BaseModel.php             # Core ORM model class
│   ├── Builder.php               # Query builder extension
│   ├── Connection.php            # Database connection handler
│   ├── Grammar.php               # SQL grammar for queries
│   ├── QueryGrammar.php          # Placeholder conversion
│   ├── SchemaGrammar.php         # CREATE TABLE compilation
│   ├── SchemaBuilder.php         # Schema operations
│   ├── Migration.php             # Migration base class
│   ├── RawColumn.php             # Raw SQL expression wrapper
│   ├── WithClient.php            # Client trait
│   ├── CurlerRollingWithRetries.php  # HTTP retry logic
│   ├── ClickhouseServiceProvider.php # Laravel service provider
│   ├── Exceptions/
│   │   └── QueryException.php    # Query error handling
│   └── Expressions/
│       └── InsertArray.php       # Array type helper
│
├── tests/                        # Test suite
│   ├── TestCase.php              # Base test class
│   ├── CreatesApplication.php    # Laravel app bootstrap
│   ├── BaseTest.php              # Core functionality tests
│   ├── CastsTest.php             # Type casting tests
│   ├── CteTest.php               # CTE (Common Table Expression) tests
│   ├── UpdateTest.php            # Update operation tests
│   ├── MultiInstancesTest.php    # Multi-connection tests
│   ├── Models/                   # Test models
│   │   ├── Example.php
│   │   ├── Example2.php
│   │   └── Example3.php
│   ├── migrations/               # Test migrations
│   ├── config/                   # Test config files
│   └── docker/                   # Docker build files
│
├── docs/                         # Documentation
├── .github/workflows/            # CI/CD (GitHub Actions)
├── composer.json                 # PHP dependencies
├── phpunit.xml                   # PHPUnit configuration
├── docker-compose.test.yaml      # Test environment
├── tests.bootstrap.sh            # Test runner script
├── README.md                     # User documentation
├── CLAUDE.md                     # AI assistant guidance
└── CHANGELOG.md                  # Version history
```

## Key Files by Purpose

### ORM Operations
- `BaseModel.php` - Model CRUD operations
- `Builder.php` - Query building and execution

### Query Compilation
- `Grammar.php` - SELECT, INSERT, UPDATE, DELETE SQL
- `QueryGrammar.php` - Parameter placeholder handling
- `SchemaGrammar.php` - DDL statements

### Infrastructure
- `Connection.php` - ClickHouse client integration
- `ClickhouseServiceProvider.php` - Laravel registration
- `Migration.php` - Schema migrations
