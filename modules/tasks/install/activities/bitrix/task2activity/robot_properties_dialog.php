<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Flow\Control\Task\Field\FlowFieldHandler;

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/task2activity/script.js'));

\Bitrix\Main\UI\Extension::load(['ui.entity-selector', 'tasks.entity-selector']);

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$data = $dialog->getRuntimeData();
$errors = [];

$documentValues = $dialog->getCurrentValue('Fields') ?? $dialog->getCurrentValues();

$renderCheckbox = function (array $field, $value = null) use ($dialog)
{
	$fieldValue = $value ?? $dialog->getCurrentValue($field);
	if (is_string($fieldValue))
	{
		$fieldValue = $fieldValue === 'Y';
	}

	echo sprintf(
		'<div class="bizproc-automation-popup-checkbox-item">
				<label class="bizproc-automation-popup-chk-label">
					<input type="hidden" name="%1$s" value="N">
					<input type="checkbox" name="%1$s" value="Y" class="bizproc-automation-popup-chk" %3$s>
					%2$s
				</label>
			</div>',
			htmlspecialcharsbx($field['FieldName']),
			htmlspecialcharsbx($field['Name']),
			htmlspecialcharsbx($fieldValue ? 'checked' : ''),
	);
};

$renderField = function (array $field, $value = null) use ($dialog, $renderCheckbox)
{
	if (
		$field['Type'] === \Bitrix\Bizproc\FieldType::BOOL
		&& isset($field['Settings'], $field['Settings']['display'])
		&& $field['Settings']['display'] === 'checkbox'
	)
	{
		$renderCheckbox($field, $value);
	}
	else
	{
		$customRenderedFieldIds = [
			'GROUP_ID' => 'group-id',
			'TAG_NAMES' => 'tags',
			'DEPENDS_ON' => 'depends-on',
			'FLOW_ID' => 'flow-id',
		];

		$controlHtml = null;
		if (isset($customRenderedFieldIds[$field['FieldName']]))
		{
			$elementIdSuffix = htmlspecialcharsbx($customRenderedFieldIds[$field['FieldName']]);
			$fieldType = $dialog->getFieldTypeObject($field);

			$selectorAttributes = sprintf(
				'data-role="inline-selector-target" data-property="%s"',
				htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($fieldType->getProperty())),
			);

			$controlHtml = "<div class=\"bizproc-type-control-string\" id=\"bizproc-task2activity-{$elementIdSuffix}\" {$selectorAttributes}></div>";
		}
		else
		{
			$controlHtml = $dialog->renderFieldControl($field, $value ?? $dialog->getCurrentValue($field));
		}
		echo sprintf(
			'<div class="bizproc-automation-popup-settings">
				<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">%s: </span>
				%s
			</div>',
			htmlspecialcharsbx($field['Name']),
			$controlHtml,
		);
	}
};

$renderDocumentField = function (array $field) use ($documentValues, $renderField)
{
	$renderField($field, $documentValues[$field['FieldName']] ?? null);
};

$taskFieldsMap = $map['Fields']['Map'];

//Visible fields
$visibleFields = array_merge(
	['TITLE', 'DESCRIPTION', 'RESPONSIBLE_ID', 'DEADLINE'],
	array_keys(\Bitrix\Tasks\Integration\Bizproc\Document\Task::getFieldsCreatedByUser())
);
foreach ($visibleFields as $fieldId)
{
	$renderDocumentField($taskFieldsMap[$fieldId]);
	unset($taskFieldsMap[$fieldId]);
}
?>
<style type="text/css">
	.tasks-task2activity-additional-up,
	.tasks-task2activity-additional {
		border-bottom: 1px dashed #8f949c;
		color: #80868e;
		font: bold 12px "HelveticaNeue", Arial, Helvetica, sans-serif;
		-webkit-transition: border-bottom .3s ease-in-out;
		-moz-transition: border-bottom .3s ease-in-out;
		-ms-transition: border-bottom .3s ease-in-out;
		-o-transition: border-bottom .3s ease-in-out;
		transition: border-bottom .3s ease-in-out;
		cursor: pointer;
		-webkit-font-smoothing: antialiased;
	}
	.tasks-task2activity-additional-up:hover,
	.tasks-task2activity-additional:hover {
		border-bottom: 1px dashed #eef2f4;
		color: #adafb1;
	}
	.tasks-task2activity-additional {
		position: relative;
	}

	.tasks-task2activity-additional:after {
		content: "";
		position: absolute;
		bottom: 4px;
		right: -10px;
		border-style: solid;
		border-width: 4px 3.5px 0 3.5px;
		border-color: #535c69 transparent transparent transparent;
	}

	.tasks-task2activity-additional-up {
		position: relative;
	}

	.tasks-task2activity-additional-up:after {
		content: "";
		position: absolute;
		bottom: 4px;
		right: -10px;
		border-style: solid;
		border-width: 0 3.5px 4px 3.5px;
		border-color: transparent transparent #535c69 transparent;
	}
	.tasks-task2activity-additional-content {
		height: 0;
		min-height: 0;
		padding-top: 17px;
		transition: all .3ms ease;
		overflow: hidden;
	}
	.tasks-task2activity-additional-content-up {
		height: auto;
		min-height: 200px;
	}
