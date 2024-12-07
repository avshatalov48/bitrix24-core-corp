<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Block\BlockPosition;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Block\BlockStyle;

class Block implements Contract\Item
{
	public int $party;
	public string $type;
	public BlockPosition $position;
	public ?BlockStyle $style = null;
	/** @var list<string> */
	public array $fieldNames = [];

	public function __construct(int $party, string $type, BlockPosition $blockPosition)
	{
		$this->party = $party;
		$this->type = $type;
		$this->position = $blockPosition;
	}

	public function getFieldNames(): array
	{
		return $this->fieldNames;
	}

	public function addFieldNames(string ...$fieldName): Block
	{
		foreach ($fieldName as $name)
		{
			$this->fieldNames[] = $name;
		}

		return $this;
	}
}