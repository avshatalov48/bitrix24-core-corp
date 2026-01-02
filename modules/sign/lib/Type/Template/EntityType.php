<?php

namespace Bitrix\Sign\Type\Template;

use Bitrix\Sign\Type\ValuesTrait;

enum EntityType: string
{
	use ValuesTrait;

	case TEMPLATE = 'template';
	case FOLDER = 'folder';

	public function isTemplate(): bool
	{
		return $this === self::TEMPLATE;
	}

	public function isFolder(): bool
	{
		return $this === self::FOLDER;
	}
}
