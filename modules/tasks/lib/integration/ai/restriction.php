<?php

namespace Bitrix\Tasks\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

abstract class Restriction
{
	protected bool $engineAvailable = false;

	public function __construct()
	{
		$this->engineAvailable = $this->isEngineAvailable();
	}

	public function isAvailable(): bool
	{
		if (!$this->engineAvailable)
		{
			return false;
		}

		$option = 'tasks_ai_' . $this->getType() . '_available';

		return Option::get('tasks', $option, 'N') === 'Y';
	}

	protected function isEngineAvailable(): bool
	{
		if (!Loader::includeModule('ai'))
		{
			return false;
		}

		$engine = Engine::getByCategory($this->getType(), new Context('tasks', ''));

		return !is_null($engine);
	}

	abstract protected function getType(): string;
}