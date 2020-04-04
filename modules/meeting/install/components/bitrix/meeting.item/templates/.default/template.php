<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->SetViewTarget('pagetitle', 100);
?>
<?
if ($arResult['FROM_MEETING'] > 0):
?>
	<a href="<?echo str_replace('#MEETING_ID#', $arResult['FROM_MEETING'], $arParams['MEETING_URL_TPL'])?>" class="webform-small-button webform-small-button-blue webform-small-button-back">
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text"><?=GetMessage('ME_BACK')?></span>
	</a>
<?
endif;
?>
	<a href="<?=$arParams['LIST_URL']?>" class="webform-small-button webform-small-button-blue webform-small-button-back">
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text"><?=GetMessage('ME_LIST_TITLE')?></span>
	</a>
<?
$this->EndViewTarget();
?>
<form name="meeting_item_edit" method="POST" enctype="multipart/form-data" action="<?=POST_FORM_ACTION_URI?>">
	<input type="hidden" name="ITEM_ID" value="<?=$arParams['ITEM_ID']?>">
	<input type="hidden" name="sessid" value="<?=bitrix_sessid()?>" />
	<input type="hidden" name="save_item" value="Y" />
	<div class="meetings-content">
		<div class="webform-round-corners webform-main-fields">
			<div class="webform-corners-top">
				<div class="webform-left-corner"></div>
				<div class="webform-right-corner"></div>
			</div>
			<div class="webform-content meeting-detail-title-label"><?=GetMessage('MI_BLOCK_TITLE')?>
<?
if ($arResult['CAN_EDIT']):
?>
				<span id="item_edit_link" class="meeting-link meeting-edit-description"><?=GetMessage('MI_EDIT')?></span>
<?
endif;
?>
			</div>
		</div>
		<div class="webform-round-corners webform-main-block webform-main-block-topless webform-main-block-bottomless">
			<div class="webform-content" id="meeting_item_block">
<?
require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/view.php');
?>
			</div>
		</div>
	</div>
<?
foreach ($arResult['ITEM']['TASKS'] as $task_id):
	$task_id = intval($task_id);
?>
	<input type="hidden" id="meeting_task_<?=$task_id;?>" name="ITEM_TASKS[]" value="<?=$task_id?>" />
<?
endforeach;
?>
</form>
<?
if ($arResult['CAN_EDIT']):
?>
<div class="item-edit-buttons" id="item_edit_buttons" style="display: none;">
	<a class="webform-small-button webform-small-button-accept" href="javascript:void(0)" onclick="viewItem()">
		<span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage('MI_SAVE')?></span><span class="webform-small-button-right"></span>
	</a>
	<a href="javascript:void(0)" class="webform-small-button-link webform-button-link-cancel" onclick="resetItem(true); return false;"><?=GetMessage('MI_CANCEL')?></a>
</div>
<?
endif;
?>

<?
$bSingle = count($arResult['ITEM']['INSTANCES']) <= 1;
$bFirst = true;
$bEdit = false;
foreach ($arResult['ITEM']['INSTANCES'] as $arInstance):
	if ($bFirst):
?>
<div class="meeting-question-report-wrap">
	<div class="meeting-ques-title"><?=GetMessage('MI_REPORTS')?></div>
<?
	endif;
?>
	<div class="meeting-ques-info-wrap">
		<div class="meeting-list-left-corner"></div>
		<div class="meeting-list-right-corner"></div>
		<table class="meeting-ques-info" cellspacing="0">
			<tbody>
				<tr class="meeting-ques-info-top">
					<td class="meeting-ques-left"><?=GetMessage('MI_REPORT_MEETING')?>:</td>
					<td class="meeting-ques-right"><a href="<?echo str_replace('#MEETING_ID#', $arInstance['MEETING_ID'], $arParams['MEETING_URL_TPL'])?>"><?=htmlspecialcharsbx(GetMessage('MI_MEETING_TITLE', array('#ID#' => $arInstance['MEETING_ID'], '#TITLE#' => $arInstance['MEETING']['TITLE'])));?></a></td>
				</tr>
