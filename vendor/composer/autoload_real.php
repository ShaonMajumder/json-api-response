<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitd98d6082e3d5ab574a001643d8f0a959
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitd98d6082e3d5ab574a001643d8f0a959', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitd98d6082e3d5ab574a001643d8f0a959', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitd98d6082e3d5ab574a001643d8f0a959::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
