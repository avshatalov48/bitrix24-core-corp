<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var $this \CBitrixComponentTemplate */
/** @var CMain $APPLICATION */
/** @var array $arResult*/
/** @var array $arParams*/

\Bitrix\Main\UI\Extension::load([
	"ui.switcher",
	"ui.notification"
]);

$APPLICATION->SetTitle( \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_PAGE_TITLE'));

$this->SetViewTarget('below_pagetitle', 100);
$APPLICATION->IncludeComponent(
	"bitrix:crm.toolbar",
	"",
	[
		'views' => [
			'features' => [
				'title' => \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_LIST_MENU_FEATURE'),
				'url' => '/crm/configs/?expert&expertMode=features',
				'isActive' => $arResult['mode'] === 'features',
			],
			'tours' => [
				'title' => \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_LIST_MENU_TOUR'),
				'url' => '/crm/configs/?expert&expertMode=tours',
				'isActive' => $arResult['mode'] === 'tours',
			]
		],
		'isWithFavoriteStar' => true,
		'hideBorder' => true,
	],
	$this
);
$this->EndViewTarget();
?>

<script>
	BX.ready(() => {
		BX.message(<?=\Bitrix\Main\Web\Json::encode([
			'crmLinkCopiedToClipboard' => \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_LIST_LINK_COPIED')
		])?>)
	})
</script>