<?
if ($arInstance['MEETING']['DATE_START'] && MakeTimeStamp($arInstance['MEETING']['DATE_START'])>0):
?>
				<tr class="meeting-ques-info-top">
					<td class="meeting-ques-left"><?=GetMessage('MI_REPORT_DATE_START')?>:</td>
					<td class="meeting-ques-right"><?=FormatDate($DB->DateFormatToPhp(FORMAT_DATE).((IsAmPmMode()) ? ' h:i a' : ' H:i'), MakeTimeStamp($arInstance['MEETING']['DATE_START']))?></td>
				</tr>
<?
endif;
?>
				<tr class="meeting-ques-info-top meeting-ques-info-top-last">
					<td class="meeting-ques-left"><?=GetMessage('MI_REPORT_STATE')?>:</td>
					<td class="meeting-ques-right"><?=GetMessage("MEETING_STATE_".$arInstance['MEETING']['CURRENT_STATE'])?></td>
				</tr>
				<tr class="meeting-ques-info-separator"><td colspan="2"></td></tr>
				<tr class="meeting-ques-info-bottom meeting-ques-info-bottom-first">
					<td class="meeting-ques-left"><?=count($arInstance['RESPONSIBLE']) > 1 ? GetMessage('MI_REPORT_RESPONSIBLES') : GetMessage('MI_REPORT_RESPONSIBLE')?>:</td>
					<td class="meeting-ques-right">
