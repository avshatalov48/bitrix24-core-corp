<?php

namespace Bitrix\Crm\Dto\Exception;

use Bitrix\Crm\Dto\Dto;

class DtoPropertyNotFoundException extends \Bitrix\Main\SystemException
{
	public function __construct(Dto $dto, string $propertyName)
	{
		$dtoName = $dto->getName();
		parent::__construct("Public property `{$propertyName}` not found in {$dtoName}");
	}
}
