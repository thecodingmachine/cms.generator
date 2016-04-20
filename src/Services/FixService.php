<?php

namespace Mouf\Cms\Generator\Services;

use Symfony\CS\Config\Config;
use Symfony\CS\Fixer;

class FixService {

    public static function csFix($path){
        $fixer = new Fixer();
        $config = new Config();
        $config->finder(new \ArrayIterator(array(new \SplFileInfo($path))));
        $fixer->registerBuiltInFixers();
        $fixer->registerBuiltInConfigs();
        $config->fixers($fixer->getFixers());
        $fixer->fix($config);
    }

}