<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
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

if ($arParams['INDIVIDUAL_USE'] != 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	\Bitrix\Main\UI\Extension::load("ui.buttons");
	\Bitrix\Main\UI\Extension::load("ui.hint");
	\Bitrix\ImConnector\Connector::initIconCss();
}

$iconCode = \Bitrix\ImConnector\Connector::getIconByConnector($arResult["CONNECTOR"]);
$idPlaceholder = $arResult["placeholder"]["app_id"] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_APP_ID');
$secretPlaceholder = $arResult["placeholder"]["app_secret"] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_APP_SECRET');
$encryptKeyPlaceholder = $arResult['placeholder']['encrypt_key'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_APP_ENCRYPT_KEY');

?>

<form action="<?=$arResult["URL"]["DELETE"]?>" method="post" id="form_delete_<?=$arResult["CONNECTOR"]?>">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<?
if (empty($arResult['PAGE'])) //case when not first open
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?
				if (!empty($arResult['STATUS'])) //case when connection competed
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_CHANGE_ANY_TIME')?>
					</div>
					<div class="ui-btn-container">
						<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>" class="ui-btn ui-btn-primary show-preloader-button">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
						</a>
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</button>
					</div>
					<?
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
					<?
					if (!empty($arResult['ACTIVE_STATUS'])) //case when connection in process
					{
						?>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SETTING_IS_NOT_COMPLETED')?>
						</div>
						<div class="ui-btn-container">
							<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>" class="ui-btn ui-btn-primary show-preloader-button">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CONTINUE_WITH_THE_SETUP')?>
							</a>
							<button class="ui-btn ui-btn-light-border"
									onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
							</button>
						</div>
						<?
					}
					else
					{
						?>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_CONNECT_STEP_NEW', [
								'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-wechat-link-help">',
								'#LINK_END#' => '</a>',
							])?>
						</div>
						<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
							<?=bitrix_sessid_post();?>
							<button class="ui-btn ui-btn-light-border"
									type="submit"
									name="<?=$arResult["CONNECTOR"]?>_active"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</button>
						</form>
						<?
					}
					?>
					<?
				}
				?>

			</div>
		</div>
	</div>
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
				<?
				if (empty($arResult['INFO_CONNECTION']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_CONNECT_STEP_NEW', [
							'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-wechat-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>
					<?
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_FINAL_FORM_DESCRIPTION')?>
					</div>
					<?
				}
				?>
			</div>
		</div>
		<?include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<?
			if(!empty($arResult["URL_WEBHOOK"]))
			{
				?>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_WEBHOOK_DESCRIPTION')?>
					</div>
					<div class="imconnector-field-control-box">
						<input type="text"
							   class="imconnector-field-control-input"
							   value="<?=htmlspecialcharsbx($arResult["URL_WEBHOOK"])?>"
							   readonly>
						<button class="ui-btn ui-btn-success copy-to-clipboard"
								data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["URL_WEBHOOK"]))?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_COPY')?>
						</button>
					</div>
				</div>
				<?
			}
			?>
			<?
			if(!empty($arResult["TOKEN"]))
			{
				?>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_TOKEN_DESCRIPTION')?>
					</div>
					<div class="imconnector-field-control-box imconnector-field-control-box-border">
						<input type="text"
							   class="imconnector-field-control-input"
							   value="<?=htmlspecialcharsbx($arResult["TOKEN"])?>"
							   readonly>
						<button class="ui-btn ui-btn-success copy-to-clipboard"
								data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["TOKEN"]))?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_COPY')?>
						</button>
					</div>
				</div>
				<?
			}
			?>
			<?php
			if(!empty($arResult['SERVER_IP_ADDRESS']))
			{
				?>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_SERVER_IP_ADDRESS_DESCRIPTION')?>
					</div>
					<div class="imconnector-field-control-box imconnector-field-control-box-border">
						<input type="text"
							   class="imconnector-field-control-input"
							   value="<?=htmlspecialcharsbx($arResult['SERVER_IP_ADDRESS'])?>"
							   readonly>
						<button class="ui-btn ui-btn-success copy-to-clipboard"
								data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['SERVER_IP_ADDRESS']))?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_COPY')?>
						</button>
					</div>
				</div>
				<?php
			}
			?>
			<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
				  method="post">
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_active">
				<?=bitrix_sessid_post();?>
				<div class="imconnector-field-box imconnector-field-control-box-border">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_APP_ID')?>
					</div>
					<div class="imconnector-field-control-box">
						<input type="text"
							   class="imconnector-field-control-input imconnector-input-data"
							   id="imconnector-wechat-app-id"
							   name="app_id"
							   value="<?=htmlspecialcharsbx($arResult["FORM"]["app_id"])?>"
							   placeholder="<?=$idPlaceholder?>">
					</div>
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_APP_SECRET')?>
					</div>
					<div class="imconnector-field-control-box">
						<input type="text"
							   class="imconnector-field-control-input imconnector-input-data"
							   id="imconnector-wechat-app-secret"
							   name="app_secret"
							   value="<?=htmlspecialcharsbx($arResult["FORM"]["app_secret"])?>"
							   placeholder="<?=$secretPlaceholder?>">
					</div>
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_APP_ENCRYPT_KEY')?>
					</div>
					<div class="imconnector-field-control-box">
						<input type="text"
							   class="imconnector-field-control-input imconnector-input-data"
							   id="imconnector-wechat-encrypt-key"
							   name="encrypt_key"
							   value="<?=htmlspecialcharsbx($arResult['FORM']['encrypt_key'])?>"
							   placeholder="<?=$encryptKeyPlaceholder?>">
					</div>
					<div class="imconnector-field-control-box">
						<button class="ui-btn ui-btn-success"
								id="webform-small-button-have-chat"
								name="<?=$arResult["CONNECTOR"]?>_save"
								value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
						</button>
					</div>
				</div>
			</form>
			<?
			if (empty($arResult['INFO_CONNECTION']))
			{
				include 'connection-help.php';
			}
			else
			{
				include 'info.php';
			}
			?>
		</div>
	</div>
	<?
}
?>
