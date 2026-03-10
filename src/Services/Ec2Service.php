<?php

namespace AwsDashboard\Services;

use AwsDashboard\Exception\AwsException;

class Ec2Service
{
    /**
     * List all EC2 instances in a given profile/region.
     *
     * @throws AwsException
     */
    public function getInstances(string $profile, string $region): array
    {
        $result = AwsCli::run('ec2', 'describe-instances', $profile, $region);

        if (!isset($result['Reservations'])) {
            return [];
        }

        $instances = [];
        foreach ($result['Reservations'] as $reservation) {
            foreach ($reservation['Instances'] as $instance) {
                $name = '';
                if (isset($instance['Tags'])) {
                    foreach ($instance['Tags'] as $tag) {
                        if ($tag['Key'] === 'Name') {
                            $name = $tag['Value'];
                            break;
                        }
                    }
                }

                $instances[] = [
                    'InstanceId'       => $instance['InstanceId'] ?? '',
                    'Name'             => $name,
                    'InstanceType'     => $instance['InstanceType'] ?? '',
                    'State'            => $instance['State']['Name'] ?? 'unknown',
                    'PublicIpAddress'  => $instance['PublicIpAddress'] ?? '-',
                    'PrivateIpAddress' => $instance['PrivateIpAddress'] ?? '-',
                    'LaunchTime'       => $instance['LaunchTime'] ?? '',
                    'Platform'         => $instance['Platform'] ?? 'linux',
                    'VpcId'            => $instance['VpcId'] ?? '-',
                    'SubnetId'         => $instance['SubnetId'] ?? '-',
                    'KeyName'          => $instance['KeyName'] ?? '-',
                    'AmiId'            => $instance['ImageId'] ?? '-',
                    'Monitoring'       => $instance['Monitoring']['State'] ?? '-',
                    'Architecture'     => $instance['Architecture'] ?? '-',
                ];
            }
        }

        usort($instances, fn($a, $b) => strcasecmp($a['Name'], $b['Name']));
        return $instances;
    }

    /**
     * Get detail for a single EC2 instance.
     *
     * @throws AwsException
     * @throws \InvalidArgumentException if instance ID format is invalid
     */
    public function getInstance(string $profile, string $region, string $instanceId): ?array
    {
        if (!preg_match('/^i-[0-9a-f]+$/', $instanceId)) {
            throw new \InvalidArgumentException("Invalid instance ID format: {$instanceId}");
        }

        try {
            $result = AwsCli::run('ec2', 'describe-instances', $profile, $region, [
                '--instance-ids' => $instanceId,
            ]);
        } catch (AwsException $e) {
            if ($e->isNotFound()) {
                return null;
            }
            throw $e;
        }

        if (empty($result['Reservations'][0]['Instances'][0])) {
            return null;
        }

        $instance = $result['Reservations'][0]['Instances'][0];
        $name = '';
        $tags = [];
        if (isset($instance['Tags'])) {
            foreach ($instance['Tags'] as $tag) {
                $tags[$tag['Key']] = $tag['Value'];
                if ($tag['Key'] === 'Name') {
                    $name = $tag['Value'];
                }
            }
        }

        $securityGroups = [];
        if (isset($instance['SecurityGroups'])) {
            foreach ($instance['SecurityGroups'] as $sg) {
                $securityGroups[] = $sg['GroupName'] . ' (' . $sg['GroupId'] . ')';
            }
        }

        return [
            'InstanceId'        => $instance['InstanceId'] ?? '',
            'Name'              => $name,
            'InstanceType'      => $instance['InstanceType'] ?? '',
            'State'             => $instance['State']['Name'] ?? 'unknown',
            'PublicIpAddress'   => $instance['PublicIpAddress'] ?? '-',
            'PrivateIpAddress'  => $instance['PrivateIpAddress'] ?? '-',
            'PublicDnsName'     => $instance['PublicDnsName'] ?? '-',
            'PrivateDnsName'    => $instance['PrivateDnsName'] ?? '-',
            'LaunchTime'        => $instance['LaunchTime'] ?? '',
            'Platform'          => $instance['Platform'] ?? 'linux',
            'VpcId'             => $instance['VpcId'] ?? '-',
            'SubnetId'          => $instance['SubnetId'] ?? '-',
            'KeyName'           => $instance['KeyName'] ?? '-',
            'AmiId'             => $instance['ImageId'] ?? '-',
            'Architecture'      => $instance['Architecture'] ?? '-',
            'Monitoring'        => $instance['Monitoring']['State'] ?? '-',
            'RootDeviceType'    => $instance['RootDeviceType'] ?? '-',
            'RootDeviceName'    => $instance['RootDeviceName'] ?? '-',
            'SecurityGroups'    => $securityGroups,
            'Tags'              => $tags,
            'EbsOptimized'      => ($instance['EbsOptimized'] ?? false) ? 'Yes' : 'No',
            'Hypervisor'        => $instance['Hypervisor'] ?? '-',
        ];
    }

