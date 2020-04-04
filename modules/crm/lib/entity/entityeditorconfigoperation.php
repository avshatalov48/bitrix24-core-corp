<?php
namespace Bitrix\Crm\Entity;

class EntityEditorConfigOperation
{
	const UNDEFINED = '';
	const GET = 'GET';
	const SET = 'SET';
	const RESET = 'RESET';
	const FORCE_COMMON_SCOPE_FOR_ALL = 'FORCE_COMMON_SCOPE_FOR_ALL';

	public static function isDefined($scope)
	{
		return(
			$scope == self::GET ||
			$scope === self::SET ||
			$scope === self::RESET ||
			$scope === self::FORCE_COMMON_SCOPE_FOR_ALL
		);
	}
}