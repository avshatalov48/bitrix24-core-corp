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
?>

<form action="<?=$arResult["URL"]["DELETE"]?>" method="post" id="form_delete_<?=$arResult["CONNECTOR"]?>">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<?
if (empty($arResult['PAGE']))
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
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_CHANGE_ANY_TIME')?>
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
					if ($arResult['ACTIVE_STATUS'])
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
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_CONNECT_STEP', array('#ID#' => Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_INFO_CONNECT_ID'), '#URL#' => Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_INFO_CONNECT_URL')))?>
						</div>
						<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
							<?=bitrix_sessid_post();?>
							<button class="ui-btn ui-btn-light-border"
									type="submit"
									name="<?=$arResult["CONNECTOR"]?>_active"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_ACTIVATE')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_ACTIVATE')?>
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

				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_FINAL_FORM_DESCRIPTION', array('#ID#' => Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_INFO_CONNECT_ID'), '#URL#' => Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_INFO_CONNECT_URL')))?>
				</div>

			</div>
		</div>
		<? include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<div class="imconnector-field-box">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_ID_CHAT')?>
				</div>
				<div class="imconnector-field-control-box imconnector-field-control-box-border">
				<input type="text"
					   class="imconnector-field-control-input"
					   id="imconnector-yandex-chat-id"
					   value="<?=htmlspecialcharsbx($arResult["CHAT_ID"])?>"
					   readonly>
				<button class="ui-btn ui-btn-success copy-to-clipboard"
						data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["CHAT_ID"]))?>">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_COPY')?>
				</button>
				</div>
			</div>
		</div>
	</div>
	<?
}
?>