</style>

<span class="tasks-task2activity-additional"
	  onclick="BX.toggleClass(this.nextElementSibling, 'tasks-task2activity-additional-content-up'); BX.toggleClass(this, 'tasks-task2activity-additional-up'); return false;">
	<?= Bitrix\Main\Localization\Loc::getMessage('TASKS_BP_RPD_ADDITIONAL') ?>
</span>
<div class="tasks-task2activity-additional-content">
	<?php
		$renderField($map['HoldToClose']);

		$priorityField = $taskFieldsMap['PRIORITY'];
		unset($taskFieldsMap['PRIORITY']);

		$checkboxes = [];
		foreach ($taskFieldsMap as $fieldId => $field)
		{
			if (isset($field['UserField']))
			{
				continue;
			}
			elseif ($field['Type'] === \Bitrix\Bizproc\FieldType::BOOL)
			{
				$checkboxes[$fieldId] = $field;
			}
			else
			{
				$renderDocumentField($field);
			}
		}
		$renderField($map['CheckListItems']);

		$priorityFieldValue = $documentValues[$priorityField['FieldName']] ?? null;
	?>
	<div class="bizproc-automation-popup-checkbox">
		<div class="bizproc-automation-popup-checkbox-item">
			<label class="bizproc-automation-popup-chk-label">
				<input type="hidden" name="PRIORITY" value="1">
				<input
					type="checkbox"
					name="PRIORITY"
					value="2"
					class="bizproc-automation-popup-chk"
					<?= $priorityFieldValue == 2 ? 'checked' : ''?>
				>
				<?=GetMessage('TASKS_BP_RPD_PRIORITY')?>
			</label>
		</div>
		<?php
			foreach ($checkboxes as $fieldId => $field)
			{
				$renderDocumentField($field);
				if ($fieldId === 'ALLOW_TIME_TRACKING')
				{
					$renderField($map['TimeEstimateHour']);
					$renderField($map['TimeEstimateMin']);
				}
			}
		?>
		<?if ($dialog->getDocumentType()[0] === 'tasks'):?>
			<div class="bizproc-automation-popup-checkbox-item">
				<label class="bizproc-automation-popup-chk-label">
					<?php $renderField($map['AsChildTask']) ?>
				</label>
			</div>
		<?endif;?>
	</div>
</div>
<input type="hidden" name="AUTO_LINK_TO_CRM_ENTITY" value="Y">
<?php

$selectedGroupId = $documentValues['GROUP_ID'] ?? null;
if (isset($selectedGroupId) && (!is_string($selectedGroupId) || !CBPDocument::isExpression($selectedGroupId)))
{
	$selectedGroupId = $selectedGroupId ? (int)$selectedGroupId : null;
}
elseif (is_string($selectedGroupId))
{
	$selectedGroupId = '"' . CUtil::JSEscape($selectedGroupId) . '"';
}

$selectedFlowId = null;
if (isset($documentValues['FLOW_ID']))
{
	$flowId = $documentValues['FLOW_ID'];
	$selectedFlowId = is_string($flowId) ? '"' . CUtil::JSEscape($flowId) . '"' : null;
	if (!is_string($flowId) || !CBPDocument::isExpression($flowId))
	{
		$selectedFlowId = $flowId ? (int)$flowId : null;
	}
}

$runtimeData = $dialog->getRuntimeData();

?>
<script>
	BX.message({
		TASKS_BP_RPD_FLOW_CONTROLLED_SHORT_VALUE: '<?=GetMessageJS('TASKS_BP_RPD_FLOW_CONTROLLED_SHORT_VALUE')?>',
		TASKS_BP_RPD_FLOW_CONTROLLED_VALUE: '<?=GetMessageJS('TASKS_BP_RPD_FLOW_CONTROLLED_VALUE')?>',
	})
	BX.Event.ready(function()
	{
		const script = new BX.Tasks.Automation.Activity.Task2Activity({
			isRobot: true,
			controlledByFlowFields: <?=\Bitrix\Main\Web\Json::encode((new FlowFieldHandler(0))->getModifiedFields())?>,
			formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
			selectedGroupId: <?= $selectedGroupId ?? 'undefined' ?>,
			selectedFlowId: <?= $selectedFlowId ?? 'undefined' ?>,
			selectedTags: <?= \Bitrix\Main\Web\Json::encode($runtimeData['tags']) ?>,
			dependsOn: <?= \Bitrix\Main\Web\Json::encode($runtimeData['dependsOn']) ?>,
		});
		script.render();
	});
</script>
