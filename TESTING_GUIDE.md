# Automated Testing Guide for Document Request API

## Overview
This guide covers the automated testing setup for the Document Request API, including unit tests, feature tests, and CI/CD integration.

## Test Structure

### Test Files
- `tests/Feature/DocumentRequestApiTest.php` - Main API endpoint tests
- `tests/TestCase.php` - Base test class with helper methods
- `tests/CreatesApplication.php` - Laravel application bootstrapping
- `database/factories/DocumentRequestFactory.php` - Test data generation

### Test Coverage
The automated tests cover:

#### Authentication Tests
- ✅ API key required for all endpoints
- ✅ Invalid API key returns 401
- ✅ API key usage tracking

#### CRUD Operations
- ✅ Create document request
- ✅ Read all document requests (with pagination)
- ✅ Read single document request (by ID and request ID)
- ✅ Update document request
- ✅ Update document request status
- ✅ Delete document request

#### Validation Tests
- ✅ Required fields validation
- ✅ Invalid gender validation
- ✅ Invalid document type validation
- ✅ URL validation for signature

#### Business Logic Tests
- ✅ Request ID auto-generation
- ✅ Status tracking (pending, processing, completed, rejected)
- ✅ Processed timestamp for completed requests
- ✅ Search functionality
- ✅ Filtering by status
- ✅ Statistics endpoint

#### Edge Cases
- ✅ Non-existent document requests (404)
- ✅ Pagination with large datasets
- ✅ Database state verification

## Running Tests

### Prerequisites
1. PHP 8.2+ installed
2. Composer dependencies installed
3. Database configured (SQLite for testing)

### Basic Test Commands

```bash
# Run all tests
php artisan test

# Run only Document Request API tests
php artisan test --filter=DocumentRequestApiTest

# Run tests with coverage report
php artisan test --coverage

# Run tests with verbose output
php artisan test -v

# Run specific test method
php artisan test --filter=test_can_create_document_request
```

### Test Environment Setup

The tests use SQLite in-memory database for fast execution:

```env
# .env.testing (optional)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Test Data Generation

The `DocumentRequestFactory` provides realistic test data:

```php
// Create a single document request
DocumentRequest::factory()->create();

// Create multiple requests
DocumentRequest::factory()->count(10)->create();

// Create with specific status
DocumentRequest::factory()->pending()->create();
DocumentRequest::factory()->completed()->create();

// Create specific document types
DocumentRequest::factory()->sf10()->create();
DocumentRequest::factory()->enrollmentCert()->create();
```

## CI/CD Integration

### GitHub Actions Workflow

The `.github/workflows/ci.yml` file provides:

#### Test Job
- ✅ PHP 8.2 setup
- ✅ MySQL 8.0 service container
- ✅ Dependency installation
- ✅ Database migration
- ✅ Test execution with coverage
- ✅ Codecov integration

#### Code Quality Job
- ✅ PHPStan static analysis
- ✅ PHP CS Fixer code style checks

#### Security Job
- ✅ Security vulnerability scanning

#### Build Job
- ✅ Production build optimization
- ✅ Artifact creation

#### Deployment Jobs
- ✅ Staging deployment (develop branch)
- ✅ Production deployment (main branch)

### Workflow Triggers
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

### Environment Protection
- Staging environment for `develop` branch
- Production environment for `main` branch

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)
```xml
<coverage>
    <report>
        <html outputDirectory="coverage"/>
        <clover outputFile="coverage.xml"/>
        <text outputFile="coverage.txt"/>
    </report>
</coverage>
```

### Test Environment Variables
```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

## Test Helper Methods

### Base TestCase Methods
```php
// Make authenticated API request
$this->authenticatedRequest('GET', '/api/v1/document-requests');

// Create test document request
$this->createDocumentRequest(['status' => 'pending']);

// Get valid test data
$this->getValidDocumentRequestData();
```

## Test Assertions

### Response Assertions
```php
// Check status code
$response->assertStatus(200);

// Check JSON structure
$response->assertJsonStructure([
    'success',
    'data' => ['id', 'request_id', 'name_of_student']
]);

// Check JSON content
$response->assertJson([
    'success' => true,
    'message' => 'Document request created successfully'
]);

// Check validation errors
$response->assertJsonValidationErrors(['name_of_student']);
```

### Database Assertions
```php
// Check record exists
$this->assertDatabaseHas('document_requests', [
    'name_of_student' => 'John Doe',
    'status' => 'pending'
]);

// Check record doesn't exist
$this->assertDatabaseMissing('document_requests', [
    'id' => 999
]);
```

## Performance Testing

### Test Execution Time
- Individual tests: < 1 second
- Full test suite: ~5-10 seconds
- With coverage: ~15-20 seconds

### Memory Usage
- SQLite in-memory database
- Minimal memory footprint
- No file I/O for database operations

## Coverage Requirements

### Minimum Coverage
- **80%** code coverage required
- **100%** API endpoint coverage
- **100%** validation logic coverage

### Coverage Reports
- HTML report: `coverage/index.html`
- XML report: `coverage.xml` (for CI/CD)
- Text report: `coverage.txt`

## Debugging Tests

### Verbose Output
```bash
php artisan test -v --filter=DocumentRequestApiTest
```

### Debug Mode
```php
// Add to test method for debugging
dd($response->json());
```

### Database State
```php
// Check database state
$this->assertDatabaseCount('document_requests', 1);
```

## Best Practices

### Test Organization
1. **Arrange** - Set up test data
2. **Act** - Execute the action
3. **Assert** - Verify the results

### Test Isolation
- Each test is independent
- Database is refreshed between tests
- No shared state between tests

### Test Data
- Use factories for realistic data
- Avoid hardcoded values
- Use meaningful test data

### Naming Conventions
- Test methods: `test_what_it_does_when_condition()`
- Factory methods: `pending()`, `completed()`, `sf10()`

## Troubleshooting

### Common Issues

#### Test Database Connection
```bash
# Clear config cache
php artisan config:clear

# Run migrations
php artisan migrate --env=testing
```

#### Factory Issues
```bash
# Regenerate autoloader
composer dump-autoload
```

#### Coverage Issues
```bash
# Install Xdebug
pecl install xdebug

# Enable coverage
php -d xdebug.mode=coverage artisan test --coverage
```

## Integration with External Tools

### Codecov
- Automatic coverage reporting
- Pull request comments
- Coverage trends

### PHPStan
- Static analysis
- Type checking
- Code quality metrics

### PHP CS Fixer
- Code style enforcement
- PSR-12 compliance
- Automated formatting

## Monitoring and Reporting

### Test Results
- Pass/fail status
- Execution time
- Memory usage
- Coverage percentage

### CI/CD Metrics
- Build time
- Test execution time
- Coverage trends
- Failure rates

## Future Enhancements

### Planned Improvements
- [ ] Performance benchmarks
- [ ] Load testing
- [ ] API contract testing
- [ ] Visual regression testing
- [ ] Accessibility testing

### Test Expansion
- [ ] Unit tests for models
- [ ] Unit tests for services
- [ ] Integration tests with external APIs
- [ ] End-to-end tests with frontend

## Support

For test-related issues:
1. Check the test output for specific error messages
2. Verify database configuration
3. Ensure all dependencies are installed
4. Review the test documentation
5. Check CI/CD logs for environment-specific issues 