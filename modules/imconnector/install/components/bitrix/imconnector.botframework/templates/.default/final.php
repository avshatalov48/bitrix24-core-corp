<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>

<div class="imconnector-step-text">
	<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FINAL_FORM_DESCRIPTION_OK_2')?>
</div>

<?
if(!empty($arResult["INFO_CONNECTION"]))
	{
	?>
	<div class="imconnector-social-connected">
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_SKYPE"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_SKYPE"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_SKYPE')?>">
				<div class="ui-icon ui-icon-service-skype"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_SLACK"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_SLACK"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_SLACK')?>">
				<div class="ui-icon ui-icon-service-slack"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_KIK"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_KIK"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_KIK')?>">
				<div class="ui-icon ui-icon-service-kik"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_GROUPME"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_GROUPME"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_GROUPME')?>">
				<div class="ui-icon ui-icon-service-groupme"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_SMS"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_SMS"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_TWILIO')?>">
				<div class="ui-icon ui-icon-service-twilio"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_MSTEAMS"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_MSTEAMS"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_MSTEAMS')?>">
				<div class="ui-icon ui-icon-service-msteams"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_WEBCHAT"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_WEBCHAT"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_WEBCHAT')?>">
				<div class="ui-icon ui-icon-service-webchat"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_EMAIL"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_EMAIL"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_EMAILOFFICE365')?>">
				<div class="ui-icon ui-icon-service-outlook"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_TELEGRAM"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_TELEGRAM"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_TELEGRAM')?>">
				<div class="ui-icon ui-icon-service-telegram"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_FACEBOOK"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_FACEBOOK"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_FACEBOOKMESSENGER')?>">
				<div class="ui-icon ui-icon-service-fb-messenger"><i></i></div>
			</a>
		<?endif;?>
		<?if(!empty($arResult["INFO_CONNECTION"]["URL_DIRECTLINE"])):?>
			<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_DIRECTLINE"])?>" target="_blank"
			   title="<?=Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_DIRECTLINE')?>">
				<div class="ui-icon ui-icon-service-directline"><i></i></div>
			</a>
		<?endif;?>
	</div>
	<?
	}
?>
