<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['crm.template.editor', 'crm_common', 'bizproc.automation', 'ui.design-tokens']);
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmsendwhatsappmessageactivity/script.js'));
\Bitrix\Main\Page\Asset::getInstance()->addCss(getLocalPath('activities/bitrix/crmsendwhatsappmessageactivity/style.css'));

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

if (!$dialog->getRuntimeData()['isWhatsAppTuned']): ?>
	<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
		<?= \Bitrix\Main\Localization\Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_RPD_NOT_TUNED') ?>
		<?php
			if (!empty($dialog->getRuntimeData()['manageUrl'])): ?>
				<br><br>
				<a href="#" onclick="top.BX.Helper.show('redirect=detail&code=14214014');" class="bizproc-automation-whats-app-message-activity-help">
					<?= \Bitrix\Main\Localization\Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_RPD_NOT_TUNED_HELP_LINK') ?>
				</a>
			<?php endif; ?>
	</div>
<?php
	return;
endif;

$map = $dialog->getMap();
$templateIdField = $map['TemplateId'];
$message = $map['Message'];
?>

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?= htmlspecialcharsbx($templateIdField['Name']) ?>: </span>
	<?= $dialog->renderFieldControl($templateIdField, null, false) ?>
</div>
<div class="bizproc-automation-popup-settings bizproc-automation-whats-app-message-activity-editor-wrapper --hidden">
	<span class="bizproc-automation-popup-settings-title"><?= htmlspecialcharsbx($message['Name']) ?>: </span>
	<div id="bizproc-automation-whats-app-message-activity-editor"></div>
</div>

<script>
	BX.Event.ready(() => {
		if (BX.Crm.Activity.CrmSendWhatsAllMessageActivity)
		{
			new BX.Crm.Activity.CrmSendWhatsAllMessageActivity({
				isRobot: true,
				documentType: <?= CUtil::PhpToJSObject($dialog->getDocumentType()) ?>,
				formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
				editorWrapper: document.getElementById('bizproc-automation-whats-app-message-activity-editor'),
				currentTemplateId: '<?= CUtil::JSEscape($dialog->getCurrentValue($templateIdField, $templateIdField['Default'] ?? null)) ?>',
				currentTemplate: <?= CUtil::PhpToJSObject($dialog->getRuntimeData()['currentTemplate']) ?>,
				currentPlaceholders: <?= CUtil::PhpToJSObject($dialog->getRuntimeData()['currentPlaceholders']) ?>,
			});
		}
	});
</script>
