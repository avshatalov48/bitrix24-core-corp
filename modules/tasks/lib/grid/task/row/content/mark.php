<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class Mark
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Mark extends Content
{
	/**
	 * @return false|string
	 * @throws Main\ArgumentException
	 */
	public function prepare()
	{
		ob_start();

		$row = $this->getRowData();

		$mark = (isset($row['MARK']) ? strtoupper($row['MARK']) : false);
		$markClass = '';
		$addInReportClass = ((isset($row['ADD_IN_REPORT']) && $row['ADD_IN_REPORT'] === 'Y') ? 'task-in-report' : '');

		if ($mark)
		{
			$markClass = 'task-grade-'.($mark === 'N' ? 'minus' : 'plus');
		}

		if ($row['ACTION']['EDIT'])
		{
			$mark = ($mark === 'N' || $mark === 'P' ? $mark : 'NULL');
			$encodedValue = Json::encode(['listValue' => $mark]);
			?>
			<a class="task-grade-and-report <?=$markClass?> <?=$addInReportClass?>" href="javascript: void(0)"
			   onclick='event.stopPropagation(); return BX.Tasks.GridActions.onMarkChangeClick(<?=$row["ID"]?>, this, <?=$encodedValue?>);'
			   title="<?=Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_MARK')?>: <?=Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_MARK_'.($mark === "N" || $mark === "P" ? $mark : "NONE"))?>">
				<span class="task-grade-and-report-inner">
					<i class="task-grade-and-report-icon"></i>
				</span>
			</a>
			<?php
		}
		else
		{
			?>
			<span class="<?=$markClass?> <?=$addInReportClass?>" href="javascript: void(0)"
				  title="<?=Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_MARK')?>: <?=Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_MARK_'.($mark ?: 'NONE'))?>">
				<span class="task-grade-and-report-inner task-grade-and-report-default-cursor">
					<i class="task-grade-and-report-icon task-grade-and-report-default-cursor"></i>
				</span>
			</span>
			<?php
		}

		return ob_get_clean();
	}
}