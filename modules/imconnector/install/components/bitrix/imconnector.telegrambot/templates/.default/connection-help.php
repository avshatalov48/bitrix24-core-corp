<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-box">
	<div class="imconnector-field-box-subtitle-darken">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INSTRUCTION_TITLE')?>
	</div>
	<div class="imconnector-field-button-box">
		<?/*<div onclick="top.BX.Helper.show('<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INFO_CREATE_CONNECT_ID')?>');"
		   class="imconnector-field-button imconnector-field-button-create">
			<div class="imconnector-field-button-icon"></div>
			<div class="imconnector-field-button-text">
				<div class="imconnector-field-button-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_USING_TITLE')?>
				</div>
				<div class="imconnector-field-button-name">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CREATE_BOT')?>
				</div>
			</div>
		</div>*/?>
		<div onclick="top.BX.Helper.show('<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INFO_CONNECT_ID')?>');"
		   class="imconnector-field-button imconnector-field-button-connect">
			<div class="imconnector-field-button-icon"></div>
			<div class="imconnector-field-button-text">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_CONNECT_HELP')?>
			</div>
		</div>
	</div>
</div>