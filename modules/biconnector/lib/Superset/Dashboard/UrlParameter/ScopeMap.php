<?php

namespace Bitrix\BIConnector\Superset\Dashboard\UrlParameter;

use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Engine\CurrentUser;

final class ScopeMap
{

	/**
	 * @return Parameter[]
	 */
	public static function getGlobals(): array
	{
		return [
			Parameter::CurrentUser,
		];
	}

	/**
	 * @return Parameter[][]
	 */
	public static function getMap(): array
	{
		return [
//			ScopeService::BIC_SCOPE_BIZPROC_TEMPLATE_ITEM => [
//				Parameter::BizprocItemId,
//			],
		];
	}

	/**
	 * @param string $scopeCode
	 *
	 * @return Parameter[]|null
	 */
	public static function getScopeParameters(string $scopeCode): ?array
	{
		return self::getMap()[$scopeCode] ?? null;
	}


	/**
	 * @param Parameter $code
	 *
	 * @return mixed
	 */
	public static function loadGlobalValue(Parameter $code): mixed
	{
		if ($code === Parameter::CurrentUser)
		{
			return (int)CurrentUser::get()->getId();
		}

		return null;
	}
}