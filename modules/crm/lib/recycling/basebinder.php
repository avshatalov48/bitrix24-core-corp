<?php
namespace Bitrix\Crm\Recycling;

abstract class BaseBinder
{
	abstract public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID);
	abstract public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs);
	abstract public function bindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs);
}