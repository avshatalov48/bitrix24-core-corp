<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Command;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;
use Bitrix\Tasks\Deadline\Entity\DeadlineUserOption;

/**
 * @method self setEntity(DeadlineUserOption $entity)
 */
class SkipDeadlineNotificationCommand extends AbstractCommand
{
	public function __construct(
		#[Validatable]
		public readonly DeadlineUserOption $entity,
	)
	{
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function execute(): Result
	{
		(new SkipDeadlineNotificationHandler())($this);

		return new Result();
	}

	public function toArray(): array
	{
		return [
			'entity' => $this->entity->toArray(),
		];
	}
}
