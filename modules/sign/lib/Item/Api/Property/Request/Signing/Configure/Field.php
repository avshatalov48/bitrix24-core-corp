<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

class Field implements Contract\Item
{
	public int $party;
	public string $type;
	public string $name;
	public ?string $label = null;
	public ?string $hint = null;
	public ?string $placeholder = null;
	public ?bool $required = null;

	public function __construct(
		int $party,
		string $type,
		string $name,
		?string $label = null,
		public ?Field\ItemCollection $items = null,
		public ?SubFieldCollection $subfields = null,
	)
	{
		$this->party = $party;
		$this->type = $type;
		$this->name = $name;
		$this->label = $label;
	}

	public static function createFromFieldItem(Item\Field $field): static
	{
		$instance = new static($field->party, $field->type, $field->name, $field->label);
		$instance->required = $field->required;

		if ($field->items !== null)
		{
			$resultItems = new Item\Api\Property\Request\Signing\Configure\Field\ItemCollection();
			foreach ($field->items as $item)
			{
				$resultItems->addItem(
					Item\Api\Property\Request\Signing\Configure\Field\Item::createFromFieldItem($item)
				);
			}

			$instance->items = $resultItems;
		}
		if ($field->subfields !== null)
		{
			$subfields = new SubFieldCollection();
			foreach ($field->subfields as $subField)
			{
				$subfields->addItem(
					SubField::createFromFieldItem($subField)
				);
			}

			$instance->subfields = $subfields;
		}

		return $instance;
	}
}