<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

CJSCore::Init(array("date", "tasks_util_query", "tasks_util_template"));

$timeRecords = isset($arParams["TEMPLATE_DATA"]["DATA"]["TASK"]["SE_ELAPSEDTIME"]) ? $arParams["TEMPLATE_DATA"]["DATA"]["TASK"]["SE_ELAPSEDTIME"] : array();
$timePerms = isset($arParams["TEMPLATE_DATA"]["CAN"]["TASK"]["SE_ELAPSEDTIME"]) ? $arParams["TEMPLATE_DATA"]["CAN"]["TASK"]["SE_ELAPSEDTIME"] : array();
$canAddTime = isset($arParams["TEMPLATE_DATA"]["CAN"]["TASK"]["ACTION"]) ? $arParams["TEMPLATE_DATA"]["CAN"]["TASK"]["ACTION"]["ELAPSEDTIME.ADD"] : false;

$hoursUnit = Loc::getMessage("TASKS_ELAPSED_H");
$minutesUnit = Loc::getMessage("TASKS_ELAPSED_M");
$secondsUnit = Loc::getMessage("TASKS_ELAPSED_S");
$rowTemplate = <<<HTML

<tr class="{{rowClass}}" id="{{rowId}}">
	<td class="task-time-date-column"><span class="task-time-date">{{date}}</span></td>
	<td class="task-time-author-column">
		<a class="task-log-author" href="{{pathToUserProfile}}" target="_top">{{userName}}</a>
		<span class="task-log-author-text">{{userName}}</span>
	</td>
	<td class="task-time-spent-column">
		{{timeFormatted}}
		<span class="task-time-table-note-img" title="{{sourceNote}}"></span>
	</td>
	<td class="task-time-comment-column">
		<div class="task-time-comment-container">
			<span class="task-time-comment">{{comment}}</span>
			<span class="task-time-comment-action">
				<span class="task-table-edit"></span>
				<span class="task-table-remove"></span>
			</span>
		</div>
	</td>
</tr>

HTML;
?>

