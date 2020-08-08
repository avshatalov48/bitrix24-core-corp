<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks;
use Bitrix\Tasks\Integration\SocialNetwork;

Loc::loadMessages(__FILE__);

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 200);
?>

<div class="sidebar-widget sidebar-widget-tasks">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title">
			<a href="<?=$arParams['PATH_TO_TASKS']?>"><?=GetMessage("TASKS_FILTER_TITLE")?></a>
		</div>
		<?php
			$path = Socialnetwork\UI\Task::getActionPath();
			$url = Tasks\UI\Task::makeActionUrl($path);
		?>
		<a class="plus-icon" href="<?=$url?>"></a>
	</div>
	<div class="sidebar-widget-item-wrap">
		<?php
			foreach ($arResult["ROLES"] as $role)
			{
				$counter = ($role['COUNTER'] > 99 ? '99+' : $role['COUNTER']);
				$counterViolation = ($role['COUNTER_VIOLATIONS'] > 99 ? '99+' : $role['COUNTER_VIOLATIONS']);
				?>
				<a class="task-item" href="<?=$role['HREF']?>">
					<span class="task-item-text"><?=$role['TITLE']?></span>
					<span class="task-item-index-wrap">
						<span class="task-item-index"><?=$counter?></span>
						<span class="task-item-counter-wrap">
							<span class="task-item-counter"><?=$counterViolation?></span>
						</span>
					</span>
				</a>
		<?php
			}
		?>
	</div>
</div>