<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Api\Property;

class UploadRequest implements Contract\Item
{
	public string $uid;
	public Property\Request\Document\Upload\FileCollection $files;

	public function __construct(string $uid, Property\Request\Document\Upload\FileCollection $files)
	{
		$this->uid = $uid;
		$this->files = $files;
	}
}