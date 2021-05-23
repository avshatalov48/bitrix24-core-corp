<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Library;
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
Library::loadMessagesConnectorClass();

if ($arParams['INDIVIDUAL_USE'] != 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	\Bitrix\Main\UI\Extension::load("ui.buttons");
	\Bitrix\Main\UI\Extension::load("ui.hint");
	\Bitrix\ImConnector\Connector::initIconCss();
}

$placeholder = ' placeholder="' . Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') . '"';
$iconCode = \Bitrix\ImConnector\Connector::getIconByConnector($arResult["CONNECTOR"]);
?>

<form action="<?=$arResult["URL"]["DELETE"]?>" method="post" id="form_delete_<?=$arResult["CONNECTOR"]?>">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<?
if (empty($arResult['PAGE'])) //case of first opening
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
			<?
			if ($arResult['STATUS']) //first open active form
			{
				?>
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_CHANGE_ANY_TIME')?>
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
			elseif($arResult['ACTIVE_STATUS']) //first open form with started setting
			{
				?>
				<div class="imconnector-field-main-subtitle">
					<?=$arResult['NAME']?>
				</div>
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
				<div class="imconnector-field-main-subtitle">
					<?=$arResult['NAME']?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_INDEX_DESCRIPTION')?>
				</div>
				<div class="ui-btn-container">
					<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post">
						<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
						<?=bitrix_sessid_post();?>
						<button class="ui-btn ui-btn-light-border"
								type="submit"
								name="<?=$arResult["CONNECTOR"]?>_active"
								value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
						</button>
					</form>
				</div>
				<?
			}
			?>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<?
	if ($arResult['STATUS']) //first open active form
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_INFO')?>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-entity-row">
						<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]['URL'])?>"
						   class="imconnector-field-box-entity-link"
						   target="_blank">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_GET_LINKS')?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?
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
else //shows form
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=$arResult['NAME']?>
				</div>
				<div class="imconnector-field-box-content">
					<?= $arResult['STATUS'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_FINAL_FORM_DESCRIPTION_OK_1') : Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_CONNECT_DESCRIPTION')?>
				</div>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-control">
			<?
			if(!empty($arResult["URL_WEBHOOK"]))
			{
				?>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_SIMPLE_FORM_DESCRIPTION')?>
					</div>
					<div class="imconnector-field-control-box imconnector-field-control-box-border">
						<input type="text"
							   class="imconnector-field-control-input"
							   id="imconnector-ms-step-input-adress"
							   value="<?=htmlspecialcharsbx($arResult["URL_WEBHOOK"])?>"
							   readonly>
						<button class="ui-btn ui-btn-success copy-to-clipboard"
								data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["URL_WEBHOOK"]))?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_COPY')?>
						</button>
					</div>
				</div>
				<?
			}
			?>

			<form action="<?=$arResult["URL"]["SIMPLE_FORM_EDIT"]?>"
				  method="post"
				  <?if(empty($arResult["INFO_CONNECTION"])) { ?>class="imconnector-field-control-box-border" <? } ?>>
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
				<?=bitrix_sessid_post();?>
				<div class="imconnector-step-text">
					<label for="bot_handle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_BOT_HANDLE_NAME')?>
					</label>
				</div>
				<input type="text"
					   name="bot_handle"
					   id="bot_handle"
					   size="50"
					   value="<?=htmlspecialcharsbx($arResult["FORM"]["bot_handle"])?>"<?=$arResult["placeholder"]["bot_handle"]?$placeholder:'';?>
					   class="imconnector-field-control-input">

				<div class="imconnector-step-text">
					<label for="app_id">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_APP_ID_NAME')?>
					</label>
				</div>
				<input type="text"
					   name="app_id"
					   id="app_id"
					   size="50"
					   value="<?=htmlspecialcharsbx($arResult["FORM"]["app_id"])?>"<?=$arResult["placeholder"]["app_id"]?$placeholder:'';?>
					   class="imconnector-field-control-input">

				<div class="imconnector-step-text">
					<label for="app_secret">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_APP_SECRET_NAME')?>
					</label>
				</div>
				<input type="text"
					   name="app_secret"
					   id="app_secret"
					   size="50"
					   value="<?=htmlspecialcharsbx($arResult["FORM"]["app_secret"])?>"<?=$arResult["placeholder"]["app_secret"]?$placeholder:'';?>
					   class="imconnector-field-control-input">
				<a name="open_block"></a>
				<div class="imconnector-step-text">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_LINKS_CHANNELS_COMMUNICATION_DESCRIPTION', array(
						'#LINK_BEGIN#' => '<a href="' . (empty($arResult['INFO_CONNECTION']['URL']) ? 'https://dev.botframework.com/bots' : $arResult['INFO_CONNECTION']['URL']) . '" target="_blank">',
						'#LINK_END#' => '</a>',
					));?>
				</div>
				<div id="imconnector-botframework-public-link-settings-toggle" class="imconnector-botframework-public-link-settingss">
					<span class="imconnector-botframework-public-link-settings-item">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_LINKS_CHANNELS_COMMUNICATION_TITLE')?>
					</span>
					<span class="imconnector-botframework-public-link-settings-triangle-down"></span>
				</div>
				<input type="hidden"
					   name="open_block"
					   id="imconnector-botframework-open-block"
					   value="<?=$arResult['OPEN_BLOCK']?>">
				<div id="imconnector-botframework-open" class="imconnector-botframework-public-link-settings-inner<?=empty($arResult['OPEN_BLOCK'])?'':' imconnector-botframework-public-open';?>">
					<div class="imconnector-step-text">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_CHANNELS_DESCRIPTION', array(
							'#LINK_BEGIN#' => '<a href="' . (empty($arResult['INFO_CONNECTION']['URL']) ? 'https://dev.botframework.com/bots' : htmlspecialcharsbx($arResult['INFO_CONNECTION']['URL'])) . '" target="_blank">',
							'#LINK_END#' => '</a>',
						))?>
					</div>
					<div class="imconnector-field-section imconnector-field-section-control">
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-skype"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_SKYPE')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_skypebot"
									   id="url_skypebot"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_skypebot"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_SKYPEBOT_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_SKYPEBOT_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-slack"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_SLACK')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_slack"
									   id="url_slack"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_slack"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_SLACK_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_SLACK_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-kik"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_KIK')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_kik"
									   id="url_kik"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_kik"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_KIK_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_KIK_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-groupme"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_GROUPME')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_groupme"
									   id="url_groupme"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_groupme"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_GROUPME_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_GROUPME_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-twilio"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_TWILIO')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_twilio"
									   id="url_twilio"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_twilio"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_TWILIO_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_TWILIO_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-outlook"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_EMAILOFFICE365')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_email"
									   id="url_email"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_email"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_EMAILOFFICE365_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_EMAILOFFICE365_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-telegram"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_TELEGRAM')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_telegram"
									   id="url_telegram"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_telegram"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_TELEGRAM_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_TELEGRAM_EXAMPLE')?>
							</div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-control-box imconnector-field-control-box-small">
								<div class="imconnector-icon-position ui-icon ui-icon-service-fb-messenger"
									 title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_FACEBOOKMESSENGER')?>">
									<i></i>
								</div>
								<input class="imconnector-field-control-input"
									   type="text"
									   name="url_facebook"
									   id="url_facebook"
									   value="<?=htmlspecialcharsbx($arResult["FORM"]["url_facebook"])?>"
									   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_FACEBOOKMESSENGER_PLACEHOLDER')?>">
							</div>
							<div class="imconnector-settings-message imconnector-settings-message-align-left imconnector-settings-message-info">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_URL_FACEBOOKMESSENGER_EXAMPLE')?>
							</div>
						</div>
					</div>
				</div>

				<div class="imconnector-step-text">
					<input type="submit"
						   name="<?=$arResult["CONNECTOR"]?>_save"
						   class="webform-small-button webform-small-button-accept"
						   value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SAVE')?>">
				</div>

				<?
				/*if($arResult["SAVE_STATUS"])
				{
					?>
					<div class="imconnector-step-text">
						<div class="imconnector-intro">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_SIMPLE_FORM_DESCRIPTION_TESTED')?>
						</div>

						<input type="submit"
							   name="<?=$arResult["CONNECTOR"]?>_tested"
							   class="webform-small-button webform-small-button-accept"
							   value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_TESTED')?>">
					</div>
					<?
				}*/
				?>

				<?
					if(!empty($arResult["STATUS"]))
					{
						include 'final.php';
					}
				?>
			</form>
			<?
			if (empty($arResult['INFO_CONNECTION']))
			{
				include 'connection-help.php';
			}
			?>
		</div>
	</div>
	<?
}
?>