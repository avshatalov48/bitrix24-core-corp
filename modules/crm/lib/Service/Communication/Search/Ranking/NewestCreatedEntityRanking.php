<?php

namespace Bitrix\Crm\Service\Communication\Search\Ranking;

class NewestCreatedEntityRanking extends DateRanking
{
	protected function getOrder(): array
	{
		return [
			'MAX_DATE_CREATE' => 'DESC',
			'MAX_DATE_MODIFY' => 'DESC',
			'MAX_ID' => 'DESC',
		];
	}
}
