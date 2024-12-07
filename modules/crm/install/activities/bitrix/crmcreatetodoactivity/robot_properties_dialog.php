<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/** @var array $arResult */
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\UI\Extension::load([
	'crm.field.color-selector',
	'crm_common',
	'bizproc.automation',
	'ui.design-tokens',
	'ui.icon-set.api.core',
	'ui.icon-set.main',
]);
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcreatetodoactivity/script.js'));


$map = $dialog->getMap();
$colorIdField = $map['ColorId'];
$colorId = $dialog->getCurrentValue($colorIdField);
$colorSettingsProvider = (new ColorSettingsProvider($colorId));

$attachmentType = isset($map['AttachmentType']) ? $map['AttachmentType'] : null;
$attachment = isset($map['Attachment']) ? $map['Attachment'] : null;

$config = array(
	'type' => $dialog->getCurrentValue($attachmentType['FieldName']),
	'typeInputName' => $attachmentType['FieldName'],
	'valueInputName' => $attachment['FieldName'],
	'multiple' => $attachment['Multiple'],
	'required' => !empty($attachment['Required']),
	'useDisk' => CModule::IncludeModule('disk'),
	'label' => $attachmentType['Name'],
	'labelFile' => $attachmentType['Options']['file'],
	'labelDisk' => $attachmentType['Options']['disk']
);

if ($dialog->getCurrentValue($attachmentType['FieldName']) === 'disk')
{
	$config['selected'] = \Bitrix\Crm\Automation\Helper::prepareDiskAttachments(
		$dialog->getCurrentValue($attachment['FieldName'])
	);
}
else
{
	$config['selected'] = \Bitrix\Crm\Automation\Helper::prepareFileAttachments(
		$dialog->getDocumentType(),
		$dialog->getCurrentValue($attachment['FieldName'])
	);
}
$configAttributeValue = Json::encode($config);
\Bitrix\Main\Page\Asset::getInstance()->addCss(getLocalPath('activities/bitrix/crmcreatetodoactivity/style.css'));
?>
<div class="bizproc-automation-popup-todo-activity-settings">
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title">
			<?= htmlspecialcharsbx($map['Subject']['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($map['Subject']) ?>
	</div>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title">
			<?= htmlspecialcharsbx($map['Description']['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($map['Description']) ?>
	</div>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title">
			<?= htmlspecialcharsbx($map['Deadline']['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($map['Deadline']) ?>
	</div>
	<?php if($map['Notification']):?>
	<div class="bizproc-automation-popup-settings">
		<?= $dialog->renderFieldControl($map['Notification']) ?>
	</div>
	<?php endif;?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($map['Responsible']['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($map['Responsible']) ?>
	</div>
	<div class="bizproc-automation-popup-settings">
		<div class="bizproc-automation-create-todo-activity-color-selector-wrapper">
			<span class="bizproc-automation-popup-settings-title"><?= htmlspecialcharsbx($map['ColorId']['Name']) ?>: </span>
			<div id="bizproc-automation-create-todo-activity-color-selector"></div>
		</div>
	</div>

	<div class="bizproc-automation-popup-todo-activity-settings-separator"></div>

	<div class="bizproc-automation-popup-settings bizproc-automation-popup-todo-activity-settings bizproc-automation-popup-settings-text">
		<a class="bizproc-automation-popup-settings-link" id="bp-create-todo-additional-settings-list">
			<?=Loc::getMessage('CRM_BP_CREATE_TODO_ADDITIONAL_SETTINGS_BUTTON')?>
		</a>
	</div>

	<div id="bizproc-automation-create-todo-activity-additional-settings"></div>
	<?php if (isset($map['AutoComplete'])):?>
		<div class="bizproc-automation-popup-checkbox bizproc-automation-popup-settings">
			<div class="bizproc-automation-popup-checkbox-item">
				<label class="bizproc-automation-popup-chk-label">
					<input type="checkbox"
						   name="<?= htmlspecialcharsbx($map['AutoComplete']['FieldName']) ?>"
						   value="Y"
						   class="bizproc-automation-popup-chk"
						<?= $dialog->getCurrentValue($map['AutoComplete']) === 'Y' ? 'checked' : '' ?>
						   data-role="save-state-checkbox"
						   data-save-state-key="activity_auto_complete"
					>
					<?= htmlspecialcharsbx($map['AutoComplete']['Name']) ?>
				</label>
			</div>
		</div>
	<?php endif;?>
</div>

<script>
	BX.message({
		CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_ENTITY_TITLE: '<?=GetMessageJS('CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_ENTITY_TITLE')?>',
		CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_CAPACITY: '<?=GetMessageJS('CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_CAPACITY')?>',
		CRM_BP_CREATE_TODO_ADDITIONAL_FIELD_DELETE: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ADDITIONAL_FIELD_DELETE')?>',
		CRM_BP_CREATE_TODO_ACTIONS_CALENDAR: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_CALENDAR')?>',
		CRM_BP_CREATE_TODO_ACTIONS_CLIENT: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_CLIENT')?>',
		CRM_BP_CREATE_TODO_ACTIONS_COLLEAGUE: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_COLLEAGUE')?>',
		CRM_BP_CREATE_TODO_ACTIONS_ADDRESS: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_ADDRESS')?>',
		CRM_BP_CREATE_TODO_ACTIONS_ROOM: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_ROOM')?>',
		CRM_BP_CREATE_TODO_ACTIONS_LINK: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_LINK')?>',
		CRM_BP_CREATE_TODO_ACTIONS_FILE: '<?=GetMessageJS('CRM_BP_CREATE_TODO_ACTIONS_FILE')?>',
	})
	BX.Event.ready(() => {
		if (BX.Crm.Activity.CrmCreateTodoActivity)
		{
			const createTodoActivity = new BX.Crm.Activity.CrmCreateTodoActivity({
				isRobot: true,
				documentType: <?= Json::encode($dialog->getDocumentType()) ?>,
				formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
				colorSelectorWrapper: document.getElementById('bizproc-automation-create-todo-activity-color-selector'),
				colorSettings: <?= Json::encode(($colorSettingsProvider->fetchForJsComponent()))?>,
				isAvailableColor: <?= Json::encode($colorSettingsProvider->isAvailableColorId($colorId))?>,
				additionalSettingsButton: document.getElementById('bp-create-todo-additional-settings-list'),
				documentFields: <?=Json::encode($map)?>,
				additionalSettingsWrapper: document.getElementById('bizproc-automation-create-todo-activity-additional-settings'),
				dataConfig: <?=Json::encode($configAttributeValue)?>,
			});
			<?php foreach ($map as $key => $field):?>
				<?php if ($field['Additional'] && $dialog->getCurrentValue($field)):?>
					createTodoActivity.renderControl(<?=Json::encode($key)?>, <?=Json::encode($dialog->getCurrentValue($field))?>);
				<?php endif;?>
			<?php endforeach;?>
		}
	});
</script>

