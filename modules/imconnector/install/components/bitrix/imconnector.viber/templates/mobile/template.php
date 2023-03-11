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

$placeholder = ' placeholder="' . Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER') . '"';
?>
	<div class="imconnector-item imconnector-item-show">

		<div class="imconnector-item-header">
			<span class="imconnector-viber-button"><?=$arResult["NAME"]?></span>
		</div>

		<div class="imconnector-wrapper">
			<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post">
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_active" value="Y">
				<?=bitrix_sessid_post();?>

				<div class="imconnector-intro">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_SIMPLE_FORM_DESCRIPTION_1')?>
				</div>
					<?
					if (!empty($arResult['messages']))
					{
						echo '<div class="imconnector-settings-message imconnector-settings-message-success">';
						foreach ($arResult['messages'] as $value)
						{
							echo '<div>' . $value . '</div>';
						}
						echo '</div>';
					}
					if (!empty($arResult['error']))
					{
						echo '<div class="imconnector-settings-message imconnector-settings-message-error">';
						foreach ($arResult['error'] as $value)
						{
							echo '<div>' . $value . '</div>';
						}
						echo '</div>';
					}
					?>
				<div class="imconnector-intro imconnector-bold"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_API_KEY')?></div>
				<input type="text"
					   class="imconnector-input"
					   id="imconnector-viber-have-bot"
					   name="api_token"
					   value="<?=$arResult["FORM"]["api_token"]?>"
						<?=$arResult["placeholder"]["api_token"]?$placeholder:'';?>>

				<input type="submit"
					   class="imconnector-button imconnector-button-accept"
					   id="webform-small-button-have-bot"
					   name="<?=$arResult["CONNECTOR"]?>_save"
					   value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SAVE')?>">
			</form>

			<?if(!empty($arResult["STATUS"])):?>
				<div class="imconnector-settings-message imconnector-settings-message-success imconnector-settings-message-align-left">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_FINAL_FORM_DESCRIPTION_1')?>
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_FINAL_FORM_DESCRIPTION_2', array("#URL_MOBILE#" => $arResult["URI_DOMAIN_MOBILE"], "#URL#" => $arResult["URI_DOMAIN"]))?>
				</div>
				<?if(!empty($arResult["INFO_CONNECTION"])):?>
					<div class="imconnector-intro">
						<span class="imconnector-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_NAME_BOT')?>:</span>
						<span class="imconnector-text imconnector-bold"><?=$arResult["INFO_CONNECTION"]['NAME']?></span><br>
						<?if(!empty($arResult["INFO_CONNECTION"]['URL_OTO'])):?>
							<span class="imconnector-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_LINK_CHAT_ONE_TO_ONE')?>:</span>
							<a class="imconnector-link imconnector-newwrap" href="<?=$arResult["INFO_CONNECTION"]['URL_OTO']?>" target="_blank"><?=$arResult["INFO_CONNECTION"]['URL_OTO']?></a></span>
						<?endif;?>
					</div>
				<?endif;?>
			<?endif;?>
		</div>
	</div>