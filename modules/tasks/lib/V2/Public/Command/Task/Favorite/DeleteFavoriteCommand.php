<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Favorite;

use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;

class DeleteFavoriteCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $userId,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$favoriteTaskRepository = Container::getInstance()->getFavoriteTaskRepository();
		$favoriteService = Container::getInstance()->getFavoriteService();

		$handler = new DeleteFavoriteHandler(
			$favoriteTaskRepository,
			$favoriteService
		);

		$handler($this);

		return new Result();
	}
}
