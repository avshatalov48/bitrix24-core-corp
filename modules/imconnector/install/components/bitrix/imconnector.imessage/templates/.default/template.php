<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

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

use \Bitrix\Main\UI\Extension,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Limit,
	\Bitrix\ImConnector\Connector;

Loc::loadMessages(__FILE__);

if ($arParams['INDIVIDUAL_USE'] != 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Extension::load('ui.hint');
	Connector::initIconCss();
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);
$placeholder = $arResult['placeholder']['business_id'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_ID');

if ($arResult['CAN_USE_CONNECTION'] === true)
{
	$onClickConnectCode = 'popupIMessageShow(); return false;';
	$onClickManuallyCode = 'BX.submit(BX(\'' . $arResult['CONNECTOR'] . '_action_form\'));';
} else
{
	$onClickConnectCode = 'BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONNECTOR_IMESSAGE . '\'); return false;';
	$onClickManuallyCode = 'BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONNECTOR_IMESSAGE . '\'); return false;';
}
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
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
				if ($arResult['STATUS']) //case when connection competed
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECTED')?>
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
					<?
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
					<?
					if ($arResult['ACTIVE_STATUS']) //case when connection in process
					{
						?>
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
						<?
					}
					else
					{
						?>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_STEP', ['#ID#' => $arResult['HELP_DESK_PARAMS']])?>
						</div>
						<form action="<?=$arResult['URL']['SAVE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" id="<?=$arResult['CONNECTOR']?>_action_form" value="true">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active" value="true">
							<?=bitrix_sessid_post()?>
							<button class="ui-btn ui-btn-light-border"
									onclick="top.BX.Helper.show('<?=$arResult['HELP_LIMIT_DESK_PARAMS']?>'); return false;"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_CONNECTION_INFORMATION')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_CONNECTION_INFORMATION')?>
							</button>
							<br /><br />
							<a href="" onclick="<?=$onClickConnectCode?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</a>
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_OR')?>
							<a href="" onclick="<?=$onClickManuallyCode?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FORM_MANUALLY')?>
							</a>
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
	<?=$arResult['LANG_JS_SETTING'];?>
	<?
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
			<?if($arResult['PAGE'] == 'connection'):?>
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_CONNECTION_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_CONNECTION_STEP')?>
				</div>
			<?else:?>
				<?
				if (empty($arResult['INFO_CONNECTION']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECT_STEP', ['#ID#' => $arResult['HELP_DESK_PARAMS']])?>
					</div>
					<?
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_FINAL_FORM_DESCRIPTION')?>
					</div>
					<?
				}
				?>
			<?endif;?>
			</div>
		</div>
		<?include 'messages.php'?>
		<?if($arResult['PAGE'] == 'connection'):?>
			<?
			if (!empty($arResult['INFO_CONNECTION']))
			{
				include 'info.php';
			}
			?>
			<div class="imconnector-field-section imconnector-field-section-control">
				<?if (!empty($arResult['INFO_CONNECTION'])):?>
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
						<?=$arResult['FORM']['business_id']?>
						</span>
					</div>

					<?if(!empty($arResult['FORM']['business_name'])):?>
					<div class="imconnector-field-box-entity-row">
						<div class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_NAME')?>:
						</div>
						<span class="imconnector-field-box-entity-link">
						<?=$arResult['FORM']['business_name']?>
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
						<input type="hidden" name="business_id" value="<?=$arResult['FORM']['business_id']?>">
						<input type="hidden" name="business_name" value="<?=$arResult['FORM']['business_name']?>">

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
							   value="<?=$arResult['FORM']['business_id']?>"
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
				if (!empty($arResult['INFO_CONNECTION']))
				{
					include 'info.php';
				}
				?>
			</div>
		<?endif;?>
	</div>
	<?
}
?>
