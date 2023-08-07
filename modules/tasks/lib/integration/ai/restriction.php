<?php

namespace Bitrix\Tasks\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

abstract class Restriction
{
	public function isAvailable(): bool
	{
		if (!Loader::includeModule('ai'))
		{
			return false;
		}

		$engine = Engine::getByCategory($this->getType(), new Context('tasks', ''));
		if (is_null($engine))
		{
			return false;
		}

		$option = 'tasks_ai_' . $this->getType() . '_available';

		return Option::get('tasks', $option, 'N') === 'Y';
	}

	abstract protected function getType(): string;
}