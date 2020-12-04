<?php


namespace Bitrix\Calendar\ICal\Basic;


class LengthPropertyType extends PropertyType
{
	private $interval;

	public static function getInstance($names, \DateInterval $interval): LengthPropertyType
	{
		return new self($names, $interval);
	}

	public function invert(): LengthPropertyType
	{
		$this->interval->invert = 1;

		return $this;
	}

	/**
	 * DurationPropertyType constructor.
	 *
	 * @param array|string $names
	 * @param \DateInterval $interval
	 */
	public function __construct($names, \DateInterval $interval)
	{
		parent::__construct($names);

		$this->interval = $interval;
	}

	public function getValue(): string
	{
		$value = $this->interval->invert ? '-P' : 'P';

		if ($this->interval->d > 0) {
			$value .= "{$this->interval->d}D";
		}

		if ($this->interval->s > 0 || $this->interval->i > 0 || $this->interval->h > 0) {
			$value .= 'T';
		}

		if ($this->interval->h > 0) {
			$value .= "{$this->interval->h}H";
		}

		if ($this->interval->i > 0) {
			$value .= "{$this->interval->i}M";
		}

		if ($this->interval->s > 0) {
			$value .= "{$this->interval->s}S";
		}

		return $value;
	}

	public function getOriginalValue(): \DateInterval
	{
		return $this->interval;
	}
}