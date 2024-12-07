<?php

namespace Bitrix\AI\Payload\Formatter;

class CaseStatement extends Formatter implements IFormatter
{
	use StatementTrait;

	private const MARKER = '@switch';
	private const PATTERN_SWITCH = '/(?P<block>@switch\s*\(\s*(?P<conditions>[^\)]+)\s*\)\s*(?P<switchbodies>.*?)\s*@endswitch)/is';
	private const PATTERN_CASE = '/@case\s*\((?P<values>[^\)]+)\)(?P<bodies>.+?)(?=@case)/is';

	private array $conditionsData = [];

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (!str_contains($this->text, self::MARKER))
		{
			return $this->text;
		}

		$this->fillConditionsData($additionalMarkers);
		$this->prepareConditionsData();
		$this->parseConditions();

		return $this->text;
	}

	/**
	 * Parses @case and replace its according conditions.
	 *
	 * @return void
	 */
	private function parseConditions(): void
	{
		if (preg_match_all(self::PATTERN_SWITCH, $this->text, $matches))
		{
			foreach ($matches['conditions'] as $i => $condition)
			{
				$replaceFrom = $matches['block'][$i] ?? '';
				$replaceTo = $this->parseSwitchBody($condition, $matches['switchbodies'][$i] ?? '');

				$this->text = str_replace($replaceFrom, $replaceTo, $this->text);
			}
		}
	}

	/**
	 * Parses switch's body.
	 *
	 * @param string $condition Condition from switch statement.
	 * @param string $body Switch's body.
	 * @return string|null
	 */
	private function parseSwitchBody(string $condition, string $body): ?string
	{
		[$body, $default] = explode('@default', $body);
		$body .= '@case';// for pretty simple pattern

		if (preg_match_all(self::PATTERN_CASE, $body, $matches))
		{
			foreach ($matches['values'] as $i => $value)
			{
				$value = mb_strtolower(trim($value));
				$body = trim($matches['bodies'][$i] ?? '');

				if ($this->conditionsData[$condition] === $value)
				{
					return $body;
				}
			}
		}

		return $default;
	}
}
