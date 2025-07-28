<?php

use Krinkle\Intuition\Intuition;
use Krinkle\Toolbase\BaseTool;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/class.php';

$tool = new WikiInfoTool();

$I18N = new Intuition( 'getwikiapi' );

$kgBase = BaseTool::newFromArray( [
	'displayTitle' => $I18N->msg( 'title' ),
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
	'styles' => [
		'main.css',
	],
	'scripts' => [
		'main.js',
	],
	'I18N' => $I18N,
	'sourceInfo' => [
		'issueTrackerUrl' => 'https://phabricator.wikimedia.org/tag/wikiinfo/',
	],
] );
$kgBase->setSourceInfoGerrit( 'labs/tools/wikiinfo', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();
