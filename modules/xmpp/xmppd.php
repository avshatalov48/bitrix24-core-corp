<?
$_SERVER['DOCUMENT_ROOT'] = DirName(__FILE__);
$_SERVER['DOCUMENT_ROOT'] = SubStr($_SERVER['DOCUMENT_ROOT'], 0, StrLen($_SERVER['DOCUMENT_ROOT']) - StrLen("/bitrix/modules/xmpp"));

define('NOT_CHECK_PERMISSIONS', true);
define('BX_BUFFER_USED',false);
define("BX_NO_ACCELERATOR_RESET", true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('xmpp'))
	die('XMPP module is not installed');

$overload  = intval(ini_get('mbstring.func_overload'));
$encoding = strtolower(ini_get('mbstring.internal_encoding'));

if (defined('BX_UTF') && BX_UTF === true)
{
	$retVal = ($overload == 2) && ($encoding == 'utf8' || $encoding == 'utf-8');
	if (!$retVal)
		die('Mbstring settings are incorrect (mbstring.func_overload='.$overload.' mbstring.internal_encoding='.$encoding.'). '.
		'Required: mbstring.func_overload=2 mbstring.internal_encoding=utf-8');
}
else
{
	if ($overload == 2)
	{
		$ru = LANG_CHARSET == 'windows-1251';
		$mb_string_req = 'mbstring.internal_encoding='.($ru ? 'cp1251' : 'latin1');

		if ($ru)
			$retVal = false !== strpos($encoding,'1251');
		else
			$retVal = false === strpos($encoding,'utf');
	}
	else
	{
		$mb_string_req = 'mbstring.func_overload=0';
		$retVal = $overload == 0;
	}
	if (!$retVal)
		die('Mbstring settings are incorrect (mbstring.func_overload='.$overload.' mbstring.internal_encoding='.$encoding.'). '.
		'Required: '.$mb_string_req);
}

CXMPPServer::Run();

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>
