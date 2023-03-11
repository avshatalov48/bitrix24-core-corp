<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

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
	\Bitrix\Main\UI\Extension::load('ui.buttons');
	\Bitrix\Main\UI\Extension::load('ui.hint');
	\Bitrix\ImConnector\Connector::initIconCss();
}

$placeholder = ' placeholder="'.Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER').'"';

$iconCode = \Bitrix\ImConnector\Connector::getIconByConnector($arResult['CONNECTOR']);
?>

	<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
		<?=bitrix_sessid_post()?>
	</form>
<?php
if (empty($arResult['PAGE']))
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?php
				if (!empty($arResult['STATUS'])) //case when connection competed
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CHANGE_ANY_TIME')?>
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
					<?php
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_TITLE')?>
					</div>
					<?php
					if (!empty($arResult['ACTIVE_STATUS']))
					{
						?>
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
						<?php
					}
					else
					{
						?>
						<div class="imconnector-field-box-content">
							<div class="imconnector-field-box-content-text-light">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_SUBTITLE') ?>
							</div>
							<ul class="imconnector-field-box-content-text-items">
								<li class="imconnector-field-box-content-text-item">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_LIST_ITEM_1') ?>
								</li>
								<li class="imconnector-field-box-content-text-item">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_LIST_ITEM_2') ?>
								</li>
								<li class="imconnector-field-box-content-text-item">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_LIST_ITEM_3') ?>
								</li>
							</ul>
							<div class="imconnector-field-box-content-text-light">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_ADDITIONAL_DESCRIPTION')?>
							</div>
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
						<?php
					}
					?>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
	include 'messages.php';

	if (!$arResult['STATUS'])
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?php include 'connection-help.php'; ?>
			</div>
		</div>
		<?php
	}
}
else
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?php
				if (empty($arResult['STATUS']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECT_STEP_NEW', [
							'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-whatsappbyedna-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>
					<?php
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_FINAL_FORM_DESCRIPTION')?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php include 'messages.php' ?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<div class="imconnector-field-box">
				<form action="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
				      method="post"
				      class="imconnector-field-control-box-border">
					<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
					<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
					<?=bitrix_sessid_post()?>

					<div class="imconnector-step-text">
						<label for="imconnector-whatsappbyedna-api-key">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_API_KEY')?>
						</label>
					</div>
					<input
						type="text"
						class="imconnector-field-control-input"
						id="imconnector-whatsappbyedna-api-key"
						name="api_key"
						value="<?=htmlspecialcharsbx($arResult['FORM']['api_key'])?>"
						<?= !empty($arResult['placeholder']['api_key']) ? $placeholder : ''?>
					>
					<div class="imconnector-step-text">
						<label for="imconnector-whatsappbyedna-sender-id">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SENDER_ID')?>
						</label>
					</div>
					<input
						type="text"
						class="imconnector-field-control-input"
						id="imconnector-whatsappbyedna-sender-id"
						name="sender_id"
						value="<?=htmlspecialcharsbx($arResult['FORM']['sender_id'])?>"
						<?= !empty($arResult['placeholder']['sender_id']) ? $placeholder : ''?>
					>
					<div class="imconnector-step-text">
						<button class="ui-btn ui-btn-success"
						        id="webform-small-button-have"
						        name="<?=$arResult['CONNECTOR']?>_save"
						        value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
						</button>
					</div>
				</form>
			</div>
			<?php
			if (empty($arResult['STATUS'])) //not connected yet case
			{
				include 'connection-help.php';
			}
			?>
		</div>
	</div>
	<?php
}
?>