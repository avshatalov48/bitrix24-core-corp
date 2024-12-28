<?php

namespace Bitrix\Crm\Timeline\Bizproc;

use Bitrix\Crm\Activity\Provider\Bizproc\Workflow;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;

class Entry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		$data = [
			'TYPE_ID' => TimelineType::BIZPROC,
			'TYPE_CATEGORY_ID' => (int)$params['TYPE_CATEGORY_ID'],
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => 0,
			'ASSOCIATED_ENTITY_ID' => 0,
			'SOURCE_ID' => $settings['WORKFLOW_ID'],
		];

		if (!empty($params['ASSOCIATED_ENTITY_TYPE_ID']))
		{
			$data['ASSOCIATED_ENTITY_TYPE_ID'] = $params['ASSOCIATED_ENTITY_TYPE_ID'];
		}

		if (!empty($params['ASSOCIATED_ENTITY_ID']))
		{
			$data['ASSOCIATED_ENTITY_ID'] = $params['ASSOCIATED_ENTITY_ID'];
		}

		$result = TimelineTable::add($data);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		self::registerBindings($createdId, $bindings);

		return $createdId;
	}
}