<?php

namespace Bitrix\Disk;


use Bitrix\Main\Entity\Event;
use Bitrix\Main\EventManager;

final class TrashCrumbStorage extends CrumbStorage
{
	protected function fetchNameByObject(BaseObject $object)
	{
		return $object->getOriginalName();
	}
}