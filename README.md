# AWS Dashboard

A PHP dashboard for managing AWS resources across multiple profiles and regions.

## Features

### EC2
- **Instances**: View all EC2 instances with state, IPs, type, and launch time
- **Instance Detail**: Full instance details including networking, storage, tags
- **Reboot**: Reboot running instances with confirmation dialog
- **Reserved Instances**: View reserved instances with pricing, scope, and state

### S3
- **Buckets**: List buckets filtered by region or across all regions
- **Object Browser**: Drill down through folders to individual objects
- **Object Details**: View metadata, content type, storage class, encryption, user-defined metadata
- **Download**: Generate pre-signed URLs for secure object download
- **Delete**: Delete objects with confirmation
- **Space Summaries**: Bucket-level and folder-level size and object count summaries

### Multi-Profile & Multi-Region
- Configure multiple AWS profiles (matching `~/.aws/credentials`)
- Each profile supports multiple regions
- Switch profiles and regions from the top bar on any page
- Region tabs on EC2 and S3 pages for quick navigation

## Requirements

- PHP 8.1+
- AWS CLI v2 installed and configured with named profiles
- Composer (for autoloading)

## Setup

1. Clone the repository
2. Run `composer install`
3. Edit `config/profiles.php` to add your AWS profiles and regions
4. Start the PHP dev server:

```bash
php -S localhost:8080 -t public/
```

5. Open http://localhost:8080 in your browser

## Configuration

Edit `config/profiles.php` to define your profiles:

```php
'profiles' => [
    [
        'name'    => 'default',
        'label'   => 'Default Account',
        'regions' => ['us-east-1', 'us-west-2', 'eu-west-1'],
    ],
    [
        'name'    => 'production',
        'label'   => 'Production Account',
        'regions' => ['us-east-1', 'us-west-2'],
    ],
],
```

Each profile `name` must match a named profile in `~/.aws/credentials`.

## Project Structure

```
public/           Entry point, static assets
  index.php       Router and controller logic
  css/style.css   Dashboard styles
  js/dashboard.js Client-side interactions
src/
  Config/         Profile and region configuration
  Services/       AWS CLI wrapper, EC2 and S3 service classes
templates/
  layouts/        Base HTML layout
  dashboard/      Dashboard overview template
  ec2/            EC2 instance and reserved instance templates
  s3/             S3 bucket, object browser, and detail templates
config/
  profiles.php    AWS profile and region definitions
```
