<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content;

use Bitrix\Intranet\Entity\Collection\BaseCollection;
use Bitrix\Intranet\User\Widget\Content\Tool\BaseTool;

/**
 * @extends BaseCollection<BaseTool>
 */
class ToolCollection extends BaseCollection implements \JsonSerializable
{
	protected static function getItemClassName(): string
	{
		return BaseTool::class;
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
