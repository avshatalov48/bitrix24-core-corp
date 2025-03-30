<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CDiskExternalLinkComponent $component */
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.notification',
]);

if(!empty($arResult['SESSION_EXPIRED']))
{
	Loc::loadMessages(__DIR__ . '/template.php');
	$sessionExpireMessage = GetMessageJS('DISK_EXT_SESSION_EXPIRED');

	$this->SetViewTarget("below_page");
	echo <<<JS
		<script>
			BX.UI.Notification.Center.notify({
				content: '{$sessionExpireMessage}',
			});
			
			let url = window.location.href;
			url = url.replace(/\&session=expired/, '');
			window.history.replaceState({}, '', url);			
		</script>
	JS;
	$this->EndViewTarget();
}

if (!($arResult['PROTECTED_BY_PASSWORD']) || $arResult['VALID_PASSWORD'])
{
	if (in_array($arResult['FILE_VIEWER'], $arResult['FILE_VIEWERS'], true))
	{
		include __DIR__ . "/file-viewers/{$arResult['FILE_VIEWER']}.php";
	}
	else
	{
		die('FILE_VIEWER_NOT_FOUND');
	}
}