<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Task\Row\Content;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

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

		$mark = ($mark === 'N' || $mark === 'P' ? $mark : 'NULL');
		$markText = match($mark)
		{
			'N' => 'TASKS_GRID_TASK_ROW_CONTENT_MARK_N_MSGVER_1',
			'P' => 'TASKS_GRID_TASK_ROW_CONTENT_MARK_P_MSGVER_1',
			default => 'TASKS_GRID_TASK_ROW_CONTENT_MARK_NONE_MSGVER_1'
		};

		if ($row['ACTION']['EDIT'])
		{
			if ($this->isRestricted())
			{
				$this->renderLock();
			}
			else
			{
				$encodedValue = Json::encode(['listValue' => $mark]);
				?>
				<a class="task-grade-and-report <?= $markClass ?> <?= $addInReportClass ?>" href="javascript: void(0)"
				   onclick='event.stopPropagation(); return BX.Tasks.GridActions.onMarkChangeClick(<?= $row["ID"] ?>, this, <?= $encodedValue ?>);'
				   title="<?= Loc::getMessage($markText) ?>">
				<span class="task-grade-and-report-inner">
					<i class="task-grade-and-report-icon"></i>
				</span>
				</a>
			<?php
			}
		}
		else
		{
			?>
			<span class="<?=$markClass?> <?=$addInReportClass?>" href="javascript: void(0)"
				  title="<?=Loc::getMessage($markText)?>">
				<span class="task-grade-and-report-inner task-grade-and-report-default-cursor">
					<i class="task-grade-and-report-icon task-grade-and-report-default-cursor"></i>
				</span>
			</span>
			<?php
		}

		return ob_get_clean();
	}

	private function isRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_RATE);
	}

	private function renderLock(): void
	{
		?>
			<div
				style="cursor: pointer"
				class='tasks-list-tariff-lock-container'
				onclick="<?=Limit::getLimitLockClick(FeatureDictionary::TASK_RATE, null)?>"
			>
				<span class='task-list-tariff-lock'></span>
			</div>
		<?php
	}
}