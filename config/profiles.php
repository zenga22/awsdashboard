<?php

/**
 * AWS Profiles and Regions Configuration
 *
 * Each profile corresponds to a named profile in ~/.aws/credentials.
 * Regions listed per profile are the regions that will be queried.
 *
 * To add a new profile, add an entry here and ensure the matching
 * profile exists in your AWS credentials file.
 */
return [
    'profiles' => [
        [
            'name'    => 'default',
            'label'   => 'Default Account',
            'regions' => ['us-east-1', 'us-west-2', 'eu-west-1'],
        ],
        // Example additional profiles:
        // [
        //     'name'    => 'production',
        //     'label'   => 'Production Account',
        //     'regions' => ['us-east-1', 'us-west-2', 'eu-west-1', 'ap-southeast-1'],
        // ],
        // [
        //     'name'    => 'staging',
        //     'label'   => 'Staging Account',
        //     'regions' => ['us-east-1', 'eu-west-1'],
        // ],
    ],

    'all_regions' => [
        'us-east-1'      => 'US East (N. Virginia)',
        'us-east-2'      => 'US East (Ohio)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'af-south-1'     => 'Africa (Cape Town)',
        'ap-east-1'      => 'Asia Pacific (Hong Kong)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-northeast-3' => 'Asia Pacific (Osaka)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ca-central-1'   => 'Canada (Central)',
        'eu-central-1'   => 'Europe (Frankfurt)',
        'eu-west-1'      => 'Europe (Ireland)',
        'eu-west-2'      => 'Europe (London)',
        'eu-west-3'      => 'Europe (Paris)',
        'eu-north-1'     => 'Europe (Stockholm)',
        'eu-south-1'     => 'Europe (Milan)',
        'me-south-1'     => 'Middle East (Bahrain)',
        'sa-east-1'      => 'South America (São Paulo)',
    ],
];
