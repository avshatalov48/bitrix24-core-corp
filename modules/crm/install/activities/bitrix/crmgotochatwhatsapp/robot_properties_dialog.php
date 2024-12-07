<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

?>
<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
	<?= \Bitrix\Main\Localization\Loc::getMessage('BP_CRM_GO_TO_CHAT_WHATSAPP_HELP') ?>
	<br><br>
	<a href="https://helpdesk.bitrix24.ru/open/14748976" onclick="top.BX.Helper.show('redirect=detail&code=18114500');" class="crm-robot-gotochat-help"><?= \Bitrix\Main\Localization\Loc::getMessage('BP_CRM_GO_TO_CHAT_WHATSAPP_HELP_LINK') ?></a>
</div>
<style>
	.crm-robot-gotochat-help {
		border-bottom: 1px dotted;
		color: #80868e;
		font-size: 13px;
		line-height: 19px;
		text-decoration: none;
		transition: border .2s linear;
		display: inline-block;
	}

	.crm-robot-gotochat-help:hover {
		color: #80868e;
		border-color: rgba(130, 139, 149, .8);
	}
</style>