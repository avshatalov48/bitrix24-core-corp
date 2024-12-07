<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Dto;

final class Filter extends Dto
{
	public array $filter;

	public function __construct(array $filter)
	{
		// skip validation and casting - filter structure is not defined
		parent::__construct();

		$this->filter = $filter;
	}

	public function toArray(): array
	{
		return $this->filter;
	}
}
