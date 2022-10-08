<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/lang.php");

// all common phrases place here
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$moduleRoot = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/tasks";

require_once($moduleRoot."/tools.php");
require_once __DIR__.'/autoload.php';

CJSCore::RegisterExt('task-popups', array(
	'js' => '/bitrix/js/tasks/task-popups.js',
	'css' => '/bitrix/js/tasks/css/task-popups.css',
	'rel' => ['ui.design-tokens'],
));

require_once($moduleRoot."/include/asset.php");