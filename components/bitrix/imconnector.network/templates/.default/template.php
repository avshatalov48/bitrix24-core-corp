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

CJSCore::Init(array('clipboard'));

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
if (empty($arResult['PAGE']) && $arResult['ACTIVE_STATUS']) //case when first time open active connector
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_SIMPLE_FORM_DESCRIPTION_1')?>
				</div>
				<div class="ui-btn-container">
					<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
					   class="ui-btn ui-btn-primary show-preloader-button">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
					</a>
					<button class="ui-btn ui-btn-light-border"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section">
			<div class="imconnector-field-main-title">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_INFO')?>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_NAME')?>
					</div>
					<span class="imconnector-field-box-text-bold">
						<?=htmlspecialcharsbx($arResult["FORM"]["NAME"])?>
					</span>
				</div>
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_CODE')?>
					</div>
					<span class="imconnector-field-box-text-bold">
						<?=htmlspecialcharsbx($arResult["FORM"]["CODE"])?>
					</span>
					<span class="imconnector-field-box-entity-icon-copy-to-clipboard copy-to-clipboard"
						  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["FORM"]["CODE"]))?>"
						  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_COPY')?>"></span>
				</div>
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_URL')?>
					</div>
					<span class="imconnector-field-box-text-bold imconnector-network-whitespace-text">
						<?=htmlspecialcharsbx($arResult["FORM"]["URL"])?>
					</span>
					<span class="imconnector-field-box-entity-icon-copy-to-clipboard copy-to-clipboard"
						  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["FORM"]["URL"]))?>"
						  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_COPY')?>"></span>
				</div>
			</div>
		</div>
	</div>
	<?
}
elseif ($arResult['ACTIVE_STATUS'])
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_CONNECTED')?>
				</div>
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_NAME')?>
					</div>
					<span class="imconnector-field-box-text-bold">
							<?=htmlspecialcharsbx($arResult["FORM"]["NAME"])?>
						</span>
				</div>
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_CODE')?>
					</div>
					<span class="imconnector-field-box-text-bold">
						<?=htmlspecialcharsbx($arResult["FORM"]["CODE"])?>
					</span>
					<span class="imconnector-field-box-entity-icon-copy-to-clipboard copy-to-clipboard"
						  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["FORM"]["CODE"]))?>"
						  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_COPY')?>"></span>
				</div>
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_URL')?>
					</div>
					<span class="imconnector-field-box-text-bold imconnector-network-whitespace-text">
						<?=htmlspecialcharsbx($arResult["FORM"]["URL"])?>
					</span>
					<span class="imconnector-field-box-entity-icon-copy-to-clipboard copy-to-clipboard"
						  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["FORM"]["URL"]))?>"
						  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_COPY')?>"></span>
				</div>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section">
			<form action="<?=$arResult["URL"]["SIMPLE_FORM_EDIT"]?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
				<?=bitrix_sessid_post();?>
				<div class="imconnector-field-section imconnector-field-section-control">
					<div class="imconnector-field-box-content">
						<span class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_SIMPLE_FORM_DESCRIPTION_1')?>
						</span>
					</div>
					<div class="imconnector-field-box">
						<span class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_1')?>
						</span>
						<div class="imconnector-field-control-box">
							<input class="imconnector-field-control-input"
								   type="text"
								   name="name"
								   value="<?=htmlspecialcharsbx($arResult["FORM"]["NAME"])?>">
						</div>
					</div>
					<div class="imconnector-field-box">
						<span class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_2')?>
						</span>
						<div class="imconnector-field-control-box">
							<input class="imconnector-field-control-input"
								   type="text"
								   name="description"
								   value="<?=htmlspecialcharsbx($arResult["FORM"]["DESCRIPTION"])?>">
						</div>
					</div>
					<div class="imconnector-field-box">
						<span class="imconnector-field-box-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_3')?>
						</span>
						<div class="imconnector-field-control-box">
							<textarea class="imconnector-field-control-input imconnector-field-control-textbox"
									  name="welcome_message"><?=htmlspecialcharsbx($arResult["FORM"]["WELCOME_MESSAGE"])?></textarea>
						</div>
					</div>

					<div class="imconnector-field-box imconnector-public-link-settings-inner-container">
						<span class="imconnector-public-link-settings-inner-param"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_4')?></span>
						<div class="imconnector-lpublic-link-settings-inner-content">
							<div class="imconnector-public-link-settings-inner-type">
								<div class="imconnector-public-link-settings-inner-upload">
									<div class="imconnector-public-public-link-settings-inner-upload-description">
											<span class="imconnector-public-link-settings-inner-upload-description-item" style="font-weight: normal">
												<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_4_DESCRIPTION_1')?>
											</span>
									</div>
									<div class="imconnector-public-link-settings-inner-upload-field imconnector-public-link-settings-inner-upload-description">
										<button class="imconnector-public-link-settings-inner-upload-button"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_4_DESCRIPTION_2')?></button>
										<input type="file" name="avatar" class="imconnector-public-link-settings-inner-upload-item">
									</div>
									<span id="avatar_text" class="imconnector-public-link-settings-inner-upload-info"></span>
									<?
									if(!empty($arResult["FORM"]["AVATAR"]))
									{
										?>
										<div class="imconnector-img-del">
											<label class="imconnector-public-link-upload-checkbox-container" for="id-2">
												<input clas="imconnector-public-link-settings-inner-upload-description-item" value="Y" name="avatar_del" type="checkbox" id="id-2">
												<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_4_DESCRIPTION_3')?></span>
											</label>
											<div class="imconnector-public-link-upload-image-container">
												<img class="imconnector-public-link-upload-image" alt="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_4')?>" src="<?=htmlspecialcharsbx($arResult['FORM']['AVATAR_LINK'])?>">
											</div>
										</div>
										<?
									}
									?>
								</div>
							</div>
						</div>
					</div>

					<div class="imconnector-field-box imconnector-public-link-inner-copy-inner">
						<div class="imconnector-public-link-inner-copy-field">
							<span class="imconnector-public-link-title imconnector-field-box-subtitle"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_5')?>:</span>
							<input class="imconnector-public-link-settings-inner-option-field" style="margin-top: 16px;" type="checkbox" name="searchable" value="Y"<?=($arResult["FORM"]["SEARCHABLE"]? 'checked': '')?>>
						</div>
					</div>

					<?
					if ($arResult["FORM"]["CODE"] != '')
					{
						?>
						<div class="imconnector-field-box">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_FIELD_6')?>
							</div>
							<div class="imconnector-field-control-box">
								<input type="text" id="network-link" class="imconnector-field-control-input" value="<?=htmlspecialcharsbx($arResult["FORM"]["CODE"])?>" readonly>
								<div class="ui-btn ui-btn-success copy-to-clipboard"
									 id="imconnector-network-link"
									 data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["FORM"]["CODE"]))?>">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_COPY')?>
								</div>
							</div>
						</div>
						<?
					}
					?>
					<div class="imconnector-step-text">
						<div class="imconnector-step-text imconnector-step-text-14">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_REST_HELP', Array(
								'#LINK_START#' => '<a href="'.Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_REST_LINK').'" target="_blank">',
								'#LINK_END#' => '</a>'
							))?>
						</div>
					</div>
					<input type="submit"
						   class="webform-small-button webform-small-button-accept"
						   name="<?=$arResult["CONNECTOR"]?>_save"
						   value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SAVE')?>">
				</div>
			</form>
		</div>
	</div>
	<?
}
else //case when open not active connector
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?= $arResult['NAME'] ?>
				</div>
				<div class="imconnector-field-box-content">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_INDEX_DESCRIPTION') ?>
				</div>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section">
			<div class="imconnector-field-main-title">
				<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_CONNECTION') ?>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-box-content">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_INDEX_DESCRIPTION') ?>
				</div>
			</div>
			<div class="imconnector-field-social-connector">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-connector-icon"><i></i></div>
				<form action="<?= $arResult["URL"]["SIMPLE_FORM"] ?>" method="post">
					<input type="hidden" name="<?= $arResult["CONNECTOR"] ?>_form" value="true">
					<?= bitrix_sessid_post(); ?>
					<button class="ui-btn ui-btn-light-border"
							name="<?= $arResult["CONNECTOR"] ?>_active"
							type="submit"
							value="<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>
					</button>
				</form>
			</div>
		</div>
	</div>
	<?
}
?>