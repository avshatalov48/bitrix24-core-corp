<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

$CIMNotify = new CIMNotify(false, Array(
	'hide_link' => false
));
$result = $CIMNotify->GetNotifyList();

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

$arResult['NOTIFY'] = $result;
$arResult['UNREAD_NOTIFY_ID'] = $unreadNotifyId;

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>