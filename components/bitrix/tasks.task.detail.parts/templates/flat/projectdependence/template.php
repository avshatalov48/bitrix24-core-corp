<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$prefix = htmlspecialcharsbx($arParams['TEMPLATE_DATA']['INPUT_PREFIX']);

$taskUrl = str_replace(array('#task_id#', '#action#'), array('{{DEPENDS_ON_ID}}', 'view'), $arParams['TEMPLATE_DATA']['PATH_TO_TASKS_TASK']);

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
?>

<div id="bx-component-scope-<?=htmlspecialcharsbx($templateId)?>">

	<table data-bx-id="projdep-item-set-container" class="task-options-task-other hidden">
		<thead>
			<tr>
				<td></td>
				<td><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_COL_REL_TYPE')?></td>
				<td></td>
				<td><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_COL_REL_TYPE')?></td>
				<td><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_COL_RELATED')?></td>
				<td></td>
			</tr>
		</thead>
		<tbody data-bx-id="projdep-item-set-items">

			<script type="text/html" data-bx-id="projdep-item-set-item">

				<tr data-bx-id="projdep-item-set-item" data-item-value="{{VALUE}}" class="{{ITEM_SET_INVISIBLE}}">
					<td>
						<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_CURRENT_TASK')?></span>
					</td>
					<td>
						<span class="task-options-inp-container">
							<select data-bx-id="item-type-left" class="task-options-inp">
								<option value="s" {{R_START}}><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_WHEN_START')?></option>
								<option value="f" {{R_FINISH}}><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_WHEN_END')?></option>
							</select>
						</span>
					</td>
					<td>
						<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_WHEN')?></span>
					</td>
					<td>
						<span class="task-options-inp-container">
							<select data-bx-id="item-type-right" class="task-options-inp">
								<option value="s" {{L_START}}><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_WHEN_START')?></option>
								<option value="f" {{L_FINISH}}><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_WHEN_END')?></option>
							</select>
						</span>
					</td>
					<td><a href="<?=htmlspecialcharsbx($taskUrl)?>" target="_blank" class="task-options-task-name">{{DEPENDS_ON_TITLE}}</a></td>

					<td>
						<input data-bx-id="item-type" type="hidden" name="<?=$prefix?>[{{VALUE}}][TYPE]" value="{{TYPE}}" />
						<input data-bx-id="item-task-id" type="hidden" name="<?=$prefix?>[{{VALUE}}][DEPENDS_ON_ID]" value="{{DEPENDS_ON_ID}}" />

						<span data-bx-id="projdep-item-set-item-delete" class="task-options-title-del" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_DELETE')?>"></span>
					</td>
				</tr>

			</script>
		</tbody>
	</table>
	<span class="task-dashed-link <?if($arResult['TEMPLATE_DATA']['RESTRICTED']):?>tasks-btn-restricted<?endif?>">
		<span data-bx-id="projdep-item-set-open-form" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_PROJDEP_ADD')?></span>
	</span>

	<?$ctrlId = md5($templateId);?>

	<div data-bx-id="projdep-item-set-picker-content" class="hidden-soft">
		<?$APPLICATION->IncludeComponent(
			"bitrix:tasks.task.selector", ".default", array(
				"MULTIPLE" => "N",
				"NAME" => $ctrlId,
				"VALUE" => [],
				"LAST_TASKS" => $arParams['DATA']['LAST_TASKS'],
				"CURRENT_TASKS" => $arParams['DATA']['CURRENT_TASKS'],
				"PATH_TO_TASKS_TASK" => ($arParams["PATH_TO_TASKS_TASK"] ?? null),
				"SITE_ID" => SITE_ID,
				"SELECT" => array('ID', 'TITLE', 'STATUS'),
			), null, array("HIDE_ICONS" => "Y")
		);?>
	</div>

	<?// in case of all items removed, the field should be sent anyway?>
	<input type="hidden" name="<?=$prefix?>[]" value="">

</div>

<script>
	new BX.Tasks.Component.TaskDetailPartsProjDep(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
		'registerDispatcher' => true,
		'selectorCode' => $ctrlId,
		'restricted' => $arResult['TEMPLATE_DATA']['RESTRICTED'],
	), false, false, true)?>);
</script>