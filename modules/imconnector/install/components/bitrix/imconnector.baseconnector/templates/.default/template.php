<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

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
	\Bitrix\ImConnector\Connector::initIconCss(); //init default and custom connector icon styles
}

$iconCode = \Bitrix\ImConnector\Connector::getIconByConnector($arResult["CONNECTOR"]);
?>

<form action="<?= $arResult["URL"]["DELETE"] ?>" method="post" id="form_delete_<?= $arResult["CONNECTOR"] ?>">
	<input type="hidden" name="<?= $arResult["CONNECTOR"] ?>_form" value="true">
	<input type="hidden" name="<?= $arResult["CONNECTOR"] ?>_del" value="Y">
	<?= bitrix_sessid_post(); ?>
</form>

<?
if ($arResult['STATUS'])
{
	if (empty($arResult['PAGE']))
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?= $iconCode ?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?= $arResult["NAME"] ?>
					</div>
					<div class="ui-btn-container">
						<a href="<?= $arResult["URL"]["SIMPLE_FORM"] ?>"
						   class="ui-btn ui-btn-primary show-preloader-button">
							<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING') ?>
						</a>
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?= CUtil::PhpToJSObject($arResult["CONNECTOR"]) ?>)">
							<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE') ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<? include 'messages.php' ?>
		<?
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?= $iconCode ?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?= $arResult["NAME"] ?>
					</div>
					<div class="imconnector-field-box-content">
						Header connection information
					</div>
					<div class="ui-btn-container">
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?= CUtil::PhpToJSObject($arResult["CONNECTOR"]) ?>)">
							<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE') ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<? include 'messages.php' ?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					Connection information title
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-content">
						Connection information
					</div>
				</div>
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
				<div class="connector-icon ui-icon ui-icon-service-<?= $iconCode ?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?= $arResult['NAME'] ?>
				</div>
				<div class="imconnector-field-box-content">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_BASECONNECTOR_INDEX_DESCRIPTION_NEW') ?>
				</div>
				<form action="<?= $arResult["URL"]["SIMPLE_FORM"] ?>" method="post" class="ui-btn-container">
					<input type="hidden" name="<?= $arResult["CONNECTOR"] ?>_form" value="true">
					<?= bitrix_sessid_post(); ?>
					<button class="ui-btn ui-btn-light-border"
							type="submit"
							name="<?= $arResult["CONNECTOR"] ?>_active"
							value="<?= Loc::getMessage('IMCONNECTOR_COMPONENT_BASECONNECTOR_CONNECT') ?>">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_BASECONNECTOR_CONNECT') ?>
					</button>
				</form>
			</div>
		</div>
	</div>
	<?
}
?>