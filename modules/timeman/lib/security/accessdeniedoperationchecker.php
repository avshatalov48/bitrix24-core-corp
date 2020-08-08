<?php
namespace Bitrix\Timeman\Security;

class AccessDeniedOperationChecker implements IUserOperationChecker
{
	public function canDoOperation($operationName, $options = [])
	{
		return false;
	}

	public function canDoAnyOperation()
	{
		return false;
	}
}