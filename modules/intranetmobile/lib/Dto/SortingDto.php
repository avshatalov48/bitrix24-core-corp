<?php

namespace Bitrix\IntranetMobile\Dto;

use Bitrix\Intranet\User\UserManager;
use Bitrix\Mobile\Dto\Dto;
use ReflectionClass;

class SortingDto extends Dto
{
	public function __construct(
		private readonly string $type = 'SORT_APH',
	)
	{
		parent::__construct();
	}

	/**
	 * @return ?array Returns null if the constant was not found in the class.
	 */
	public function getType(): ?array
	{
		return (new ReflectionClass(UserManager::class))->getConstant($this->type) ?: null ;
	}
}