<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<!-- =========================== begin right sidebar =========================== -->

<?php
if ($arResult['INNER_HTML'] !== 'Y')
{
	?>
	<div id="task-detail-right-sidebar">
	<?php
}

if ($arResult['DEFER_LOAD'] === 'Y')
{
	?>
	<script>
		BX.message({
			TASKS_SIDEBAR_NO_ESTIMATE_TIME : '<?php echo GetMessageJS('TASKS_SIDEBAR_NO_ESTIMATE_TIME'); ?>'
		});
		BX.ready(function(){
			tasksDetailPartsNS.reloadRightSideBar(<?php echo (int) $arParams['TASK_ID']; ?>);
		});
	</script>

	<div class="sidebar-block task-detail-info">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">
			<div class="task-detail-info-users task-detail-info-director">
				<div class="task-detail-info-users-border"></div>
				<div class="task-detail-info-users-inner">
					<div class="task-detail-info-users-title"><span><?php echo GetMessage("TASKS_CREATOR")?></span></div>
					<div class="task-detail-info-users-list">
						<div class="task-detail-info-user">
							<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arResult["TASK"]["CREATED_BY"]))?>" class="task-detail-info-user-avatar"><?
								?><img src="<?=(isset($arResult["TASK"]["CREATED_BY_PHOTO"]) && strlen($arResult["TASK"]["CREATED_BY_PHOTO"]) > 0 ? $arResult["TASK"]["CREATED_BY_PHOTO"] : "/bitrix/images/1.gif")?>" width="30" height="30"><?
							?></a>
							<div class="task-detail-info-user-info">
								<div class="task-detail-info-user-name"><a href="<?php 
								echo CComponentEngine::MakePathFromTemplate(
									$arParams["PATH_TO_USER_PROFILE"], 
									array("user_id" => $arResult["TASK"]["CREATED_BY"]))?>" target="_top"><?php 
								echo tasksFormatName(
									$arResult["TASK"]["CREATED_BY_NAME"], 
									$arResult["TASK"]["CREATED_BY_LAST_NAME"], 
									$arResult["TASK"]["CREATED_BY_LOGIN"], 
									$arResult["TASK"]["CREATED_BY_SECOND_NAME"], 
									$arParams["NAME_TEMPLATE"],
									false);
									?></a></div>
								<?php if ($arResult["TASK"]["CREATED_BY_WORK_POSITION"]):?><div class="task-detail-info-user-position"><?php echo $arResult["TASK"]["CREATED_BY_WORK_POSITION"]?><?php else:?><div class="task-detail-info-user-position-empty"><?php endif?></div>
							</div>
						</div>
					</div>
				</div>
				<div class="task-detail-info-users-border"></div>
			</div>

			<div class="task-detail-info-users task-detail-info-responsible">
				<div class="task-detail-info-users-border"></div>
				<div class="task-detail-info-users-inner">
					<div class="task-detail-info-users-title"
						><span><?php
							echo GetMessage("TASKS_RESPONSIBLE");
						?></span></div>
					<div class="task-detail-info-users-list">
						<div class="task-detail-info-user">
							<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arResult["TASK"]["RESPONSIBLE_ID"]))?>" class="task-detail-info-user-avatar"><?
								?><img src="<?=(isset($arResult["TASK"]["RESPONSIBLE_PHOTO"]) && strlen($arResult["TASK"]["RESPONSIBLE_PHOTO"]) > 0 ? $arResult["TASK"]["RESPONSIBLE_PHOTO"] : "/bitrix/images/1.gif")?>" width="30" height="30"><?
							?></a>
							<div class="task-detail-info-user-info">
								<div class="task-detail-info-user-name"><a href="<?php 
									echo CComponentEngine::MakePathFromTemplate(
										$arParams["PATH_TO_USER_PROFILE"], 
										array("user_id" => $arResult["TASK"]["RESPONSIBLE_ID"]))
										?>" target="_top"><?php 
									echo tasksFormatName(
										$arResult["TASK"]["RESPONSIBLE_NAME"], 
										$arResult["TASK"]["RESPONSIBLE_LAST_NAME"], 
										$arResult["TASK"]["RESPONSIBLE_LOGIN"], 
										$arResult["TASK"]["RESPONSIBLE_SECOND_NAME"], 
										$arParams["NAME_TEMPLATE"],
										false);
									?></a></div>
								<?php if ($arResult["TASK"]["RESPONSIBLE_WORK_POSITION"]):?><div class="task-detail-info-user-position"><?php 
								echo $arResult["TASK"]["RESPONSIBLE_WORK_POSITION"]?><?php else:?><div class="task-detail-info-user-position-empty"><?php endif?></div>
							</div>
						</div>
					</div>
				</div>
				<div class="task-detail-info-users-border"></div>
			</div>

			<div class="task-detail-info-users<?php if (!sizeof($arResult["TASK"]["ACCOMPLICES"])):?> task-detail-info-users-empty<?php endif?> task-detail-info-assistants" id="task-detail-info-assistants">
				<div class="task-detail-info-users-border"></div>
				<div class="task-detail-info-users-inner">
					<div class="task-detail-info-users-title"
						><span><?php echo GetMessage("TASKS_SIDEBAR_ACCOMPLICES"); ?></span></div>
					<div class="task-detail-info-users-list" id="task-detail-assistants"></div>
				</div>
				<div class="task-detail-info-users-border"></div>
			</div>

			<div class="task-detail-info-users<?php if (!sizeof($arResult["TASK"]["AUDITORS"])):?> task-detail-info-users-empty<?php endif?> task-detail-info-auditors" id="task-detail-info-auditors">
				<div class="task-detail-info-users-border"></div>
				<div class="task-detail-info-users-inner">
					<div class="task-detail-info-users-title"
						><span><?php
							echo GetMessage("TASKS_SIDEBAR_AUDITORS");
						?></span>
					</div>
					<div class="task-detail-info-users-list" id="task-detail-auditors">
					</div>
				</div>
			</div>

			<div class="task-detail-info-users-links">
			</div>
		</div>
		<i class="r0"></i><i class="r1"></i><i class="r2"></i>
	</div>
	<?php
}
else
{
	$groupIdForSite = 'false';

	if (isset($_GET["GROUP_ID"]) && (intval($_GET["GROUP_ID"]) > 0))
		$groupIdForSite = (int) $_GET["GROUP_ID"];
	elseif (isset($arParams["GROUP_ID"]) && (intval($arParams["GROUP_ID"]) > 0))
		$groupIdForSite = (int) $arParams["GROUP_ID"];

	?>
	<script>
		BX.message({
			TASKS_SIDEBAR_NO_ESTIMATE_TIME : '<?php echo GetMessageJS('TASKS_SIDEBAR_NO_ESTIMATE_TIME'); ?>'
		});

		BX.ready(function(){
			if (BX("task-detail-info-auditors-add"))
			{
				BX.bind(
					BX("task-detail-info-auditors-change"),
					"click",
					tasksDetailPartsNS.getMembersAddChangeFunction(
						'AUDITORS',
						BX("task-detail-info-auditors-change"),
						<?php echo $arResult['TASK']['ID']; ?>,
						<?php echo $groupIdForSite; ?>,
						[<?php echo implode(', ', $arResult['TASK']['AUDITORS']); ?>]
					)
				);
				BX.bind(
					BX("task-detail-info-auditors-add"), 
					"click", 
					tasksDetailPartsNS.getMembersAddChangeFunction(
						'AUDITORS',
						BX("task-detail-info-auditors-add"),
						<?php echo $arResult['TASK']['ID']; ?>,
						<?php echo $groupIdForSite; ?>,
						[<?php echo implode(', ', $arResult['TASK']['AUDITORS']); ?>]
					)
				);
			}

			if (BX("task-detail-info-assistants-add"))
			{
				BX.bind(
					BX("task-detail-info-assistants-change"), 
					"click", 
					tasksDetailPartsNS.getMembersAddChangeFunction(
						'ACCOMPLICES',
						BX("task-detail-info-assistants-change"),
						<?php echo $arResult['TASK']['ID']; ?>,
						<?php echo $groupIdForSite; ?>,
						[<?php echo implode(', ', $arResult['TASK']['ACCOMPLICES']); ?>]
					)
				);
				BX.bind(
					BX("task-detail-info-assistants-add"), 
					"click", 
					tasksDetailPartsNS.getMembersAddChangeFunction(
						'ACCOMPLICES',
						BX("task-detail-info-assistants-add"),
						<?php echo $arResult['TASK']['ID']; ?>,
						<?php echo $groupIdForSite; ?>,
						[<?php echo implode(', ', $arResult['TASK']['ACCOMPLICES']); ?>]
					)
				);
			}

			<?php
			if ($arResult['IS_IFRAME'] && ($arParams['FIRE_ON_CHANGED_EVENT'] === 'Y'))
			{
				$arPaths = array(
					"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
					"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
				);

				?>
				var iframePopup = window.top.BX.TasksIFrameInst;
				if (iframePopup)
				{
					window.top.BX.TasksIFrameInst.onTaskChanged(<?php
						$bSkipJsMenu = false;
						$bIsIe = false;
						$userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
						if (strpos($userAgent, "opera") === false && strpos($userAgent, "msie") !== false)
							$bIsIe = true;

						if (isset($arResult["IS_IFRAME"]) && ($arResult["IS_IFRAME"] === true) && $bIsIe)
							$bSkipJsMenu = true;

						tasksRenderJSON($arResult["TASK"], $arResult["TASK"]["CHILDREN_COUNT"],
							$arPaths, true, true, true, $arParams["NAME_TEMPLATE"], 
							$arAdditionalFields = array(), $bSkipJsMenu
						);
					?>);
				}
				<?php
			}
			?>
		});
	</script>

	<div class="sidebar-block task-detail-info">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">

			<?if((is_array($arParams['DISPLAY_DATA']) && in_array('CREATOR', $arParams['DISPLAY_DATA']))):?>

				<div class="task-detail-info-users task-detail-info-director">
					<div class="task-detail-info-users-border"></div>

						<div class="task-detail-info-users-inner">
							<div class="task-detail-info-users-title"><span><?php echo GetMessage("TASKS_CREATOR")?></span></div>
							<div class="task-detail-info-users-list">
								<div class="task-detail-info-user">
									<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arResult["TASK"]["CREATED_BY"]))?>" class="task-detail-info-user-avatar"><?
										?><img src="<?=(isset($arResult["TASK"]["CREATED_BY_PHOTO"]) && strlen($arResult["TASK"]["CREATED_BY_PHOTO"]) > 0 ? $arResult["TASK"]["CREATED_BY_PHOTO"] : "/bitrix/images/1.gif")?>" width="30" height="30"><?
									?></a>
									<div class="task-detail-info-user-info">
										<div class="task-detail-info-user-name"><a href="<?php 
										echo CComponentEngine::MakePathFromTemplate(
											$arParams["PATH_TO_USER_PROFILE"], 
											array("user_id" => $arResult["TASK"]["CREATED_BY"]))?>" target="_top"><?php 
										echo tasksFormatName(
											$arResult["TASK"]["CREATED_BY_NAME"], 
											$arResult["TASK"]["CREATED_BY_LAST_NAME"], 
											$arResult["TASK"]["CREATED_BY_LOGIN"], 
											$arResult["TASK"]["CREATED_BY_SECOND_NAME"], 
											$arParams["NAME_TEMPLATE"],
											false);
											?></a></div>
										<?php if ($arResult["TASK"]["CREATED_BY_WORK_POSITION"]):?><div class="task-detail-info-user-position"><?php echo $arResult["TASK"]["CREATED_BY_WORK_POSITION"]?><?php else:?><div class="task-detail-info-user-position-empty"><?php endif?></div>
									</div>
								</div>
							</div>
						</div>
						<div class="task-detail-info-users-border"></div>
				</div>

			<?endif?>

			<?if((is_array($arParams['DISPLAY_DATA']) && in_array('RESPONSIBLE', $arParams['DISPLAY_DATA']))):?>

				<div class="task-detail-info-users task-detail-info-responsible">
					<div class="task-detail-info-users-border"></div>
					<div class="task-detail-info-users-inner">
						<div class="task-detail-info-users-title"
							<?php
							if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
							{
								?>
								onclick="tasksDetailPartsNS.showResponsibleChangePopup(
									this,
									<?php echo (int) $arResult['TASK']['ID']; ?>,
									<?php echo $groupIdForSite; ?>,
									<?php echo (int) $arResult['TASK']['RESPONSIBLE_ID']; ?>
								);"
								<?php
							}
							?>
							><span><?php
								echo GetMessage("TASKS_RESPONSIBLE");
							?></span><?php
							if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
							{
								?><a class="webform-field-action-link" 
									id="task-detail-responsible-change" 
									href="javascript:void(0);"><?php echo GetMessage("TASKS_SIDEBAR_CHANGE");
								?></a><?php
							}
						?></div>
						<div class="task-detail-info-users-list">
							<div class="task-detail-info-user">

								<?if(intval($arResult["TASK"]["RESPONSIBLE_ID"])):?>

									<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arResult["TASK"]["RESPONSIBLE_ID"]))?>" class="task-detail-info-user-avatar"><?
										?><img src="<?=(isset($arResult["TASK"]["RESPONSIBLE_PHOTO"]) && strlen($arResult["TASK"]["RESPONSIBLE_PHOTO"]) > 0 ? $arResult["TASK"]["RESPONSIBLE_PHOTO"] : "/bitrix/images/1.gif")?>" width="30" height="30"><?
									?></a>

								<?else:?>
									<div class="task-detail-info-user-avatar">
										<img src="/bitrix/images/1.gif" width="30" height="30" />
									</div>
								<?endif?>

								<div class="task-detail-info-user-info">
									<div class="task-detail-info-user-name">
										<?if(intval($arResult["TASK"]["RESPONSIBLE_ID"])):?>
											<a href="<?php 
												echo CComponentEngine::MakePathFromTemplate(
													$arParams["PATH_TO_USER_PROFILE"], 
													array("user_id" => $arResult["TASK"]["RESPONSIBLE_ID"]))
													?>" target="_top"><?php 
												echo tasksFormatName(
													$arResult["TASK"]["RESPONSIBLE_NAME"], 
													$arResult["TASK"]["RESPONSIBLE_LAST_NAME"], 
													$arResult["TASK"]["RESPONSIBLE_LOGIN"], 
													$arResult["TASK"]["RESPONSIBLE_SECOND_NAME"], 
													$arParams["NAME_TEMPLATE"],
													false);
												?></a>
										<?else:?>
											<?=GetMessage('TASKS_NEW_RESPONSIBLE')?>
										<?endif?>
									</div>

									<?if(intval($arResult["TASK"]["RESPONSIBLE_ID"])):?>
										<?php if ($arResult["TASK"]["RESPONSIBLE_WORK_POSITION"]):?><div class="task-detail-info-user-position"><?php 
										echo $arResult["TASK"]["RESPONSIBLE_WORK_POSITION"]?><?php else:?><div class="task-detail-info-user-position-empty"><?php endif?></div>
									<?endif?>
								</div>
							</div>
						</div>
					</div>
					<div class="task-detail-info-users-border"></div>
				</div>

			<?endif?>

			<table class="task-detail-info-layout" cellspacing="0">

				<?foreach($arParams['DISPLAY_DATA'] as $dataPieceName):?>

					<?if(in_array($dataPieceName, $arResult['TABLE_ROWS_MAP'])):?>

						<?
						switch($dataPieceName)
						{
							case 'STATUS':

								?>
									<tr>
										<td class="task-detail-info-layout-name"><?php echo GetMessage("TASKS_SIDEBAR_STATUS")?>:</td>
										<td class="task-detail-info-layout-value">
											<span class="task-detail-info-status task-detail-info-status-in-progress"><span class="task-detail-info-status-text"><?php echo GetMessage("TASKS_STATUS_".$arResult["TASK"]["REAL_STATUS"])?></span>
												<span class="task-detail-info-status-date">
													<?php if ($arResult["TASK"]["REAL_STATUS"] != 4 && $arResult["TASK"]["REAL_STATUS"] != 5):?>
														<?php echo GetMessage("TASKS_SIDEBAR_START_DATE")?>
													<?php endif?>
													<?php echo tasksFormatDate($arResult["TASK"]["STATUS_CHANGED_DATE"])?>
													<?php if(date("H:i", strtotime($arResult["TASK"]["STATUS_CHANGED_DATE"])) != "00:00"):?>
														<?php echo FormatDateFromDB($arResult["TASK"]["STATUS_CHANGED_DATE"], CSite::getTimeFormat())?>
													<?php endif?>
												</span>
											</span>
										</td>
									</tr>
								<?

								break;

							case 'PRIORITY':

								?>
									<tr <?=((!$arResult['SHOW_ACCOMPLICES'] || empty($arResult["TASK"]["ACCOMPLICES"])) && (!$arResult['SHOW_AUDITORS'] || empty($arResult["TASK"]["AUDITORS"])) ? 'class="task-detail-last-row"' : '')?>>
										<td class="task-detail-info-layout-name"><?php echo GetMessage("TASKS_PRIORITY")?>:</td>
										<td class="task-detail-info-layout-value">
											<span class="task-detail-priority task-detail-priority-<?php
												echo $arResult['TASK']['PRIORITY'];

												if ( ! $arResult['ALLOWED_ACTIONS']['ACTION_EDIT'] )
												{
													?> task-detail-priority-readonly<?php
												}
												?>" id="task-detail-priority"<?php
												if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
												{
													?> onclick="return tasksDetailPartsNS.ShowPriorityPopupDetail(<?php echo $arResult["TASK"]["ID"]?>, this, <?php echo $arResult["TASK"]["PRIORITY"]?>);"<?php
												}
												?>><span class="task-detail-priority-icon"></span><span class="task-detail-priority-text"><?php
													echo GetMessage("TASKS_PRIORITY_".$arResult["TASK"]["PRIORITY"]);
											?></span></span>
										</td>
									</tr>
								<?

								break;

							case 'DEADLINE':

								if (
									$arResult['ALLOWED_ACTIONS']['ACTION_EDIT']
									|| $arResult['ALLOWED_ACTIONS']['ACTION_CHANGE_DEADLINE']
									|| $arResult["TASK"]["DEADLINE"]
								)
								{
								?>
								<tr>
									<td class="task-detail-info-layout-name" style=""><?php echo GetMessage("TASKS_QUICK_DEADLINE")?>:</td>
									<td class="task-detail-info-layout-value">
										<span class="<?php if ($arResult["TASK"]["DEADLINE"]):?>task-detail-deadline<?php endif?> <?php
											if (
												$arResult['ALLOWED_ACTIONS']['ACTION_EDIT']
												|| $arResult['ALLOWED_ACTIONS']['ACTION_CHANGE_DEADLINE']
											)
											{
												?>webform-field-action-link" 
												onclick="
												BX.calendar({
													node: this, 
													field: 'task-deadline-hidden', 
													form: '', 
													bTime: true, 
													//currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset()*60, 
													value: BX.CJSTask.ui.getInputDateTimeValue(BX('task-deadline-hidden')),
													bHideTimebar: false,
													callback_after: function(value) {
														tasks_funcOnChangeOfSomeDateFields(BX('task-deadline-hidden'));
													}
												});
												"<?php
											}
											else
											{
												echo '"';
											};
											?> 
											id="task-detail-deadline"
											style="display:inline; line-height:19px;"><?php

											if ($arResult["TASK"]["DEADLINE"])
											{
												echo tasksFormatDate($arResult["TASK"]["DEADLINE"]);
												if(convertTimeToMilitary($arResult["TASK"]["DEADLINE"], CSite::GetDateFormat(), "HH:MI") != "00:00")
												{
													echo " ".convertTimeToMilitary($arResult["TASK"]["DEADLINE"], CSite::GetDateFormat(), CSite::GetTimeFormat());
												}
											}
											else
											{
												echo GetMessage("TASKS_SIDEBAR_DEADLINE_NO");
											}
										?></span><?php
										if (
											$arResult['ALLOWED_ACTIONS']['ACTION_EDIT']
											|| $arResult['ALLOWED_ACTIONS']['ACTION_CHANGE_DEADLINE']
										)
										{
											?><input type="text" style="display:none;" id="task-deadline-hidden" 
												value="<?php echo $arResult["TASK"]["DEADLINE"]?>"
												data-default-hour="<?=intval($arParams['COMPANY_WORKTIME']['END']['H'])?>" 
												data-default-minute="<?=intval($arParams['COMPANY_WORKTIME']['END']['M'])?>"

											/><span class="task-deadline-delete"<?php if (!$arResult["TASK"]["DEADLINE"]):?>style="display: none;"<?php endif?> 
												onclick="tasksDetailPartsNS.ClearDeadline(<?php echo $arResult["TASK"]["ID"]?>, this)"><?php
										}
									?></td>
								</tr>
								<?php
								}

								break;

							case 'TIME_ESTIMATE':

								if ($arResult['TASK']['ALLOW_TIME_TRACKING'] === 'Y')
								{
									?>
									<tr>
										<td class="task-detail-info-layout-name" style=""><?php echo GetMessage('TASKS_SIDEBAR_TIME_ESTIMATE'); ?>:</td>
										<td class="task-detail-info-layout-value">
											<span 
												id="task-detail-estimate-time-<?php echo (int) $arResult['TASK']['ID']; ?>"
												class=""><?php

												if ($arResult['TASK']['TIME_ESTIMATE'] > 0)
												{
													echo sprintf(
														'%02d:%02d:%02d',
														floor($arResult['TASK']['TIME_ESTIMATE']  / 3600),		// hours
														floor($arResult['TASK']['TIME_ESTIMATE'] / 60) % 60,	// minutes
														$arResult['TASK']['TIME_ESTIMATE'] % 60					// seconds
													);
												}
												else
													echo GetMessage('TASKS_SIDEBAR_NO_ESTIMATE_TIME');
											?></span>
										</td>
									</tr>
									<tr>
										<td class="task-detail-info-layout-name" style=""><?php echo GetMessage('TASKS_SIDEBAR_TIME_SPENT'); ?>:</td>
										<td class="task-detail-info-layout-value">
											<span class="" 
												id="task-detail-spent-time-<?php echo (int) $arResult['TASK']['ID']; ?>"
												style="display:inline; line-height:19px;"><?php

												if ($arResult['TIMER_IS_RUNNING_FOR_CURRENT_USER'] === 'Y')
													$timeRunned = $arResult['TASK']['TIME_SPENT_IN_LOGS'] + $arResult['TIMER']['RUN_TIME'];
												else
													$timeRunned = $arResult['TASK']['TIME_SPENT_IN_LOGS'];

												echo sprintf(
													'%02d:%02d:%02d',
													floor($timeRunned / 3600),		// hours
													floor($timeRunned / 60) % 60,	// minutes
													$timeRunned % 60				// seconds
												);
											?></span>
										</td>
									</tr>
									<?php
								}

								break;

							case 'DATE_PLAN':

								$amPmFormatSymbol = 'a';
								if (strpos(FORMAT_DATETIME, 'TT') !== false)
									$amPmFormatSymbol = 'A';

								if ($arResult["TASK"]["START_DATE_PLAN"]):?>
								<tr>
									<td class="task-detail-info-layout-name"><?php echo GetMessage("TASKS_SIDEBAR_START")?>:</td>
									<td class="task-detail-info-layout-value">
										<span class="task-detail-start-date">
											<?php
											echo tasksFormatDate($arResult["TASK"]["START_DATE_PLAN"]);
											if (IsAmPmMode()) :?>
												<?php if(date("g:i a", strtotime($arResult["TASK"]["START_DATE_PLAN"])) != "12:00 am"):?>
													<?php echo date("g:i " . $amPmFormatSymbol, strtotime($arResult["TASK"]["START_DATE_PLAN"]))?>
												<?php endif?>
											<?php else :?>
												<?php if(date("H:i", strtotime($arResult["TASK"]["START_DATE_PLAN"])) != "00:00"):?>
													<?php echo date("H:i", strtotime($arResult["TASK"]["START_DATE_PLAN"]))?>
												<?php endif?>
											<?php endif?>
										</span>
									</td>
								</tr>
								<?php endif?>

								<?php if ($arResult["TASK"]["END_DATE_PLAN"]):?>
								<tr>
									<td class="task-detail-info-layout-name"><?php echo GetMessage("TASKS_SIDEBAR_FINISH")?>:</td>
									<td class="task-detail-info-layout-value">
										<span class="task-detail-end-date">
											<?php echo tasksFormatDate($arResult["TASK"]["END_DATE_PLAN"])?>
											<?php if (IsAmPmMode()) :?>
												<?php if(date("g:i a", strtotime($arResult["TASK"]["END_DATE_PLAN"])) != "12:00 am"):?>
													<?php echo date("g:i " . $amPmFormatSymbol, strtotime($arResult["TASK"]["END_DATE_PLAN"]))?>
												<?php endif?>
											<?php else :?>
												<?php if(date("H:i", strtotime($arResult["TASK"]["END_DATE_PLAN"])) != "00:00"):?>
													<?php echo date("H:i", strtotime($arResult["TASK"]["END_DATE_PLAN"]))?>
												<?php endif?>
											<?php endif?>
										</span>
									</td>
								</tr>
								<?php endif?>
								<?

								break;

							case 'MARK':

								?>
									<tr>
										<td class="task-detail-info-layout-name"><?php echo GetMessage("TASKS_MARK")?>:</td>
										<td class="task-detail-info-layout-value task-detail-grade-value"><span
											class="task-detail-grade<?php
												if($arResult["TASK"]["MARK"] == "P")
												{
													?> task-detail-grade-plus<?php
												}
												elseif($arResult["TASK"]["MARK"] == "N")
												{
													?> task-detail-grade-minus<?php
												}
												else
												{
													?> task-detail-grade-none<?php
												}

												if ( ! $arResult['ALLOWED_ACTIONS']['ACTION_EDIT'] )
												{
													?> task-detail-grade-readonly<?php
												}
												?>"
											id="task-detail-grade"
											<?php

											if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
											{
												?> onclick="return tasksDetailPartsNS.ShowGradePopupDetail(
													<?php echo $arResult["TASK"]["ID"]?>,
													this,
													{
														listValue : '<?php
															if ($arResult["TASK"]["MARK"] == "N" || $arResult["TASK"]["MARK"] == "P")
																echo $arResult["TASK"]["MARK"];
															else
																echo "NULL";
														?>'
													}
												);"<?php
											}
											?>
											><span class="task-detail-grade-icon"></span
												><span class="task-detail-grade-text"><?php
													if ($arResult["TASK"]["MARK"])
														echo GetMessage("TASKS_MARK_".$arResult["TASK"]["MARK"]);
													else
														echo GetMessage("TASKS_MARK_NONE");
												?></span
											></span
										></td>
									</tr>
								<?

								break;

							case 'IN_REPORT':

								?>
									<tr>
										<td class="task-detail-info-layout-name"><?php echo GetMessage("TASKS_SIDEBAR_IN_REPORT")?>:</td>
										<td class="task-detail-info-layout-value"><span class="task-detail-report"><?php
										if ($arResult["TASK"]["SUBORDINATE"] == "Y" && $arResult["TASK"]["RESPONSIBLE_ID"] != $USER->GetID())
										{
											?><a class="webform-field-action-link<?php if($arResult["TASK"]["ADD_IN_REPORT"] == "Y"):?> selected<?php endif?> task-detail-report-yes"
												id="task-detail-report-yes"
												onclick="SetReport(<?php echo $arResult["TASK"]["ID"]?>, true)"
												href="javascript: void(0);"><?php
													echo GetMessage("TASKS_SIDEBAR_IN_REPORT_YES");
											?></a><a class="webform-field-action-link<?php if($arResult["TASK"]["ADD_IN_REPORT"] != "Y"):?> selected<?php endif?> task-detail-report-no" 
												id="task-detail-report-no"
												onclick="SetReport(<?php echo $arResult["TASK"]["ID"]?>, false)"
												href="javascript: void(0);"><?php
													echo GetMessage("TASKS_SIDEBAR_IN_REPORT_NO");
											?></a><?php
										}
										else
										{
											if ($arResult["TASK"]["ADD_IN_REPORT"] == "Y")
												echo GetMessage("TASKS_SIDEBAR_IN_REPORT_YES");
											else
												echo GetMessage("TASKS_SIDEBAR_IN_REPORT_NO");
										}
										?></span></td>
									</tr>
								<?

								break;

							case 'TEMPLATE':

								$arTemplate = false;

								if (isset($arResult['TASK']['FORKED_BY_TEMPLATE']))
									$arTemplate = $arResult['TASK']['FORKED_BY_TEMPLATE'];
								elseif (isset($arResult["TASK"]["TEMPLATE"]))
									$arTemplate = $arResult['TASK']['TEMPLATE'];

								if (($arTemplate || isset($arResult['TASK']['FORKED_BY_TEMPLATE_ID'])) && !empty($arTemplate["REPLICATE_PARAMS"]))
								{
									?>
									<tr>
										<td class="task-detail-info-layout-name"><?php
											echo GetMessage("TASKS_SIDEBAR_REPEAT") . ':';
										?></td>
										<td class="task-detail-info-layout-value"><span class="task-detail-periodicity"><?php
											if ($arTemplate)
											{
												echo tasksPeriodToStr($arTemplate["REPLICATE_PARAMS"]);
												?> (<a href="<?php
													echo CComponentEngine::MakePathFromTemplate(
														$arParams["PATH_TO_TEMPLATES_TEMPLATE"],
														array("template_id" => $arTemplate["ID"], "action" => "edit")
													);
												?>" class="task-detail-periodicity-link" target="_top"><?php
													echo GetMessage("TASKS_SIDEBAR_TEMPLATE");
												?></a>)<?php
											}
											else
												echo GetMessage('TASKS_SIDEBAR_TEMPLATE_NOT_EXISTS');
										?></span></td>
									</tr>
									<?php
								}

								break;

						}
						?>

					<?endif?>

				<?endforeach?>

			</table>

			<?if($arResult['SHOW_ACCOMPLICES']):?>

				<div class="task-detail-info-users<?php if (!sizeof($arResult["TASK"]["ACCOMPLICES"])):?> task-detail-info-users-empty<?php endif?> task-detail-info-assistants" id="task-detail-info-assistants">
					<div class="task-detail-info-users-border"></div>
					<div class="task-detail-info-users-inner">
						<div class="task-detail-info-users-title"
							><span><?php echo GetMessage("TASKS_SIDEBAR_ACCOMPLICES"); ?></span><?php
							if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
							{
								?><a class="webform-field-action-link" 
									id="task-detail-info-assistants-change" href=""><?php
										echo GetMessage("TASKS_EDIT_TASK");
								?></a><?php
							}
						?></div>
						<div class="task-detail-info-users-list" id="task-detail-assistants">
							<?php
								if ($arResult["TASK"]["ACCOMPLICES"]):
									$rsAccomplices = CUser::GetList(($b = "LOGIN"), ($o = "ASC"), array("ID" => implode("|", $arResult["TASK"]["ACCOMPLICES"])));
									while($arAccomplice = $rsAccomplices->GetNext()):
							?>
							<div class="task-detail-info-user">
								<div class="task-detail-info-user-name"><a href="<?php 
									echo CComponentEngine::MakePathFromTemplate(
										$arParams["PATH_TO_USER_PROFILE"], 
										array("user_id" => $arAccomplice["ID"]))?>"><?php 
									echo tasksFormatName(
										$arAccomplice["NAME"], 
										$arAccomplice["LAST_NAME"], 
										$arAccomplice["LOGIN"], 
										$arAccomplice["SECOND_NAME"], 
										$arParams["NAME_TEMPLATE"],
										false
										)?></a></div>
								<?php if ($arAccomplice["WORK_POSITION"]):?><div class="task-detail-info-user-position"><?php echo $arAccomplice["WORK_POSITION"]?><?php else:?><div class="task-detail-info-user-position-empty"><?php endif?></div>
							</div>
							<?php endwhile?>
							<?php endif?>
						</div>
					</div>
					<div class="task-detail-info-users-border"></div>
				</div>

			<?endif?>

			<?if($arResult['SHOW_AUDITORS']):?>

				<div class="task-detail-info-users<?php if (!sizeof($arResult["TASK"]["AUDITORS"])):?> task-detail-info-users-empty<?php endif?> task-detail-info-auditors" id="task-detail-info-auditors">
					<div class="task-detail-info-users-border"></div>
					<div class="task-detail-info-users-inner">
						<div class="task-detail-info-users-title"
							><span><?php
								echo GetMessage("TASKS_SIDEBAR_AUDITORS");
							?></span><?php
							if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
							{
								?><a class="webform-field-action-link" 
									id="task-detail-info-auditors-change" href=""><?php
									echo GetMessage("TASKS_EDIT_TASK");
								?></a><?php
							}
							else
							{
								?>
								<div id="task-detail-info-stop-watch"
									<?php
									if ( ! in_array($arResult['LOGGED_IN_USER'], $arResult["TASK"]["AUDITORS"]) )
									{
										?>style="display: none;"<?php
									}
									?>
									>
									<?if(is_array($arParams['DISPLAY_DATA']) && in_array('STOP_WATCH', $arParams['DISPLAY_DATA'])):?>
										<span  
											class="webform-field-action-link" 
											href="javascript:void(0);"
											onclick="
												if (confirm(BX.message('TASKS_SIDEBAR_STOP_WATCH_CONFIRM')))
													tasksDetailPartsNS.stopWatch(<?php echo (int) $arResult['TASK']['ID']; ?>);
											"
											><?php
												echo GetMessage("TASKS_SIDEBAR_STOP_WATCH");
										?></span>
									<?endif?>
								</div><?php
							}
							?></div>
						<div class="task-detail-info-users-list" id="task-detail-auditors">
							<?php
							if ($arResult["TASK"]["AUDITORS"])
							{
								$rsAuditors = CUser::GetList(($b = "LOGIN"), ($o = "ASC"), array("ID" => implode("|", $arResult["TASK"]["AUDITORS"])));
								while($arAuditor = $rsAuditors->GetNext())
								{
									$htmlId = ' id="task-detail-info-user-auditor-' . (int) $arAuditor['ID'] . '-container" ';
									?>
									<div class="task-detail-info-user" <?php echo $htmlId; ?>>
										<div class="task-detail-info-user-name"><a href="<?php 
											echo CComponentEngine::MakePathFromTemplate(
												$arParams["PATH_TO_USER_PROFILE"], 
												array("user_id" => $arAuditor["ID"]))?>"><?php 
											echo tasksFormatName(
												$arAuditor["NAME"], 
												$arAuditor["LAST_NAME"], 
												$arAuditor["LOGIN"], 
												$arAuditor["SECOND_NAME"], 
												$arParams["NAME_TEMPLATE"],
												false
												)?></a></div>
										<?php if ($arAuditor["WORK_POSITION"]):?><div class="task-detail-info-user-position"><?php echo $arAuditor["WORK_POSITION"]?><?php else:?><div class="task-detail-info-user-position-empty"><?php endif?></div>
									</div>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>

			<?endif?>

			<?if($arParams['SHOW_EDIT_MEMBERS'] != 'N'):?>

				<div class="task-detail-info-users-links">
					<?php
					if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
					{
						?>
						<div class="task-detail-info-users-link"<?php if (count($arResult["TASK"]["ACCOMPLICES"])):?> style="display:none;"<?php endif?>
							><a class="webform-field-action-link" id="task-detail-info-assistants-add" href=""><?php
								echo GetMessage("TASKS_SIDEBAR_ADD_ACCOMPLICES");
							?></a
						></div>
						<div class="task-detail-info-users-link"<?php if (count($arResult["TASK"]["AUDITORS"])):?> style="display:none;"<?php endif?>
							><a class="webform-field-action-link" id="task-detail-info-auditors-add" href=""><?php
								echo GetMessage("TASKS_SIDEBAR_ADD_AUDITORS");
							?></a
						></div>
						<?php
					}
					else
					{
						?>
						<div id="task-detail-info-start-watch-block" 
							class="task-detail-info-users-link"
							<?php
							if (in_array($arResult['LOGGED_IN_USER'], $arResult["TASK"]["AUDITORS"]))
							{
								?> style="display:none;"<?php
							}
							?>
							><a id="task-detail-info-start-watch" 
								class="webform-field-action-link"
								onclick="tasksDetailPartsNS.startWatch(<?php echo (int) $arResult['TASK']['ID']; ?>);" 
								href="javascript:void(0);"><?php
									echo GetMessage('TASKS_SIDEBAR_START_WATCH');
							?></a
						></div>
						<?php
					}
					?>
				</div>

			<?endif?>

		</div>
		<i class="r0"></i><i class="r1"></i><i class="r2"></i>
	</div>
	<?php
}

if ($arResult['INNER_HTML'] !== 'Y')
{
	?>
	</div>
	<?php
}
?>
<!-- =========================== end of right sidebar =========================== -->
<?php
