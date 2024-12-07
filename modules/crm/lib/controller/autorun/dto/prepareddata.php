<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedEntityTypeId;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

class PreparedData extends Dto
{
	public string $hash;
	public string $gridId;
	public int $entityTypeId;
	public Filter $filter;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'hash'),

			new NotEmptyField($this, 'gridId'),

			new NotEmptyField($this, 'entityTypeId'),
			new DefinedEntityTypeId($this, 'entityTypeId'),
		];
	}
}