<?
	if (count($arInstance['RESPONSIBLE']) > 0):
		foreach ($arInstance['RESPONSIBLE'] as $USER_ID):
			$APPLICATION->IncludeComponent('bitrix:main.user.link', '', array('ID' => $USER_ID, "NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]), null, array('HIDE_ICONS' => 'Y'));
		endforeach;
	else:
		echo GetMessage('MI_REPORT_NO_RESPONSIBLE');
	endif;
?>
					</td>
				</tr>
<?
	if ($arInstance['DEADLINE'] && MakeTimeStamp($arInstance['DEADLINE'])>0):
?>
				<tr class="meeting-ques-info-bottom">
					<td class="meeting-ques-left"><?=GetMessage('MI_REPORT_DEADLINE')?>:</td>
					<td class="meeting-ques-right"><?=FormatDateFromDB($arInstance['DEADLINE'])?></td>
				</tr>
<?
	endif;

	$arReport = count($arInstance['REPORTS']) > 0 ? $arInstance['REPORTS'][0] : array(
		'ID' => 'n0',
		'USER_ID' => $USER->GetID(),
		'REPORT' => '',
	);
?>
<?
	if ($arInstance['B_EDIT']):
		$bEdit = true;
?>
				<tr class="meeting-ques-info-bottom-block meeting-ques-info-bot-last">
					<td colspan="2">
						<form name="meeting_item_edit_<?=$arInstance['ID']?>" method="POST" enctype="multipart/form-data" action="<?=POST_FORM_ACTION_URI?>">
							<input type="hidden" name="ITEM_ID" value="<?=$arParams['ITEM_ID']?>" />
							<input type="hidden" name="REPORT_ID" value="<?=$arReport['ID']?>" />
							<input type="hidden" name="INSTANCE_ID" value="<?=$arInstance['ID']?>" />
							<input type="hidden" name="sessid" value="<?=bitrix_sessid()?>" />
							<div class="meeting-ques-content-bottom">
								<div class="webform-field-label"><?=GetMessage('MI_REPORT')?></div>
								<div id="meeting-new-add-description-form" class="meeting-new-add-description-form">
<?
		$APPLICATION->IncludeComponent('bitrix:fileman.light_editor', '', array(
			'CONTENT' => $arReport['REPORT'],
			'INPUT_NAME' => 'REPORT',
			'RESIZABLE' => 'Y',
			'AUTO_RESIZE' => 'Y',
			'HEIGHT' => '100px',
			'WIDTH' => '100%',
		), null, array('HIDE_ICONS' => 'Y'));

		$arFiles = array();
		$arFilesExt = array();

		if (count($arReport['FILES']) > 0)
		{
			foreach ($arReport['FILES'] as $arFile)
			{
				$arFiles[] = $arFile['FILE_ID'];
				if ($arFile['FILE_SRC'])
					$arFilesExt[$arFile['FILE_ID']] = $arFile['FILE_SRC'];
			}
		}

		$APPLICATION->IncludeComponent('bitrix:main.file.input', '', array(
			'INPUT_NAME' => 'FILES',
			'INPUT_NAME_UNSAVED' => 'FILES_TMP',
			'INPUT_VALUE' => $arFiles,
			'CONTROL_ID' => 'MEETING_ITEM_REPORT_FILES_'.$arInstance['ID'],
			'MODULE_ID' => 'meeting'
		));
?>
								</div>
							</div>
						</form>
					</td>
				</tr>
<?
	else:
?>
				<tr class="meeting-ques-info-bottom meeting-ques-info-bot-sep-wrap">
					<td class="meeting-ques-left">
						<div class="meeting-ques-info-bot-sep"></div>
					</td>
					<td class="meeting-ques-right">
						<div class="meeting-ques-info-bot-sep"></div>
					</td>
				</tr>
				<tr class="meeting-ques-info-bottom<?=(count($arReport['FILES']) > 0 ? '' : ' meeting-ques-info-bot-last')?>">
					<td class="meeting-ques-left"><?=GetMessage('MI_REPORT')?>:</td>
					<td class="meeting-ques-right">
						<span class="meeting-ques-edit-item"><span><?=strlen($arReport['REPORT']) > 0 ? $arReport['REPORT'] : GetMessage('MI_REPORT_NO_REPORT')?></span>
							<?/*<div class="meeting-ag-edit-edit"></div>*/?>
						</span>
					</td>
				</tr>
<?
		if (count($arReport['FILES']) > 0):
?>
				<tr class="meeting-ques-info-bottom meeting-ques-info-bot-last">
					<td class="meeting-ques-left"><?=GetMessage('ME_FILES')?>:</td>
					<td class="meeting-ques-right">
<?
			foreach ($arReport['FILES'] as $ix => $arFile):
?>
				<div class="meeting-detail-file"><span class="meeting-detail-file-number"><?=$ix+1?>.</span><span class="meeting-detail-file-info"><?if($arFile['FILE_SRC']):?><a href="#message<?=$arFile['FILE_SRC']?>" class="meeting-detail-file-comment"></a><?endif?><a class="meeting-detail-file-link" href="<?=$arFile['DOWNLOAD_URL']?>"><?=$arFile['ORIGINAL_NAME']?></a><span class="meeting-detail-file-size">(<?=$arFile['FILE_SIZE_FORMATTED']?>)</span></span></div>
<?
			endforeach;
?>
					</td>
				</tr>
<?
		endif;
	endif;
?>
			</tbody>
		</table>
<?
	if ($arInstance['B_EDIT']):
		$bEdit = true;
?>
		<div class="meeting-question-buttons"><a class="webform-small-button webform-small-button-accept" href="javascript:void(0)" onclick="saveReport(this, document.forms.meeting_item_edit_<?=$arInstance['ID']?>)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage('MI_SAVE')?></span><span class="webform-small-button-right"></span></a></div>
<?
	endif;
?>
	</div>
<?
	if ($bFirst):
?>
</div>
<div class="meeting-question-history-wrap">
<?
		if (!$bSingle):
			$t = array(str_replace('#CNT#', count($arResult['ITEM']['INSTANCES'])-1, GetMessage('MI_HISTORY_SHOW')), GetMessage('MI_HISTORY_HIDE'))
?>
	<span class="meeting-link meeting-quest-hist-hide" onclick="BX.toggle(BX('meeting_question_history')); this.innerHTML=BX.toggle(this.innerHTML, <?=CUtil::PhpToJsObject($t)?>);"><?=$t[0]?></span>
	<div class="meeting-question-history" id="meeting_question_history" style="display:none;">
<?
		endif;
	endif;
	$bFirst = false;
endforeach;
?>
	</div>
<?
if (!$bSingle):
?>
</div>
<?
endif;
if ($bEdit):
?>
<script type="text/javascript">
function saveReport(el, form)
{
	if (BX.hasClass(el, 'webform-small-button-accept'))
	{
		el.className = 'webform-small-button';
		el.firstChild.nextSibling.innerHTML = '<?=CUtil::JSEscape(GetMessage('MI_SAVING'))?>';
		BX.ajax.submit(form, function(d){
			el.className = 'webform-small-button webform-small-button-accept';
			el.firstChild.nextSibling.innerHTML = '<?=CUtil::JSEscape(GetMessage('MI_SAVE'))?>';
			if(!isNaN(parseInt(d)))
				form.REPORT_ID.value=d;

			if (el.nextSibling)
				el.parentNode.removeChild(el.nextSibling);

			el.parentNode.appendChild(BX.create('SPAN', {props: {className: 'meeting-report-saved'}, text: '<?=CUtil::JSEscape(GetMessage('MI_SAVED'))?>'.replace('#TIME#', BX.date.format(BX.isAmPmMode() ? 'h:i a' : 'H:i'))}));
		});
	}
}
</script>
<?
endif;
if (CBXFeatures::IsFeatureEnabled('tasks') && IsModuleInstalled('tasks')):
?>
<div id="task_selector" style="display: none;">
<?
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.task.selector",
		".default",
		array(
			"MULTIPLE" => "N",
			"NAME" => "MEETING_TASKS",
			"VALUE" => "",
			"POPUP" => "N",
			"ON_SELECT" => "attachTask",
			"SITE_ID" => SITE_ID,
			"FILTER" => array('RESPONSIBLE_ID' => $USER->GetID()),
			"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			"SELECT" => array('ID', 'TITLE', 'STATUS'),
		),
		null,
		array("HIDE_ICONS" => "Y")
	);
?>
</div>
<a name="tasks"></a>
<div class="meeting-question-tasks-wrap">
	<div class="meeting-question-tasks-top">
		<span class="meeting-ques-title"><?=GetMessage("MI_TASKS")?></span>
		<span class="meeting-link" onclick="AddQuickPopupTask(event)"><?=GetMessage('MI_TASK_ADD')?></span><span class="meeting-link" onclick="showTaskSelector(this)"><?=GetMessage('MI_TASK_ATTACH')?></span>
	</div>
</div>
<div id="meeting_item_tasks" style="clear: both">
<?
	require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/tasks.php');
?>
</div>
<?
endif;
?>
<div class="meeting-question-tasks-wrap">
	<div class="meeting-question-tasks-top">
		<span class="meeting-ques-title"><?=GetMessage("MI_COMMENTS")?></span>
	</div>
</div>
<div id="meeting_item_comments" style="clear: both">
<?
require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/comments.php');
?>
</div>
<script type="text/javascript">
<?
if ($arResult['CAN_EDIT']):
?>
function editItem()
{
	var w = BX.showWait(BX('meeting_item_block'));
	BX.ajax.get('<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('MEETING_ITEM_EDIT=Y', array('MEETING_ITEM_EDIT', 'MEETING_ITEM_VIEW', 'MEETING_TASKS_RELOAD', 'MEETING_ITEM_COMMENTS')))?>', function(data) {
		BX('meeting_item_block', true).innerHTML = data;
		BX('item_edit_link', true).innerHTML = '<?=CUtil::JSEscape(GetMessage('MI_EDIT_FINISH'))?>';
		BX('item_edit_link', true).onclick = viewItem;
		BX.closeWait(w);
		BX.show(BX('item_edit_buttons', true));
	});
}

function viewItem()
{
	var w = BX.showWait(BX('meeting_item_block', true));
	saveData(function(data) {
		BX('meeting_item_block', true).innerHTML = data;
		BX('item_edit_link', true).innerHTML = '<?=CUtil::JSEscape(GetMessage('MI_EDIT'))?>';
		BX('item_edit_link', true).onclick = editItem;
		BX.closeWait(w);
	});
	BX.hide(BX('item_edit_buttons', true));
}

function resetItem()
{
	var w = BX.showWait(BX('meeting_item_block'));
	BX.ajax.get('<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('MEETING_ITEM_VIEW=Y', array('MEETING_ITEM_EDIT', 'MEETING_ITEM_VIEW', 'MEETING_TASKS_RELOAD', 'MEETING_ITEM_COMMENTS')))?>', function(data) {
		BX('meeting_item_block', true).innerHTML = data;
		BX('item_edit_link', true).innerHTML = '<?=CUtil::JSEscape(GetMessage('MI_EDIT'))?>';
		BX('item_edit_link', true).onclick = editItem;
		BX.closeWait(w);
	});
	BX.hide(BX('item_edit_buttons', true));
}
<?
endif;
?>
function saveData(cb) {
	BX.ajax.submit(document.forms.meeting_item_edit, cb);
}

function attachTask(task)
{
	var row = BX('task-' + task.id);

	var callback = null;
	if (row)
	{
		callback = function () {
			row.style.display = '';
		};
	}
	else
	{
		callback = updateTaskList;
	}

	addTaskToMeeting(task.id, callback);

	window.task_selector_wnd.close();
}

function detachTask(task_id)
{
	if (task_id.id) task_id = task_id.id;
	var q = BX('task-' + task_id);
	if (q)
		q.parentNode.removeChild(q);

	q = BX('meeting_task_' + task_id);
	if (q)
	{
		q.parentNode.removeChild(q);
		saveData();
	}
}

function showTaskSelector(el)
{
	if (!window.task_selector_wnd)
	{
		var q = BX('task_selector');
		q.parentNode.removeChild(q);
		q.style.display = 'block';
		window.task_selector_wnd = waitPopup = new BX.PopupWindow('task_selector', el, {
			autoHide: true,
			lightShadow: true,
			content: q
		});
	}

	window.task_selector_wnd.show();
}

window.task_selector_wnd = null;

BX.addCustomEvent('tasksTaskEvent', function(type, data) {

	var id = data && data.taskUgly && data.taskUgly.id ? data.taskUgly.id : null;
	if (id === null)
	{
		return;
	}

	if (type === "ADD")
	{
		addTaskToMeeting(id, updateTaskList);
	}
	else if (type === "DELETE")
	{
		detachTask(id);
	}
});

function updateTaskList()
{
	var w = BX.showWait(BX('meeting_item_tasks', true));
	BX.ajax.get(
		'<?=CUtil::JSEscape($APPLICATION->GetCurPageParam(
			'MEETING_TASKS_RELOAD=Y',
			array('MEETING_ITEM_EDIT', 'MEETING_ITEM_VIEW', 'MEETING_TASKS_RELOAD', 'MEETING_ITEM_COMMENTS')))
			?>',
		function(data)
		{
			BX('meeting_item_tasks', true).innerHTML = data;
			BX.closeWait(w);
		}
	);
}

function addTaskToMeeting(taskId, cb)
{
	if (!BX('meeting_task_' + taskId))
	{
		document.forms.meeting_item_edit.appendChild(BX.create('INPUT', {
			attrs: {type: 'hidden', name: 'ITEM_TASKS[]', value: taskId, id: 'meeting_task_' + taskId }
		}));

		saveData(cb);
	}
}

BX.addCustomEvent('onTaskListTaskDelete', detachTask);
<?
if ($arResult['CAN_EDIT']):
?>
BX.ready(function() {
	BX('item_edit_link', true).onclick = editItem;
});
<?
endif;
?>
</script>