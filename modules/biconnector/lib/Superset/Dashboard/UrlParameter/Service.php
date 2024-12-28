<?php

namespace Bitrix\BIConnector\Superset\Dashboard\UrlParameter;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

final class Service
{
	public function __construct(private readonly SupersetDashboard $dashboard)
	{
	}

	/**
	 * @return Parameter[]
	 */
	public function getUrlParameters(): array
	{
		$parameters = [];
		if (!$this->dashboard->isUrlParamsFilled())
		{
			$this->dashboard->fillUrlParams();
		}

		foreach ($this->dashboard->getUrlParams()->getCodeList() as $code)
		{
			$parameter = Parameter::tryFrom($code);
			if ($parameter)
			{
				$parameters[] = $parameter;
			}
		}

		return $parameters;
	}

	/**
	 * @param array $params
	 * @param array $scopes
	 *
	 * @return Result
	 */
	public function saveDashboardParams(array $params, array $scopes): Result
	{
		$dashboardId = $this->dashboard->getId();
		$result = new Result();
		$db = Application::getConnection();
		try
		{
			$db->startTransaction();
			$existingParams = SupersetDashboardUrlParameterTable::getList([
				'filter' => [
					'=DASHBOARD_ID' => $dashboardId,
				],
			])->fetchCollection();

			foreach ($existingParams as $param)
			{
				$param->delete();
			}

			$compatibleParams = $this->getCompatibleParams($scopes);
			foreach ($params as $param)
			{
				if (
					in_array($param, $compatibleParams, true)
					&& Parameter::tryFrom($param) !== null
				)
				{
					SupersetDashboardUrlParameterTable::createObject()
						->setDashboardId($dashboardId)
						->setCode($param)
						->save()
					;
				}
			}

			$db->commitTransaction();
		}
		catch (\Exception $e)
		{
			$db->rollbackTransaction();
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * Get compatible params by array of scope codes.
	 *
	 * @param array $scopes
	 *
	 * @return array Array of param codes.
	 */
	private function getCompatibleParams(array $scopes): array
	{
		$fullMap = ScopeMap::getMap();
		$scopeMap = [];
		foreach ($scopes as $scope)
		{
			if (isset($fullMap[$scope]))
			{
				$scopeMap[] = array_map(static fn(Parameter $param) => $param->code(), $fullMap[$scope]);
			}
		}

		$availableParams = [];
		if (count($scopeMap) >= 1)
		{
			$availableParams = array_intersect(...$scopeMap);
		}

		$globalParams = array_map(static fn(Parameter $param) => $param->code(), ScopeMap::getGlobals());
		$availableParams = array_merge($availableParams, $globalParams);

		return $availableParams;
	}

	/**
	 * @return bool
	 */
	public function isExistScopeParams(): bool
	{
		$globals = ScopeMap::getGlobals();
		foreach ($this->getUrlParameters() as $parameter)
		{
			if (!in_array($parameter, $globals, true))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getGlobalValues(): array
	{
		$values = [];
		$globals = ScopeMap::getGlobals();
		foreach ($this->getUrlParameters() as $parameter)
		{
			if (in_array($parameter, $globals, true))
			{
				$values[$parameter->code()] = ScopeMap::loadGlobalValue($parameter);
			}
		}

		return $values;
	}

	/**
	 * @param Uri $url
	 * @param array $parameters
	 *
	 * @return string
	 */
	public function getEmbeddedUrl(array $values = [], array $externalParams = []): string
	{
		$url = $this->dashboard->getDetailUrl();
		$parameters = [];

		foreach ($this->getUrlParameters() as $parameter)
		{
			if (isset($values[$parameter->code()]))
			{
				$parameters[$parameter->code()] = $values[$parameter->code()];
			}
			elseif (in_array($parameter, ScopeMap::getGlobals(), true))
			{
				$parameters[$parameter->code()] = ScopeMap::loadGlobalValue($parameter);
			}
		}

		if (!empty($parameters))
		{
			$externalParams = array_merge(
				$externalParams,
				['params' => self::encode($parameters)]
			);
		}

		$url->addParams($externalParams);

		return $url->getUri();
	}

	/**
	 * @param array $variables
	 *
	 * @return string
	 */
	public static function encode(array $variables): string
	{
		return Uri::urnEncode(
			base64_encode(
				(new Signer())->sign(Json::encode($variables), self::getSalt())
			)
		);
	}

	/**
	 * @param string $encoded
	 *
	 * @return array|null
	 */
	public static function decode(string $encoded): ?array
	{
		try {
			$encoded = Uri::urnDecode($encoded);
			$encoded = base64_decode($encoded);
			$json = (new Signer())->unsign($encoded, self::getSalt());

			return Json::decode($json);
		}
		catch (\Exception $e)
		{
		}

		return null;
	}

	private static function getSalt(): ?string
	{
		$id = CurrentUser::get()->getId();

		return $id > 0 ? "dashboard_user_{$id}" : null;
	}
}