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
		<script type="text/javascript">
			BX.UI.Notification.Center.notify({
				content: '{$sessionExpireMessage}',
			});
		</script>
	JS;
	$this->EndViewTarget();
}

if (!($arResult['PROTECTED_BY_PASSWORD']) || $arResult['VALID_PASSWORD'])
{
	$APPLICATION->includeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'EXTERNAL_LINK_MODE' => true,
				'SHOW_BUTTON_OPEN_NEW_WINDOW' => false,
				'LINK_TO_EDIT' => $arResult['LINK_TO_EDIT'] ?? '',
				'LINK_TO_DOWNLOAD' => $arResult['LINK_TO_DOWNLOAD'] ?? '',
				'DOCUMENT_SESSION' => $arResult['DOCUMENT_SESSION'],
			],
			'PLAIN_VIEW' => true,
			'IFRAME_MODE' => true,
			'PREVENT_LOADING_WITHOUT_IFRAME' => false,
			'USE_PADDING' => false,
		]
	);
}