<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfb2f542e9d8d0ae3bd8c0aef3dd0cba9
{
    public static $prefixLengthsPsr4 = array (
        'J' => 
        array (
            'Jfcherng\\Roundcube\\Plugin\\CloudView\\' => 36,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Jfcherng\\Roundcube\\Plugin\\CloudView\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static $classMap = array (
        'Jfcherng\\Roundcube\\Plugin\\CloudView\\CloudviewHelper' => __DIR__ . '/../..' . '/CloudviewHelper.php',
        'Jfcherng\\Roundcube\\Plugin\\CloudView\\MimeHelper' => __DIR__ . '/../..' . '/MimeHelper.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfb2f542e9d8d0ae3bd8c0aef3dd0cba9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfb2f542e9d8d0ae3bd8c0aef3dd0cba9::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfb2f542e9d8d0ae3bd8c0aef3dd0cba9::$classMap;

        }, null, ClassLoader::class);
    }
}