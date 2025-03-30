<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Favorites;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

class RemoveFavoriteCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $managerId,
		public readonly array $resourcesIds,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'managerId' => $this->managerId,
			'resourcesIds' => $this->resourcesIds,
		];
	}

	public function execute(): Result
	{
		try
		{
			(new RemoveFavoriteCommandHandler())($this);

			return new Result();
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
