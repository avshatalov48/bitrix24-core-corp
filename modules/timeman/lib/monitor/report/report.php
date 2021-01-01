<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Monitor\Group\Group;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\History\UserPage;
use Bitrix\Timeman\Monitor\Utils\Time;

class Report
{
	private $dateStart;
	private $dateFinish;
	private $userId;
	private $history;
	private $report;
	private $line;
	private $group;

	public function __construct(int $userId, Date $dateStart, Date $dateFinish)
	{
		$this->userId = $userId;
		$this->dateStart = $dateStart;
		$this->dateFinish = $dateFinish;

		$this->history = new History($this->userId, $this->dateStart, $this->dateFinish);
		$this->group = new Group($this->userId);
		$this->history->applyGroup($this->group);

		if ($this->history->get())
		{
			$this->createReport();
			$this->createLine();
		}
	}

	public function getForUser(): array
	{
		return [
			'userId' => $this->userId,
			'dateStart' => $this->dateStart,
			'dateFinish' => $this->dateFinish,
			'line' => $this->line,
			'report' => $this->report
		];
	}

	public function getForManager(): array
	{
		return [
			'userId' => $this->userId,
			'dateStart' => $this->dateStart,
			'dateFinish' => $this->dateFinish,
			'line' => $this->line,
			'report' => [$this->report[Group::CODE_WORKING]]
		];
	}

	protected function createLine(): void
	{
		$groups = $this->group->get();

		$lines = [];
		foreach ($this->report as $group => $history)
		{
			foreach ($history['REPORT'] as $entry)
			{
				$time = (int)$entry['TIME'];

				$lines['FULL_TIME'] += $time;
				$lines['GROUPS'][$group]['TIME'] += $time;
			}
		}

		foreach ($lines['GROUPS'] as $key => $line)
		{
			$lines['GROUPS'][$key]['NAME'] = $groups[$key]['NAME'];
			$lines['GROUPS'][$key]['COLOR'] = $groups[$key]['COLOR'];
			$lines['GROUPS'][$key]['HIDDEN'] = $groups[$key]['HIDDEN'];
			$lines['GROUPS'][$key]['PERCENT'] = round($lines['GROUPS'][$key]['TIME'] * 100 / $lines['FULL_TIME'], 2);
			$lines['GROUPS'][$key]['TIME_FORMATTED'] = Time::format($lines['GROUPS'][$key]['TIME']);
		}

		$lines['FULL_TIME_FORMATTED'] = Time::format($lines['FULL_TIME']);

		$this->line = $lines;
	}

	protected function createReport(): void
	{
		$history = $this->history->get();
		$groups = $this->group->get();

		$report = [];
		foreach ($history as $entry)
		{
			foreach ($entry as $fieldKey => $field)
			{
				if ($field === null)
				{
					unset($entry[$fieldKey]);
				}
			}

			$entry['TIME_FORMATTED'] = Time::format((int)$entry['TIME']);

			$report[$entry['GROUP_CODE']]['PROPERTIES'] = $groups[$entry['GROUP_CODE']];
			$report[$entry['GROUP_CODE']]['REPORT'][] = $entry;
		}

		$this->report = $report;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getPeriod(): array
	{
		return [
			'start' => $this->dateStart,
			'finish' => $this->dateFinish,
		];
	}

	public static function getUserPagesOnDate(int $userId, int $siteId, Date $date): array
	{
		$pages = UserPage::getForUserOnDate($userId, $siteId, $date);
		foreach ($pages as $key => $page)
		{
			$pages[$key]['TIME_FORMATTED'] = Time::format((int)$page['TIME_SPEND']);
		}

		return $pages;
	}
}