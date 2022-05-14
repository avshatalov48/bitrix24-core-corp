<?

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule("mobile");

$componentName = $_GET["componentName"];

if ($USER->IsAuthorized())
{
	if ($componentName)
	{
		$APPLICATION->IncludeComponent("bitrix:immobile.webcomponent", "", array(
			"componentName" => $componentName,
		), null, array("HIDE_ICONS" => "Y"));
	}
}
else
{
	Bitrix\Mobile\Auth::setNotAuthorizedHeaders();
	echo \Bitrix\Main\Web\Json::encode(Bitrix\Mobile\Auth::getNotAuthorizedResponse());
}

