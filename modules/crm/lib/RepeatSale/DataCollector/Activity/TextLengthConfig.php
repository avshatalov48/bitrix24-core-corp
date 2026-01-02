<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

final class TextLengthConfig
{
	private const DEFAULT_CONFIG = [
		'min_length' => 100,
		'max_length' => 5000,
	];

	public function getConfigForType(ActivityType $type): array
	{
		return $this->lengthLimitsMap()[$type->value] ?? self::DEFAULT_CONFIG;
	}

	private function lengthLimitsMap(): array
	{
		return [
			ActivityType::CALL_RECORDING_TRANSCRIPTS->value => [
				'min_length' => 100,
				'max_length' => 20000,
			],
			ActivityType::COMMENTS->value => [
				'min_length' => 100,
				'max_length' => 5000,
			],
			ActivityType::TODOS->value => [
				'min_length' => 100,
				'max_length' => 5000,
			],
			ActivityType::EMAILS->value => [
				'min_length' => 100,
				'max_length' => 5000,
			],
			ActivityType::OPEN_LINES->value => [
				'min_length' => 100,
				'max_length' => 5000,
			],
		];
	}
}
