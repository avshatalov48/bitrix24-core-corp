<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Engine\ThirdParty;

trait StatementTrait
{
	private string $currentResultKey = 'current_result';

	/**
	 * Returns Engine's code. If Engine is ThirdParty, tries to get Engine's alias code.
	 *
	 * @return string
	 */
	private function getEngineCode(): string
	{
		if ($this->engine instanceof ThirdParty)
		{
			return $this->engine->getCodeAlias();
		}

		return $this->engine->getCode();
	}

	/**
	 * Retrieves system and user data for conditions.
	 *
	 * @param array $additionalMarkers Optional additional markers.
	 * @return void
	 */
	protected function fillConditionsData(array $additionalMarkers = []): void
	{
		$this->conditionsData = [
			'engine.code' => $this->getEngineCode(),
			'engine.category' => $this->engine->getCategory(),
			'context.id' => $this->engine->getContext()->getContextId(),
			'context.module' => $this->engine->getContext()->getModuleId(),
		];

		foreach ($additionalMarkers as $key => $value)
		{
			$this->conditionsData["marker.$key"] = $value;
		}

		$userId = $this->engine->getContext()->getUserId();

		foreach ($this->getUserDataById($userId) as $key => $value)
		{
			if (!is_array($value))
			{
				$this->conditionsData["user.$key"] = $value;
			}
		}
	}

	/**
	 * Prepares statements data (makes lowercase and skip '_').
	 *
	 * @return void
	 */
	protected function prepareConditionsData(): void
	{
		$conditionsData = [];

		if (!empty($this->conditionsData['marker.'.$this->currentResultKey]))
		{
			foreach (array_values($this->conditionsData['marker.'.$this->currentResultKey]) as $key => $value)
			{
				if (is_array($value))
				{
					continue;
				}
				$conditionsData["{$this->currentResultKey}$key"] = trim(mb_strtolower($value));
			}
		}

		foreach ($this->conditionsData as $key => $value)
		{
			if (is_array($value))
			{
				continue;
			}
			if (!str_contains($key, 'marker.'))
			{
				$key = strtolower(str_replace('_', '', $key));
			}
			$conditionsData[$key] = trim(mb_strtolower($value));
		}

		$this->conditionsData = $conditionsData;
	}
}
