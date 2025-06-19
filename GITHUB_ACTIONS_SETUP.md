# GitHub Actions Setup Guide

This guide will help you set up GitHub Actions for your Laravel application with automated testing, code quality checks, and deployment to AWS.

## Prerequisites

- GitHub repository with your Laravel application
- AWS account with appropriate permissions
- Laravel application with tests and code quality tools configured

## Step 1: Configure GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions, and add the following secrets:

### Required Secrets

#### Database Configuration (RDS)
```
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint.region.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
MYSQL_ATTR_SSL_CA=/path/to/rds-ca-2019-root.pem
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=false
```

#### AWS Configuration
```
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-production-s3-bucket
```

### Optional Secrets (for deployment)

#### Staging Environment
```
AWS_STAGING_BUCKET=your-staging-s3-bucket
AWS_STAGING_CLOUDFRONT_ID=your-staging-cloudfront-id
```

#### Production Environment
```
AWS_CLOUDFRONT_ID=your-production-cloudfront-id
```

## Step 2: Choose Your Workflow

### Option A: Full CI/CD Pipeline (Recommended)
Use `laravel-ci-cd.yml` for a complete pipeline with:
- Testing against RDS database
- Code quality checks (PHP CodeSniffer, PHPStan)
- Security scanning (Enlightn)
- Build optimization
- Deployment to staging and production

### Option B: Simple CI Pipeline
Use `laravel-simple.yml` for basic testing and code quality checks.

## Step 3: Workflow Triggers

The workflows are configured to run on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

## Step 4: Database Setup for CI/CD

### Important Notes:
1. **RDS Security Group**: Ensure your RDS security group allows connections from GitHub Actions IP ranges
2. **Database User**: Create a dedicated database user for CI/CD with appropriate permissions
3. **SSL Configuration**: The workflow includes SSL configuration for secure RDS connections
4. **Test Database**: Consider using a separate test database to avoid affecting development data

### RDS Security Group Configuration:
```bash
# Add GitHub Actions IP ranges to your RDS security group
# You can find current IP ranges at: https://api.github.com/meta
```

## Step 5: Test Your Setup

1. Push a commit to trigger the workflow
2. Check the Actions tab in your GitHub repository
3. Verify all jobs pass successfully

## Step 6: Monitor and Maintain

### Regular Maintenance:
- Update PHP version in workflows as needed
- Review and update dependencies
- Monitor AWS costs
- Check security scan results

### Troubleshooting:
- Check workflow logs for detailed error messages
- Verify all secrets are correctly configured
- Ensure RDS connectivity from GitHub Actions
- Review AWS IAM permissions

## Security Best Practices

1. **Rotate AWS Keys**: Regularly rotate your AWS access keys
2. **Least Privilege**: Use IAM roles with minimal required permissions
3. **Secret Management**: Never commit secrets to your repository
4. **Network Security**: Use VPC and security groups to restrict access
5. **SSL/TLS**: Always use SSL connections to RDS

## Cost Optimization

1. **Workflow Optimization**: Use caching for dependencies
2. **AWS Resources**: Monitor and optimize AWS resource usage
3. **Build Artifacts**: Clean up old artifacts regularly
4. **Database**: Use appropriate RDS instance sizes for testing

## Next Steps

1. Set up branch protection rules
2. Configure deployment environments
3. Set up monitoring and alerting
4. Implement automated security scanning
5. Configure backup strategies

For more information, see the [GitHub Actions documentation](https://docs.github.com/en/actions) and [Laravel deployment guide](https://laravel.com/docs/deployment). 