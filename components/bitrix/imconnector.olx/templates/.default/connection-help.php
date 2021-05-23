<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
use \Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-box">
	<div class="imconnector-field-box-subtitle-darken">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_INSTRUCTION_TITLE')?>
	</div>
	<div class="imconnector-field-button-box">
		<a onclick="top.BX.Helper.show('redirect=detail&code=11405312');"
		   class="imconnector-field-button imconnector-field-button-connect">
			<div class="imconnector-field-button-icon"></div>
			<div class="imconnector-field-button-text">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CONNECT_HELP')?>
			</div>
		</a>
	</div>
</div>
