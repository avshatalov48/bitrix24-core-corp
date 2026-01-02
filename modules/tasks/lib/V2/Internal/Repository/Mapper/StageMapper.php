<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;

class StageMapper
{
	public function mapToCollection(array $stages): Entity\StageCollection
	{
		$result = [];
		foreach ($stages as $stage)
		{
			$result[] = $this->mapToEntity($stage);
		}

		return new Entity\StageCollection(...$result);
	}

	public function mapToEntity(array $stage): Entity\Stage
	{
		return new Entity\Stage(
			id: isset($stage['ID']) ? (int)$stage['ID'] : null,
			title: $stage['TITLE'],
			color: $stage['COLOR'],
		);
	}
}
