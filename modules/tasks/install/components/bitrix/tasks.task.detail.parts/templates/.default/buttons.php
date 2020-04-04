<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>


<!-- =========================== begin buttons =========================== -->


<?php
if ($arResult['INNER_HTML'] !== 'Y')
{
	?>
	<div id="task-detail-buttons-area">
	<?php
}

if ($arResult["TASK"]["REAL_STATUS"] == CTasks::STATE_DECLINED)
{
	?>
	<div class="webform-round-corners webform-warning-block">
		<div class="webform-corners-top">
			<div class="webform-left-corner"></div>
			<div class="webform-right-corner"></div>
		</div>
		<div class="webform-content">
			<div class="webform-warning-content">
				<div class="webform-warning-title"><?php
					echo GetMessage("TASKS_TASK_DECLINE_REASON");
				?>:</div>
				<div class="webform-warning-text"><?php
					echo $arResult["TASK"]["DECLINE_REASON"];
				?></div>
			</div>
		</div>
		<div class="webform-corners-bottom">
			<div class="webform-left-corner"></div>
			<div class="webform-right-corner"></div>
		</div>
	</div>
	<?php
}
?>

<div id="task-detail-buttons-div" class="webform-buttons task-buttons">
	<?php
	if ($arResult['SHOW_TIMER_NODE'] === 'Y')
	{
		if ($arResult['TIMER_IS_RUNNING_FOR_CURRENT_USER'] === 'Y')
			$timeRunned = $arResult['TASK']['TIME_SPENT_IN_LOGS'] + $arResult['TIMER']['RUN_TIME'];
		else
			$timeRunned = $arResult['TASK']['TIME_SPENT_IN_LOGS'];

		?>
		<span id="task_details_buttons_timer_<?php echo (int) $arResult['TASK']['ID']; ?>" 
			class="task-timeman-link <?php 
				if (($arResult['TASK']['TIME_ESTIMATE'] > 0) && $timeRunned > $arResult['TASK']['TIME_ESTIMATE'])
					echo 'task-timeman-link-red';
				elseif ($arResult['TIMER_IS_RUNNING_FOR_CURRENT_USER'] === 'Y')
					echo 'task-timeman-link-green';
			?>">
			<span class="task-timeman-icon"></span>
			<span id="task_details_buttons_timer_<?php echo (int) $arResult['TASK']['ID']; ?>_text" class="task-timeman-text"><?php

			echo sprintf(
				'%02d:%02d:%02d',
				floor($timeRunned / 3600),		// hours
				floor($timeRunned / 60) % 60,	// minutes
				$timeRunned % 60				// seconds
			);

			if ($arResult['TASK']['TIME_ESTIMATE'] > 0)
			{
				echo ' / ' . sprintf(
					'%02d:%02d',
					floor($arResult['TASK']['TIME_ESTIMATE']  / 3600),	// hours
					floor($arResult['TASK']['TIME_ESTIMATE'] / 60) % 60	// minutes
				);
			}
			?></span>
			<span class="task-timeman-arrow"></span>
		</span>
		<script>
		BX.ready(function(){
			tasksDetailPartsNS.initTimer(
				<?php echo (int) $arResult['TASK']['ID']; ?>,
				'<?php echo $arResult['TIMER_IS_RUNNING_FOR_CURRENT_USER']; ?>'
			);

			<?php
			if ($arResult["IS_IFRAME"] && ($arResult['TIMER_IS_RUNNING_FOR_CURRENT_USER'] === 'Y'))
			{
				?>
				BX.TasksTimerManager.onDataRecieved({
					TASKS_TIMER   : {
						TASK_ID          : <?php echo (int) $arResult['TIMER']['TASK_ID']; ?>,
						USER_ID          : <?php echo (int) $arResult['TIMER']['USER_ID']; ?>,
						TIMER_STARTED_AT : <?php echo (int) $arResult['TIMER']['TIMER_STARTED_AT']; ?>,
						RUN_TIME         : <?php echo (int) $arResult['TIMER']['RUN_TIME']; ?>
					},
					TASK_ON_TIMER : {
						ID                  : <?php echo (int) $arResult['TASK']['ID']; ?>,
						TITLE               : '<?php echo CUtil::JSEscape($arResult['TASK']['TITLE']); ?>',
						STATUS              : <?php echo (int) $arResult['TASK']['STATUS']; ?>,
						TIME_SPENT_IN_LOGS  : <?php echo (int) $arResult['TASK']['TIME_SPENT_IN_LOGS']; ?>,
						TIME_ESTIMATE       : <?php echo (int) $arResult['TASK']['TIME_ESTIMATE']; ?>,
						ALLOW_TIME_TRACKING : '<?php echo $arResult['TASK']['ALLOW_TIME_TRACKING']; ?>'
					}
				});
				<?php
			}
			?>
		});		
		</script>
		<?php
	}

		if ($arResult['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING'])
		{
			if ($arResult['TIMER_IS_RUNNING_FOR_CURRENT_USER'] === 'N')
			{
				?><a onclick="
						tasksDetailPartsNS.doAction(
						<?php echo (int) $arResult['TASK']['ID']; ?>,
						'start_timer'
					);
					return false;" href="javascript:void(0);" class="webform-small-button webform-small-button-accept"><span class="webform-small-button-left"></span><span class="webform-small-button-icon task-button-icon-play"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_START_TASK_TIMER")?></span><span class="webform-small-button-right"></span></a><?php
			}
			else
			{
				?>
					<a onclick="
							tasksDetailPartsNS.doAction(
							<?php echo (int) $arResult['TASK']['ID']; ?>,
							'stop_timer'
						);
						return false;"
						href="javascript:void(0);"
						class="webform-small-button"><span class="webform-small-button-left"></span><span class="webform-small-button-icon task-button-icon-pause"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_PAUSE_TASK_TIMER")?></span><span class="webform-small-button-right"></span></a><?php
			}
		}

		// Show ACTION_START button only if we can't start time tracking for the task
		if (
			$arResult['ALLOWED_ACTIONS']['ACTION_START']
			&& ( ! $arResult['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING'] )
		)
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'start'
				);
				return false;" href="javascript:void(0);" class="webform-small-button webform-small-button-accept"><span class="webform-small-button-left"></span><span class="webform-small-button-icon task-button-icon-play"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_START_TASK")?></span><span class="webform-small-button-right"></span></a><?php
		}

		// Show ACTION_PAUSE button only if we can't start time tracking for the task
		if (
			$arResult['ALLOWED_ACTIONS']['ACTION_PAUSE']
			&& ( ! $arResult['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING'] )
		)
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'pause'
				);
				return false;" href="javascript:void(0);" class="webform-small-button webform-small-button-accept"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_PAUSE_TASK")?></span><span class="webform-small-button-right"></span></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_COMPLETE'])
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'complete'
				);
				return false;" href="javascript:void(0);" class="webform-small-button webform-small-button-accept"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_CLOSE_TASK")?></span><span class="webform-small-button-right"></span></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_APPROVE'])
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'approve'
				);
				return false;" href="javascript:void(0);" class="webform-small-button webform-small-button-accept"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_APPROVE_TASK")?></span><span class="webform-small-button-right"></span></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_DISAPPROVE'])
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'disapprove'
				);
				return false;" href="javascript:void(0);" class="webform-small-button webform-small-button-decline"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php echo GetMessage("TASKS_REDO_TASK")?></span><span class="webform-small-button-right"></span></a><?php
		}

		$copyUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));

		$showPopupMenu = false;

		$inFavorite = $arResult['TASK']['FAVORITE'] == 'Y';
		$favoriteItemMessage = GetMessage("TASKS_".($inFavorite ? 'DELETE' : 'ADD')."_FAVORITE");
		?><script type="text/javascript">
			var taskMenu = [

				<?if(is_array($arParams['MENU_ITEMS'])):?>

					<?$i = 0;?>
					<?foreach($arParams['MENU_ITEMS'] as $item):?>
						<?=(!$i ? '': ',')?>{ text : '<?=CUtil::JSEscape($item['TITLE'])?>', title : '<?=CUtil::JSEscape($item['TITLE'])?>', className : "<?=CUtil::JSEscape($item['CLASS_NAME'])?>", href : "<?=CUtil::JSEscape($item['HREF'])?>" }
						<?$i++;?>
					<?endforeach?>

					<?$showPopupMenu = count($arParams['MENU_ITEMS']) > 0;?>

				<?else:?>

					{ code: 'COPY', text : '<?php echo CUtil::JSEscape(GetMessage("TASKS_COPY_TASK")); ?>', title : '<?php echo CUtil::JSEscape(GetMessage("TASKS_COPY_TASK_EX")); ?>', className : "menu-popup-item-copy", href : "<?php echo $copyUrl.(strpos($copyUrl, "?") === false ? "?" : "&")."COPY=".$arResult["TASK"]["ID"].($arResult["IS_IFRAME"] ? "&IFRAME=Y" : "")?>" }
					,{ code: 'ADD_SUBTASK', text : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_SUBTASK_2")); ?>', title : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_SUBTASK_2")); ?>', className : "menu-popup-item-create", href: "<?php echo CUtil::JSEscape($createSubtaskUrl)?>", onclick : function(event, item) {AddPopupSubtask(<?php echo $arResult["TASK"]["ID"]?>, event);} }
					,{ code: 'FAVORITE', inFavorite: <?=($inFavorite ? 'true' : 'false')?>, text : '<?=CUtil::JSEscape($favoriteItemMessage)?>', title : '<?=CUtil::JSEscape($favoriteItemMessage)?>', className : "task-menu-popup-item-favorite", onclick : function(event, item) {
							item.inFavorite = !item.inFavorite;

							if(!item.inFavorite)
							{
								item.text = '<?=GetMessage("TASKS_ADD_FAVORITE")?>';
							}
							else
							{
								item.text = '<?=GetMessage("TASKS_DELETE_FAVORITE")?>';
							}
							tasksDetailPartsNS.toggleFavorite(<?=intval($arResult["TASK"]["ID"])?>, item.inFavorite, this);
							this.popupWindow.close();
					} }
					<?
					if ($arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] === 'Y')
					{
						?>,{ code: 'ADD_TIMEMAN', text : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_TASK_TO_TIMEMAN")); ?>', title : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_TASK_TO_TIMEMAN_EX")); ?>', className : "menu-popup-item-add-to-tm", onclick : function() { var func = false; if (window.top.Add2Timeman) func = window.top.Add2Timeman; else if (window.Add2Timeman) func = window.Add2Timeman; if (func !== false) func (this, <?php echo $arResult["TASK"]["ID"]?>); } }<?php
					}
					?>

					<?$showPopupMenu = true;?>
				<?endif?>
			];

		</script>
		<?if($showPopupMenu):?>
			<a href="javascript:void(0)" class="webform-small-button task-small-button-menu" id="task-small-button-menu" onclick="return tasksDetailPartsNS.ShowActionMenu(this, <?php echo $arResult["TASK"]["ID"]?>, taskMenu);"><span class="webform-small-button-left"></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></a>
		<?endif?>
		<?
		if ($arResult['ALLOWED_ACTIONS']['ACTION_DELEGATE'])
		{
			$groupIdForSite = 'false';

			if (isset($_GET["GROUP_ID"]) && (intval($_GET["GROUP_ID"]) > 0))
				$groupIdForSite = (int) $_GET["GROUP_ID"];
			elseif (isset($arParams["GROUP_ID"]) && (intval($arParams["GROUP_ID"]) > 0))
				$groupIdForSite = (int) $arParams["GROUP_ID"];

			?><a 
				href="javascript: void(0);" 
				class="webform-small-button-link task-button-delegate-link" 
				onclick="
					tasksDetailPartsNS.ShowDelegatePopup(
						this, 
						<?php echo $arResult['TASK']['ID']; ?>,
						<?php echo $groupIdForSite; ?>
						)"
				><?php
					echo GetMessage('TASKS_DELEGATE_TASK');
			?></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
		{
			$editURL  = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $arResult["TASK"]["ID"], "action" => "edit"));
			if ($arResult["IS_IFRAME"])
			{
				$editURL .= ((strpos($editURL, "?") === false ? "?" : "&") ? "?" : "&")."IFRAME=Y";
			}
			?><a href="<?php echo $editURL?>" class="webform-small-button-link task-button-edit-link"><?php echo GetMessage("TASKS_EDIT_TASK")?></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_DEFER'])
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'defer'
				);
				return false;" href="javascript:void(0);" class="webform-small-button-link task-button-hold-link"><?php echo GetMessage("TASKS_DEFER_TASK")?></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_RENEW'])
		{
			?><a onclick="
				tasksDetailPartsNS.doAction(
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'renew'
				);
				return false;" href="javascript:void(0);" class="webform-small-button-link task-button-hold-link"><?php echo GetMessage("TASKS_RENEW_TASK")?></a><?php
		}

		if($arResult['ALLOWED_ACTIONS']['ACTION_REMOVE'])
		{
			?><a 
				href="?ACTION=delete&amp;<?=bitrix_sessid_get()?><?=((string) $arParams['CONTROLLER_ID'] ? "&amp;controller_id=".urlencode($arParams['CONTROLLER_ID']) : "")?>"
				class="webform-small-button-link task-button-delete-link" target="_top" onclick="tasksDetailPartsNS.onDeleteClick(event, <?php echo $arResult["TASK"]["ID"]?>);"><?php echo GetMessage("TASKS_DELETE_TASK")?></a><?php
		}
		?>

		<?if(is_array($arResult["RATING"]) && intval($arResult["TASK"]["ID"]) && $arParams['SHOW_RATING'] != 'N'):?>
			<span class="feed-inform-ilike task-inform-ilike-container">
				<?
				$APPLICATION->IncludeComponent(
					"bitrix:rating.vote",
					$arParams['RATING_TYPE'],
					Array(
						"ENTITY_TYPE_ID" => "TASK",
						"ENTITY_ID" => $arResult["TASK"]["ID"],
						"OWNER_ID" => $arResult["TASK"]["CREATED_BY"],
						"USER_VOTE" => $arResult["RATING"]["USER_VOTE"],
						"USER_HAS_VOTED" => $arResult["RATING"]["USER_HAS_VOTED"],
						"TOTAL_VOTES" => $arResult["RATING"]["TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arResult["RATING"]["TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arResult["RATING"]["TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arResult["RATING"]["TOTAL_VALUE"],
						"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
					),
					$component,
					array("HIDE_ICONS" => "Y")
				);
				?>
			</span>
		<?endif?>
</div>

<?php
if ($arResult['INNER_HTML'] !== 'Y')
{
	?>
	</div>
	<?php
}
?>

<script>
BX.ready(function(){

	BX.addCustomEvent(window.document, 'onTaskListTaskToggleFavorite', function(params){

		var way = params.way;

		if(typeof taskMenu != 'undefined')
		{
			for(var k in taskMenu)
			{
				if(typeof taskMenu[k].code != 'undefined')
				{
					if(taskMenu[k].code == 'ADD_FAVORITE')
					{
						taskMenu[k].skip = way;
					}
					else if(taskMenu[k].code == 'DELETE_FAVORITE')
					{
						taskMenu[k].skip = !way;
					}
				}
			}
		}
	});
});
</script>

<!-- =========================== end of buttons =========================== -->
