<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill;

use Bitrix\Sign\Contract;

class MemberFields implements Contract\Item
{
	public string $memberId;
	public FieldCollection $fields;
	public bool $trusted = false;

	public function __construct(string $memberId, FieldCollection $fields, bool $trusted = false)
	{
		$this->memberId = $memberId;
		$this->fields = $fields;
		$this->trusted = $trusted;
	}
}