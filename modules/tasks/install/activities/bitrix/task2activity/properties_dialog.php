<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Flow\Control\Task\Field\FlowFieldHandler;

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/task2activity/script.js'));

\Bitrix\Main\UI\Extension::load(['ui.entity-selector', 'tasks.entity-selector']);

$currentValues = $dialog->getCurrentValues();
$map = $dialog->getMap();
$taskFieldsMap = $map['Fields']['Map'];

$documentValues = $dialog->getCurrentValue('Fields') ?? $dialog->getCurrentValues();

$getUserFieldValueControl = function (array $field, $value = null) use ($dialog)
{
	$fieldValue = $value ?? $dialog->getCurrentValue($field);

	if ($fieldValue)
	{
		if ($field['UserField']['USER_TYPE_ID'] === 'boolean')
		{
			$field['UserField']['VALUE'] = ($fieldValue == 'Y' ? 1 : 0);
		}
		else
		{
			$field['UserField']['VALUE'] = $fieldValue;
		}
		$field['UserField']['ENTITY_VALUE_ID'] = 1; //hack to not empty value
	}

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent(
		'bitrix:system.field.edit',
		$field['UserField']['USER_TYPE']['USER_TYPE_ID'],
		[
			'bVarsFromForm' => false,
			'arUserField' => $field['UserField'],
			'form_name' => $dialog->getFormName(),
			'SITE_ID' => $dialog->getSiteId(),
		],
		null,
		['HIDE_ICONS' => 'Y'],
	);
	$controlHtml = ob_get_clean();

	if ($field['FieldName'] === 'UF_TASK_WEBDAV_FILES' || $field['FieldName'] === 'UF_CRM_TASK')
	{
		$fieldMap = [
			'FieldName' => $field['UserField']['FIELD_NAME'] . '_text',
			'Type' => \Bitrix\Bizproc\FieldType::STRING,
			'Required' => $field['Required'],
			'Multiple' => $field['Multiple'],
		];

		$fieldType = $dialog->getFieldTypeObject($fieldMap);

		$controlHtml .= $fieldType->renderControl(
			[
				'Form' => $dialog->getFormName(),
				'Field' => $fieldMap['FieldName']
			],
			is_array($fieldValue) ? array_filter($fieldValue, [CBPDocument::class, 'isExpression']) : $fieldValue,
		true,
		0
		);
	}

	return $controlHtml;
};

$renderField = function (array $field, $value = null) use ($dialog, $getUserFieldValueControl)
{
	$userFieldTypes = [
		\Bitrix\Bizproc\FieldType::STRING,
		\Bitrix\Bizproc\FieldType::DOUBLE,
		\Bitrix\Bizproc\FieldType::DATETIME,
		\Bitrix\Bizproc\FieldType::BOOL,
	];

	$controlHtml = null;
	if (
		!isset($field['UserField'])
		|| (
			in_array($field['Type'], $userFieldTypes, true)
			&& mb_substr($field['UserField']['FIELD_NAME'], 0, mb_strlen('UF_AUTO_')) === 'UF_AUTO_'
		)
	)
	{
		$controlHtml = $dialog->renderFieldControl(
			$field,
			$value ?? $dialog->getCurrentValue($field),
			true,
			\Bitrix\Bizproc\FieldType::RENDER_MODE_DESIGNER,
		);
	}

	$fieldName =
		($field['Required'] ?? null)
			? sprintf('<span class="adm-required-field">%s</span>', htmlspecialcharsbx($field['Name']))
			: htmlspecialcharsbx($field['Name'])
	;

	echo sprintf(
		'<tr><td align="right" width="40%%">%s:</td><td width="60%%">%s</td></tr>',
		$fieldName,
		$controlHtml ?? $getUserFieldValueControl($field, $value),
	);
};

$renderDocumentField = function (array $field) use ($documentValues, $renderField)
{
	// compatibility
	$textFieldValue = $documentValues["{$field['FieldName']}_text"] ?? null;
	if (isset($textFieldValue))
	{
		$documentValues[$field['FieldName']] = $textFieldValue;
	}
	// end of compatibility

	$renderField($field, $documentValues[$field['FieldName']] ?? null);
};

$renderField($map['HoldToClose']);
if ($dialog->getDocumentType()[0] === 'tasks')
{
	$renderField($map['AsChildTask']);
}

foreach ($taskFieldsMap as $fieldId => $fieldValue)
{
	if (
		(($fieldValue['UserField']['USER_TYPE']['USER_TYPE_ID'] ?? null) === 'crm')
		&& CModule::IncludeModule('crm')
	)
	{
		$renderField($map['AUTO_LINK_TO_CRM_ENTITY']);
	}
	$renderDocumentField($fieldValue);

	if ($fieldId === 'ALLOW_TIME_TRACKING')
	{
		$renderField($map['TimeEstimateHour']);
		$renderField($map['TimeEstimateMin']);
	}
}
$renderField($map['CheckListItems']);

echo $GLOBALS["APPLICATION"]->GetCSS();
?>
<script>
	BX.message({
		TASKS_BP_FLOW_CONTROLLED_VALUE: '<?=GetMessageJS('TASKS_BP_FLOW_CONTROLLED_VALUE')?>',
	})
	BX.Event.ready(function()
	{
		new BX.Tasks.Automation.Activity.Task2Activity({
			isRobot: false,
			controlledByFlowFields: <?=\Bitrix\Main\Web\Json::encode((new FlowFieldHandler(0))->getModifiedFields())?>
		});
	});
</script>