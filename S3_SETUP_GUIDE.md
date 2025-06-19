# AWS S3 Integration Setup Guide

## 🚀 Overview

This guide will walk you through setting up AWS S3 for file storage in your Document Request API. S3 will handle signatures, affidavits of loss, birth certificates, and other supporting documents.

## 📋 Prerequisites

- AWS Account
- AWS CLI (optional but recommended)
- Laravel application with S3 package installed

## 🔧 Step 1: Create S3 Bucket

### Option A: Using AWS Console (Recommended)

1. **Log into AWS Console**
   - Go to [AWS S3 Console](https://console.aws.amazon.com/s3/)
   - Sign in with your AWS account

2. **Create Bucket**
   - Click "Create bucket"
   - **Bucket name**: `your-university-documents-[unique-suffix]` (e.g., `university-documents-2024-abc123`)
   - **Region**: Choose the same region as your RDS instance
   - **Block Public Access**: Uncheck "Block all public access" (we need public read access)
   - **Bucket Versioning**: Enable (recommended for data protection)
   - **Default encryption**: Enable (recommended for security)

3. **Configure Bucket**
   - **Object Ownership**: ACLs enabled
   - **Bucket Policy**: We'll add this later
   - Click "Create bucket"

### Option B: Using AWS CLI

```bash
# Configure AWS CLI (if not already done)
aws configure

# Create bucket
aws s3 mb s3://your-university-documents-[unique-suffix] --region us-east-1

# Enable versioning
aws s3api put-bucket-versioning --bucket your-university-documents-[unique-suffix] --versioning-configuration Status=Enabled

# Enable encryption
aws s3api put-bucket-encryption --bucket your-university-documents-[unique-suffix] --server-side-encryption-configuration '{
    "Rules": [
        {
            "ApplyServerSideEncryptionByDefault": {
                "SSEAlgorithm": "AES256"
            }
        }
    ]
}'
```

## 🔐 Step 2: Create IAM User for S3 Access

### Option A: Using AWS Console

1. **Go to IAM Console**
   - Navigate to [IAM Console](https://console.aws.amazon.com/iam/)
   - Click "Users" → "Create user"

2. **Create User**
   - **User name**: `laravel-s3-user`
   - **Access type**: Programmatic access
   - Click "Next"

3. **Attach Policy**
   - Click "Attach existing policies directly"
   - Search for "AmazonS3FullAccess" (or create custom policy)
   - Select and click "Next"

4. **Review and Create**
   - Review settings
   - Click "Create user"

5. **Save Credentials**
   - **Access Key ID**: Copy this
   - **Secret Access Key**: Copy this (you won't see it again)

### Option B: Using AWS CLI

```bash
# Create IAM user
aws iam create-user --user-name laravel-s3-user

# Create access key
aws iam create-access-key --user-name laravel-s3-user

# Attach S3 policy
aws iam attach-user-policy --user-name laravel-s3-user --policy-arn arn:aws:iam::aws:policy/AmazonS3FullAccess
```

## 📁 Step 3: Configure Bucket Policy

Create a bucket policy to allow public read access for uploaded files:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::your-university-documents-[unique-suffix]/*"
        }
    ]
}
```

### Apply Policy via AWS Console:
1. Go to your S3 bucket
2. Click "Permissions" tab
3. Click "Bucket policy"
4. Paste the policy above (replace bucket name)
5. Click "Save changes"

### Apply Policy via AWS CLI:
```bash
# Save policy to file
cat > bucket-policy.json << 'EOF'
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::your-university-documents-[unique-suffix]/*"
        }
    ]
}
EOF

# Apply policy
aws s3api put-bucket-policy --bucket your-university-documents-[unique-suffix] --policy file://bucket-policy.json
```

## ⚙️ Step 4: Configure Laravel Environment

Add these variables to your `.env` file:

```env
# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-university-documents-[unique-suffix]
AWS_URL=https://your-university-documents-[unique-suffix].s3.us-east-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# File Upload Configuration
FILESYSTEM_DISK=s3
MAX_FILE_SIZE=10485760
```

## 🧪 Step 5: Test S3 Connection

Create a test command to verify S3 connectivity:

```bash
# Test S3 connection
php artisan tinker --execute="
use Illuminate\Support\Facades\Storage;
try {
    Storage::disk('s3')->put('test.txt', 'Hello S3!');
    echo 'S3 connection successful!';
    Storage::disk('s3')->delete('test.txt');
} catch (Exception \$e) {
    echo 'S3 connection failed: ' . \$e->getMessage();
}
"
```

## 📊 Step 6: File Structure in S3

Your S3 bucket will have this structure:

```
your-university-documents-[unique-suffix]/
├── signatures/
│   ├── DOC-2024-ABC123_signature_2024-01-15_10-30-45_a1b2c3d4.jpg
│   └── DOC-2024-DEF456_signature_2024-01-16_14-20-30_e5f6g7h8.png
├── supporting_documents/
│   ├── DOC-2024-ABC123_affidavit_of_loss_2024-01-15_10-35-12_i9j0k1l2.pdf
│   ├── DOC-2024-ABC123_birth_certificate_2024-01-15_10-40-25_m3n4o5p6.pdf
│   ├── DOC-2024-ABC123_valid_id_2024-01-15_10-45-18_q7r8s9t0.jpg
│   └── DOC-2024-DEF456_transcript_of_records_2024-01-16_14-25-42_u1v2w3x4.pdf
```

## 🔒 Step 7: Security Best Practices

### 1. **IAM Policy Restrictions**
Create a more restrictive IAM policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::your-university-documents-[unique-suffix]",
                "arn:aws:s3:::your-university-documents-[unique-suffix]/*"
            ]
        }
    ]
}
```

### 2. **CORS Configuration**
Add CORS policy to your bucket:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "POST", "PUT", "DELETE"],
        "AllowedOrigins": ["*"],
        "ExposeHeaders": []
    }
]
```

### 3. **Lifecycle Policy**
Set up automatic cleanup for old files:

```json
{
    "Rules": [
        {
            "ID": "DeleteOldFiles",
            "Status": "Enabled",
            "Filter": {
                "Prefix": ""
            },
            "Expiration": {
                "Days": 2555
            }
        }
    ]
}
```

## 📱 Step 8: API Endpoints

Your API now supports these file operations:

### Upload Files
```bash
# Upload single file
curl -X POST "https://your-api.com/api/v1/document-requests/{id}/files/upload" \
  -H "X-API-Key: your-api-key" \
  -F "file=@signature.jpg" \
  -F "file_type=signature"

# Upload multiple files
curl -X POST "https://your-api.com/api/v1/document-requests/{id}/files/upload-multiple" \
  -H "X-API-Key: your-api-key" \
  -F "files[]=@document1.pdf" \
  -F "files[]=@document2.jpg" \
  -F "file_type=affidavit_of_loss"
```

### Manage Files
```bash
# Get all files
curl -X GET "https://your-api.com/api/v1/document-requests/{id}/files" \
  -H "X-API-Key: your-api-key"

# Get files by type
curl -X GET "https://your-api.com/api/v1/document-requests/{id}/files/type/signature" \
  -H "X-API-Key: your-api-key"

# Delete file
curl -X DELETE "https://your-api.com/api/v1/document-requests/{id}/files/{fileId}" \
  -H "X-API-Key: your-api-key"
```

## 📋 Step 9: Supported File Types

| File Type | Max Size | Allowed Formats | Folder |
|-----------|----------|-----------------|---------|
| signature | 5MB | JPEG, PNG, GIF, PDF | signatures/ |
| affidavit_of_loss | 10MB | PDF, JPEG, PNG | supporting_documents/ |
| birth_certificate | 10MB | PDF, JPEG, PNG | supporting_documents/ |
| valid_id | 10MB | PDF, JPEG, PNG | supporting_documents/ |
| transcript_of_records | 15MB | PDF, JPEG, PNG | supporting_documents/ |
| other | 10MB | PDF, JPEG, PNG, DOC, DOCX | supporting_documents/ |

## 🔍 Step 10: Monitoring and Logging

### CloudWatch Logging
Enable access logging for your S3 bucket:

```bash
aws s3api put-bucket-logging --bucket your-university-documents-[unique-suffix] --bucket-logging-status '{
    "LoggingEnabled": {
        "TargetBucket": "your-university-documents-[unique-suffix]",
        "TargetPrefix": "logs/"
    }
}'
```

### Cost Monitoring
Set up billing alerts in AWS Billing Console:
1. Go to AWS Billing Console
2. Click "Billing preferences"
3. Set up billing alerts for S3 costs

## 🚨 Troubleshooting

### Common Issues

1. **Access Denied Error**
   - Check IAM user permissions
   - Verify bucket policy
   - Ensure bucket name is correct

2. **File Upload Fails**
   - Check file size limits
   - Verify file type is allowed
   - Check S3 bucket permissions

3. **Public URL Not Working**
   - Verify bucket policy allows public read
   - Check object ACL settings
   - Ensure bucket is not blocking public access

### Debug Commands

```bash
# Test S3 connectivity
php artisan tinker --execute="Storage::disk('s3')->put('test.txt', 'test'); echo 'Success';"

# Check bucket contents
aws s3 ls s3://your-university-documents-[unique-suffix] --recursive

# Test file upload via API
curl -X POST "https://your-api.com/api/v1/document-requests/1/files/upload" \
  -H "X-API-Key: your-api-key" \
  -F "file=@test.jpg" \
  -F "file_type=signature"
```

## 📈 Step 11: Performance Optimization

### 1. **CDN Integration**
Consider using CloudFront for faster file delivery:

```bash
# Create CloudFront distribution
aws cloudfront create-distribution --distribution-config file://cloudfront-config.json
```

### 2. **Compression**
Enable compression for text-based files:

```php
// In FileUploadService
if (in_array($file->getMimeType(), ['text/plain', 'application/json'])) {
    $content = gzencode(file_get_contents($file->getPathname()));
    Storage::disk('s3')->put($s3Path, $content, ['ContentEncoding' => 'gzip']);
}
```

### 3. **Batch Operations**
Use batch operations for multiple files:

```php
// Upload multiple files efficiently
$files = collect($uploadedFiles)->map(function ($file) {
    return ['file' => $file, 'type' => 'signature'];
});

Storage::disk('s3')->putFiles($files);
```

## 🔄 Step 12: Backup Strategy

### 1. **Cross-Region Replication**
Set up replication to another region:

```bash
aws s3api put-bucket-replication --bucket your-university-documents-[unique-suffix] --replication-configuration file://replication-config.json
```

### 2. **Versioning**
Enable versioning for file recovery:

```bash
aws s3api put-bucket-versioning --bucket your-university-documents-[unique-suffix] --versioning-configuration Status=Enabled
```

### 3. **Lifecycle Management**
Automate file lifecycle:

```bash
aws s3api put-bucket-lifecycle-configuration --bucket your-university-documents-[unique-suffix] --lifecycle-configuration file://lifecycle-config.json
```

## 📞 Support

For S3-related issues:
1. Check AWS S3 documentation
2. Review CloudWatch logs
3. Verify IAM permissions
4. Test with AWS CLI
5. Check Laravel logs for application errors

## 🔗 Useful Links

- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Laravel File Storage](https://laravel.com/docs/11.x/filesystem)
- [AWS CLI Documentation](https://docs.aws.amazon.com/cli/)
- [S3 Best Practices](https://docs.aws.amazon.com/AmazonS3/latest/userguide/best-practices.html)

## File Naming Convention

Files uploaded to S3 follow this naming convention for easy identification and recovery:

```
{request_id}_{requestor_name}_{file_type}_{timestamp}_{random_string}.{extension}
```

**Example:**
```
DOC-2024-ABC12345_John_Doe_signature_2024-06-19_14-30-25_A1B2C3D4.png
```

**Components:**
- `request_id`: The unique document request ID (e.g., DOC-2024-ABC12345)
- `requestor_name`: Sanitized name of the person requesting the document
- `file_type`: Either 'signature' or 'supporting_document'
- `timestamp`: When the file was uploaded (YYYY-MM-DD_HH-MM-SS)
- `random_string`: 8-character random string for uniqueness
- `extension`: Original file extension

**Benefits:**
- Easy identification of files even if database records are corrupted
- Automatic organization by requestor name
- Timestamp for chronological sorting
- Unique identifiers prevent filename conflicts

## Practical Example

**Scenario:** John Doe requests a transcript document

1. **Document Request Created:**
   - Request ID: `DOC-2024-ABC12345`
   - Requestor: John Doe

2. **Files Uploaded:**
   - Signature: `DOC-2024-ABC12345_John_Doe_signature_2024-06-19_14-30-25_A1B2C3D4.png`
   - Supporting Document: `DOC-2024-ABC12345_John_Doe_supporting_document_2024-06-19_14-31-10_E5F6G7H8.pdf`

3. **Recovery Benefits:**
   - If database is corrupted, you can identify files by requestor name
   - Files are automatically organized by request ID
   - Timestamps help with chronological sorting
   - No filename conflicts due to random strings 