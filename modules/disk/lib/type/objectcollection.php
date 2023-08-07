<?php

namespace Bitrix\Disk\Type;

use Bitrix\Disk\BaseObject;

/**
 * @method BaseObject[] getIterator()
 */
final class ObjectCollection extends TypedCollection
{
	protected function __construct(BaseObject ...$baseObjects)
	{
		parent::__construct(...$baseObjects);
	}

	protected static function getItemClass(): string
	{
		return BaseObject::class;
	}
}