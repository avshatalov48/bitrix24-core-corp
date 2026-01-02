<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataStatus;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\Status;

class CopilotAdviceInfoPreloader
{
	private static array $storage = [];

	private CollectedDataProvider $provider;

	public function __construct()
	{
		$this->init();
	}

	final public function preload(int ...$flowIds): void
	{
		try
		{
			static::$storage = $this->provider->getFlowAdviceInfoByFlowIds(...$flowIds) + static::$storage;
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
		}
	}

	final public function get(int $flowId): array
	{
		return static::$storage[$flowId];
	}

	private function init(): void
	{
		$this->provider = new CollectedDataProvider();
	}
}