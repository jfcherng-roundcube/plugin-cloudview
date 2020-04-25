<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit87b88877555d2a22f5950c96aef81ce3
{
    public static $prefixLengthsPsr4 = array (
        'J' => 
        array (
            'Jfcherng\\Roundcube\\Plugin\\Helper\\' => 33,
            'Jfcherng\\Roundcube\\Plugin\\CloudView\\' => 36,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Jfcherng\\Roundcube\\Plugin\\Helper\\' => 
        array (
            0 => __DIR__ . '/..' . '/jfcherng-roundcube/helper/src',
        ),
        'Jfcherng\\Roundcube\\Plugin\\CloudView\\' => 
        array (
            0 => __DIR__ . '/../..' . '/../src',
        ),
    );

    public static $classMap = array (
        'Jfcherng\\Roundcube\\Plugin\\CloudView\\MimeHelper' => __DIR__ . '/../..' . '/../src/MimeHelper.php',
        'Jfcherng\\Roundcube\\Plugin\\Helper\\RoundcubeHelper' => __DIR__ . '/..' . '/jfcherng-roundcube/helper/src/RoundcubeHelper.php',
        'Jfcherng\\Roundcube\\Plugin\\Helper\\RoundcubePluginTrait' => __DIR__ . '/..' . '/jfcherng-roundcube/helper/src/RoundcubePluginTrait.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit87b88877555d2a22f5950c96aef81ce3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit87b88877555d2a22f5950c96aef81ce3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit87b88877555d2a22f5950c96aef81ce3::$classMap;

        }, null, ClassLoader::class);
    }
}