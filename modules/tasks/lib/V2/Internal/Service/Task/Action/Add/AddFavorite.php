<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class AddFavorite
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		if (!array_key_exists('PARENT_ID', $fields))
		{
			return;
		}

		$favoriteRepository = Container::getInstance()->getFavoriteTaskRepository();
		if (!$favoriteRepository->getByPrimary((int)$fields['PARENT_ID'], $this->config->getUserId()))
		{
			return;
		}

		$favoriteRepository->add((int)$fields['ID'], $this->config->getUserId());
	}
}