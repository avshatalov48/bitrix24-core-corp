<?

use Bitrix\Main\Context;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (is_object($APPLICATION))
{
	$APPLICATION->RestartBuffer();
}

if (!CModule::IncludeModule("imopenlines"))
{
	CMain::FinalActions();
	die();
}

$error = '';
$getRequest = Context::getCurrent()->getRequest()->toArray();
$check = parse_url($getRequest['DOMAIN']);
if(!in_array($check['scheme'], Array('http', 'https')) || empty($check['host']))
{
	$style = "
	    font-family: 'Helvetica Neue', Helvetica, sans-serif;
	    font-size: 14px;
		padding: 10 12px;
    	display: block;
    	background-color: #e8f7fe;
    	border: 1px solid #e8f7fe;
    	border-radius: 4px;
   	";

	echo '<span style="'.$style.'">'.\Bitrix\Main\Localization\Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_ADDRESS').'</span>';
	die();
}
$params = array();

$params['DOMAIN'] = $check['scheme'].'://'.$check['host'];
$params['SERVER_NAME'] = $check['host'];

if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER']) || mb_strpos($_SERVER['HTTP_REFERER'], $params['DOMAIN']) !== 0)
{
	$style = "
	    font-family: 'Helvetica Neue', Helvetica, sans-serif;
	    font-size: 14px;
		padding: 10 12px;
    	display: block;
    	background-color: #e8f7fe;
    	border: 1px solid #e8f7fe;
    	border-radius: 4px;
   	";

	echo '<span style="'.$style.'">'.\Bitrix\Main\Localization\Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_SECURITY').'</span>';
	die();
}

$parsedUserCode = \Bitrix\ImOpenLines\Session\Common::parseUserCode($getRequest['DIALOG_ENTITY_ID']);
$params['IMOP_ID'] = $parsedUserCode['CONFIG_ID'];

$APPLICATION->IncludeComponent("bitrix:imopenlines.iframe.quick", ".default", $params, false, Array("HIDE_ICONS" => "Y"));

CMain::FinalActions();
die();