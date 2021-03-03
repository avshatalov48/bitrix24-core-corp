<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
/** $arResult['CONNECTION_STATUS']; */
/** $arResult['REGISTER_STATUS']; */
/** $arResult['ERROR_STATUS']; */
/** $arResult['SAVE_STATUS']; */

$infoHelperConnectId = 'redirect=detail&code=11579286';

Loc::loadMessages(__FILE__);

if ($arParams['INDIVIDUAL_USE'] != 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	\Bitrix\Main\UI\Extension::load('ui.buttons');
	\Bitrix\Main\UI\Extension::load('ui.hint');
	\Bitrix\ImConnector\Connector::initIconCss();
}

$iconCode = \Bitrix\ImConnector\Connector::getIconByConnector($arResult['CONNECTOR']);
$placeholder = $arResult['placeholder']['api_key'] ? Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') : Loc::getMessage('IMCONNECTOR_COMPONENT_OK_API_KEY');
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
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
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_CHANGE_ANY_TIME')?>
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
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_CONNECT_STEP', ['#ID#' => $infoHelperConnectId])?>
						</div>
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
							<?=bitrix_sessid_post();?>
							<button class="ui-btn ui-btn-light-border"
									type="submit"
									name="<?=$arResult['CONNECTOR']?>_active"
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
				<?
				if (empty($arResult['INFO_CONNECTION']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_CONNECT_STEP', array('#ID#' => $infoHelperConnectId))?>
					</div>
					<?
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_FINAL_FORM_DESCRIPTION')?>
					</div>
					<?
				}
				?>
			</div>
		</div>
		<?include 'messages.php'?>
		<div class="imconnector-field-section imconnector-field-section-control">
			<form action="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
				  method="post">
			<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
			<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
			<?=bitrix_sessid_post();?>
			<div class="imconnector-field-box">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_API_KEY')?>:
				</div>
				<div class="imconnector-field-control-box imconnector-field-control-box-border">
					<input type="text"
						   class="imconnector-field-control-input"
						   id="imconnector-ok-have-bot"
						   name="api_key"
						   value="<?=htmlspecialcharsbx($arResult['FORM']['api_key'])?>"
						   placeholder="<?=$placeholder?>"
						   <?=$arResult['placeholder']['api_key'] ? 'data-value="true"':''?>
					>
					<button class="ui-btn ui-btn-success"
							id="webform-small-button-have-bot"
							name="<?=$arResult['CONNECTOR']?>_save"
							value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SAVE')?>"
							<?=!$arResult['placeholder']['api_key'] && !$arResult['FORM']['api_key'] ? 'disabled': ''?>
					>
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SAVE')?>
					</button>
				</div>

					<div class="imconnector-step-text">
						<label for="group_name">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_GROUP_NAME')?> *
						</label>
					</div>
					<input type="text"
						   name="group_name"
						   id="group_name"
						   size="50"
						   value="<?=htmlspecialcharsbx($arResult['FORM']['group_name'])?>"
						   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_GROUP_NAME')?>"
						   class="imconnector-field-control-input">

					<div class="imconnector-step-text">
						<label for="group_link">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_GROUP_LINK')?> *<a onclick="top.BX.Helper.show('<?=$infoHelperConnectId?>');" class="ui-hint"><span class="ui-hint-icon"></span></a>
						</label>
					</div>
					<input type="text"
						   name="group_link"
						   id="group_link"
						   size="50"
						   value="<?=htmlspecialcharsbx($arResult['FORM']['group_link'])?>"
						   placeholder="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OK_GROUP_LINK')?>"
						   class="imconnector-field-control-input">
				</div>
				<div class="imconnector-field-control-box imconnector-field-control-box-border">
				</div>
			</form>
			<?
			if (empty($arResult['INFO_CONNECTION'])) //not connected yet case
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