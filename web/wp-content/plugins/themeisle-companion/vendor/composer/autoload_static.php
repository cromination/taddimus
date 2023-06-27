<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit44550db3ff37fc82bffa3b9d40565dfd
{
    public static $files = array (
        '2e85745cdd367ff6e5579a8598f422b9' => __DIR__ . '/..' . '/codeinwp/elementor-extra-widgets/load.php',
        '62bc7c35996f19a64625f7ff3ba2fb5e' => __DIR__ . '/..' . '/codeinwp/full-width-page-templates/load.php',
        '7b1f4385ddfc86d120fe4380e8cb0fa6' => __DIR__ . '/..' . '/codeinwp/themeisle-content-forms/load.php',
        '4577ab960be90dedc8f8512147cf356d' => __DIR__ . '/..' . '/codeinwp/themeisle-sdk/load.php',
        'e5ea328e2edc5283d7a9134fc492f138' => __DIR__ . '/..' . '/codeinwp/themeisle-content-forms/load.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit44550db3ff37fc82bffa3b9d40565dfd::$classMap;

        }, null, ClassLoader::class);
    }
}
