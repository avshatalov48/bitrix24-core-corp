<?php

namespace Bitrix\Disk\Type;

use Bitrix\Disk\AttachedObject;

/**
 * @method AttachedObject[] getIterator()
 */
final class AttachedObjectCollection extends TypedCollection
{
	protected function __construct(AttachedObject ...$baseObjects)
	{
		parent::__construct(...$baseObjects);
	}

	protected static function getItemClass(): string
	{
		return AttachedObject::class;
	}
}