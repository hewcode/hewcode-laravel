<?php

namespace Hewcode\Hewcode;

use Hewcode\Hewcode\Support\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|self dateFormat(?string $format = null)
 * @method static string|self datetimeFormat(?string $format = null)
 * @method static Config config()
 * @method static array sharedData()
 * @method static array getSharedData(string $key)
 * @method static \Illuminate\Http\JsonResponse response(int $status = 200, mixed $data = null)
 * @method static void shareWithResponse(string $key, string|null $identifier, array $data)
 * @method static void discover(string $path)
 * @method static array getDiscoveryPaths()
 * @method static string|null routeName(string $name, Panel\Panel|string|null $panel = null)
 * @method static string|null route(string $name, array $parameters = [], bool $absolute = true, Panel\Panel|string|null $panel = null)
 * @method static array|\Hewcode\Hewcode\Panel\Resource[] discovered(string $panel)
 * @method static array|\Hewcode\Hewcode\Panel\Resource[] getResources(string $panel)
 * @method static \Hewcode\Hewcode\Panel\Resource|null getResourceByName(string $name, string $panel)
 * @method static Panel\Panel panel(?string $name = null)
 * @method static Collection<int, \Hewcode\Hewcode\Panel\Panel> panels()
 * @method static bool isPanel(?string $name = null)
 * @method static \Hewcode\Hewcode\Panel\Panel|null currentPanel()
 * @method static Support\IconRegistry iconRegistry()
 * @method static array registerIcon(?string $iconName)
 * @method static string|null resolvePageComponent(array $paths)
 *
 * @see \Hewcode\Hewcode\Manager
 */
class Hewcode extends Facade
{
    public const VERSION = '1.0.0';

    protected static function getFacadeAccessor(): string
    {
        return 'hewcode';
    }
}
