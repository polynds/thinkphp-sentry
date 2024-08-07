<?php

namespace Polynds\ThinkphpSentry\Features\Storage;

use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystem;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Polynds\ThinkphpSentry\Features\Feature;
use RuntimeException;

class Integration extends Feature
{
    private const FEATURE_KEY = 'storage';

    private const STORAGE_DRIVER_NAME = 'sentry';

    public function isApplicable(): bool
    {
        // Since we only register the driver this feature is always applicable
        return true;
    }

    public function register(): void
    {
        $this->container()->afterResolving(FilesystemManager::class, function (FilesystemManager $filesystemManager): void {
            $filesystemManager->extend(
                self::STORAGE_DRIVER_NAME,
                function (Application $application, array $config) use ($filesystemManager): Filesystem {
                    if (empty($config['sentry_disk_name'])) {
                        throw new RuntimeException(sprintf('Missing `sentry_disk_name` config key for `%s` filesystem driver.', self::STORAGE_DRIVER_NAME));
                    }

                    if (empty($config['sentry_original_driver'])) {
                        throw new RuntimeException(sprintf('Missing `sentry_original_driver` config key for `%s` filesystem driver.', self::STORAGE_DRIVER_NAME));
                    }

                    if ($config['sentry_original_driver'] === self::STORAGE_DRIVER_NAME) {
                        throw new RuntimeException(sprintf('`sentry_original_driver` for Sentry storage integration cannot be the `%s` driver.', self::STORAGE_DRIVER_NAME));
                    }

                    $disk = $config['sentry_disk_name'];

                    $config['driver'] = $config['sentry_original_driver'];
                    unset($config['sentry_original_driver']);

                    $diskResolver = (function (string $disk, array $config) {
                        // This is a "hack" to make sure that the original driver is resolved by the FilesystemManager
                        $oldConfig = config("filesystems.disks.{$disk}");

                        config(["filesystems.disks.{$disk}" => $config]);

                        /** @var FilesystemManager $this */
                        $resolved = $this->resolve($disk);

                        config(["filesystems.disks.{$disk}" => $oldConfig]);

                        return $resolved;
                    })->bindTo($filesystemManager, FilesystemManager::class);

                    /** @var Filesystem $originalFilesystem */
                    $originalFilesystem = $diskResolver($disk, $config);

                    $defaultData = ['disk' => $disk, 'driver' => $config['driver']];

                    $recordSpans = $config['sentry_enable_spans'] ?? $this->isTracingFeatureEnabled(self::FEATURE_KEY);
                    $recordBreadcrumbs = $config['sentry_enable_breadcrumbs'] ?? $this->isBreadcrumbFeatureEnabled(self::FEATURE_KEY);

                    if ($originalFilesystem instanceof AwsS3V3Adapter) {
                        return new SentryS3V3Adapter($originalFilesystem, $defaultData, $recordSpans, $recordBreadcrumbs);
                    }

                    if ($originalFilesystem instanceof FilesystemAdapter) {
                        return new SentryFilesystemAdapter($originalFilesystem, $defaultData, $recordSpans, $recordBreadcrumbs);
                    }

                    if ($originalFilesystem instanceof CloudFilesystem) {
                        return new SentryCloudFilesystem($originalFilesystem, $defaultData, $recordSpans, $recordBreadcrumbs);
                    }

                    return new SentryFilesystem($originalFilesystem, $defaultData, $recordSpans, $recordBreadcrumbs);
                }
            );
        });
    }

    /**
     * Decorates the configuration for a single disk with Sentry driver configuration.
 * This replaces the driver with a custom driver that will capture performance traces and breadcrumbs.
     *
     * The custom driver will be an instance of @param array<string, mixed> $diskConfig
     *
     * @param array<string, array<string, mixed>> $diskConfigs
     *
     * @return array<string, mixed>
     *@return array<string, array<string, mixed>>
     * @see \Polynds\ThinkphpSentry\Features\Storage\SentryFilesystemAdapter
     * if the original driver is an @see \Illuminate\Filesystem\FilesystemAdapter.
     * If the original driver is neither of those, it will be @see \Polynds\ThinkphpSentry\Features\Storage\SentryFilesystem
     * or @see \Polynds\ThinkphpSentry\Features\Storage\SentryCloudFilesystem based on the contract of the original driver.
     *
     * You might run into problems if you expect another specific driver class.
     *
     * @see \Polynds\ThinkphpSentry\Features\Storage\SentryS3V3Adapter
     * if the original driver is an @see \Illuminate\Filesystem\AwsS3V3Adapter,
     * and an instance of/
    * public static function configureDisk(string $diskName, array $diskConfig, bool $enableSpans = true, bool $enableBreadcrumbs = true): array
    * {
        * $currentDriver = $diskConfig['driver'];
 *
* if ($currentDriver !== self::STORAGE_DRIVER_NAME) {
            * $diskConfig['driver'] = self::STORAGE_DRIVER_NAME;
            * $diskConfig['sentry_disk_name'] = $diskName;
            * $diskConfig['sentry_original_driver'] = $currentDriver;
            * $diskConfig['sentry_enable_spans'] = $enableSpans;
            * $diskConfig['sentry_enable_breadcrumbs'] = $enableBreadcrumbs;
        * }
 *
* return $diskConfig;
    * }
 *
* /**
     * Decorates the configuration for all disks with Sentry driver configuration.
     *
     * @see self::configureDisk()
     *
     */
    public static function configureDisks(array $diskConfigs, bool $enableSpans = true, bool $enableBreadcrumbs = true): array
    {
        $diskConfigsWithSentryDriver = [];
        foreach ($diskConfigs as $diskName => $diskConfig) {
            $diskConfigsWithSentryDriver[$diskName] = static::configureDisk($diskName, $diskConfig, $enableSpans, $enableBreadcrumbs);
        }

        return $diskConfigsWithSentryDriver;
    }
}
