<?php

namespace Bitrix\Crm\Dto\Exception;

use Bitrix\Crm\Dto\Dto;

class DtoPropertyTypeIsUndefined extends \Bitrix\Main\SystemException
{
	public function __construct(Dto $dto, string $propertyName)
	{
		$dtoName = $dto->getName();
		parent::__construct("Type of property `{$propertyName}` is not defined in {$dtoName}");
	}
}
