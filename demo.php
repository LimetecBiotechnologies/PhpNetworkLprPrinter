<?php
/*
 * Class PhpLprPrinter
 * Print your files via PHP with LPR network printer
 * (C) Copyright 2011 Pedro Villena <craswer@gmail.com>
 * Licensed under the GNU GPL v3 license. See file COPYRIGHT for details. 
 */

use Limetec\PhpNetworkLprPrinter\PhpNetworkLprPrinter;

foreach (array(__DIR__ . '/../../autoload.php',__DIR__ . '/../autoload.php', __DIR__ . '/vendor/autoload.php') as $file) {
    if (file_exists($file)) {
        require $file;

        break;
    }
}

echo "<h1>PhpNetworkLprPrinter example</h1>";

$lpr = new PhpNetworkLprPrinter("192.168.1.136"); //Host of the printer here
$lpr->printText("Hello world!"); //Text here

//Show debug trace
echo "<h3>Debug</h3><pre>";
print_r($lpr->getDebug());
echo "</pre>";