<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ScoringEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityID = $params["ENTITY_ID"];
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$bindings = is_array($params["BINDINGS"]) ? $params["BINDINGS"] : [];
		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::SCORING,
				'TYPE_CATEGORY_ID' => 0,
				'CREATED' => new DateTime(),
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Scoring,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		self::registerBindings($ID, $bindings);
		self::buildSearchContent($ID);
		return $ID;

	}
}