<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Connector;

use Bitrix\ImConnector\Rest\Helper;

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

Loc::loadMessages(__FILE__);

if($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Connector::initIconCss(); //init default and custom connector icon styles
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>

<?
if(empty($arResult['PAGE']))
{
	?>
	<div class="imconnector-field-container">
	<?if($arResult['STATUS'] === true): //case when connection completed?>
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=$arResult['NAME']?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_REST_INFO_TITLE')?>
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
	<?elseif($arResult['ACTIVE_STATUS'] === true):?>
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
					<?=$arResult['NAME']?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_REST_INDEX_SUBTITLE') ?>
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
				if (!empty($arResult['STATUS']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_REST_INFO_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_REST_INFO_TITLE')?>
					</div>
					<div class="ui-btn-container">
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</button>
					</div>
					<?
				}
				elseif (!empty($arResult['ERROR_STATUS']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_REST_CONNECTOR_ERROR_STATUS')?>
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
					<?
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_REST_INDEX_DESCRIPTION_NEW')?>
					</div>
					<div class="ui-btn-container">
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</button>
					</div>
					<?
				}
				?>
			</div>
		</div>
		<? include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<?
				$placementSid = $APPLICATION->includeComponent(
					'bitrix:app.layout',
					'',
					[
						'ID' => $arResult['INFO_CONNECTOR']['REST_APP_ID'],
						'PLACEMENT' => Helper::PLACEMENT_SETTING_CONNECTOR,
						'PLACEMENT_ID' => $arResult['INFO_CONNECTOR']['REST_PLACEMENT_ID'],
						'PLACEMENT_OPTIONS' => $arResult['PLACEMENT_OPTIONS'],
					],
					null,
					['HIDE_ICONS' => 'Y']
				);
			?>
		</div>
	</div>
	<?
}
