<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorEventLogger;
use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorLogger;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\IO;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

final class ProxyIntegrator implements SupersetIntegrator
{
	private const PROXY_ACTION_PING_SUPERSET = '/instance/ping';
	private const PROXY_ACTION_START_SUPERSET = '/instance/start';
	private const PROXY_ACTION_FREEZE_SUPERSET = '/instance/freeze';
	private const PROXY_ACTION_UNFREEZE_SUPERSET = '/instance/unfreeze';
	private const PROXY_ACTION_DELETE_SUPERSET = '/instance/delete';
	private const PROXY_ACTION_CHANGE_BI_TOKEN_SUPERSET = '/instance/changeToken';
	private const PROXY_ACTION_REFRESH_DOMAIN_CONNECTION = '/instance/refreshDomain';
	private const PROXY_ACTION_CLEAR_CACHE = '/instance/cache/delete';
	private const PROXY_ACTION_LIST_DASHBOARD = '/dashboard/list';
	private const PROXY_ACTION_DASHBOARD_DETAIL = '/dashboard/get';
	private const PROXY_ACTION_GET_EMBEDDED_DASHBOARD_CREDENTIALS = '/dashboard/embedded/get';
	private const PROXY_ACTION_COPY_DASHBOARD = '/dashboard/copy';
	private const PROXY_ACTION_EXPORT_DASHBOARD = '/dashboard/export';
	private const PROXY_ACTION_DELETE_DASHBOARD = '/dashboard/delete';
	private const PROXY_ACTION_IMPORT_DASHBOARD = '/dashboard/import';
	private const PROXY_ACTION_EMBED_DASHBOARD = '/dashboard/embed';
	private const PROXY_ACTION_GET_COMMON_USER_CREDENTIALS = '/user/get';
	private const PROXY_ACTION_CHANGE_SUPERSET_USER_PASSWORD = '/user/changePassword';
	private const PROXY_ACTION_IMPORT_DATASET = '/dataset/import';
	private const PROXY_ACTION_CREATE_EMPTY_DASHBOARD = '/dashboard/createEmpty';

	static private self $instance;

	private ProxySender $sender;
	private IntegratorLogger $logger;

	private bool $skipFields = false;

	private bool $isServiceStatusChecked = false;
	private bool $isServiceAvailable = true;

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	private static function getDefaultLogger(): IntegratorLogger
	{
		return new IntegratorEventLogger();
	}

	private function __construct()
	{
		$this->sender = new ProxySender();
		$this->logger = self::getDefaultLogger();
	}

	/**
	 * @inheritDoc
	 */
	public function getDashboardList(array $ids): IntegratorResponse
	{
		if (empty($ids))
		{
			return new ProxyIntegratorResponse(
				data: new Dto\DashboardList(
					dashboards: [],
					commonCount: 0,
				)
			);
		}

		$inversedIdList = SupersetDashboardTable::getList([
			'select' => ['EXTERNAL_ID'],
			'filter' => [
				'!@EXTERNAL_ID' => $ids,
			],
		])->fetchAll();

		$requestParams = [
			'ids' => $ids,
			'neqIds' => array_column($inversedIdList, 'EXTERNAL_ID'),
		];

		$result = $this->performRequest(
			action: self::PROXY_ACTION_LIST_DASHBOARD,
			requestParams: $requestParams,
			requiredFields: ['dashboards', 'common_count'],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}
		$resultData = $result->requestResult->getData();

		$innerDashboards = $resultData['data']['dashboards'];
		$commonCount = $resultData['data']['common_count'];
		$dashboards = [];

		$response->setInnerStatus($resultData['status']);

		foreach ($innerDashboards as $dashboardData)
		{
			$jsonMetadata = $this->decode($dashboardData['json_metadata']) ?? [];
			$dashboards[] = new Dto\Dashboard(
				id: $dashboardData['id'],
				title: $dashboardData['title'],
				dashboardStatus: $dashboardData['status'] ?? '',
				url: $dashboardData['url'] ?? '',
				editUrl: $dashboardData['edit_url'] ?? '',
				isEditable: $dashboardData['is_editable'] ?? false,
				nativeFilterConfig: $jsonMetadata['native_filter_configuration'] ?? [],
			);
		}

		$dashboardList = new Dto\DashboardList(
			dashboards: $dashboards,
			commonCount: $commonCount,
		);

		return $response->setData($dashboardList);
	}

