<?php
namespace Bitrix\Crm\Integrity;

class DuplicateListFactory

{
	static public function create(
		$isAutomatic,
		$typeID,
		$entityTypeID,
		$userID,
		$enablePermissionCheck = false,
		$options = null
	)
	{
		if ($isAutomatic)
		{
			return new AutomaticDuplicateList(
				$typeID,
				$entityTypeID,
				$userID,
				$enablePermissionCheck,
				$options
			);
		}
		else
		{
			return new DuplicateList(
				$typeID,
				$entityTypeID,
				$userID,
				$enablePermissionCheck,
				$options
			);
		}
	}
}