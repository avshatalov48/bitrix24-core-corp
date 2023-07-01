<?php

namespace Bitrix\Crm\Timeline\Entity\Object;

use Bitrix\Crm\Timeline\Entity\EO_CustomIcon;
use JsonSerializable;

class CustomIcon extends EO_CustomIcon implements JsonSerializable
{
	public function jsonSerialize()
	{
		return [
			'code' => $this->getCode(),
			'isSystem' => false,
			'fileUri' => \CFile::GetPath($this->getFileId()),
		];
	}
}
