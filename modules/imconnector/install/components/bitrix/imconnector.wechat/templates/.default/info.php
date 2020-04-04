<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-container">
	<div class="imconnector-field-section">
		<div class="imconnector-field-main-title">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_INFO')?>
		</div>
		<div class="imconnector-field-box">
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_NAME_CHAT_LINK')?>
				</div>
				<a href="<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_IM"])?>"
				   class="imconnector-field-box-entity-link"
				   target="_blank">
					<?=htmlspecialcharsbx($arResult["INFO_CONNECTION"]["URL_IM"])?>
				</a>
				<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
					  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["INFO_CONNECTION"]["URL_IM"]))?>"></span>
			</div>
		</div>
	</div>
</div>
