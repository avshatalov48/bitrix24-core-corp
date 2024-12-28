<?php

namespace Bitrix\BIConnector\Superset\Dashboard\UrlParameter;

use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Engine\CurrentUser;

final class ScopeMap
{
	public const GLOBAL_SCOPE = 'global_scope';
	/**
	 * @return Parameter[]
	 */
	public static function getAvailableParameters(): array
	{
		return [
			Parameter::CurrentUser,
			Parameter::WorkflowTemplateId,
			Parameter::TasksFlowsFlowId,
		];
	}

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
			ScopeService::BIC_SCOPE_WORKFLOW_TEMPLATE => [
				Parameter::WorkflowTemplateId,
			],
			ScopeService::BIC_SCOPE_TASKS_FLOWS_FLOW => [
				Parameter::TasksFlowsFlowId,
			],
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
	 * @param Parameter $parameter
	 *
	 * @return string[]
	 */
	public static function getParameterScopeCodes(Parameter $parameter): array
	{
		if (in_array($parameter, self::getGlobals(), true))
		{
			return [self::GLOBAL_SCOPE];
		}

		$scopes = [];
		foreach (self::getMap() as $scopeCode => $parameters)
		{
			if (in_array($parameter, $parameters, true))
			{
				$scopes[] = $scopeCode;
			}
		}

		return $scopes;
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