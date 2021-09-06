<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Monitor\Config;
use Bitrix\Timeman\Monitor\Constant\EntityType;
use Bitrix\Timeman\Monitor\Contract\IMonitorReportData;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\History\ReportComment;
use Bitrix\Timeman\Monitor\History\UserChart;
use Bitrix\Timeman\Monitor\Utils\Time;
use Bitrix\Timeman\Monitor\Utils\User;

class DayReport implements IMonitorReportData
{
	private $date;
	private $userId;
	private $history;
	private $report;
	private $chartData;
	private $timeline;
	private $comment;

	public function __construct(int $userId, Date $date)
	{
		$this->userId = $userId;
		$this->date = $date;

		$this->history = History::getOnDate($this->userId, $this->date);
		$this->chartData = UserChart::getOnDate($this->userId, $this->date);
		$this->comment = ReportComment::getOnDate($this->userId, $this->date);

		if ($this->history)
		{
			$this->createReport();
		}
	}

	public function getData(): array
	{
		$culture = Context::getCurrent()->getCulture();

		$userInfo = User::getInfo($this->userId);

		return [
			'info' => [
				'date' => [
					'value' => $this->date->format('Y-m-d'),
					'format' => [
						'short' => $culture->getShortDateFormat(),
						'long' => $culture->getLongDateFormat(),
					],
				],
				'user' => [
					'id' => $userInfo['id'],
					'fullName' => $userInfo['name'],
					'icon' => $userInfo['icon'],
					'link' => $userInfo['link'],
				],
				'reportComment' => $this->comment,
			],
			'timeline' => $this->timeline,
			'report' => $this->report,
		];
	}

	protected function createReport(): void
	{
		$report = [];
		foreach ($this->history as $entryIndex => $entry)
		{
			if (!$entry['timeStart'])
			{
				unset($entry['timeStart']);
			}

			$entry['privateCode'] = $entryIndex;
			$entry['time'] = (int)$entry['time'];

			switch ($entry['type'])
			{
				case EntityType::ABSENCE_SHORT:
					$entry['allowedTime'] = Time::msToSec(Config::$shortAbsenceTime);
					$entry['hint'] = Loc::getMessage('TIMEMAN_MONITOR_DAY_REPORT_ABSENCE_SHORT_HINT');
					break;

				case EntityType::OTHER:
					$entry['allowedTime'] = Time::msToSec(Config::$otherTime);
					$entry['hint'] = Loc::getMessage('TIMEMAN_MONITOR_DAY_REPORT_OTHER_HINT');
					break;
			}

			$report['data'][] = $entry;
		}

		$timeline = [];
		foreach ($this->chartData as $interval)
		{
			$timeline['data'][] = [
				'type' => $interval['TYPE'],
				'start' => $interval['START']->format('Y-m-d' . '\T' . 'H:i:s' . '\.\0\0\Z'),
				'finish' => $interval['FINISH']->format('Y-m-d' . '\T' . 'H:i:s' . '\.\0\0\Z'),
			];
		}

		$this->report = $report;
		$this->timeline = $timeline;
	}
}