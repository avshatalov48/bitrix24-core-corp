<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Connector;

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

if ($arParams['INDIVIDUAL_USE'] != 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Extension::load('ui.hint');
	Connector::initIconCss();
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);
$placeholder = $arResult['placeholder']['api_token'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_API_KEY');
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<?if(empty($arResult['PAGE'])): //case when not first open?>
	<div class="imconnector-field-container">
	<?if($arResult['STATUS'] === true): //case when connection competed?>
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_CHANGE_ANY_TIME')?>
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
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_SUBTITLE') ?>
					</div>

					<ul class="imconnector-field-box-content-text-items">
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_LIST_ITEM_1') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_LIST_ITEM_2') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_LIST_ITEM_3') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_LIST_ITEM_4') ?></li>
					</ul>

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INDEX_ADDITIONAL_DESCRIPTION_NEW', [
							'#LINK_START#' => '<a href="#" id="imconnector-viber-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>

					<div class="imconnector-field-box-content-btn">
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
							<?if($arResult['CAN_USE_CONNECTION'] === true):?>
								<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
								<?=bitrix_sessid_post();?>
								<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
										type="submit"
										name="<?=$arResult['CONNECTOR']?>_active"
										value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
								</button>
							<?else:?>
								<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
										onclick="BX.UI.InfoHelper.show('<?=$arResult['INFO_HELPER_LIMIT']?>'); return false;">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
								</button>
							<?endif;?>
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
				<?if(empty($arResult['INFO_CONNECTION'])):?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_CONNECT_STEP_NEW', [
							'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-viber-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>
				<?else:?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_FINAL_FORM_DESCRIPTION')?>
					</div>
				<?endif;?>
			</div>
		</div>
		<?include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<div class="imconnector-field-box">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_API_KEY')?>
				</div>
				<form action="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
					  method="post"
					  class="imconnector-field-control-box imconnector-field-control-box-border">
					<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
					<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
					<?=bitrix_sessid_post();?>
					<input type="text"
						   class="imconnector-field-control-input"
						   id="imconnector-viber-have-bot"
						   name="api_token"
						   value="<?=htmlspecialcharsbx($arResult['FORM']['api_token'])?>"
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
	<?endif;
