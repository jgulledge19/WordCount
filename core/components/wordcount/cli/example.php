<?php


/**
 * Simple MODX via CLI
 */

/* Assume these are false until we know otherwise */
$cliMode = false;
$outsideModx = false;

/* Instantiate MODX if necessary */
if (!isset($modx)) {
    $outsideModx = true;

    /* Set path to MODX core directory */
    if (!defined('MODX_CORE_PATH')) {
        /* be sure this has a trailing slash */
        define('MODX_CORE_PATH', dirname(dirname(dirname(dirname(__FILE__)))).DIRECTORY_SEPARATOR);

    }

    /* get the MODX class file */
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

    /* instantiate the $modx object */
    $modx = new modX();
    if ((!$modx) || (!$modx instanceof modX)) {
        echo 'Could not create MODX class';
    }
    /* initialize MODX and set current context */
    $modx->initialize('web');
    // or $modx->initialize('mgr');

    /* load the error handler */
    $modx->getService('error', 'error.modError', '', '');

    /* Set up logging */
    $modx->setLogLevel(xPDO::LOG_LEVEL_INFO);

    /* Check for CLI mode and set log target */
    if (php_sapi_name() == 'cli') {
        $cliMode = true;
        $modx->setLogTarget('ECHO');
    } else {
        $modx->setLogTarget('HTML');
    }

}
echo PHP_EOL;
require_once MODX_CORE_PATH .'components/wordcount/model/wordcount/WordCount.php';

$wordCount = new WordCount();
$wordCount->init($modx);
echo 'With strip_tags()'.PHP_EOL;
$total = $wordCount
    //->setDebug()
    ->addResourceWhere(
        array(
            'id:IN' => array(1,2,3,4,5,30),
            'OR:parent:IN' => array(1,2,3,4,5,30)
        )
    )
    ->addResourceFilter(
        'id:NOT IN',
        array(2672, 2673, 2674, 36, 1853, 2770, 2694, 1860, 2695, 2698)
    )
    //->setConfig('stripTags', false)
    ->countResources()
;
echo '  Estimated total word count for resources: '.$total.' from a page total of: '.$wordCount->resourcePageTotal();
echo PHP_EOL;

echo '  Estimated total word count for Chunks: '.$wordCount->countChunks();
echo PHP_EOL;

echo '  Estimated total word count for Lexicons: '.$wordCount->countLexicons();
echo PHP_EOL;

echo '  Estimated total word count for Resource 1724 with strip tags: '.
    $wordCount
        ->setConfig('stripTags', true)
        ->countResource(1724);
echo PHP_EOL;


echo 'Without strip_tags()'.PHP_EOL;
$wordCount->setConfig('stripTags', false);

$total = $wordCount
    //->setDebug()
    ->addResourceWhere(
        array(
            'id:IN' => array(1,2,3,4,5,30),
            'OR:parent:IN' => array(1,2,3,4,5,30)
        )
    )
    ->addResourceFilter(
        'id:NOT IN',
        array(2672, 2673, 2674, 36, 1853, 2770, 2694, 1860, 2695, 2698)
    )
    //->setConfig('stripTags', false)
    ->countResources()
;
echo '  Estimated total word count for resources: '.$total.' from a page total of: '.$wordCount->resourcePageTotal();
echo PHP_EOL;

echo '  Estimated total word count for Chunks: '.$wordCount->countChunks();
echo PHP_EOL;

echo '  Estimated total word count for Lexicons: '.$wordCount->countLexicons();
echo PHP_EOL;

echo '  Estimated total word count for Resource 1724: '.
    $wordCount->countResource(1724);
echo PHP_EOL;

