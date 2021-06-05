<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;
use Bitrix\Timeman\Monitor\Config;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\Report\Status;

class Monitor extends Controller
{
	public function recordHistoryAction($history)
	{
		$enabled = Config::getEnabled();
		$state = Config::getState();
		if ($enabled !== 'Y')
		{
			return [
				'enabled' => $enabled,
				'state' => $state
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
		if ($recorded)
		{
			Status::setForCurrentUser(Status::CLOSED);
		}

		return [
			'enabled' => $enabled,
			'state' => $state,
			'recorded' => $recorded,
		];
	}

	public function setStatusWaitingDataAction(): bool
	{
		return Status::setForCurrentUser(Status::WAITING_DATA);
	}

	public function isHistorySentAction(): bool
	{
		return Status::getForCurrentUser();
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
}