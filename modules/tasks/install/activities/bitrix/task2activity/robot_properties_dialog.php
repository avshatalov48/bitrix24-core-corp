<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$data = $dialog->getRuntimeData();
$errors = array();

$checkboxes = array();

$renderField = function ($fieldId, $field, $value) use ($dialog, $rawValues)
{
	?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete"><?=htmlspecialcharsbx($field['Name'])?>: </span>
		<?
		switch ($field['BaseType'])
		{
			case 'string':
				?>
				<input name="<?=htmlspecialcharsbx($fieldId)?>" type="text" class="bizproc-automation-popup-input"
					   value="<?=htmlspecialcharsbx($value)?>"
					   data-role="inline-selector-target"
				>
				<?
				break;
			case 'user':
				$userValue = $rawValues[$fieldId];
				if (!$userValue && $field['Required'])
				{
					$userValue = method_exists(\Bitrix\Bizproc\Automation\Helper::class, 'getResponsibleUserExpression')
						? \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($dialog->getDocumentType()) : 'author';
				}

				if (method_exists($dialog, 'renderFieldControl'))
				{
					$field['Type'] = 'user';
					$field['FieldName'] = $fieldId;
					echo $dialog->renderFieldControl($field, $userValue);
				}
				else
				{
					?>
					<div data-role="user-selector" data-config="<?=htmlspecialcharsbx(
						\Bitrix\Main\Web\Json::encode(array(
							'valueInputName' => $fieldId,
							'selected' => \Bitrix\Bizproc\Automation\Helper::prepareUserSelectorEntities(
								$dialog->getDocumentType(),
								$userValue
							),
							'multiple' => $field['Multiple'],
							'required' => $field['Required'],
						))
					)?>"></div>
					<?
				}
				break;
			case 'datetime':
				?>
				<input name="<?=htmlspecialcharsbx($fieldId)?>" type="text" class="bizproc-automation-popup-input"
					   value="<?=htmlspecialcharsbx($value)?>"
					   data-role="inline-selector-target"
					   data-selector-type="datetime"
				>
				<?
				break;
			case 'text':
				?>
				<textarea name="<?=htmlspecialcharsbx($fieldId)?>"
						  class="bizproc-automation-popup-textarea"
						  data-role="inline-selector-target"
				><?=htmlspecialcharsbx($value)?></textarea>
				<?
				break;
			case 'select':
				$options = isset($field['Options']) && is_array($field['Options'])
					? $field['Options'] : array();
				?>
				<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($fieldId)?>">
					<?
					foreach ($options as $k => $v)
					{
						echo '<option value="'.htmlspecialcharsbx($k).'"'.($k == $value ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					}
					?>
				</select>
				<?
				break;
		}
		?>
	</div>
	<?
};

unset($arDocumentFields['PRIORITY']);

//Visible fields
foreach (['TITLE', 'DESCRIPTION', 'RESPONSIBLE_ID', 'DEADLINE'] as $fieldId)
{
	$value = $arCurrentValues[$fieldId];
	$renderField($fieldId, $arDocumentFields[$fieldId], $value);
	unset($arDocumentFields[$fieldId]);
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
	<?=GetMessage('TASKS_BP_RPD_ADDITIONAL')?>
</span>
<div class="tasks-task2activity-additional-content">
<?
	foreach ($arDocumentFields as $fieldId => $field)
	{
		if (!in_array($fieldId, $allowedTaskFields))
			continue;

		if ($field['BaseType'] === 'bool')
		{
			$checkboxes[$fieldId] = $field;
			continue;
		}

		$value = $arCurrentValues[$fieldId];

		$renderField($fieldId, $field, $value);
	}
	?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete"><?=GetMessage('BPSA_CHECK_LIST_ITEMS')?>: </span>
		<?=$dialog->renderFieldControl(['FieldName' => 'CHECK_LIST_ITEMS', 'Type' => 'string', 'Multiple' => true], $arCurrentValues['CHECK_LIST_ITEMS'])?>
	</div>
	<div class="bizproc-automation-popup-checkbox">
		<div class="bizproc-automation-popup-checkbox-item">
			<label class="bizproc-automation-popup-chk-label">
				<input type="hidden" name="PRIORITY" value="1">
				<input type="checkbox" name="PRIORITY" value="2" class="bizproc-automation-popup-chk" <?=$arCurrentValues['PRIORITY'] == 2 ? 'checked' : ''?>>
				<?=GetMessage('TASKS_BP_RPD_PRIORITY')?>
			</label>
		</div>
		<?foreach ($checkboxes as $fieldId => $field):?>
			<div class="bizproc-automation-popup-checkbox-item">
				<label class="bizproc-automation-popup-chk-label">
					<input type="hidden" name="<?=htmlspecialcharsbx($fieldId)?>" value="N">
					<input type="checkbox" name="<?=htmlspecialcharsbx($fieldId)?>" value="Y" class="bizproc-automation-popup-chk" <?=$arCurrentValues[$fieldId] != 'N' ? 'checked' : ''?>>
					<?=htmlspecialcharsbx($field['Name'])?>
				</label>
			</div>
		<?endforeach;?>
		<?if ($dialog->getDocumentType()[0] === 'tasks'):?>
			<div class="bizproc-automation-popup-checkbox-item">
				<label class="bizproc-automation-popup-chk-label">
					<input type="hidden" name="AS_CHILD_TASK" value="N">
					<input type="checkbox" name="AS_CHILD_TASK" value="Y" class="bizproc-automation-popup-chk" <?=$arCurrentValues['AS_CHILD_TASK'] == 'Y' ? 'checked' : ''?>>
					<?=GetMessage('TASKS_BP_RPD_AS_CHILD_TASK')?>
				</label>
			</div>
		<?endif;?>
	</div>
</div>
<input type="hidden" name="HOLD_TO_CLOSE" value="N">
<input type="hidden" name="AUTO_LINK_TO_CRM_ENTITY" value="Y">
