<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Mobile\Dto\Dto;
use Bitrix\TasksMobile\Enum\CounterColor;

final class TaskCounterDto extends Dto
{
	/** @var array<string, int> */
	public array $counters = [
		'expired' => 0,
		'newComments' => 0,
		'mutedExpired' => 0,
		'mutedNewComments' => 0,
		'projectExpired' => 0,
		'projectNewComments' => 0,
	];

	public string $color = CounterColor::GRAY;

	public int $value = 0;

	protected function getDecoders(): array
	{
		return [
			function (array $counter) {
				if (!empty($counter['counters']))
				{
					$converter = new Converter(
						Converter::KEYS
						| Converter::TO_CAMEL
						| Converter::LC_FIRST
					);
					$counter['counters'] = $converter->process($counter['counters']);
				}

				return $counter;
			},
		];
	}
}
