<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
use \Bitrix\Main\UI\Extension,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImConnector\Connector;

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
$clientIdPlaceholder = $arResult['placeholder']['client_id'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CLIENT_ID');
$clientSecretPlaceholder = $arResult['placeholder']['client_secret'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CLIENT_SECRET');
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post()?>
</form>
<?php
if (empty($arResult['PAGE'])) //case when not first open
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?php
				if ($arResult['STATUS']) //case when connection competed
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CHANGE_ANY_TIME')?>
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
						<?=$arResult['NAME']?>
					</div>
					<?php
					if ($arResult['ACTIVE_STATUS']) //case when connection in process
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
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECT_STEP', array('#ID#' => Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INFO_CONNECT_ID')))?>
						</div>
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
							<?=bitrix_sessid_post()?>
							<button class="ui-btn ui-btn-light-border"
									type="submit"
									name="<?=$arResult['CONNECTOR']?>_active"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</button>
						</form>
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

	if ($arResult['STATUS'])
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
				if (empty($arResult['INFO_CONNECTION']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CONNECT_STEP', array('#ID#' => Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_INFO_CONNECT_ID')))?>
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
		<?include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<form action="<?=$arResult['URL']['SIMPLE_FORM']?>"
				  method="post">
				<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
				<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
				<?=bitrix_sessid_post()?>
				<div class="imconnector-field-box imconnector-field-control-box-border">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CLIENT_ID')?>
					</div>
					<div class="imconnector-field-control-box">
						<input type="text"
							   class="imconnector-field-control-input imconnector-input-data"
							   id="imconnector-avito-client-id"
							   name="client_id"
							   value="<?=$arResult['FORM']['client_id']?>"
							   placeholder="<?=$clientIdPlaceholder?>">
					</div>
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_AVITO_CLIENT_SECRET')?>
					</div>
					<div class="imconnector-field-control-box">
						<input type="text"
							   class="imconnector-field-control-input imconnector-input-data"
							   id="imconnector-avito-client-secret"
							   name="client_secret"
							   value="<?=$arResult['FORM']['client_secret']?>"
							   placeholder="<?=$clientSecretPlaceholder?>">
					</div>
					<div class="imconnector-field-control-box">
						<button class="ui-btn ui-btn-success"
								id="webform-small-button-have-chat"
								name="<?=$arResult['CONNECTOR']?>_save"
								value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
						</button>
					</div>
				</div>
			</form>
			<?php
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
	<?php
}
?>
