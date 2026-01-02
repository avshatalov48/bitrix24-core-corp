<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Controllers\Dto;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Deadline\Configuration;
use Bitrix\Tasks\Internals\Attribute\Max;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Dto\AbstractBaseDto;

/**
 * @method self setDefault(int $default)
 * @method self setIsExactTime(bool $isExactTime)
 */
class DeadlineDto extends AbstractBaseDto
{
	#[Required]
	#[Min(0)]
	#[Max(Configuration::MAX_DEFAULT_DEADLINE_IN_SECONDS)]
	public int $default;

	public bool $isExactTime = false;
	public bool $canChangeDeadline = false;
	public ?DateTime $maxDeadlineChangeDate = null;
	public ?int $maxDeadlineChanges = null;
	public bool $requireDeadlineChangeReason = false;

	protected function getRules(): array
	{
		return ['maxDeadlineChanges' => [new Min( min: 1)]]; // If set, it must be at least 1
	}

	public static function createFromArray(array|Arrayable $data): static
	{
		if (isset($data['maxDeadlineChangeDate']) && is_string($data['maxDeadlineChangeDate']))
		{
			try
			{
				$data['maxDeadlineChangeDate'] = new DateTime($data['maxDeadlineChangeDate']);
			}
			catch (\Exception)
			{
				$data['maxDeadlineChangeDate'] = null; // Or handle error appropriately
			}
		}

		if (isset($data['maxDeadlineChanges']))
		{
			$data['maxDeadlineChanges'] = (int)$data['maxDeadlineChanges'];
		}

		return parent::createFromArray($data);
	}
}
