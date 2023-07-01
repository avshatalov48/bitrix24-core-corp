<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

session_write_close();

$CIMNotify = new CIMNotify(false);
$result = $CIMNotify->GetNotifyList(['PAGE' => 0]);

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "ml-notify");
$GLOBALS["APPLICATION"]->SetPageProperty("Viewport", "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=290");

$unreadNotifyId = 0;
$arUnreaded = $CIMNotify->GetUnreadNotify(Array('SPEED_CHECK' => 'N', 'USE_TIME_ZONE' => 'N'));
if (!empty($arUnreaded['notify']))
{
	$unreadNotifyId = min(array_keys($arUnreaded['notify']));
}

$notifyList = array();

if ($arUnreaded['result'])
{
	$notifyList = $arUnreaded["notify"];
	arsort($notifyList);

	foreach($result as $key =>$notify)
	{
		if(!array_key_exists($key, $notifyList))
		{
			$notifyList[$key] = $notify;
		}
	}

	$result = $notifyList;
}


$parser = new \CTextParser();
foreach ($parser->allow as $tag => $value)
{
	$parser->allow[$tag] = 'N';
}
$parser->allow['BIU'] = 'Y';
$parser->allow['FONT'] = 'Y';
$parser->allow['EMOJI'] = 'Y';
$parser->allow['SMILES'] = 'Y';
$parser->allow['NL2BR'] = 'Y';
$parser->allow['ANCHOR'] = 'Y';
$parser->allow['TEXT_ANCHOR'] = 'Y';

foreach($result as $key =>$notify)
{
	$notify['text'] = $parser->convertText($notify['text']);
	$result[$key] = $notify;
}

$arResult['NOTIFY'] = $result;
$arResult['UNREAD_NOTIFY_ID'] = $unreadNotifyId;

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>