<?php


namespace Bitrix\Crm\RestView;


use Bitrix\Rest\Integration\View\Base;

final class Enum extends Base
{

	public function getFields(): array
	{
		return [];
	}

	public function externalizeResult($name, $fields): array
	{
		return $fields;
	}

	public function internalizeArguments($name, $arguments): array
	{
		return $arguments;
	}
}