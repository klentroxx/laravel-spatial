<?php

declare(strict_types=1);

namespace ASanikovich\LaravelSpatial;

use ASanikovich\LaravelSpatial\Enums\AxisOrder;
use ASanikovich\LaravelSpatial\Enums\GeometryType;
use ASanikovich\LaravelSpatial\Exceptions\LaravelSpatialException;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Support\Facades\DB;
use Throwable;

final class LaravelSpatialServiceProvider extends DatabaseServiceProvider
{
    /**
     * @throws LaravelSpatialException
     * @throws Throwable
     */
    public function boot(): void
    {
        $this->publishes(
            [__DIR__.'/../config/laravel-spatial.php' => config_path('laravel-spatial.php')],
            'laravel-spatial-config'
        );

        $this->mergeConfigFrom(__DIR__.'/../config/laravel-spatial.php', 'laravel-spatial');

        $this->validateConfig();

        if (DB::connection()->isDoctrineAvailable()) {
            $this->registerDoctrineTypes();
        }
    }

    /**
     * @throws Throwable
     */
    private function registerDoctrineTypes(): void
    {
        foreach (GeometryType::cases() as $type) {
            $this->registerDoctrineType($type->getDoctrineClassName(), $type->value);
        }

        $this->registerDoctrineType(GeometryType::GEOMETRY_COLLECTION->getDoctrineClassName(), 'geomcollection');
    }

    /**
     * @param  class-string<Type>  $class
     *
     * @throws Throwable
     */
    private function registerDoctrineType(string $class, string $type): void
    {
        DB::registerDoctrineType($class, $type, $type);

        DB::connection()->registerDoctrineType($class, $type, $type);
    }

    /**
     * @throws LaravelSpatialException
     */
    private function validateConfig(): void
    {
        /** @var array<class-string<Geometry\Geometry>>|array<string> $configGeometryType */
        $configGeometryType = config('laravel-spatial.geometryTypes');
        /** @var value-of<AxisOrder> $configAxisOrder */
        $configAxisOrder = config('laravel-spatial.axisOrder');

        foreach (GeometryType::cases() as $type) {
            $configType = $configGeometryType[$type->value] ?? null;
            if (! $configType) {
                throw new LaravelSpatialException(
                    sprintf('Invalid class for geometry type "%s", please check config', $type->value)
                );
            }

            $baseClass = $type->getBaseGeometryClassName();
            /** @phpstan-ignore-next-line  */
            if ($configType !== $baseClass && ! $configType instanceof $baseClass) {
                throw new LaravelSpatialException(sprintf(
                    'Class for geometry type "%s" should be instance of "%s" ("%s" provided), please check config',
                    $type->value,
                    $baseClass,
                    $configType,
                ));
            }
        }

        if ( AxisOrder::tryFrom($configAxisOrder) === null ) {
            throw new LaravelSpatialException(
              "Invalid 'axisOrder' config value. The value must be an instance of AxisOrder enum. Please check config!");
        }
    }
}
