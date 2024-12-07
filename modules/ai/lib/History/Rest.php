<?php

namespace Bitrix\AI\History;

use Bitrix\AI\Context;
use Bitrix\AI\Config;
use Bitrix\AI\Engine;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\HistoryTable;
use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\Prompt;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;

class Rest
{
	/**
	 * Sets ON module's option for saving any requests.
	 *
	 * @return void
	 */
	public static function enable(): void
	{
		Config::setOptionsValue('write_history_always', 'Y');
		Config::setOptionsValue('write_history_request', 'Y');
		Config::setOptionsValue('write_errors', 'Y');
	}

	/**
	 * Sets OFF module's option for saving any requests.
	 *
	 * @return void
	 */
	public static function disable(): void
	{
		Config::setOptionsValue('write_history_always', 'N');
		Config::setOptionsValue('write_history_request', 'N');
		Config::setOptionsValue('write_errors', 'N');
	}

	/**
	 * Returns history for debugging purposes.
	 *
	 * @return array
	 */
	public static function getList(): array
	{
		$data = [];

		$history = self::getHistoryList();
		$context = Context::getFake();

		foreach ($history as $item)
		{
			$engine = Engine::getByCode($item['ENGINE_CODE'], $context);
			if ($engine === null)
			{
				continue;
			}

			/** @var IPayload $payloadClass */
			$payloadClass = $item['PAYLOAD_CLASS'];
			$isPrompt = $item['PAYLOAD_CLASS'] === 'Bitrix\AI\Payload\Prompt';
			$payload = $payloadClass::unpack($item['PAYLOAD']);
			if ($payload === null)
			{
				continue;
			}
			$engine->setPayload($payload);

			$contextItem = $item['CONTEXT'];
			if (!empty($contextItem))
			{
				try
				{
					$contextItem = Json::decode($contextItem);
				}
				catch (SystemException)
				{}
			}

			$data[] = [
				'date' => (string)$item['DATE_CREATE'],
				'provider' => $item['ENGINE_CODE'],
				'parameters' => $item['PARAMETERS'],
				'request' => $item['REQUEST_TEXT'],
				'result' => $item['RESULT_TEXT'],
				'context' => $contextItem,
				'payload' => [
					'role' => $payload->getRole()?->getCode(),
					'role_modify' => (string)$payload->getRole()?->getModifyTime(),
					'cost' => $payload->getCost(),
					'raw' => $payload->getRawData(),
					'markers' => $payload->getMarkers(),
				],
				'prompt' => !$isPrompt ? null : [
					'code' => $payload->getRawData(),
					'text' => Prompt\Manager::getByCode($payload->getRawData())?->getPrompt(),
				],
				'cached' => (bool)$item['CACHED']
			];
		}

		return $data;
	}

	/**
	 * Returns raw history for current user.
	 *
	 * @return array
	 */
	private static function getHistoryList(): array
	{
		return HistoryTable::query()
			->setSelect(['*'])
			->where('CREATED_BY_ID', User::getCurrentUserId())
			->whereNotNull('ENGINE_CODE')
			->setOrder(['ID' => 'DESC'])
			->fetchAll()
		;
	}
}
