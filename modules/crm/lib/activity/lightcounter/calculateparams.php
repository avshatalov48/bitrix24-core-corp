<?php

namespace Bitrix\Crm\Activity\LightCounter;

use Bitrix\Main\Type\DateTime;

final class CalculateParams
{
	private ?DateTime $deadline;

	private ?int $notifyType;

	private ?int $notifyValue;

	/** @var int[]|null  */
	private ?array $offsets;

	public function __construct(
		?DateTime $deadline,
		?int $notifyType,
		?int $notifyValue,
		?array $offsets
	)
	{
		$this->deadline = $deadline ? clone $deadline : null; // prevent unexpected changes by reference
		$this->notifyType = $notifyType;
		$this->notifyValue = $notifyValue;
		$this->offsets = $offsets;
	}

	public function deadline(): ?DateTime
	{
		return $this->deadline;
	}

	public function notifyType(): ?int
	{
		return $this->notifyType;
	}

	public function notifyValue(): ?int
	{
		return $this->notifyValue;
	}

	/**
	 * @return int[]|null
	 */
	public function offsets(): ?array
	{
		return $this->offsets;
	}

	public static function createFromArrays(array $activityArrFields, ?array $arPingOffsets): CalculateParams
	{
		$deadline = null;
		if (isset($activityArrFields['DEADLINE']))
		{
			$deadline = DateTime::createFromUserTime($activityArrFields['DEADLINE']);
		}

		return new CalculateParams(
			$deadline,
			$activityArrFields['NOTIFY_TYPE'] ?? null,
			$activityArrFields['NOTIFY_VALUE'] ?? null,
			$arPingOffsets
		);
	}
}