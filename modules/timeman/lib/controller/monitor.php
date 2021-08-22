<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;
use Bitrix\Timeman\Monitor\Config;
use Bitrix\Timeman\Monitor\History\History;

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
}