<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;
use Bitrix\Timeman\Monitor\Config;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\State;

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

		return [
			'enabled' => $enabled,
			'state' => $state,
			'recorded' => History::record($history)
		];
	}
}