<?
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
$_SERVER['DOCUMENT_ROOT'] = mb_substr($_SERVER['DOCUMENT_ROOT'], 0, mb_strlen($_SERVER['DOCUMENT_ROOT']) - mb_strlen("/bitrix/modules/mail"));

define('NOT_CHECK_PERMISSIONS', true);
define('BX_BUFFER_USED',false);
define("BX_NO_ACCELERATOR_RESET", true);

ob_start(); //This will prevent warning: cannot modify header information
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
ob_end_clean();

if (!CModule::IncludeModule('mail'))
	die('Mail module is not installed');

CSMTPServer::Run();

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>