	public function getDashboardById(int $dashboardId): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_DASHBOARD_DETAIL,
			requestParams: ['id' => $dashboardId],
			requiredFields: ['dashboard'],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $result->requestResult->getData();
		$dashboardData = $resultData['data']['dashboard'];

		$dashboard = null;
		if ($dashboardData)
		{
			$jsonMetadata = Json::decode($dashboardData['json_metadata']) ?? [];
			$dashboard = new Dto\Dashboard(
				id: $dashboardData['id'],
				title: $dashboardData['title'],
				dashboardStatus: $dashboardData['status'] ?? '',
				url: $dashboardData['url'] ?? '',
				editUrl: $dashboardData['edit_url'] ?? '',
				isEditable: $dashboardData['is_editable'] ?? false,
				nativeFilterConfig: $jsonMetadata['native_filter_configuration'] ?? [],
			);
		}

		return $response->setData($dashboard);
	}

	/**
	 * @inheritDoc
	 */
	public function getDashboardEmbeddedCredentials(int $dashboardId): IntegratorResponse
	{
		$requestParams = [
			'id' => $dashboardId,
		];
		$result = $this->performRequest(
			action: self::PROXY_ACTION_GET_EMBEDDED_DASHBOARD_CREDENTIALS,
			requestParams: $requestParams,
			requiredFields: ['uuid', 'guest_token', 'domain'],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$credentialsData = $result->requestResult->getData()['data'];
		$credentials = new Dto\DashboardEmbeddedCredentials(
			uuid: $credentialsData['uuid'],
			guestToken: $credentialsData['guest_token'],
			supersetDomain: $credentialsData['domain'],
		);

		return $response->setData($credentials);
	}

	/**
	 * @inheritDoc
	 */
	public function copyDashboard(int $dashboardId, string $name): IntegratorResponse
	{
		$requestParams = [
			'id' => $dashboardId,
			'name' => $name,
		];

		$result = $this->performRequest(
			action: self::PROXY_ACTION_COPY_DASHBOARD,
			requestParams: $requestParams,
			requiredFields: ['dashboard'],
		);

		$response = $result->response;

		return $response->setData($result->requestResult->getData()['data']['dashboard']);
	}

	/**
	 * @inheritDoc
	 */
	public function exportDashboard(int $dashboardId): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_EXPORT_DASHBOARD,
			requestParams: ['id' => $dashboardId],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$requestResult = $result->requestResult->getData();
		$content = $requestResult['data']['body'];
		$content = base64_decode($content);
		if ($content <= 0)
		{
			$this->logger->logMethodErrors(
				self::PROXY_ACTION_EXPORT_DASHBOARD,
				'400',
				[
					new Error("File content is empty. DashboardId: $dashboardId"),
				]
			);
			return $response;
		}

		$dashboardName = SupersetDashboardTable::getRow([
			'select' => ['TITLE'],
			'filter' => [
				'=EXTERNAL_ID' => $dashboardId,
			],
		])['TITLE'];
		$fileName = $dashboardName . '.zip';

		$filePath = \CTempFile::GetFileName(md5(uniqid('bic', true)));
		$file = new IO\File($filePath);
		$contentSize = $file->putContents($content);

		$file = \CFile::MakeFileArray($filePath);
		$file['MODULE_ID'] = 'biconnector';
		$file['name'] = $fileName;
		if (\CFile::CheckFile($file, strExt: 'zip') !== '')
		{
			$this->logger->logMethodErrors(
				self::PROXY_ACTION_EXPORT_DASHBOARD,
				'400',
				[
					new Error("Exported file was not found. DashboardId: $dashboardId"),
				]
			);
			return $response;
		}

		$fileId = \CFile::SaveFile($file, 'biconnector/dashboard_export');
		if ((int)$fileId <= 0)
		{
			$this->logger->logMethodErrors(
				self::PROXY_ACTION_EXPORT_DASHBOARD,
				'400',
				[
					new Error("Exported file was not saved. DashboardId: $dashboardId"),
				]
			);
		}
		$newFile = \CFile::GetByID($fileId)->Fetch();

		$responseData = [
			'filePath' => $newFile['SRC'],
			'contentSize' => $contentSize,
		];

		return $response->setData($responseData);
	}

	/**
	 * @inheritDoc
	 */
	public function deleteDashboard(array $dashboardIds): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_DELETE_DASHBOARD,
			requestParams: ['ids' => $dashboardIds],
		);

		return $result->response;
	}

	/**
	 * @inheritDoc
	 */
	public function startSuperset(string $biconnectorToken = ''): IntegratorResponse
	{
		$requestParams = ['biconnectorToken' => $biconnectorToken];
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			$requestParams['userName'] = Application::getConnection()->getDatabase();
		}

		$region = Application::getInstance()->getLicense()->getRegion();
		if (!empty($region))
		{
			$requestParams['region'] = $region;
		}

		$result = $this->performRequest(
			action: self::PROXY_ACTION_START_SUPERSET,
			requestParams: $requestParams,
			requiredFields: ['token'],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		return $response->setData($result->requestResult->getData()['data']['token']);
	}

	/**
	 * @inheritDoc
	 */
	public function freezeSuperset(array $params = []): IntegratorResponse
	{
		$requestParams = [];
		if (isset($params['reason']))
		{
			$requestParams['reason'] = $params['reason'];
		}

		return $this->performRequest(
			action: self::PROXY_ACTION_FREEZE_SUPERSET,
			requestParams: $requestParams,
		)
			->response
		;
	}

	/**
	 * @inheritDoc
	 */
	public function unfreezeSuperset(array $params = []): IntegratorResponse
	{
		$requestParams = [];
		if (isset($params['reason']))
		{
			$requestParams['reason'] = $params['reason'];
		}

		return $this->performRequest(
			action: self::PROXY_ACTION_UNFREEZE_SUPERSET,
			requestParams: $requestParams,
		)
			->response
		;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteSuperset(): IntegratorResponse
	{
		return $this->performRequest(self::PROXY_ACTION_DELETE_SUPERSET)->response;
	}

	/**
	 * @inheritDoc
	 */
	public function changeBiconnectorToken(string $biconnectorToken): IntegratorResponse
	{
		return $this->performRequest(
				action: self::PROXY_ACTION_CHANGE_BI_TOKEN_SUPERSET,
				requestParams: [
					'biconnectorToken' => $biconnectorToken,
				],
			)
			->response
		;
	}

	/**
	 * @inheritDoc
	 */
	public function clearCache(): IntegratorResponse
	{
		return $this->performRequest(self::PROXY_ACTION_CLEAR_CACHE)->response;
	}

	/**
	 * @inheritDoc
	 */
	public function refreshDomainConnection(): IntegratorResponse
	{
		return $this->performRequest(self::PROXY_ACTION_REFRESH_DOMAIN_CONNECTION)->response;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupersetCommonUserCredentials(): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_GET_COMMON_USER_CREDENTIALS,
			requiredFields: ['login', 'password'],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$userCredentialsData = $result->requestResult->getData()['data'];
		$credentials = new Dto\UserCredentials(
			login: $userCredentialsData['login'],
			password: $userCredentialsData['password'],
		);

		return $response->setData($credentials);
	}

	/**
	 * @inheritDoc
	 */
	public function changeSupersetCommonUserCredentials(string $password): IntegratorResponse
	{
		$parameters = [
			'password' => $password,
		];

		$result = $this->performRequest(
			action: self::PROXY_ACTION_CHANGE_SUPERSET_USER_PASSWORD,
			requestParams: $parameters,
			requiredFields: ['login', 'password'],
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$userCredentialsData = $result->requestResult->getData()['data'];
		$credentials = new Dto\UserCredentials(
			login: $userCredentialsData['login'],
			password: $userCredentialsData['password'],
		);

		return $response->setData($credentials);
	}

	/**
	 * @inheritDoc
	 */
	public function importDashboard(
		string $filePath,
		string $appCode,
	): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_IMPORT_DASHBOARD,
			requestParams: [
				'filePath' => $filePath,
				'appCode' => $appCode,
			],
			requiredFields: ['dashboards'],
			isMultipart: true,
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $result->requestResult->getData()['data'];

		return $response->setData($resultData);
	}

	/**
	 * @inheritDoc
	 */
	public function embedDashboard(int $dashboardId): IntegratorResponse
	{
		$result = $this->sender->performRequest(self::PROXY_ACTION_EMBED_DASHBOARD);
		// TODO: IMPLEMENT
		return new ProxyIntegratorResponse(0, null);
	}

	private static function createResponse(Result $result, array $requiredFields = []): ProxyIntegratorResponse
	{
		$response = new ProxyIntegratorResponse();

		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			$response->addError(...$errors);
			$response->setStatus((int)current($errors)->getCode());

			return $response;
		}
		$resultData = $result->getData();
		if (
			(int)$resultData['status'] === ProxyIntegratorResponse::HTTP_STATUS_SERVICE_FROZEN
			&& SupersetInitializer::isSupersetActive()
		)
		{
			$response->setStatus(IntegratorResponse::STATUS_FROZEN);

			return $response;
		}

		if (!empty($requiredFields))
		{
			if (!isset($resultData['data']))
			{
				return $response
					->addError(new Error('Server sends empty data'))
					->setStatus(IntegratorResponse::STATUS_SERVER_ERROR)
					;
			}

			foreach ($requiredFields as $requiredField)
			{
				if (!isset($resultData['data'][$requiredField]))
				{
					$response->addError(new Error("Server response must contain field \"{$requiredField}\""));
				}
			}
		}

		if ($response->hasErrors())
		{
			$response->setStatus(IntegratorResponse::STATUS_SERVER_ERROR);
		}
		else
		{
			$response->setInnerStatus($resultData['status']);
		}

		return $response;
	}

	/**
	 * @param string $action
	 * @param array $requestParams
	 * @param string[] $requiredFields
	 * @param bool $isMultipart
	 * @return PerformingResult
	 */
	private function performRequest(string $action, array $requestParams = [], array $requiredFields = [], bool $isMultipart = false): PerformingResult
	{
		if ($isMultipart)
		{
			$result = $this->sender->performMultipartRequest($action, $requestParams);
		}
		else
		{
			$result = $this->sender->performRequest($action, $requestParams);
		}

		if ($this->skipFields)
		{
			$requiredFields = [];
		}

		$response = self::createResponse($result, $requiredFields);

		if ($response->getStatus() === IntegratorResponse::STATUS_FROZEN && SupersetInitializer::isSupersetActive())
		{
			$this->logger->logMethodInfo($action, $response->getStatus(), 'superset was frozen');
			SupersetInitializer::setSupersetStatus(SupersetInitializer::SUPERSET_STATUS_FROZEN);
			$this->setServiceStatus(true);

			return new PerformingResult(
				response: $response,
				requestResult: $result,
			);
		}
		else if (!$this->isStatusUnsuccessful($response->getStatus()) && SupersetInitializer::isSupersetFrozen())
		{
			$this->logger->logMethodInfo($action, $response->getStatus(), 'superset was unfrozen');
			SupersetInitializer::setSupersetStatus(SupersetInitializer::SUPERSET_STATUS_READY);
		}

		if ($this->isStatusUnsuccessful($response->getStatus()))
		{
			$errors = [new Error('Got unsuccessful status from proxy-service')];
			if ($response->hasErrors())
			{
				array_push($errors, ...$response->getErrors());
			}

			$this->logger->logMethodErrors($action, $response->getStatus(), $errors);
			$this->setServiceStatus(false);
		}
		else if ($response->hasErrors())
		{
			$this->logger->logMethodErrors($action, $result->getData()['status'] ?? '400', $response->getErrors());
		}
		else
		{
			$this->setServiceStatus(true);
		}

		return new PerformingResult(
			response: $response,
			requestResult: $result,
		);
	}

	private function isStatusUnsuccessful(int $status): bool
	{
		return ($status >= 500) && ($status !== ProxyIntegratorResponse::HTTP_STATUS_SERVICE_FROZEN);
	}

	/**
	 * @inheritDoc
	 */
	public function importDataset(string $filePath): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_IMPORT_DATASET,
			requestParams: ['filePath' => $filePath],
			isMultipart: true,
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $result->requestResult->getData()['data'];

		return $response->setData($resultData);
	}

	/**
	 * @inheritDoc
	 */
	public function createEmptyDashboard(array $fields): IntegratorResponse
	{
		$result = $this->performRequest(
			action: self::PROXY_ACTION_CREATE_EMPTY_DASHBOARD,
			requestParams: ['fields' => $fields]
		);

		$response = $result->response;
		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $result->requestResult->getData()['data'];

		return $response->setData($resultData);
	}

	private function decode(string $data)
	{
		try
		{
			return Json::decode($data);
		}
		catch (ArgumentException $e)
		{
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function ping(): bool
	{
		if (SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_LOAD)
		{
			return true;
		}

		if (!$this->isServiceStatusChecked)
		{
			$this->performRequest(self::PROXY_ACTION_PING_SUPERSET);
		}

		return $this->isServiceAvailable;
	}

	protected function setServiceStatus(bool $isAvailable): void
	{
		$this->isServiceStatusChecked = true;
		$this->isServiceAvailable = $isAvailable;
	}

	/**
	 * @inheritDoc
	 */
	public function skipRequireFields(): static
	{
		$this->skipFields = true;

		return $this;
	}
}
