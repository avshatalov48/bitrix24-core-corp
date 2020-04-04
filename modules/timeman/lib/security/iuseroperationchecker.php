<?php
namespace Bitrix\Timeman\Security;

interface IUserOperationChecker
{
	public function canDoOperation($operationName, $options = []);
	public function canDoAnyOperation();
}