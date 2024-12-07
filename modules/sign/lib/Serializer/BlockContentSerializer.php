<?php

namespace Bitrix\Sign\Serializer;

use Bitrix\Sign\Item;
use Bitrix\Sign\Type\BlockType;

class BlockContentSerializer
{
	public function __construct(
		private FieldValueSerializer $fieldValueSerializer,
	)
	{
	}

	public function serialize(Item\Block $block, ?Item\Member $member = null): Item\Block\Content
	{
		if ($block->fields === null)
		{
			return new Item\Block\Content();
		}

		$fields = $block->fields;
		if ($fields->isEmpty())
		{
			return new Item\Block\Content();
		}

		return $this->getContent($block->type, $fields, $member);
	}

	private function getContent(
		string $blockType,
		Item\FieldCollection $fields,
		?Item\Member $member,
	): Item\Block\Content
	{
		switch ($blockType)
		{
			case BlockType::TEXT:
				if (count($fields) <= 1)
				{
					$text = $this->fieldValueSerializer->serializeAsText($fields->getFirst(), $member);
					return new Item\Block\Content(text: $text);
				}

				$text = [];
				foreach ($fields as $field)
				{
					$value = $this->fieldValueSerializer->serializeAsText($field);
					if ($value === null || $value === '')
					{
						continue;
					}
					$text[] = "{$field->label}: {$value}";
				}

				$text = implode("\n", $text);
				return new Item\Block\Content(text: $text);

			case BlockType::IMAGE:
				$field = $fields->getFirst();
				if ($field === null)
				{
					return new Item\Block\Content();
				}

				return new Item\Block\Content(file: $this->fieldValueSerializer->serializeAsFile($field));
		}

		return new Item\Block\Content();
	}
}
