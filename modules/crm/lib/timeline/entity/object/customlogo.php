<?php

namespace Bitrix\Crm\Timeline\Entity\Object;

use Bitrix\Crm\Timeline\Entity\EO_CustomLogo;
use JsonSerializable;

class CustomLogo extends EO_CustomLogo implements JsonSerializable
{
	public function jsonSerialize(): array
	{
		return [
			'code' => $this->getCode(),
			'isSystem' => false,
			'fileUri' => $this->getFileUri(),
		];
	}

	public function getFileUri(): ?string
	{
		return \CFile::GetPath($this->getFileId());
	}
}
