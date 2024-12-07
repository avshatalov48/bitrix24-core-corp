<?php

namespace Bitrix\AI\Payload\Formatter;

class Setters extends Formatter implements IFormatter
{
	private const MARKER = '@set';

	private const PATTERN = '/(?P<blocks>@set(?P<setters>{setters})\s*\((?P<values>[^)]+)\))/is';
	private const SETTERS_TO_PARAM = [
		'temperature' => 'temperature',
		'tokens' => 'max_tokens',
		'responsejson' => 'response_json'

	];

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (
			!str_contains($this->text, self::MARKER)
			|| !preg_match_all($this->getPattern(), $this->text, $matches)
		)
		{
			return $this->text;
		}

		foreach ($matches['blocks'] as $i => $block)
		{
			$paramKey = $this->getParamKey($matches['setters'][$i]);
			$paramValue = $this->getParamValue($matches['values'][$i]);

			if (!is_null($paramKey))
			{
				if ($paramKey === 'response_json')
				{
					$this->engine->setResponseJsonMode((bool)$paramValue);
				}
				else
				{
					$this->engine->setParameters([$paramKey => $paramValue]);
				}
			}

			$this->text = str_replace($block, '', $this->text);
		}

		return $this->text;
	}

	private function getPattern(): string
	{
		$setters = implode('|', array_keys(self::SETTERS_TO_PARAM));

		return str_replace('{setters}', $setters, self::PATTERN);
	}

	private function getParamKey(string $setterKey): ?string
	{
		$setterKey = strtolower(trim($setterKey));

		return self::SETTERS_TO_PARAM[$setterKey] ?? null;
	}

	private function getParamValue(string $rawValue): int|float
	{
		$rawValue = str_replace(',', '.', $rawValue);
		$rawValue = preg_replace('/([^\d^.]+)/', '', $rawValue);

		return !strpos($rawValue, '.') ? (int)$rawValue : (float)$rawValue;
	}
}
