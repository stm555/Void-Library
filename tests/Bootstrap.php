<?php
/**
 * Void Library
 * -- this is a lightly modified version of the Zend Framework Bootstrap
 *    and so has the following license information
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Void
 * @package    Void
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/*
 * Set error reporting to the level to which Zend Framework code must comply.
 */
error_reporting( E_ALL | E_STRICT );
/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$voidRoot        = realpath(dirname(__DIR__));
$voidCoreLibrary = "$voidRoot/library";
$zfCoreLibrary = "$voidRoot/../zf2/library";
$voidCoreTests   = "$voidRoot/tests";

/*
 * Prepend the Zend Framework library/ and tests/ directories to the
 * include_path. This allows the tests to run out of the box and helps prevent
 * loading other copies of the framework code and tests that would supersede
 * this copy.
 */
$path = array(
    $voidCoreLibrary,
    $zfCoreLibrary,
    $voidCoreTests,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * Setup autoloading
 */
function VoidTest_Autoloader($class)
{
    $class = ltrim($class, '\\');

    if (!preg_match('#^(Void(Test)?|PHPUnit|Zend)(\\\\|_)#', $class)) {
        return false;
    }

    // $segments = explode('\\', $class); // preg_split('#\\\\|_#', $class);//
    $segments = preg_split('#[\\\\_]#', $class); // preg_split('#\\\\|_#', $class);//
    $ns       = array_shift($segments);

    switch ($ns) {
        case 'Void':
            $file = dirname(__DIR__) . '/library/Void/';
            break;
        case 'Zend':
            return include_once str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        default:
            $file = false;
            break;
    }

    if ($file) {
        $file .= implode('/', $segments) . '.php';
        if (file_exists($file)) {
            return include_once $file;
        }
    }

    $segments = explode('_', $class);
    $ns       = array_shift($segments);

    switch ($ns) {
        case 'PHPUnit':
        case 'Zend':
            return include_once str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        case 'Void':
            $file = dirname(__DIR__) . '/library/Void/';
            break;
        case 'VoidTest':
            $file = __DIR__ . '/Void/';
            break;
        default:
            return false;
    }
    $file .= implode('/', $segments) . '.php';
    if (file_exists($file)) {
        return include_once $file;
    }

    return false;
}
spl_autoload_register('VoidTest_Autoloader', true, true);

/*
 * Load the user-defined test configuration file, if it exists; otherwise, load
 * the default configuration.
 */
if (is_readable($voidCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php')) {
    require_once $voidCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php';
} else {
    require_once $voidCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php.dist';
}

if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true &&
    version_compare(PHPUnit_Runner_Version::id(), '3.1.6', '>=')) {

    /*
     * Add Zend Framework library/ directory to the PHPUnit code coverage
     * whitelist. This has the effect that only production code source files
     * appear in the code coverage report and that all production code source
     * files, even those that are not covered by a test yet, are processed.
     */
    PHPUnit_Util_Filter::addDirectoryToWhitelist($zfCoreLibrary);
    /*
     * Add Void library/ directory to the PHPUnit code coverage
     * whitelist. This has the effect that only production code source files
     * appear in the code coverage report and that all production code source
     * files, even those that are not covered by a test yet, are processed.
     */
    PHPUnit_Util_Filter::addDirectoryToWhitelist($voidCoreLibrary);

    /*
     * Omit from code coverage reports the contents of the tests directory
     */
    foreach (array('.php', '.phtml', '.csv', '.inc') as $suffix) {
        PHPUnit_Util_Filter::addDirectoryToFilter($voidCoreTests, $suffix);
    }
    PHPUnit_Util_Filter::addDirectoryToFilter(PEAR_INSTALL_DIR);
    PHPUnit_Util_Filter::addDirectoryToFilter(PHP_LIBDIR);
}


/**
 * Start output buffering, if enabled
 */
if (defined('TESTS_ZEND_OB_ENABLED') && constant('TESTS_ZEND_OB_ENABLED')) {
    ob_start();
}

/*
 * Unset global variables that are no longer needed.
 */
unset($voidRoot, $voidCoreLibrary, $zfCoreLibrary, $voidCoreTests, $path);
