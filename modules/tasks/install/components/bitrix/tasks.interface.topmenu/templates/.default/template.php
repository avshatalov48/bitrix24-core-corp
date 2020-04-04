<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$defaultMenuTarget =  "above_pagetitle";

\CJSCore::init("spotlight");

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->SetViewTarget($defaultMenuTarget, 200);
}

if($_REQUEST['IFRAME'] && $_REQUEST['IFRAME']==='Y') {
    ?>
    <style>.pagetitle-above {
            margin-top: 18px;
        }
    </style>
    <?
}

$menuId = intval($arParams["GROUP_ID"]) ? "tasks_panel_menu_group" : "tasks_panel_menu";

if((int)$arParams["GROUP_ID"] == 0 && $arParams['USER_ID'] == $arParams['LOGGED_USER_ID'])
{
	?>

	<div class="" id="<?=$arResult['HELPER']->getScopeId()?>">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.buttons',
		'',
		array(
			'ID' => $menuId,
			'ITEMS' => $arResult['ITEMS'],
			'DISABLE_SETTINGS' => $arParams["USER_ID"] !== \Bitrix\Tasks\Util\User::getId()
		),
		$component,
		array('HIDE_ICONS' => true)
	);
	?></div><?
}

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->EndViewTarget();
}

$arResult['HELPER']->initializeExtension();