    /**
     * Reboot an EC2 instance.
     *
     * @return array{success: bool, message: string}
     */
    public function rebootInstance(string $profile, string $region, string $instanceId): array
    {
        if (!preg_match('/^i-[0-9a-f]+$/', $instanceId)) {
            return ['success' => false, 'message' => 'Invalid instance ID format.'];
        }

        try {
            AwsCli::run('ec2', 'reboot-instances', $profile, $region, [
                '--instance-ids' => $instanceId,
            ]);
            return ['success' => true, 'message' => "Reboot initiated for {$instanceId}."];
        } catch (AwsException $e) {
            return ['success' => false, 'message' => $e->getUserMessage()];
        }
    }

    /**
     * Get reserved instances for a profile/region.
     *
     * @throws AwsException
     */
    public function getReservedInstances(string $profile, string $region): array
    {
        $result = AwsCli::run('ec2', 'describe-reserved-instances', $profile, $region);

        if (!isset($result['ReservedInstances'])) {
            return [];
        }

        $reserved = [];
        foreach ($result['ReservedInstances'] as $ri) {
            $reserved[] = [
                'ReservedInstancesId' => $ri['ReservedInstancesId'] ?? '',
                'InstanceType'        => $ri['InstanceType'] ?? '',
                'State'               => $ri['State'] ?? '',
                'InstanceCount'       => $ri['InstanceCount'] ?? 0,
                'OfferingType'        => $ri['OfferingType'] ?? '',
                'OfferingClass'       => $ri['OfferingClass'] ?? '',
                'ProductDescription'  => $ri['ProductDescription'] ?? '',
                'Start'               => $ri['Start'] ?? '',
                'End'                 => $ri['End'] ?? '',
                'Duration'            => $ri['Duration'] ?? 0,
                'FixedPrice'          => $ri['FixedPrice'] ?? 0,
                'UsagePrice'          => $ri['UsagePrice'] ?? 0,
                'CurrencyCode'        => $ri['CurrencyCode'] ?? 'USD',
                'Scope'               => $ri['Scope'] ?? '-',
                'AvailabilityZone'    => $ri['AvailabilityZone'] ?? '-',
            ];
        }

        usort($reserved, fn($a, $b) => strcmp($a['State'], $b['State']));
        return $reserved;
    }

    /**
     * Get a summary count of instances by state.
     *
     * @throws AwsException
     */
    public function getInstanceSummary(string $profile, string $region): array
    {
        $instances = $this->getInstances($profile, $region);
        $summary = ['total' => 0, 'running' => 0, 'stopped' => 0, 'terminated' => 0, 'other' => 0];

        foreach ($instances as $inst) {
            $summary['total']++;
            $state = $inst['State'];
            if (isset($summary[$state])) {
                $summary[$state]++;
            } else {
                $summary['other']++;
            }
        }

        return $summary;
    }
}
