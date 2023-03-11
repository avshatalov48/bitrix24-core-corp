<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Connector;

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
/** @var CBitrixComponent $component */
/** $arResult['CONNECTION_STATUS']; */
/** $arResult['REGISTER_STATUS']; */
/** $arResult['ERROR_STATUS']; */
/** $arResult['SAVE_STATUS']; */

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__ . '/meta.php');

const HELP_DESK_HUMAN_AGENT = '14927782';

if($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Extension::load('ui.hint');
	Connector::initIconCss();
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);

?>
<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>

<?php
if (!$arResult['IFRAME'])
{
	?>
	<div class="imconnector-page-menu-sidebar">
		<?$APPLICATION->ShowViewContent('left-panel');?>
	</div>
	<?
}

if (!empty($arResult['CONFIG_MENU']) && is_countable($arResult['CONFIG_MENU']) && count($arResult['CONFIG_MENU']) > 1)
{
	$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrappermenu', '', [
		'ITEMS' => $arResult['CONFIG_MENU'],
		'TITLE' => Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CATALOG_MENU_TITLE'),
		'RELOAD_PAGE_AFTER_SAVE' => true
	]);
}

foreach ($arResult['CONFIG_MENU'] as $key => $menuItem)
{
	$className = ($key === $arResult['MENU_TAB'] ? 'imconnector-page-show' : 'imconnector-page-hide imconnector-hidden-page');
	if (!empty($arResult['MENU_TAB']) && $key === 'connector')
	{
		$className = 'imconnector-page-show';
	}
	?>
	<div data-fb-connector-page="<?=$key?>" class="<?=$className?>">
		<?php include_once $menuItem['PAGE']; ?>
	</div>
	<?php
}

if (!$arResult['MENU_TAB'])
{?>
	<div data-fb-connector-page="connector" class="imconnector-page-show">
		<?php include_once 'connector.php'; ?>
	</div>
	<?php
}
?>
<script>BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);</script>