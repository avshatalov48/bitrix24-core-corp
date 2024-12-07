<?php

namespace Bitrix\AI\Payload\Formatter;

class IfStatement extends Formatter implements IFormatter
{
	use StatementTrait;

	private const MARKER = '@if';
	private const PATTERN_IF = '/(?P<block>@if\s*\(\s*(?P<conditions>[^!^<^>^=]+)\s*(?P<operators>[!=<>]+)\s*(?P<values>[^)^\s]+)\s*\)(?P<then>.*?)@endif)/is';
	private const OPERATORS = [
		'eq' => '=',
		'neq' => '!=',
		'more' => '>',
		'less' => '<',
	];
	private const FUNCTIONS = [
		'/length\s*\((?P<value>[^\)]+)\)/is' => 'mb_strlen',
	];

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
	 * Parses @if/@else and replace its according conditions.
	 *
	 * @return void
	 */
	private function parseConditions(): void
	{
		if (preg_match_all(self::PATTERN_IF, $this->text, $matches))
		{
			foreach ($matches['conditions'] as $i => $condition)
			{
				$replaceFrom = $matches['block'][$i] ?? '';
				$replaceTo = '';

				$condition = mb_strtolower(trim($condition));
				$value = trim(mb_strtolower($matches['values'][$i] ?? ''));
				$operator = trim(mb_strtolower($matches['operators'][$i] ?? ''));

				$then = trim($matches['then'][$i] ?? '');
				$else = '';

				if (str_contains($then, '@else'))
				{
					[$then, $else] = explode('@else', $then);
				}

				if ($value === 'null')
				{
					$value = '';
				}

				if ($this->isValidOperator($operator))
				{
					$condition = $this->execFunction($condition);

					$replaceTo = $this->applyOperator($condition, $operator, $value)
						? $then
						: $else
					;
				}

				$this->text = str_replace($replaceFrom, $replaceTo, $this->text);
			}
		}
	}

	/**
	 * Returns true if operator is allowed.
	 *
	 * @param string $operator Operator symbol.
	 * @return bool
	 */
	private function isValidOperator(string $operator): bool
	{
		return in_array($operator, array_values(self::OPERATORS), true);
	}

	/**
	 * Applies operator logic to the two values.
	 *
	 * @param string $value1 First value to compare.
	 * @param string $operator Operator symbol.
	 * @param string $value2 Second value to compare.
	 * @return bool
	 */
	private function applyOperator(string $value1, string $operator, string $value2): bool
	{
		return match ($operator)
		{
			self::OPERATORS['eq'] => $value1 === $value2,
			self::OPERATORS['neq'] => $value1 !== $value2,
			self::OPERATORS['more'] => $value1 > $value2,
			self::OPERATORS['less'] => $value1 < $value2,
			default => false,
		};
	}

	/**
	 * If condition contains allowed function, executes it. Otherwise, returns from conditions data.
	 *
	 * @param string $condition Raw condition.
	 * @return mixed
	 */
	private function execFunction(string $condition): mixed
	{
		foreach (self::FUNCTIONS as $regex => $fnc)
		{
			if (preg_match($regex, $condition, $matches))
			{
				$conditionKey = trim($matches['value']);
				return $fnc($this->conditionsData[$conditionKey] ?? '');
			}
		}

		return $this->conditionsData[$condition] ?? '';
	}
}
