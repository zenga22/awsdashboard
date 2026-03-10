<?php

namespace AwsDashboard\Config;

class ProfileManager
{
    private array $profiles;
    private array $allRegions;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/profiles.php';
        $this->profiles = $config['profiles'];
        $this->allRegions = $config['all_regions'];
    }

    public function getProfiles(): array
    {
        return $this->profiles;
    }

    public function getProfile(string $name): ?array
    {
        foreach ($this->profiles as $profile) {
            if ($profile['name'] === $name) {
                return $profile;
            }
        }
        return null;
    }

    public function getProfileNames(): array
    {
        return array_column($this->profiles, 'name');
    }

    public function getRegionsForProfile(string $profileName): array
    {
        $profile = $this->getProfile($profileName);
        return $profile ? $profile['regions'] : [];
    }

    public function getAllRegions(): array
    {
        return $this->allRegions;
    }

    public function getRegionLabel(string $regionCode): string
    {
        return $this->allRegions[$regionCode] ?? $regionCode;
    }

    public function validateProfile(string $name): bool
    {
        return $this->getProfile($name) !== null;
    }

    public function validateRegion(string $profileName, string $region): bool
    {
        $regions = $this->getRegionsForProfile($profileName);
        return in_array($region, $regions, true);
    }
}
