#!/bin/bash

# Laravel Code Quality Setup Script
# This script installs code quality tools for GitHub Actions

echo "🚀 Setting up Laravel Code Quality Tools..."

# Install PHP CodeSniffer
echo "📦 Installing PHP CodeSniffer..."
composer require --dev squizlabs/php_codesniffer

# Install PHPStan (optional, for advanced workflow)
echo "📦 Installing PHPStan..."
composer require --dev phpstan/phpstan

# Install Enlightn (optional, for security scanning)
echo "📦 Installing Enlightn..."
composer require --dev enlightn/enlightn

# Create PHPStan configuration
echo "⚙️ Creating PHPStan configuration..."
cat > phpstan.neon << EOF
parameters:
    level: 5
    paths:
        - app/
    excludePaths:
        - app/Console/Kernel.php
        - app/Exceptions/Handler.php
    checkMissingIterableValueType: false
EOF

# Create PHP CodeSniffer configuration
echo "⚙️ Creating PHP CodeSniffer configuration..."
cat > phpcs.xml << EOF
<?xml version="1.0"?>
<ruleset name="Laravel">
    <description>Laravel Coding Standards</description>
    
    <file>app/</file>
    <file>tests/</file>
    
    <rule ref="PSR12"/>
    
    <rule ref="PSR1.Methods.CamelCapsMethodName.NotCamelCaps"/>
    
    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="lineLimit" value="120"/>
            <property name="absoluteLineLimit" value="150"/>
        </properties>
    </rule>
    
    <rule ref="Squiz.WhiteSpace.FunctionSpacing">
        <properties>
            <property name="spacing" value="1"/>
        </properties>
    </rule>
</ruleset>
EOF

# Add scripts to composer.json
echo "📝 Adding scripts to composer.json..."
if ! grep -q '"scripts"' composer.json; then
    # Add scripts section if it doesn't exist
    sed -i '' 's/"require-dev": {/"require-dev": {\n    },\n    "scripts": {\n        "test": "php artisan test",\n        "test-coverage": "php artisan test --coverage",\n        "cs": "phpcs --standard=PSR12 app\/ tests\/",\n        "cs-fix": "phpcbf --standard=PSR12 app\/ tests\/",\n        "stan": "phpstan analyse app\/ --level=5",\n        "security": "php artisan enlightn --report",\n        "quality": "composer cs && composer stan && composer security"\n    }/' composer.json
else
    echo "⚠️ Scripts section already exists in composer.json"
    echo "Please manually add these scripts:"
    echo '  "test": "php artisan test"'
    echo '  "test-coverage": "php artisan test --coverage"'
    echo '  "cs": "phpcs --standard=PSR12 app/ tests/"'
    echo '  "cs-fix": "phpcbf --standard=PSR12 app/ tests/"'
    echo '  "stan": "phpstan analyse app/ --level=5"'
    echo '  "security": "php artisan enlightn --report"'
    echo '  "quality": "composer cs && composer stan && composer security"'
fi

echo "✅ Code quality setup complete!"
echo ""
echo "📋 Available commands:"
echo "  composer test          - Run tests"
echo "  composer test-coverage - Run tests with coverage"
echo "  composer cs            - Run code style check"
echo "  composer cs-fix        - Fix code style issues"
echo "  composer stan          - Run static analysis"
echo "  composer security      - Run security scan"
echo "  composer quality       - Run all quality checks"
echo ""
echo "🎯 Next steps:"
echo "1. Commit these changes to your repository"
echo "2. Set up GitHub Actions workflow"
echo "3. Configure GitHub secrets for AWS"
echo "4. Push to trigger your first CI/CD run!" 