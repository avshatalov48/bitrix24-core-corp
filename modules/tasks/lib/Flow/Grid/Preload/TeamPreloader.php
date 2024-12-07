<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Provider\MembersProvider;
use Bitrix\Tasks\Internals\Log\Logger;

class TeamPreloader
{
	private static array $storage = [];

	private MembersProvider $membersProvider;

	public function __construct()
	{
		$this->init();
	}

	final public function preload(FlowCollection $flows): void
	{
		try
		{
			static::$storage = $this->membersProvider->getTeamCount($flows);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
		}
	}

	final public function get(int $flowId): int
	{
		return static::$storage[$flowId] ?? 0;
	}

	private function init(): void
	{
		$this->membersProvider = new MembersProvider();
	}
}