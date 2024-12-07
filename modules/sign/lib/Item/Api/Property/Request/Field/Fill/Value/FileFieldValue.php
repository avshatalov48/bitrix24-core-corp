<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill\Value;

class FileFieldValue extends BaseFieldValue
{
	public string $type;
	public string $content;

	public function __construct(string $type, string $content)
	{
		$this->type = $type;
		$this->content = $content;
	}
}