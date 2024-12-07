<?php

namespace Bitrix\Tasks\Flow\Controllers\Trait;

use Bitrix\Main\Localization\Loc;
use ReflectionClass;

trait MessageTrait
{
	public function getAccessDeniedError(): string
	{
		return (string)Loc::getMessage('TASKS_FLOW_AJAX_ACCESS_DENIED');
	}

	public function getAccessDeniedDescription(): string
	{
		return (string)Loc::getMessage('TASKS_FLOW_AJAX_ACCESS_DENIED_DESCRIPTION');
	}

	public function getUnknownError(int|string $code = 0): string
	{
		$code = (new ReflectionClass($this))->getShortName() . ': ' . $code;
		return Loc::getMessage('TASKS_FLOW_AJAX_UNKNOWN_ERROR') . ' [' . $code . ']';
	}
}