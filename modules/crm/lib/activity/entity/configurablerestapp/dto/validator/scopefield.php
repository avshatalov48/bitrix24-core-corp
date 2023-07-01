<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\EnumField;

final class ScopeField extends EnumField
{
	public function __construct(Dto $dto, string $fieldToCheck)
	{
		parent::__construct($dto, $fieldToCheck, ['web', 'mobile']);
	}
}
