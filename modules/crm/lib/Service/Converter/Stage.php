<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\EO_Status;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;

class Stage extends OrmObject
{
	/**
	 * @param EO_Status $model
	 * @return array
	 */
	public function toJson($model): array
	{
		/** @noinspection PhpParamsInspection */
		$data = parent::toJson($model);

		if(empty($data['color']))
		{
			if($data['semantics'] === PhaseSemantics::SUCCESS)
			{
				$data['color'] = StatusTable::DEFAULT_SUCCESS_COLOR;
			}
			elseif($data['semantics'] === PhaseSemantics::FAILURE)
			{
				$data['color'] = StatusTable::DEFAULT_FAILURE_COLOR;
			}
			else
			{
				$data['color'] = StatusTable::DEFAULT_PROCESS_COLOR;
			}
		}

		return $data;
	}
}