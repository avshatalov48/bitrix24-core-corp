<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

class UpdateResourceCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $updatedBy,
		public readonly Entity\Resource\Resource $resource,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'resource' => $this->resource->toArray(),
			'updatedBy' => $this->updatedBy,
		];
	}

	protected function execute(): Result
	{
		try
		{
			return new ResourceResult(
				(new UpdateResourceCommandHandler())($this),
			);
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
