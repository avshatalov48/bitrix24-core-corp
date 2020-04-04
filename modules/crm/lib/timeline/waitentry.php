<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class WaitEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$authorID = isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0;
		if($authorID <= 0)
		{
			$authorID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::WAIT,
				'TYPE_CATEGORY_ID' => 0,
				'CREATED' => new DateTime(),
				'AUTHOR_ID' => $authorID,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Wait,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		self::registerBindings($ID, $bindings);
		return $ID;
	}
}