<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-container">
	<div class="imconnector-field-section">
		<div class="imconnector-field-main-title">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INFO')?>
		</div>
		<div class="imconnector-field-box">
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_NAME_BOT')?>
				</div>
				<div class="imconnector-field-box-entity-text-bold">
					<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["NAME"])?>
				</div>
			</div>

			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_LINK_CHAT_ONE_TO_ONE')?>
				</div>
				<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_OTO"])?>"
				   class="imconnector-field-box-entity-link"
				   target="_blank">
					<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_OTO"])?>
				</a>
				<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
					  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["INFO_CONNECTION"]["URL_OTO"]))?>"></span>
			</div>
		</div>
	</div>
</div>
