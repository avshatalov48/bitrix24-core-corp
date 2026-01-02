<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Intranet\Entity\Collection\BaseCollection;

/**
 * @extends BaseCollection<BaseContent>
 */
class StaticCollection extends BaseCollection implements \JsonSerializable
{
	/**
	 * @inheritdoc
	 */
	public static function getItemClassName(): string
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
