<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content\Date;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;
use CTasks;
use CTasksTools;
use CTimeZone;

/**
 * Class Deadline
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class Deadline extends Date
{
	private const BXT_SELECTOR = 'bxt-tasks-grid-deadline';
	private static $workTimeSettings = [];

	public function prepare()
	{
		$row = $this->getRowData();

		$state = $this->getDeadlineStateData();
		$timestamp = ($row['DEADLINE'] ? $this->getDateTimestamp($row['DEADLINE']) : $this->getCompanyWorkTimeEnd());

		$jsDeadline = DateTime::createFromTimestamp($timestamp - CTimeZone::GetOffset());
		$text = ($state['state'] ?: $this->formatDate($row['DEADLINE']));

		$onClick = '';
		$link = '';

		$gridLabel = [
			'html' => '<span class="'.self::BXT_SELECTOR.'">'.$text.'</span>',
		];

		if ($row['ACTION']['CHANGE_DEADLINE'])
		{
			$taskId = (int)$row['ID'];
			$onClick = "onclick=\"BX.Tasks.GridActions.onDeadlineChangeClick({$taskId}, this, '{$jsDeadline}'); event.stopPropagation();\"";
			$link = ' task-deadline-date';

			$gridLabel['events'] = [
				'click' => "BX.Tasks.GridActions.onDeadlineChangeClick.bind(BX.Tasks.GridActions, {$taskId}, null, '{$jsDeadline}', event);",
			];
		}

		if ($state['state'])
		{
			$color = mb_strtoupper($state['color']);
			$gridLabel['color'] = constant("Bitrix\Main\Grid\Cell\Label\Color::{$color}");
			$gridLabel['light'] = !$state['fill'];

			return [$gridLabel];
		}

		$link = ($link ?: 'task-deadline-datetime');
		$link .= ' '.self::BXT_SELECTOR;

		return "<span class=\"{$link}\"><span {$onClick}>{$text}</span></span>";
	}

	/**
	 * @return array
	 */
	public function getDeadlineStateData(): array
	{
		$row = $this->getRowData();
		return (new UI\Task\Deadline())->buildState($row['REAL_STATUS'], $row['DEADLINE']);
	}

	private function getCompanyWorkTimeEnd(): int
	{
		if (empty(self::$workTimeSettings))
		{
			self::$workTimeSettings = Calendar::getSettings();
		}

		return (new DateTime())->setTime(
			self::$workTimeSettings['HOURS']['END']['H'],
			self::$workTimeSettings['HOURS']['END']['M'],
			self::$workTimeSettings['HOURS']['END']['S']
		)->getTimestamp();
	}
}