<table id="task-time-table" class="task-time-table<?if ($arParams["PUBLIC_MODE"]):?> task-time-table-public<?endif?>">
	<col class="task-time-date-column" />
	<col class="task-time-author-column" />
	<col class="task-time-spent-column" />
	<col class="task-time-comment-column" />
	<tr>
		<th class="task-time-date-column"><?=Loc::getMessage("TASKS_ELAPSED_DATE")?></th>
		<th class="task-time-author-column"><?=Loc::getMessage("TASKS_ELAPSED_AUTHOR")?></th>
		<th class="task-time-spent-column"><?=Loc::getMessage("TASKS_ELAPSED_TIME_SHORT")?></th>
		<th class="task-time-comment-column"><?=Loc::getMessage("TASKS_ELAPSED_COMMENT")?></th>
	</tr>
	<?
	$records = array();
	foreach($timeRecords as $time)
	{
		$timePerm = isset($timePerms[$time["ID"]]) && isset($timePerms[$time["ID"]]["ACTION"]) ? $timePerms[$time["ID"]]["ACTION"] : array();
		$can = array(
			"MODIFY" => isset($timePerm["MODIFY"]) ? $timePerm["MODIFY"] : false,
			"REMOVE" => isset($timePerm["REMOVE"]) ? $timePerm["MODIFY"] : false,
		);

		$rowClass = "";
		if ($can["MODIFY"])
		{
			$rowClass .= " task-time-table-edit";
		}

		if ($can["REMOVE"])
		{
			$rowClass .= " task-time-table-remove";
		}

		// todo: replace with \Bitrix\Tasks\UI::formatTimeAmount() in php and BX.Tasks.Util.formatTimeAmount() in js
		$secondsSign = ($time["SECONDS"] >= 0 ? 1 : -1);
		$hours = (int)$secondsSign * floor(abs($time["SECONDS"]) / 3600);
		$minutes = ($secondsSign * floor(abs($time["SECONDS"]) / 60)) % 60;
		$minutes = sprintf("%02d", $minutes);
		$seconds = $time["SECONDS"] % 60;

		$date = FormatDateFromDB($time["CREATED_DATE"], FORMAT_DATETIME);
		$profileLink = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $time["USER_ID"]));
		$userName = tasksFormatNameShort($time["USER_NAME"], $time["USER_LAST_NAME"], $time["USER_LOGIN"], $time["USER_SECOND_NAME"], $arParams["NAME_TEMPLATE"], true);

		$sourceNote = "";
		if ($time["SOURCE"] == CTaskElapsedItem::SOURCE_MANUAL)
		{
			$sourceNote = Loc::getMessage("TASKS_ELAPSED_SOURCE_MANUAL");
			$rowClass .= " task-time-table-manually";
		}
		elseif ($time["SOURCE"] == CTaskElapsedItem::SOURCE_UNDEFINED)
		{
			$sourceNote = Loc::getMessage("TASKS_ELAPSED_SOURCE_UNDEFINED");
			$rowClass .= " task-time-table-unknown";
		}

		$comment = htmlspecialcharsbx($time["COMMENT_TEXT"]);

		$rowId = $this->randString();
		$records[] = array(
			"id" => $time["ID"],
			"time" => $time["SECONDS"],
			"date" => $date,
			"comment" => $comment,
			"rowId" => $rowId
		);

		echo str_replace(
			array(
				"{{rowId}}",
				"{{rowClass}}",
				"{{date}}",
				"{{pathToUserProfile}}",
				"{{userName}}",
				"{{sourceNote}}",
				"{{comment}}",
				"{{timeFormatted}}"
			),
			array(
				$rowId,
				$rowClass,
				$date,
				$profileLink,
				$userName,
				$sourceNote,
				$comment,
				\Bitrix\Tasks\UI::formatTimeAmount($time["SECONDS"]),
			),
			$rowTemplate
		);
	}
	?>
	<tr class="task-time-add-link-row"<?if (!$canAddTime):?> style="display: none;"<?endif?>>
		<td class="task-time-date-column">
			<span class="task-dashed-link"><span class="task-dashed-link-inner"><?=Loc::getMessage("TASKS_ELAPSED_ADD")?></span></span>
		</td>
		<td class="task-time-author-column">&nbsp;</td>
		<td class="task-time-spent-column">&nbsp;</td>
		<td class="task-time-comment-column">&nbsp;</td>
	</tr>
	<tr class="task-time-form-row" style="display: none;">
		<td class="task-time-date-column">
			<input type="hidden" name="id" value="" />
			<input type="text" class="task-time-field-textbox" name="date" value="" readonly="readonly"/>
		</td>
		<td class="task-time-author-column">&nbsp;</td>
		<td class="task-time-spent-column">
			<nobr>
				<span class="task-time-spent-hours"><input type="text" name="hours" value="1" class="task-time-field-textbox" />
				<span><?=Loc::getMessage("TASKS_ELAPSED_H")?></span></span>
				<span class="task-time-spent-minutes"><input type="text" name="minutes" value="00" class="task-time-field-textbox" />
				<span><?=Loc::getMessage("TASKS_ELAPSED_M")?></span></span>
				<span class="task-time-spent-seconds"><input type="text" name="seconds" value="00" class="task-time-field-textbox" />
				<span><?=Loc::getMessage("TASKS_ELAPSED_S")?></span></span>
			</nobr>
		</td>
		<td class="task-time-comment-column">
			<div class="task-time-comment-container">
				<input type="text" name="comment" value="" class="task-time-field-textbox" />
				<span class="task-time-comment-action">
					<span class="task-table-edit-ok"></span>
					<span class="task-table-edit-remove"></span>
				</span>
			</div>
		</td>
	</tr>
</table>

<script>
	new BX.Tasks.Component.TaskElapsedTime("task-time-table", {
		taskId: "<?=CUtil::JSEscape($arParams["TASK_ID"])?>",
		records: <?=CUtil::PhpToJSObject($records)?>,
		nameTemplate: "<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>",
		pathToUserProfile: "<?=CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
		template: "<?=CUtil::JSEscape($rowTemplate)?>",
		messages: {
			removeConfirm: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ELAPSED_REMOVE_CONFIRM"))?>",
			sourceUndefined: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ELAPSED_SOURCE_UNDEFINED"))?>",
			sourceManual: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ELAPSED_SOURCE_MANUAL"))?>"
		}
	});
</script>