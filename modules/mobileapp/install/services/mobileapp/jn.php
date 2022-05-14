<?
define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

/** @var CAllMain $APPLICATION */

$componentName = urldecode($_GET['componentName']);
$namespace = $_GET['namespace'];
$version = $_REQUEST['version'];

$APPLICATION->IncludeComponent('bitrix:mobileapp.jnrouter', '', [
	'componentName' => $componentName,
	'namespace' => $namespace,
	'clientVersion' => $version,
	'checkVersion' => isset($_REQUEST['check']),
	'needAuth' => true,
], null, ['HIDE_ICONS' => 'Y']);

\CMain::FinalActions();

