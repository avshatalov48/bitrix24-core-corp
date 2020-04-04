<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 200);

?>

<div class="sidebar-widget sidebar-widget-tasks">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?=GetMessage("TASKS_FILTER_TITLE")?></div>

		<?
		$path = \Bitrix\Tasks\Integration\Socialnetwork\UI\Task::getActionPath();
		$url = \Bitrix\Tasks\UI\Task::makeActionUrl($path);
		?>

		<a class="plus-icon" href="<?=$url?>"></a>
	</div>
	<div class="sidebar-widget-item-wrap">
	<?
	if ($arResult['USE_ROLE_FILTER'] === 'Y')
	{
		foreach($arResult["ROLES_LIST"] as $roleCodename => $roleData)
		{
			if ($roleData['CNT_ALL'] > 99)
			{
				$counter = '99+';
			}
			else
			{
				$counter = intval($roleData['CNT_ALL']);
			}

			$counterNotif = '';
			if(!$arResult['HIDE_COUNTERS'])
			{
				if ($roleData['CNT_NOTIFY'] > 99)
				{
					$counterNotif = '99+';
				}
				else
				{
					$counterNotif = intval($roleData['CNT_NOTIFY']);
				}
			}
			?>

			<a class="task-item" href="<?=$roleData['HREF']?>">
				<span class="task-item-text"><?=$roleData['TITLE']?></span>
					<span class="task-item-index-wrap">
						<span class="task-item-index"><?=$counter?></span>
						<span class="task-item-counter-wrap">
							<span class="task-item-counter"><?=intval($counterNotif)?></span>
						</span>
				</span>
			</a>
			<?php
		}
	}
	else
	{
		foreach($arResult["PRESETS_LIST"] as $key=>$filter)
		{
			if ($key >= 0)
				continue;

			$display = '';
			if($arResult['HIDE_COUNTERS'])
			{
				$display = ' style="display:none;" ';
			}

			$levelOffset = '';
			if($filter["#DEPTH"] > 1)
			{
				$levelOffset = 'task-filter-item-sublevel task-filter-item-sublevel_'.($filter["#DEPTH"]-1);
			}
			?>

			<a class="task-item <?=$levelOffset?>" href="<?=($arParams["PATH_TO_TASKS"]."?F_FILTER_SWITCH_PRESET=".$key)?>">
				<span class="task-item-text"><?=$filter["Name"]?></span>
					<span class="task-item-index-wrap">
						<span class="task-item-index" <?=$display?>><?=intval($arResult["COUNTS"][$key])?></span>
				</span>
			</a>

			<?
		}
	}
	?>
	</div>
</div>
