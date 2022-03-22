<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit61658f4d29f38280e862aef9ee9cf514
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit61658f4d29f38280e862aef9ee9cf514::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit61658f4d29f38280e862aef9ee9cf514::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit61658f4d29f38280e862aef9ee9cf514::$classMap;

        }, null, ClassLoader::class);
    }
}
