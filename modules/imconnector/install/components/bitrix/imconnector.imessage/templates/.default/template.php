<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global \CMain $APPLICATION */
/** @global \CUser $USER */
/** @global \CDatabase $DB */
/** @var \CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImConnector\Connector;

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
$placeholder = !empty($arResult['placeholder']['business_id'])
	? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER')
	: Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_ID');

if (!empty($arResult['CAN_USE_CONNECTION']) && $arResult['CAN_USE_CONNECTION'] === true)
{
	$onClickConnectCode = 'popupIMessageShow(); return false;';
	$onClickManuallyCode = 'BX.submit(BX(\'' . $arResult['CONNECTOR'] . '_action_form\'));';
}
else
{
	$onClickConnectCode = 'BX.UI.InfoHelper.show(\'' . $arResult['INFO_HELPER_LIMIT'] . '\'); return false;';
	$onClickManuallyCode = 'BX.UI.InfoHelper.show(\'' . $arResult['INFO_HELPER_LIMIT'] . '\'); return false;';
}
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<?if(empty($arResult['PAGE'])): //case when not first open?>
	<div class="imconnector-field-container">
	<?if (!empty($arResult['STATUS']) && $arResult['STATUS'] === true): //case when connection competed?>
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=$arResult['NAME']?>
				</div>
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECTED_NEW')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CHANGE_ANY_TIME')?>
				</div>
				<div class="ui-btn-container">
					<a href="<?=$arResult['URL']['SAVE_FORM']?>" class="ui-btn ui-btn-primary show-preloader-button">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
					</a>
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
					<a href="<?=$arResult['URL']['SAVE_FORM']?>" class="ui-btn ui-btn-primary show-preloader-button">
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
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_SUBTITLE') ?>
					</div>

					<ul class="imconnector-field-box-content-text-items">
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_LIST_ITEM_1') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_LIST_ITEM_2') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_LIST_ITEM_3') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_LIST_ITEM_4') ?></li>
					</ul>

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INDEX_ADDITIONAL_DESCRIPTION_NEW', [
							'#LINK1_START#' => '<a href="#" id="imconnector-imessage-link-help-create">',
							'#LINK1_END#' => '</a>',
							'#LINK2_START#' => '<a href="#" id="imconnector-imessage-link-help-connect">',
							'#LINK2_END#' => '</a>',
						])?>
						<br />
						<form action="<?=$arResult['URL']['SAVE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" id="<?=$arResult['CONNECTOR']?>_action_form" value="true">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active" value="true">
							<?=bitrix_sessid_post()?>
							<a href="" onclick="<?=$onClickConnectCode?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</a>
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_OR')?>
							<a href="" onclick="<?=$onClickManuallyCode?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_MANUALLY')?>
							</a>
						</form>
					</div>

					<div class="imconnector-field-box-content-btn">
						<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
								onclick="top.BX.Helper.show('<?=$arResult['HELP_LIMIT_DESK_PARAMS']?>'); return false;"
								value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_CONNECTION_INFORMATION')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_CONNECTION_INFORMATION')?>
						</button>
					</div>
				</div>
			</div>
		</div>
	<?endif;?>
	</div>
	<?=$arResult['LANG_JS_SETTING'];?>
	<?
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
		<?
	}
else:?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
			<? if (!empty($arResult['PAGE']) && $arResult['PAGE'] === 'connection'):?>
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_CONNECTION_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_CONNECTION_STEP')?>
				</div>
			<?else:?>
				<?if(empty($arResult['INFO_CONNECTION'])):?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_STEP_NEW', [
							'#LINK1_START#' => '<a id="imconnector-imessage-link-help-create" class="imconnector-field-box-link">',
							'#LINK1_END#' => '</a>',
							'#LINK2_START#' => '<a id="imconnector-imessage-link-help-connect" class="imconnector-field-box-link">',
							'#LINK2_END#' => '</a>',
						])?>
					</div>
				<?else:?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECTED_NEW')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FINAL_FORM_DESCRIPTION')?>
					</div>
				<?endif;?>
			<?endif;?>
			</div>
		</div>
		<?include 'messages.php'?>
		<?if (!empty($arResult['PAGE']) && $arResult['PAGE'] === 'connection'):?>
			<?
			if(!empty($arResult['INFO_CONNECTION']))
			{
				include 'info.php';
			}
			?>
			<div class="imconnector-field-section imconnector-field-section-control">
				<?if(!empty($arResult['INFO_CONNECTION'])):?>
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INFO_NEW_CONNECT')?>
				</div>
				<?endif;?>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-entity-row">
						<div class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_ID')?>:
						</div>
						<span class="imconnector-field-box-entity-link">
							<?=htmlspecialcharsbx($arResult['FORM']['business_id'] ?? '')?>
						</span>
					</div>

					<?if (!empty($arResult['FORM']['business_name'])):?>
					<div class="imconnector-field-box-entity-row">
						<div class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_NAME')?>:
						</div>
						<span class="imconnector-field-box-entity-link">
							<?=htmlspecialcharsbx($arResult['FORM']['business_name'])?>
						</span>
					</div>
					<?endif;?>
				</div>
				<div class="imconnector-step-text">
					<form action="<?=$arResult['URL']['SAVE_FORM']?>"
						  method="post"
						  class="imconnector-field-control-box imconnector-field-control-box-border">
						<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
						<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active" value="true">
						<?=bitrix_sessid_post();?>
						<input type="hidden" name="business_id" value="<?=htmlspecialcharsbx($arResult['FORM']['business_id'])?>">
						<input type="hidden" name="business_name" value="<?=htmlspecialcharsbx($arResult['FORM']['business_name'])?>">

						<?
						$buttonName = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT');
						if(!empty($arResult['INFO_CONNECTION']))
						{
							$buttonName = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_RECONNECT');
						}
						?>
						<button class="ui-btn ui-btn-success"
								id="webform-small-button-have-bot"
								name="<?=$arResult['CONNECTOR']?>_save"
								value="<?=$buttonName?>">
							<?=$buttonName?>
						</button>
					</form>
				</div>
			</div>
		<?else:?>
			<div class="imconnector-field-section imconnector-field-section-control">
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_ID')?>:
					</div>
					<form action="<?=$arResult['URL']['SAVE_FORM']?>"
						  method="post"
						  class="imconnector-field-control-box imconnector-field-control-box-border">
						<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
						<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
						<?=bitrix_sessid_post();?>
						<input type="text"
							   class="imconnector-field-control-input"
							   id="imconnector-imessage-business-id"
							   name="business_id"
							   value="<?=htmlspecialcharsbx($arResult['FORM']['business_id'] ?? '')?>"
							   placeholder="<?=$placeholder?>">
						<button class="ui-btn ui-btn-success"
								id="webform-small-button-have-bot"
								name="<?=$arResult['CONNECTOR']?>_save"
								value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
						</button>
					</form>
				</div>
				<?
				if(!empty($arResult['INFO_CONNECTION']))
				{
					include 'info.php';
				}
				?>
			</div>
		<?endif;?>
	</div>
<?endif;
