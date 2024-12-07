<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Document\Upload;

use Bitrix\Sign\Contract;

class File implements Contract\Item
{
	public string $name;
	public string $type;
	public string $content;

	public function __construct(string $name, string $type, string $content)
	{
		$this->name = $name;
		$this->type = $type;
		$this->content = $content;
	}
}