<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
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
/** $arResult["CONNECTION_STATUS"]; */
/** $arResult["REGISTER_STATUS"]; */
/** $arResult["ERROR_STATUS"]; */
/** $arResult["SAVE_STATUS"]; */

Loc::loadMessages(__FILE__);

if ($arParams['INDIVIDUAL_USE'] !== 'Y')
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
	<?=bitrix_sessid_post()?>
</form>
<?if(empty($arResult['PAGE'])): //case when not first open?>
	<div class="imconnector-field-container">
	<?if (!empty($arResult['STATUS']) && $arResult['STATUS'] === true): //case when connection completed ?>
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CHANGE_ANY_TIME')?>
				</div>
				<div class="ui-btn-container">
					<button class="ui-btn ui-btn-light-border"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
					</button>
				</div>
			</div>
		</div>
	<? elseif(!empty($arResult['ACTIVE_STATUS']) && $arResult['ACTIVE_STATUS'] === true):?>
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=$arResult['NAME']?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SETTING_IS_NOT_COMPLETED')?>
				</div>
				<div class="ui-btn-container">
					<a href="<?=$arResult['URL']['SIMPLE_FORM']?>" class="ui-btn ui-btn-primary show-preloader-button">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CONTINUE_WITH_THE_SETUP')?>
					</a>
					<button class="ui-btn ui-btn-light-border"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
					</button>
				</div>
			</div>
		</div>
	<?else:?>
		<div class="imconnector-field-section imconnector-field-section-social imconnector-field-section-info">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box" data-role="more-info">
				<div class="imconnector-field-main-subtitle imconnector-field-section-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_SUBTITLE') ?>
					</div>

					<ul class="imconnector-field-box-content-text-items">
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_LIST_ITEM_1') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_LIST_ITEM_2') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_LIST_ITEM_3') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_LIST_ITEM_4') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_LIST_ITEM_5') ?></li>
					</ul>

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INDEX_ADDITIONAL_DESCRIPTION_NEW', [
							'#LINK_START#' => '<a href="#" id="imconnector-avito-link-help-create">',
							'#LINK_END#' => '</a>',
						])?>
					</div>

					<div class="imconnector-field-box-content-btn">
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
							<?=bitrix_sessid_post()?>
							<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
									type="submit"
									name="<?=$arResult['CONNECTOR']?>_active"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	<?endif;?>
	</div>
	<?php
	include 'messages.php';

	if (!empty($arResult['STATUS']))
	{
		include 'info.php';
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?include 'connection-help.php';?>
			</div>
		</div>
		<?php
	}
else:?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?php
				if (empty($arResult['FORM']['INFO_CONNECTION']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECT_STEP_NEW', [
							'#LINK_START#' => '<a id="imconnector-avito-link-help-create" class="imconnector-field-box-link">',
							'#LINK_END#' => '</a>',
						])?>
					</div>
					<?php
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_FINAL_FORM_DESCRIPTION')?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<?if (!empty($arResult['STATUS']))
	{
		include 'info.php';
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_AUTHORIZATION') ?>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_LOG_IN_OAUTH') ?>
					</div>
				</div>
				<?php
				if (!empty($arResult['FORM']['USER']['URI']))
				{
					?>
					<div class="imconnector-field-social-connector">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode ?> imconnector-field-social-connector-icon">
							<i></i></div>
						<div class="ui-btn ui-btn-light-border"
							 onclick="BX.util.popup('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['URI'])) ?>', 700, 700)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_AUTHORIZE') ?>
						</div>
					</div>
					<?
				}
				?>
			</div>
		</div>
		<?php
	}
endif;

