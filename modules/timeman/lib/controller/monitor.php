<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Json;
use Bitrix\Timeman\Monitor\Config;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\Report\DayReport;
use Bitrix\Timeman\Monitor\Security\Permissions;

class Monitor extends Controller
{
	public function recordHistoryAction($history)
	{
		$enabled = Config::getEnabled();
		if ($enabled !== 'Y')
		{
			return [
				'enabled' => $enabled,
			];
		}

		$history = Encoding::convertEncoding($history, LANG_CHARSET, 'UTF-8');

		try
		{
			$history = Json::decode($history);
		}
		catch (ArgumentException $e)
		{
			$this->addError(new Error('Unable to decode history', 415));

			return null;
		}

		foreach ($history as $day)
		{
			History::deleteForCurrentUser($day['dateLog'], $day['desktopCode']);
		}

		$recorded = History::record($history);

		return [
			'enabled' => $enabled,
			'recorded' => $recorded,
		];
	}

	public function enableForCurrentUserAction(): bool
	{
		return Config::enableForCurrentUser();
	}

	public function isEnableForCurrentUserAction(): bool
	{
		return Config::isMonitorEnabledForCurrentUser();
	}

	public function isAvailableAction(): bool
	{
		return Config::isAvailable();
	}

	public function getDayReportAction(int $userId, string $dateLog): ?array
	{
		if (!Permissions::createForCurrentUser()->isUserAvailable($userId))
		{
			$this->errorCollection[] = new Error('Access denied', 'ACCESS_DENIED');
			return null;
		}

		$report = new DayReport($userId, new Date($dateLog, 'Y-m-d'));

		return $report->getData();
	}
}