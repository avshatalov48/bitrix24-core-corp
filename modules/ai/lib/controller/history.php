<?php
namespace Bitrix\AI\Controller;

use Bitrix\AI\History\Manager;
use Bitrix\Main\Engine\ActionFilter;

class History extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
		];
	}

	/**
	 * Returns History depends on Context.
	 *
	 * @param array $parameters Parameters with context. See parent class.
	 * @return array
	 */
	public function getAction(array $parameters): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		return [
			'items' => Manager::readHistory($this->context)->toArray(),
			'capacity' => Manager::getCapacity(),
		];
	}
}
