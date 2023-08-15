<?php

use Krinkle\Toolbase\BaseTool;
use Krinkle\Intuition\Intuition;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';

$tool = new WikiInfoTool();

$I18N = new Intuition( 'getwikiapi' );

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => $I18N->msg( 'title' ),
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	),
	'I18N' => $I18N,
	'sourceInfo' => array(
		'issueTrackerUrl' => 'https://phabricator.wikimedia.org/tag/wikiinfo/',
	),
) );
$kgBase->setSourceInfoGerrit( 'labs/tools/wikiinfo', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();
