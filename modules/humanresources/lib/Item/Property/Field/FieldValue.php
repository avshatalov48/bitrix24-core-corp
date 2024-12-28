<?php

namespace Bitrix\HumanResources\item\Property\Field;

use Bitrix\HumanResources\Contract\Item;

class FieldValue implements Item
{
	public string $id = '';
	public array  $value = [];
	public string $type = '';
}