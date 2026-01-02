<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;

class CheckListUserOptionMapper
{
	public function mapToCollection(array $userOptions): Entity\CheckList\UserOptionCollection
	{
		$entities = [];
		foreach ($userOptions as $userOption)
		{
			$entities[] = $this->mapToEntity($userOption);
		}

		return new Entity\CheckList\UserOptionCollection(...$entities);
	}

	public function mapToEntity(array $userOption): Entity\CheckList\UserOption
	{
		return new Entity\CheckList\UserOption(
			id: $userOption['ID'] ?? null,
			userId: $userOption['USER_ID'] ?? null,
			itemId: $userOption['ITEM_ID'] ?? null,
			code: $userOption['OPTION_CODE'] ?? null,
		);
	}

	public function mapFromEntity(Entity\CheckList\UserOption $userOption): array
	{
		$data = [];
		if ($userOption->id)
		{
			$data['ID'] = $userOption->id;
		}

		if ($userOption->userId)
		{
			$data['USER_ID'] = $userOption->userId;
		}

		if ($userOption->itemId)
		{
			$data['ITEM_ID'] = $userOption->itemId;
		}

		if ($userOption->code)
		{
			$data['OPTION_CODE'] = $userOption->code;
		}

		return $data;
	}
}
