<?php
declare(strict_types=1);

namespace Bitrix\Disk\Type;

use Bitrix\Disk\Document\TrackedObject;

/**
 * @method TrackedObject[] getIterator()
 */
final class TrackedObjectCollection extends TypedCollection
{
	protected function __construct(TrackedObject ...$trackedObjects)
	{
		parent::__construct(...$trackedObjects);
	}

	protected static function getItemClass(): string
	{
		return TrackedObject::class;
	}
}