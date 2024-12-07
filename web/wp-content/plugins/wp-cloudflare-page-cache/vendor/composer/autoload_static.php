<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4c660e83afb5996a27e483c3fa4c0111
{
    public static $files = array (
        'f3e742daca6ecc1d4ff0a2b5cf792c05' => __DIR__ . '/..' . '/codeinwp/themeisle-sdk/load.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'SPC_Pro\\' => 8,
            'SPC\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'SPC_Pro\\' => 
        array (
            0 => __DIR__ . '/../..' . '/pro',
        ),
        'SPC\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'SPC\\Builders\\Cache_Rule' => __DIR__ . '/../..' . '/src/Builders/Cache_Rule.php',
        'SPC\\Constants' => __DIR__ . '/../..' . '/src/Constants.php',
        'SPC\\Loader' => __DIR__ . '/../..' . '/src/Loader.php',
        'SPC\\Migrator' => __DIR__ . '/../..' . '/src/Migrator.php',
        'SPC\\Modules\\Admin' => __DIR__ . '/../..' . '/src/Modules/Admin.php',
        'SPC\\Modules\\Frontend' => __DIR__ . '/../..' . '/src/Modules/Frontend.php',
        'SPC\\Modules\\HTML_Modifier' => __DIR__ . '/../..' . '/src/Modules/HTML_Modifier.php',
        'SPC\\Modules\\Module_Interface' => __DIR__ . '/../..' . '/src/Modules/Module_Interface.php',
        'SPC\\Modules\\Settings_Manager' => __DIR__ . '/../..' . '/src/Modules/Settings_Manager.php',
        'SPC\\Services\\Cloudflare_Client' => __DIR__ . '/../..' . '/src/Services/Cloudflare_Client.php',
        'SPC\\Services\\Cloudflare_Rule' => __DIR__ . '/../..' . '/src/Services/Cloudflare_Rule.php',
        'SPC\\Utils\\Helpers' => __DIR__ . '/../..' . '/src/Utils/Helpers.php',
        'SPC\\Utils\\Sanitization' => __DIR__ . '/../..' . '/src/Utils/Sanitization.php',
        'SPC_Pro\\Builders\\Transform_Rule' => __DIR__ . '/../..' . '/pro/Builders/Transform_Rule.php',
        'SPC_Pro\\Constants' => __DIR__ . '/../..' . '/pro/Constants.php',
        'SPC_Pro\\Loader' => __DIR__ . '/../..' . '/pro/Loader.php',
        'SPC_Pro\\Modules\\Admin' => __DIR__ . '/../..' . '/pro/Modules/Admin.php',
        'SPC_Pro\\Modules\\Fallback_Cache' => __DIR__ . '/../..' . '/pro/Modules/Fallback_Cache.php',
        'SPC_Pro\\Modules\\Frontend' => __DIR__ . '/../..' . '/pro/Modules/Frontend.php',
        'SPC_Pro\\Modules\\HTML_Modifier' => __DIR__ . '/../..' . '/pro/Modules/HTML_Modifier.php',
        'SPC_Pro\\Modules\\Settings_Manager' => __DIR__ . '/../..' . '/pro/Modules/Settings_Manager.php',
        'SPC_Pro\\Services\\Cloudflare_Transform_Rule' => __DIR__ . '/../..' . '/pro/Services/Cloudflare_Transform_Rule.php',
        'WP_Async_Request' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-async-request.php',
        'WP_Background_Process' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-background-process.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4c660e83afb5996a27e483c3fa4c0111::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4c660e83afb5996a27e483c3fa4c0111::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit4c660e83afb5996a27e483c3fa4c0111::$classMap;

        }, null, ClassLoader::class);
    }
}
