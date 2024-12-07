<?php

namespace Bitrix\Crm\Service\Communication\Search\Ranking;

use Bitrix\Main\Localization\Loc;

enum RankingTypes: int
{
	case unknown = 0;
	case newestCreatedEntity = 1;
	case newestUpdatedEntity = 2;

	public function title(): string
	{
		return match($this)
		{
			self::newestCreatedEntity => Loc::getMessage('CRM_COMMUNICATION_RANKING_NEWEST_CREATED_ENTITY'),
			self::newestUpdatedEntity => Loc::getMessage('CRM_COMMUNICATION_RANKING_NEWEST_UPDATED_ENTITY'),
			self::unknown => '',
		};
	}

	public static function getValueById(int $id): self
	{
		if ($id === self::newestCreatedEntity->value)
		{
			return self::newestCreatedEntity;
		}

		if ($id === self::newestUpdatedEntity->value)
		{
			return self::newestUpdatedEntity;
		}

		return self::unknown;
	}
}
