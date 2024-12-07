<?php
/** @var array $arParams */
/** @var array $arResult */
/** @var CMain $APPLICATION */
/** @var CBitrixComponent $component */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\ScrumLimit;
use Bitrix\Tasks\Util\User;

if ($arParams['MENU_MODE'])
{
	return;
}

$defaultMenuTarget =  "above_pagetitle";

CJSCore::init("spotlight");

Extension::load('ui.info-helper');

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->SetViewTarget($defaultMenuTarget, 200);
}

$menuId = intval($arParams["GROUP_ID"]) ? "tasks_panel_menu_group" : "tasks_panel_menu";

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if(
	(
		(int)$arParams["GROUP_ID"] == 0
		|| $arParams['PROJECT_VIEW'] == 'Y'
	)
	&& $arParams['USER_ID'] == $arParams['LOGGED_USER_ID']
	&& (
		$request->get('IFRAME') !== 'Y'
		|| $request->get('SHOW_TASKS_TOP_MENU') === 'Y'
	)
)
{

	if ($request->get('IFRAME') === 'Y'): ?>
		<style>.pagetitle-above {
				margin-top: 18px;
			}
		</style>
	<?php
	endif ?>

	<div class="" id="<?=$arResult['HELPER']->getScopeId()?>">
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.buttons',
		'',
		array(
			'ID' => $menuId,
			'ITEMS' => $arResult['ITEMS'],
			'DISABLE_SETTINGS' => $arParams["USER_ID"] !== User::getId(),
		),
		$component,
		array('HIDE_ICONS' => true)
	);
	?></div><?php
}

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->EndViewTarget();
}

$isScrumLimitExceeded = ScrumLimit::isLimitExceeded() || !ScrumLimit::isFeatureEnabled();
if (ScrumLimit::canTurnOnTrial())
{
	$isScrumLimitExceeded = false;
}

$arResult['HELPER']->initializeExtension([
	'isScrumLimitExceeded' => $isScrumLimitExceeded,
	'isTaskAccessPermissionsLimit' => !(
		Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASK_ACCESS_PERMISSIONS)
	),
	'isRoleControlDisabled' => Filter::isRolesEnabled(),
]);