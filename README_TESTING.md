# Document Request API - Automated Testing Setup

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Laravel 11
- SQLite (for testing)

### Running Tests

```bash
# Run all tests
php artisan test

# Run only Document Request API tests
php artisan test --filter=DocumentRequestApiTest

# Run tests with coverage (requires Xdebug)
php artisan test --coverage

# Run specific test method
php artisan test --filter=test_can_create_document_request
```

## 📊 Test Coverage

### ✅ What's Tested

#### Authentication & Security
- API key authentication required
- Invalid API key handling
- API key usage tracking

#### CRUD Operations
- Create document requests
- Read all requests (with pagination)
- Read single request (by ID and request ID)
- Update document requests
- Update request status
- Delete document requests

#### Validation
- Required fields validation
- Data type validation
- Business rule validation

#### Business Logic
- Request ID auto-generation
- Status tracking and transitions
- Processed timestamp management
- Search and filtering
- Statistics calculation

#### Edge Cases
- Non-existent resources (404)
- Large dataset pagination
- Database state verification

## 🏗️ Test Architecture

### Test Files Structure
```
tests/
├── Feature/
│   └── DocumentRequestApiTest.php    # Main API tests
├── Unit/
│   └── ExampleTest.php               # Unit tests
├── TestCase.php                      # Base test class
└── CreatesApplication.php            # Laravel bootstrap
```

### Factory Files
```
database/factories/
└── DocumentRequestFactory.php        # Test data generation
```

### Configuration Files
```
phpunit.xml                          # PHPUnit configuration
.github/workflows/ci.yml             # GitHub Actions CI/CD
```

## 🔧 Test Configuration

### PHPUnit Configuration
- **Coverage**: HTML, XML, and text reports
- **Environment**: SQLite in-memory database
- **Cache**: Array driver for fast execution
- **Queue**: Sync driver for immediate processing

### Test Environment Variables
```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

## 🏭 CI/CD Pipeline

### GitHub Actions Workflow

The `.github/workflows/ci.yml` provides:

#### 🔍 Test Job
- PHP 8.2 setup with extensions
- MySQL 8.0 service container
- Dependency installation
- Database migration
- Test execution with coverage
- Codecov integration

#### 📏 Code Quality Job
- PHPStan static analysis (level 8)
- PHP CS Fixer code style checks

#### 🔒 Security Job
- Security vulnerability scanning

#### 🏗️ Build Job
- Production build optimization
- Artifact creation

#### 🚀 Deployment Jobs
- Staging deployment (develop branch)
- Production deployment (main branch)

### Workflow Triggers
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

### Environment Protection
- Staging environment for `develop` branch
- Production environment for `main` branch

## 📈 Performance Metrics

### Test Execution Time
- **Individual tests**: < 1 second
- **Full test suite**: ~1-2 seconds
- **With coverage**: ~3-5 seconds

### Memory Usage
- SQLite in-memory database
- Minimal memory footprint
- No file I/O for database operations

### Coverage Requirements
- **Minimum**: 80% code coverage
- **API endpoints**: 100% coverage
- **Validation logic**: 100% coverage

## 🛠️ Helper Methods

### Base TestCase Methods
```php
// Make authenticated API request
$this->authenticatedRequest('GET', '/api/v1/document-requests');

// Create test document request
$this->createDocumentRequest(['status' => 'pending']);

// Get valid test data
$this->getValidDocumentRequestData();

// Get or create test API key
$this->getTestApiKey();
```

### Factory Methods
```php
// Create with specific status
DocumentRequest::factory()->pending()->create();
DocumentRequest::factory()->completed()->create();

// Create specific document types
DocumentRequest::factory()->sf10()->create();
DocumentRequest::factory()->enrollmentCert()->create();

// Create multiple requests
DocumentRequest::factory()->count(10)->create();
```

## 🧪 Test Examples

### Authentication Test
```php
public function test_api_requires_authentication(): void
{
    $response = $this->getJson('/api/v1/document-requests');
    
    $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'API key is required'
            ]);
}
```

### CRUD Test
```php
public function test_can_create_document_request(): void
{
    $response = $this->withHeaders([
        'X-API-Key' => $this->getTestApiKey()->key
    ])->postJson('/api/v1/document-requests', $this->validDocumentRequestData);
    
    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Document request created successfully'
            ]);
}
```

### Validation Test
```php
public function test_validation_errors_for_required_fields(): void
{
    $response = $this->withHeaders([
        'X-API-Key' => $this->getTestApiKey()->key
    ])->postJson('/api/v1/document-requests', []);
    
    $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'learning_reference_number',
                'name_of_student',
                'gender'
            ]);
}
```

## 🔍 Debugging Tests

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
$this->assertDatabaseHas('document_requests', [
    'name_of_student' => 'John Doe',
    'status' => 'pending'
]);
```

## 📋 Best Practices

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

## 🚨 Troubleshooting

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

## 📚 Integration with External Tools

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

## 📊 Monitoring and Reporting

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

## 🔮 Future Enhancements

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

## 📞 Support

For test-related issues:
1. Check the test output for specific error messages
2. Verify database configuration
3. Ensure all dependencies are installed
4. Review the test documentation
5. Check CI/CD logs for environment-specific issues

## 📄 License

This testing setup is part of the Document Request API project and follows Laravel testing best practices. 