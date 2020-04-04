<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>
	<div class="imconnector-settings-message imconnector-settings-message-success imconnector-settings-message-align-left">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_FINAL_FORM_DESCRIPTION_1')?>
	</div>
<?if(!empty($arResult["INFO_CONNECTION"])):?>
	<div class="imconnector-step-text">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_NAME_BOT')?>:
		<?=$arResult["INFO_CONNECTION"]['NAME']?><br>
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_LINK_PUBLIC_ACCOUNT')?>:
		<span class="imconnector-link"><a href="<?=$arResult["INFO_CONNECTION"]['URL']?>" target="_blank"><?=$arResult["INFO_CONNECTION"]['URL']?></a></span><br>
		<?if(!empty($arResult["INFO_CONNECTION"]['URL_OTO'])):?>
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_LINK_CHAT_ONE_TO_ONE')?>:
		<span class="imconnector-link"><a href="<?=$arResult["INFO_CONNECTION"]['URL_OTO']?>" target="_blank"><?=$arResult["INFO_CONNECTION"]['URL_OTO']?></a></span>
		<?endif;?>
	</div>
<?endif;?>