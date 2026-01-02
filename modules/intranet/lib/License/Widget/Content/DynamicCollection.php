<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Intranet\Entity\Collection\BaseCollection;

/**
 * @extends BaseCollection<DynamicContent>
 */
class DynamicCollection extends BaseCollection implements \JsonSerializable
{
	/**
	 * @inheritdoc
	 */
	protected static function getItemClassName(): string
	{
		return DynamicContent::class;
	}

	public function jsonSerialize(): array
	{
		$result = [];

		foreach ($this->getIterator() as $item)
		{
			$result[$item->getName()] = $item->getDynamicConfiguration();
		}

		return $result;
	}
}
