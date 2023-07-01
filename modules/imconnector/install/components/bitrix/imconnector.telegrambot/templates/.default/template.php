<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImConnector\Connector;

/** @global array $arParams */
/** @global array $arResult */
/** @global \CMain $APPLICATION */
/** @global \CUser $USER */
/** @global \CDatabase $DB */
/** @var \CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \CBitrixComponent $component */

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

if ($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Extension::load('ui.hint');
	Connector::initIconCss();
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);
$placeholder = !empty($arResult['placeholder']['api_token'])
	? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER')
	: Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_API_TOKEN_NAME');

$initData = [
	'api_token' => '',
	'welcome_message' => '',
	'eshop_enabled' => 'N',
	'eshop_id' => '',
	'eshop_custom_url' => '',
];
if (empty($arResult['FORM']))
{
	$arResult['FORM'] = $initData;
}
else
{
	$arResult['FORM'] = array_merge($initData, $arResult['FORM']);
}

?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<?if (empty($arResult['PAGE'])):?>
	<div class="imconnector-field-container">
	<?if (!empty($arResult['STATUS']) && $arResult['STATUS'] === true): //case when connection competed?>
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CHANGE_ANY_TIME')?>
				</div>
				<div class="ui-btn-container">
					<a href="<?=$arResult['URL']['SIMPLE_FORM']?>" class="ui-btn ui-btn-primary show-preloader-button">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
					</a>
					<button class="ui-btn ui-btn-light-border"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
					</button>
				</div>
			</div>
		</div>
	<? elseif (!empty($arResult['ACTIVE_STATUS']) && $arResult['ACTIVE_STATUS'] === true):?>
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
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_SUBTITLE') ?>
					</div>

					<ul class="imconnector-field-box-content-text-items">
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_LIST_ITEM_1') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_LIST_ITEM_2') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_LIST_ITEM_3') ?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_LIST_ITEM_4') ?></li>
					</ul>

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INDEX_ADDITIONAL_DESCRIPTION_NEW_MSGVER_2', [
							'#LINK_START#' => '<a id="imconnector-telegrambot-link-help" href="#">',
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
	<?
	include 'messages.php';

	if (!empty($arResult['STATUS']))
	{
		include 'info.php';
	}?>
<?else:?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?if (empty($arResult['INFO_CONNECTION'])):?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECT_STEP_NEW', [
							'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-telegrambot-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>
				<?else:?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_FINAL_FORM_DESCRIPTION')?>
					</div>
				<?endif;?>
			</div>
		</div>
		<?include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
		<?if (empty($arResult['INFO_CONNECTION']) || !$arResult['IS_SUCCESS_SAVE']) //not connected yet case
		{?>
				<form
					action="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
					method="post"
					class="imconnector-field-control-box-border"
				>
					<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
					<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
					<?=bitrix_sessid_post();?>
					<div class="imconnector-field-container">
						<div class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_API_TOKEN')?>
						</div>
						<input
							type="text"
							class="imconnector-field-control-input"
							id="imconnector-telegrambot-have-bot"
							name="api_token"
							value="<?=htmlspecialcharsbx($arResult['FORM']['api_token'] ?? '')?>"
							placeholder="<?=$placeholder?>"
						>
					</div>
					<div class="imconnector-field-container">
						<span class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_WELCOME_MESSAGE')?>
						</span>
						<textarea
							class="imconnector-field-control-input imconnector-field-control-textbox imconnector-telegrambot-welcome-message-textarea"
							name="welcome_message"><?=htmlspecialcharsbx($arResult['FORM']['welcome_message'] ?? '')?></textarea>
					</div>
					<div class="imconnector-field-container">
						<input
							class="imconnector-public-link-settings-inner-option-field"
							type="checkbox"
							name="eshop_enabled"
							id="imconnector-telegrambot-eshop-enabled"
							value="Y"
							<?=($arResult['FORM']['eshop_enabled'] === 'Y' ? 'checked' : '')?>
						>
						<div class="imconnector-field-box-subtitle imconnector-telegrambot-eshop-checkbox-beta" style="display: inline-block">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECT_ESHOP')?>
							<span class="imconnector-telegrambot-eshop-beta"></span>
						</div>
						<div class="imconnector-telegrambot-eshop-checkbox-beta-more-info">
							<a
								onclick="top.BX.Helper.show('redirect=detail&code=15718474');"
								class="imconnector-field-box-entity-link"
								target="_blank">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECT_ESHOP_MORE')?>
							</a>
						</div>
					</div>
					<div class="imconnector-field-container" id="imconnector-telegrambot-eshop-url">
						<div class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_SELECT_ESHOP')?>
						</div>
						<select name="eshop_id" id="imconnector-telegrambot-eshop-list" class="imconnector-telegrambot-select-eshop imconnector-field-control-select" onchange='window.checkCustomEshopSelected()'>
							<option value="0" <?=$arResult['FORM']['eshop_id']=== '0' ? 'selected': ''?>>
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_SELECT_ESHOP_EXTERNAL')?>
							</option>
							<?php foreach ($arResult['ESHOP_LIST'] as $key => $eshop): ?>
								<option value="<?=$eshop['ID']?>" <?=$arResult['FORM']['eshop_id']===$eshop['ID'] || is_null($arResult['FORM']['eshop_id']) ? 'selected': ''?>>
									<?=htmlspecialcharsbx($eshop['TITLE'])?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="imconnector-field-container" id="imconnector-telegrambot-eshop-custom-url">
						<div class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_URL_ESHOP')?>
						</div>
						<input
							type="text"
							class="imconnector-field-control-input"
							name="eshop_custom_url"
							value="<?=htmlspecialcharsbx($arResult['FORM']['eshop_custom_url'])?>"
							placeholder="https://"
						>
					</div>
					<div class="imconnector-field-container">
						<button class="ui-btn ui-btn-success"
						        id="webform-small-button-have-bot"
						        name="<?=$arResult['CONNECTOR']?>_save"
						        value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
						</button>
					</div>
				</form>
		<?
			include 'connection-help.php';
		}
		else
		{
			include 'info.php';
		}
		?>
		</div>
	</div>
<?endif;
