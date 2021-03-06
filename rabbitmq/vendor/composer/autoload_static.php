<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit51c1268b72528ad71f4eddb80518849b
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PhpAmqpLib\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PhpAmqpLib\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-amqplib/php-amqplib/PhpAmqpLib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit51c1268b72528ad71f4eddb80518849b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit51c1268b72528ad71f4eddb80518849b::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
