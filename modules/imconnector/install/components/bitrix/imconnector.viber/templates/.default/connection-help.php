<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-box">
	<div class="imconnector-field-box-subtitle-darken">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INSTRUCTION_TITLE')?>
	</div>
	<div class="imconnector-field-button-box">
		<div onclick="top.BX.Helper.show('<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INFO_CONNECT_ID')?>');"
		   class="imconnector-field-button imconnector-field-button-connect">
			<div class="imconnector-field-button-icon"></div>
			<div class="imconnector-field-button-text">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_CONNECT_HELP')?>
			</div>
		</div>
		<?/*<div onclick="top.BX.Helper.show('<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_INFO_IOS_CONNECT_ID')?>');"
		   class="imconnector-field-button imconnector-field-button-ios">
			<div class="imconnector-field-button-icon"></div>
			<div class="imconnector-field-button-text">
				<div class="imconnector-field-button-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_USING_TITLE')?>
				</div>
				<div class="imconnector-field-button-name">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_USING_IOS')?>
				</div>
			</div>
		</div>*/?>
	</div>
</div>
