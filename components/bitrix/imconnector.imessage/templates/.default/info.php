<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-container">
	<div class="imconnector-field-section">
		<div class="imconnector-field-main-title">
			<?if (!empty($arResult['PAGE']) && $arResult['PAGE'] == 'connection'):?>
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INFO_OLD_CONNECT')?>
			<?else:?>
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_INFO')?>
			<?endif;?>
		</div>
		<div class="imconnector-field-box">
			<?if(!empty($arResult['INFO_CONNECTION']['business_name'])):?>
				<div class="imconnector-field-box-entity-row">
					<div class="imconnector-field-box-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_NAME')?>
					</div>
					<span class="imconnector-field-box-entity-link">
					<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['business_name'])?>
					</span>
				</div>
			<?endif;?>
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_BUSINESS_ID')?>
				</div>
				<span class="imconnector-field-box-entity-link">
					<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['business_id'])?>
				</span>
			</div>
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NAME_CHAT_LINK')?>
				</div>
				<a href="<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['url'])?>"
				   class="imconnector-field-box-entity-link"
				   target="_blank">
					<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['url'])?>
				</a>
				<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
					  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['INFO_CONNECTION']['url']))?>"></span>
			</div>
		</div>
	</div>
</div>
