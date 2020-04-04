<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->ShowViewContent("task_menu");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$bodyClass = $bodyClass ? $bodyClass." page-one-column" : "page-one-column";
$APPLICATION->SetPageProperty("BodyClass", $bodyClass);

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'.default',
	array(
		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],
		'SECTION_URL_PREFIX' => '',
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
		'MARK_SECTION_PROJECTS' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => true)
);

?>
<div style="background-color: #fff; min-width: 800px; max-width:1145px; margin: 0 auto; padding: 7px 15px 15px;">
	<?php
	if ( ! empty($arResult['PROJECTS']) )
	{
		?>
		<div class="task-direct-wrap">
			<table class="task-direct-table task-project-table">
				<tr class="task-direct-title">
					<td class="task-direct-name task-direct-cell"><?php
						echo GetMessage('TASKS_PROJECTS_WITH_MY_MEMBERSHIP');
					?></td>
					<td class="task-direct-do task-direct-cell"><span class="task-direct-title-text"><?php
						echo GetMessage('TASKS_PROJECTS_TASK_IN_WORK');
					?></span></td>
					<td class="task-direct-help task-direct-cell"><span class="task-direct-title-text"><?php
						echo GetMessage('TASKS_PROJECTS_TASK_COMPLETE');
					?></span></td>
					<td class="task-direct-instructed task-direct-cell"><span class="task-direct-title-text"><?php
						echo GetMessage('TASKS_PROJECTS_TASK_ALL');
					?></span></td>
				</tr>
				<tr class="task-direct-total">
					<td class="task-direct-total-title task-direct-cell"><?php
						echo GetMessage('TASKS_PROJECTS_SUMMARY');
					?></td>
					<td class="task-direct-cell">
						<span class="task-direct-number-block">
							<span class="task-direct-number"><?php
								echo $arResult['TOTALS']['IN_WORK'];
							?></span>
						</span>
					</td>
					<td class="task-direct-cell">
						<span class="task-direct-number-block">
							<span class="task-direct-number"><?php
								echo $arResult['TOTALS']['COMPLETE'];
							?></span>
						</span>
					</td>
					<td class="task-direct-cell">
						<span class="task-direct-number-block">
							<span class="task-direct-number"><?php
								echo $arResult['TOTALS']['ALL'];
							?></span>
						</span>
					</td>
				</tr>

				<?php
				$cls = 'task-direct-white-row';
				foreach ($arResult['PROJECTS'] as $groupId => $arProject)
				{
					if ($cls === 'task-direct-white-row')
						$cls = 'task-direct-grey-row';
					else
						$cls = 'task-direct-white-row';

					$listId = 'tasks_project_list_' . $arProject['ID'];

					?>
					<tr class="task-direct-responsible <?php echo $cls; ?>">
						<td class="task-direct-cell">
							<div class="task-direct-respons-block">
								<div class="profile-menu-group ">
									<?=$arProject['IMAGE_HTML']?>
								</div>
								<span class="task-direct-respons-alignment"></span
								><span class="task-direct-respons-right"
									><a href="<?php echo $arProject['PATHES']['IN_WORK']; ?>" class="task-direct-respons-name"><?php
										echo $arProject['TITLE'];
									?></a><span class="task-direct-respons-post"><?php
									if ($arProject['HEADS_COUNT'] > 1)
										echo GetMessage('TASKS_PROJECTS_HEADS');
									else
										echo GetMessage('TASKS_PROJECTS_HEAD');
									?></span><?php
									foreach ($arProject['HEADS'] as $arHead)
									{
										?><a href="<?php echo $arHead['HREF']; ?>" class="task-project-director" 
											title="<?php echo $arHead['FORMATTED_NAME'] ?>"
											<?php
											if ($arHead['PHOTO_SRC'])
											{
												?>style="background: url('<?php echo $arHead['PHOTO_SRC']; ?>') no-repeat center center; background-size: cover;"<?php
											}
											?>
										></a><?php
									}
									?><span class="task-project-party"><?php
										if ($arProject['NOT_HEADS_COUNT'])
										{
											echo CTasksTools::getMessagePlural(
												$arProject['NOT_HEADS_COUNT'],
												'TASKS_PROJECTS_MEMBERS',
												array(
													'#SPAN#'  => '<span id="' . $listId . '" class="task-project-party-list">',
													'#COUNT#' =>  $arProject['NOT_HEADS_COUNT'],
													'#/SPAN#' => '</span>'
												)
											);
										}
									?></span>
									<script type="text/javascript">
									(function(){
										var x1 = new tasksProjectsOverviewNS.userPopupList(<?php echo $arProject['MEMBERS_FOR_JS']; ?>);
										BX.bind(BX('<?php echo $listId; ?>'), "click", BX.proxy(x1.showEmployees, x1));
									})();
									</script>
								</span>
							</div>
						</td>
						<td class="task-direct-cell">
							<span class="task-direct-number-block">
								<a href="<?php echo $arProject['PATHES']['IN_WORK']; ?>" class="task-direct-number"><?php
										echo $arProject['COUNTERS']['IN_WORK'];
								?></a><?php
								if ($arProject['COUNTERS']['EXPIRED'])
								{
									?><span class="task-direct-counter"><?php echo $arProject['COUNTERS']['EXPIRED']; ?></span><?php
								}
								?>
							</span>
						</td>
						<td class="task-direct-cell">
							<span class="task-direct-number-block">
								<a href="<?php echo $arProject['PATHES']['COMPLETE']; ?>" class="task-direct-number"><?php
										echo $arProject['COUNTERS']['COMPLETE'];
								?></a>
							</span>
						</td>
						<td class="task-direct-cell">
							<span class="task-direct-number-block">
								<a href="<?php echo $arProject['PATHES']['ALL']; ?>" class="task-direct-number"><?php
										echo $arProject['COUNTERS']['ALL'];
								?></a>
							</span>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
		<?php
	}
	else
		echo GetMessage('TASKS_PROJECTS_OVERVIEW_NO_DATA');
	?>
</div>
