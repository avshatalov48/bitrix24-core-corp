<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget;

use Bitrix\Intranet\Entity\Collection\BaseCollection;

/**
 * @extends BaseCollection<BaseContent>
 */
class ContentCollection extends BaseCollection implements \JsonSerializable
{
	protected static function getItemClassName(): string
	{
		return BaseContent::class;
	}

	public function jsonSerialize(): array
	{
		$result = [];

		foreach ($this->getIterator() as $item)
		{
			$result[$item->getName()] = $item->getConfiguration();
		}

		return $result;
	}
}
