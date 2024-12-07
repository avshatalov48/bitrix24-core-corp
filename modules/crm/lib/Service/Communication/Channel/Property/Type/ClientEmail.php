<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property\Type;

use Bitrix\Crm\Service\Communication\Search\EntityFinder;

class ClientEmail extends Base
{
	public function getPreparedValue(): array
	{
		return [
			'FM' => [
				'EMAIL' => [
					'n1' => [
						'VALUE' => $this->value,
						'VALUE_TYPE' => 'WORK',
					],
				],
			],
		];
	}

	public function canUsePreparedValue(): bool
	{
		return true;
	}

	public function appendSearchCriterion(EntityFinder $entityFinder): void
	{
		$entityFinder->appendPhoneCriterion($this->value);
	}
}
