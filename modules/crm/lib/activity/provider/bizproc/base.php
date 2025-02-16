<?php

namespace Bitrix\Crm\Activity\Provider\Bizproc;

use Bitrix\Crm\Activity\Provider\Base as BaseActivityProvider;

abstract class Base extends BaseActivityProvider
{
	abstract public static function getProviderTypeId(): string;

	public static function getCustomViewLink(array $activityFields): ?string
	{
		static $cache = [];

		$filter = [
			'=OWNER_ID' => $activityFields['OWNER_ID'],
			'=OWNER_TYPE_ID' => $activityFields['OWNER_TYPE_ID'],
			'=PROVIDER_ID' => static::getId(),
			'=PROVIDER_TYPE_ID' => static::getProviderTypeId(),
		];
		$stringFilter = \Bitrix\Main\Web\Json::encode($filter);
		if (is_null($cache[$stringFilter] ?? null))
		{
			$result = \Bitrix\Crm\ActivityTable::getList([
				'filter' => $filter,
				'select' => ['ORIGIN_ID']
			]);
			if ($activity = $result->fetch())
			{
				$cache[$stringFilter] = $activity;

				return \CComponentEngine::MakePathFromTemplate(
					'/company/personal/bizproc/#WORKFLOW_ID#/',
					[
						'WORKFLOW_ID' => $activity['ORIGIN_ID'],
					]
				);
			}
		}
		else
		{
			return \CComponentEngine::MakePathFromTemplate(
				'/company/personal/bizproc/#WORKFLOW_ID#/',
				[
					'WORKFLOW_ID' => $cache[$stringFilter]['ORIGIN_ID'],
				]
			);
		}

		return parent::getCustomViewLink($activityFields);
	}
}