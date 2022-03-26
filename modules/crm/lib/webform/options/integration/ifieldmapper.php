<?php

namespace Bitrix\Crm\WebForm\Options\Integration;

interface IFieldMapper
{
	public function prepareFormFillResult(array $incomeValues) : array;
}