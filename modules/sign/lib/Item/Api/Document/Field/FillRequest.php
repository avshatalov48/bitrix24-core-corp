<?php

namespace Bitrix\Sign\Item\Api\Document\Field;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Api\Property;

class FillRequest implements Contract\Item
{
	public string $documentUid;
	public Property\Request\Field\Fill\MemberFieldsCollection $fields;

	public function __construct(
		string $documentUid,
		Property\Request\Field\Fill\MemberFieldsCollection $fields
	)
	{
		$this->documentUid = $documentUid;
		$this->fields = $fields;
	}
}