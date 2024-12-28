<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

use Bitrix\BIConnector\Integration\Superset\CultureFormatter;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto\User;
use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorEventLogger;
use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorLogger;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Registrar;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\SupersetStatusOptionContainer;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\IO;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

final class Integrator
{
	private const PROXY_ACTION_REGISTER_PORTAL = '/portal/register';
	private const PROXY_ACTION_VERIFY_PORTAL = '/portal/verify';
	private const PROXY_ACTION_PING_SUPERSET = '/instance/ping';
	private const PROXY_ACTION_START_SUPERSET = '/instance/start';
	private const PROXY_ACTION_FREEZE_SUPERSET = '/instance/freeze';
	private const PROXY_ACTION_UNFREEZE_SUPERSET = '/instance/unfreeze';
	private const PROXY_ACTION_DELETE_SUPERSET = '/instance/delete';
	private const PROXY_ACTION_CHANGE_BI_TOKEN_SUPERSET = '/instance/changeToken';
	private const PROXY_ACTION_REFRESH_DOMAIN_CONNECTION = '/instance/refreshDomain';
	private const PROXY_ACTION_CLEAR_CACHE = '/instance/clearCache';
	private const PROXY_ACTION_LIST_DASHBOARD = '/dashboard/list';
	private const PROXY_ACTION_DASHBOARD_DETAIL = '/dashboard/get';
	private const PROXY_ACTION_GET_EMBEDDED_DASHBOARD_CREDENTIALS = '/dashboard/embedded/get';
	private const PROXY_ACTION_COPY_DASHBOARD = '/dashboard/copy';
	private const PROXY_ACTION_EXPORT_DASHBOARD = '/dashboard/export';
	private const PROXY_ACTION_DELETE_DASHBOARD = '/dashboard/delete';
	private const PROXY_ACTION_IMPORT_DASHBOARD = '/dashboard/import';
	private const PROXY_ACTION_CREATE_USER = '/user/create';
	private const PROXY_ACTION_GET_LOGIN_URL = '/user/getLoginUrl';
	private const PROXY_ACTION_UPDATE_USER = '/user/update';
	private const PROXY_ACTION_USER_ACTIVATE = '/user/activate';
	private const PROXY_ACTION_USER_DEACTIVATE = '/user/deactivate';
	private const PROXY_ACTION_USER_SET_EMPTY_ROLE = '/user/setEmptyRole';
	private const PROXY_ACTION_USER_SYNC_PROFILE = '/user/syncProfile';
	private const PROXY_ACTION_UPDATE_DASHBOARD = '/dashboard/update';
	private const PROXY_ACTION_IMPORT_DATASET = '/dataset/import';
	private const PROXY_ACTION_CREATE_EMPTY_DASHBOARD = '/dashboard/createEmpty';
	private const PROXY_ACTION_SET_DASHBOARD_OWNER = '/dashboard/setOwner';
	private const PROXY_ACTION_CHANGE_DASHBOARD_OWNER = '/dashboard/changeOwner';
	private const PROXY_ACTION_LIST_DATASET = '/dataset/list';
	private const PROXY_ACTION_GET_DATASET = '/dataset/get';
	private const PROXY_ACTION_CREATE_DATASET = '/dataset/create';
	private const PROXY_ACTION_DELETE_DATASET = '/dataset/delete';
	private const PROXY_ACTION_GET_DATASET_URL = '/dataset/getUrl';

	static private self $instance;

	private Sender $sender;
	private IntegratorLogger $logger;


	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function createDefaultRequest(string $action): IntegratorRequest
	{
		$statusContainer = new SupersetStatusOptionContainer();

		$registrarMiddleware = new Middleware\Registrar(Registrar::getRegistrar(), $this->logger);
		$request = (new IntegratorRequest($this->sender))
			->setAction($action)
			->addBefore(new Middleware\TariffRestriction())
			->addBefore($registrarMiddleware)
			->addBefore(new Middleware\ReadyGate($statusContainer, $this->logger))
			->addBefore(new Middleware\UserAccess())
			->addAfter($registrarMiddleware)
			->addAfter(new Middleware\Logger($this->logger))
			->addAfter(new Middleware\StatusArbiter($this->logger))
		;

		return $request;
	}

	private static function getDefaultLogger(): IntegratorLogger
	{
		return new IntegratorEventLogger();
	}

	private function __construct()
	{
		$this->sender = new Sender();
		$this->logger = self::getDefaultLogger();
	}

	public function setSender(Sender $sender): void
	{
		$this->sender = $sender;
	}

	// region Service methods

	/**
	 * Register new portal on proxy-server. On success - got unique portal ID for authentication in proxy.
	 *
	 * On request save unique ID from response to config by Client middleware,
	 * after that portal make verify request to proxy-server, for verify this portal ID
	 *
	 * @see self::verifyPortal()
	 *
	 * @return IntegratorResponse<string>
	 */
	public function registerPortal(): IntegratorResponse
	{
		$response = $this
			->createDefaultRequest(self::PROXY_ACTION_REGISTER_PORTAL)
			->removeBefore(Middleware\ReadyGate::getMiddlewareId())
			->removeBefore(Middleware\Registrar::getMiddlewareId())
			->removeBefore(Middleware\UserAccess::getMiddlewareId())
			->removeAfter(Middleware\StatusArbiter::getMiddlewareId())
			->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$clientId = $response->getData()['portalId'] ?? null;

		return $response->setData($clientId);
	}

	/**
	 * Verify portal ID on proxy-server, created by <b>registerPortal</b> action.
	 *
	 * On request proxy-server make verify request to verify.php endpoint and return verify result in this method
	 *
	 * Method for portal ID verify
	 * @see install/public/bitrix/biconstructor/verify.php
	 *
	 * @return IntegratorResponse
	 */
	public function verifyPortal(): IntegratorResponse
	{
		return $this
			->createDefaultRequest(self::PROXY_ACTION_VERIFY_PORTAL)
			->removeBefore(Middleware\ReadyGate::getMiddlewareId())
			->removeBefore(Middleware\Registrar::getMiddlewareId())
			->removeBefore(Middleware\UserAccess::getMiddlewareId())
			->removeAfter(Middleware\StatusArbiter::getMiddlewareId())
			->perform()
		;
	}

	/**
	 * Returns response with list of dashboards info on successful request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param array $ids External ids of dashboards.
	 * @return IntegratorResponse<Dto\DashboardList>
	 */
	public function getDashboardList(array $ids): IntegratorResponse
	{
		if (empty($ids))
		{
			return new IntegratorResponse(
				status: IntegratorResponse::STATUS_OK,
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
			'neqIds' => array_column($inversedIdList, 'EXTERNAL_ID'),
		];

		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_LIST_DASHBOARD)
				->setParams($requestParams)
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $response->getData();

		$innerDashboards = $resultData['dashboards'] ?? [];
		$commonCount = $resultData['common_count'] ?? 0;
		$dashboards = [];

		foreach ($innerDashboards as $dashboardData)
		{
			$jsonMetadata = $this->decode($dashboardData['json_metadata']) ?? [];
			$dateModify = null;
			if (isset($dashboardData['timestamp_modify']))
			{
				$dateModify = DateTime::createFromTimestamp((int)$dashboardData['timestamp_modify']);
			}

			$dashboards[] = new Dto\Dashboard(
				id: $dashboardData['id'],
				title: $dashboardData['title'],
				url: $dashboardData['url'] ?? '',
				editUrl: $dashboardData['edit_url'] ?? '',
				isEditable: $dashboardData['is_editable'] ?? false,
				published: $dashboardData['published'] ?? true,
				nativeFilterConfig: $jsonMetadata['native_filter_configuration'] ?? [],
				dateModify: $dateModify,
			);
		}

		$dashboardList = new Dto\DashboardList(
			dashboards: $dashboards,
			commonCount: $commonCount,
		);

		return $response->setData($dashboardList);
	}

	/**
	 * Returns response with dashboard with requested id.
	 *
	 * @param int $dashboardId
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function getDashboardById(int $dashboardId): IntegratorResponse
	{
		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_DASHBOARD_DETAIL)
				->setParams(['id' => $dashboardId])
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$responseData = $response->getData();
		$dashboardData = $responseData['dashboard'] ?? null;

		$dashboard = null;
		if ($dashboardData)
		{
			$jsonMetadata = $this->decode($dashboardData['json_metadata']) ?? [];
			$dateModify = null;
			if (isset($dashboardData['timestamp_modify']))
			{
				$dateModify = DateTime::createFromTimestamp((int)$dashboardData['timestamp_modify']);
			}

			$dashboard = new Dto\Dashboard(
				id: $dashboardData['id'],
				title: $dashboardData['title'],
				url: $dashboardData['url'] ?? '',
				editUrl: $dashboardData['edit_url'] ?? '',
				isEditable: $dashboardData['is_editable'] ?? false,
				published: $dashboardData['published'] ?? true,
				nativeFilterConfig: $jsonMetadata['native_filter_configuration'] ?? [],
				dateModify: $dateModify,
			);
		}

		return $response->setData($dashboard);
	}

	/**
	 * Returns response with dashboard credentials to embed on successful request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param int $dashboardId
	 * @return IntegratorResponse<Dto\DashboardEmbeddedCredentials>
	 */
	public function getDashboardEmbeddedCredentials(int $dashboardId): IntegratorResponse
	{
		$requestParams = [
			'id' => $dashboardId,
		];

		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_GET_EMBEDDED_DASHBOARD_CREDENTIALS)
				->setParams($requestParams)
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$credentialsData = $response->getData();
		if ($credentialsData)
		{
			$response->setData(new Dto\DashboardEmbeddedCredentials(
				uuid: $credentialsData['uuid'],
				guestToken: $credentialsData['guest_token'],
				supersetDomain: $credentialsData['domain'],
			));
		}

		return $response;
	}

	/**
	 * Updates supersetUser
	 *
	 * @param User $user
	 * @return IntegratorResponse
	 */
	public function updateUser(Dto\User $user): IntegratorResponse
	{
		$parameters = [
			'email' => $user->email,
			'username' => $user->userName,
			'first_name' => $user->firstName,
			'last_name' => $user->lastName,
		];

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_UPDATE_USER)
				->setParams(['fields' => $parameters])
				->setUser($user)
				->perform()
		;
	}

	/**
	 * Activates superset user
	 *
	 * @param User $user
	 * @return IntegratorResponse
	 */
	public function activateUser(Dto\User $user): IntegratorResponse
	{
		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_USER_ACTIVATE)
				->setUser($user)
				->perform()
		;
	}

	/**
	 * Deactivates superset user
	 *
	 * @param User $user
	 * @return IntegratorResponse
	 */
	public function deactivateUser(Dto\User $user): IntegratorResponse
	{
		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_USER_DEACTIVATE)
				->setUser($user)
				->perform()
		;
	}

	/**
	 * Sets empty role for superset user
	 *
	 * @param User $user
	 * @return IntegratorResponse
	 */
	public function setEmptyRole(Dto\User $user): IntegratorResponse
	{
		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_USER_SET_EMPTY_ROLE)
				->setUser($user)
				->perform()
		;
	}

	/**
	 * Returns response with ID of copied dashboard on success request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param int $dashboardId
	 * @param string $name
	 * @return IntegratorResponse
	 */
	public function copyDashboard(int $dashboardId, string $name): IntegratorResponse
	{
		$requestParams = [
			'id' => $dashboardId,
			'name' => $name,
		];

		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_COPY_DASHBOARD)
				->setParams($requestParams)
				->perform()
		;

		return $response->setData($response->getData()['dashboard']);
	}

	/**
	 * Returns stream with file of exported dashboard on success request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param int $dashboardId
	 * @param array $dashboardSettings
	 * @return IntegratorResponse
	 */
	public function exportDashboard(int $dashboardId, array $dashboardSettings = []): IntegratorResponse
	{
		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_EXPORT_DASHBOARD)
				->setParams(
					[
						'id' => $dashboardId,
						'dashboardSettings' => $dashboardSettings,
					]
				)
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$content = $response->getData()['body'];
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
	 * Uses external ids of dashboards.
	 * Returns response with result of deleting dashboards.
	 * If response code is not OK - returns empty data.
	 *
	 * @param array $dashboardIds External ids of dashboards.
	 * @return IntegratorResponse<int>
	 */
	public function deleteDashboard(array $dashboardIds): IntegratorResponse
	{

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_DELETE_DASHBOARD)
				->setParams(['ids' => $dashboardIds])
				->perform()
		;
	}

	/**
	 * Returns response with result of start superset.
	 * If status code is OK/IN_PROGRESS - superset was started.
	 *
	 * @param string $biconnectorToken
	 * @return IntegratorResponse<Array<string,string>>
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

		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_START_SUPERSET)
				->removeBefore(Middleware\ReadyGate::getMiddlewareId())
				->setParams($requestParams)
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$responseData = [
			'token' => $response->getData()['token'],
			'superset_address' =>$response->getData()['superset_address'] ?? null,
		];

		return $response->setData($responseData);
	}

	/**
	 * Returns response with result of freeze superset.
	 * $params['reason'] - reason of freezing superset.
	 * If the reason is "TARIFF" - instanse won't activate automatically.
	 * Use unfreezeSuperset method with same reason to unfreeze instance.
	 *
	 * @param array $params
	 * @return IntegratorResponse<null>
	 */
	public function freezeSuperset(array $params = []): IntegratorResponse
	{
		$requestParams = [];
		if (isset($params['reason']))
		{
			$requestParams['reason'] = $params['reason'];
		}

		return (new IntegratorRequest($this->sender))
			->setAction(self::PROXY_ACTION_FREEZE_SUPERSET)
			->setParams($requestParams)
			->addAfter(new Middleware\Logger($this->logger))
			->perform()
		;
	}

	/**
	 * Returns response with result of unfreeze superset.
	 * $params['reason'] - reason of previous freezing superset.
	 * If the reason is "TARIFF" - instance will be activated if it was freezed only with TARIFF reason.
	 *
	 * @param array $params
	 * @return IntegratorResponse<null>
	 */
	public function unfreezeSuperset(array $params = []): IntegratorResponse
	{
		$requestParams = [];
		if (isset($params['reason']))
		{
			$requestParams['reason'] = $params['reason'];
		}

		return (new IntegratorRequest($this->sender))
			->setAction(self::PROXY_ACTION_UNFREEZE_SUPERSET)
			->setParams($requestParams)
			->addAfter(new Middleware\Logger($this->logger))
			->perform()
		;
	}

	/**
	 * Returns response with result of delete superset.
	 * If status code is OK/IN_PROGRESS - superset was deleted.
	 *
	 * @return IntegratorResponse<null>
	 */
	public function deleteSuperset(): IntegratorResponse
	{
		return (new IntegratorRequest($this->sender))
			->setAction(self::PROXY_ACTION_DELETE_SUPERSET)
			->addAfter(new Middleware\Logger($this->logger))
			->perform()
		;
	}

	/**
	 * Change bi token for getting data from apache superset
	 * If response is OK - the token was changed successfully.
	 *
	 * @param string $biconnectorToken
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function changeBiconnectorToken(string $biconnectorToken): IntegratorResponse
	{
		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_CHANGE_BI_TOKEN_SUPERSET)
				->setParams(['biconnectorToken' => $biconnectorToken])
				->perform()
		;
	}

	/**
	 * Returns response with result of clear cache superset.
	 * If status code is OK - superset cache was clean.
	 *
	 * @return IntegratorResponse<null>
	 */
	public function clearCache(): IntegratorResponse
	{
		return $this->createDefaultRequest(self::PROXY_ACTION_CLEAR_CACHE)->perform();
	}

	public function refreshDomainConnection(): IntegratorResponse
	{
		return $this->createDefaultRequest(self::PROXY_ACTION_REFRESH_DOMAIN_CONNECTION)->perform();
	}

	/**
	 * Creates user in Superset
	 *
	 * @param Dto\User $user
	 * @return IntegratorResponse
	 */
	public function createUser(Dto\User $user): IntegratorResponse
	{
		$action = self::PROXY_ACTION_CREATE_USER;
		$parameters = [
			'username' => $user->userName,
			'email' => $user->email,
			'first_name' => $user->firstName,
			'last_name' => $user->lastName,
		];

		return
			$this
				->createDefaultRequest($action)
				->setParams(['fields' => $parameters])
				->removeBefore(Middleware\UserAccess::getMiddlewareId())
				->perform()
		;
	}

	/**
	 * Gets login url with jwt
	 *
	 * @return IntegratorResponse
	 */
	public function getLoginUrl(): IntegratorResponse
	{
		return $this->createDefaultRequest(self::PROXY_ACTION_GET_LOGIN_URL)->perform();
	}

	/**
	 * Returns response with dashboard import result.
	 * If response is OK - dashboard was imported successfully.
	 *
	 * @param string $filePath
	 * @param string $appCode
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function importDashboard(
		string $filePath,
		string $appCode,
	): IntegratorResponse
	{

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_IMPORT_DASHBOARD)
				->setParams([
					'filePath' => $filePath,
					'currency' => CultureFormatter::getPortalCurrencySymbol(),
					'appCode' => $appCode,
				])
				->removeAfter(Middleware\StatusArbiter::getMiddlewareId())
				->setMultipart(true)
				->perform()
		;
	}

	/**
	 * Returns status of superset service availability.
	 * If service available - returns true, false otherwise
	 *
	 * @return bool
	 */
	public function ping(): bool
	{
		static $isChecked = false;
		if (!$isChecked)
		{
			$this
				->createDefaultRequest(self::PROXY_ACTION_PING_SUPERSET)
				->removeBefore(Middleware\ReadyGate::getMiddlewareId())
				->perform()
			;
			$isChecked = true;
		}

		return SupersetInitializer::getSupersetStatus() !== SupersetInitializer::SUPERSET_STATUS_ERROR;
	}

	/**
	 * Returns response with dataset import result.
	 * If response is OK - dataset was imported successfully.
	 *
	 * @param string $filePath
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function importDataset(string $filePath): IntegratorResponse
	{
		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_IMPORT_DATASET)
				->setParams([
					'filePath' => $filePath,
					'currency' => CultureFormatter::getPortalCurrencySymbol(),
				])
				->setMultipart(true)
				->perform()
		;
	}

	/**
	 * Returns response with created dashboard result.
	 * If response is OK - dashboard was created successfully.
	 *
	 * @param array $fields
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function createEmptyDashboard(array $fields): IntegratorResponse
	{
		$fields['published'] = false;

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_CREATE_EMPTY_DASHBOARD)
				->setParams(['fields' => $fields])
				->perform()
		;
	}

	/**
	 * Sets owner for dashboard
	 *
	 * @param int $dashboardId
	 * @param Dto\User $user
	 * @return IntegratorResponse
	 */
	public function setDashboardOwner(int $dashboardId, Dto\User $user): IntegratorResponse
	{

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_SET_DASHBOARD_OWNER)
				->setParams(['id' => $dashboardId])
				->setUser($user)
				->perform()
		;
	}

	/**
	 * Sync roles, owners and so on
	 *
	 * @param Dto\User $user
	 * @param array $data
	 * @return IntegratorResponse
	 */
	public function syncProfile(Dto\User $user, array $data): IntegratorResponse
	{
		$dashboards = SupersetDashboardTable::getList([
			'select' => ['EXTERNAL_ID'],
			'filter' => [
				'=STATUS' => SupersetDashboard::getActiveDashboardStatuses(),
				'=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM,
			],
			'cache' => ['ttl' => 3600],
		])->fetchAll();

		$parameters = [
			'role' => $data['role'],
			'dashboardIdList' => $data['dashboardIdList'],
			'dashboardAllIdList' => array_map('intval', array_column($dashboards, 'EXTERNAL_ID')),
		];

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_USER_SYNC_PROFILE)
				->setParams(['fields' => $parameters])
				->setUser($user)
				->perform()
		;
	}


	/**
	 * Update dashboard fields, that allowed in proxy white-list
	 *
	 * @param int $dashboardId external id of edited dashboard
	 * @param array $editedFields fields for edit in superset. Format: *field_name_in_superset* -> *new_value*
	 * @return IntegratorResponse<Array<string|string>> return array of fields that changed
	 */
	public function updateDashboard(int $dashboardId, array $editedFields): IntegratorResponse
	{
		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_UPDATE_DASHBOARD)
				->setParams([
					'id' => $dashboardId,
					'fields' => $editedFields,
				])
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $response->getData();
		if (isset($resultData['changed_fields']))
		{
			$response->setData($resultData['changed_fields']);
		}
		else
		{
			$keys = [];
			foreach ($editedFields as $key => $val)
			{
				$keys[] = htmlspecialcharsbx($key);
			}

			$keys = implode(', ', $keys);
			$error = new Error("Update dashboard returns empty 'changed_fields'. Try to change: {$keys}");

			$this->logger->logMethodErrors(
				self::PROXY_ACTION_UPDATE_DASHBOARD,
				$response->getStatus(),
				[$error]
			);

			$response->addError($error);
		}

		return $response;
	}

	/**
	 * Changes dashboard owners
	 *
	 * @param int $dashboardId
	 * @param Dto\User $userFrom
	 * @param Dto\User $userTo
	 * @return IntegratorResponse
	 */
	public function changeDashboardOwner(int $dashboardId, Dto\User $userFrom, Dto\User $userTo): IntegratorResponse
	{
		$parameters = [
			'id' => $dashboardId,
			'userFrom' => $userFrom->clientId,
			'userTo' => $userTo->clientId,
		];

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_CHANGE_DASHBOARD_OWNER)
				->setParams($parameters)
				->perform()
		;
	}

	/**
	 * Returns response with list of dataset info on successful request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param array $ids External ids of dashboards.
	 * @return IntegratorResponse
	 */
	public function getDatasetList(array $ids): IntegratorResponse
	{
		if (empty($ids))
		{
			return new IntegratorResponse(
				status: IntegratorResponse::STATUS_OK,
				data: []
			);
		}

		$inversedIdList = ExternalDatasetTable::getList([
			'select' => ['EXTERNAL_ID'],
			'filter' => [
				'!@EXTERNAL_ID' => $ids,
			],
		])->fetchAll();

		$requestParams = [
			'neqIds' => array_column($inversedIdList, 'EXTERNAL_ID'),
		];

		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_LIST_DATASET)
				->setParams($requestParams)
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $response->getData();

		return $response->setData($resultData['datasets']);
	}

	/**
	 * Returns response with dataset info on successful request.
	 *
	 * @param int $id
	 * @return IntegratorResponse
	 */
	public function getDatasetById(int $id): IntegratorResponse
	{
		$requestParams = [
			'id' => $id,
		];

		$response =
			$this
				->createDefaultRequest(self::PROXY_ACTION_GET_DATASET)
				->setParams($requestParams)
				->perform()
		;

		if ($response->hasErrors())
		{
			return $response;
		}

		$resultData = $response->getData();

		return $response->setData($resultData);
	}

	/**
	 * Adds dataset
	 *
	 * @param array $fields
	 * @return IntegratorResponse
	 */
	public function createDataset(array $fields): IntegratorResponse
	{
		$parameters = [
			'name' => $fields['name'],
		];

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_CREATE_DATASET)
				->removeBefore(Middleware\ReadyGate::getMiddlewareId())
				->removeAfter(Middleware\StatusArbiter::getMiddlewareId())
				->setParams(['fields' => $parameters])
				->perform()
			;
	}

	/**
	 * Deletes dataset
	 *
	 * @param int $id
	 * @return IntegratorResponse
	 */
	public function deleteDataset(int $id): IntegratorResponse
	{
		$parameters = [
			'id' => $id,
		];

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_DELETE_DATASET)
				->removeBefore(Middleware\ReadyGate::getMiddlewareId())
				->removeAfter(Middleware\StatusArbiter::getMiddlewareId())
				->setParams($parameters)
				->perform()
			;
	}

	/**
	 * Gets dataset url for creating chart
	 *
	 * @param int $id
	 * @return IntegratorResponse
	 */
	public function getDatasetUrl(int $id): IntegratorResponse
	{
		$parameters = [
			'id' => $id,
		];

		return
			$this
				->createDefaultRequest(self::PROXY_ACTION_GET_DATASET_URL)
				->setParams($parameters)
				->perform()
			;
	}

	// endregion

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
	 * @param string $action
	 * @return bool
	 * @throws ArgumentException
	 */
	public static function isUserRequired(string $action): bool
	{
		$actions = [
			self::PROXY_ACTION_PING_SUPERSET => false,
			self::PROXY_ACTION_START_SUPERSET => false,
			self::PROXY_ACTION_FREEZE_SUPERSET => false,
			self::PROXY_ACTION_UNFREEZE_SUPERSET => false,
			self::PROXY_ACTION_DELETE_SUPERSET => false,
			self::PROXY_ACTION_CHANGE_BI_TOKEN_SUPERSET => false,
			self::PROXY_ACTION_REFRESH_DOMAIN_CONNECTION => false,
			self::PROXY_ACTION_CLEAR_CACHE => false,
			self::PROXY_ACTION_LIST_DASHBOARD => false,
			self::PROXY_ACTION_DASHBOARD_DETAIL => false,
			self::PROXY_ACTION_GET_EMBEDDED_DASHBOARD_CREDENTIALS => false,
			self::PROXY_ACTION_COPY_DASHBOARD => true,
			self::PROXY_ACTION_EXPORT_DASHBOARD => false,
			self::PROXY_ACTION_DELETE_DASHBOARD => false,
			self::PROXY_ACTION_IMPORT_DASHBOARD => false,
			self::PROXY_ACTION_CREATE_USER => true,
			self::PROXY_ACTION_GET_LOGIN_URL => true,
			self::PROXY_ACTION_UPDATE_DASHBOARD => false,
			self::PROXY_ACTION_IMPORT_DATASET => false,
			self::PROXY_ACTION_CREATE_EMPTY_DASHBOARD => true,
			self::PROXY_ACTION_SET_DASHBOARD_OWNER => true,
			self::PROXY_ACTION_CHANGE_DASHBOARD_OWNER => false,
			self::PROXY_ACTION_LIST_DATASET => false,
			self::PROXY_ACTION_GET_DATASET => false,
			self::PROXY_ACTION_CREATE_DATASET => true,
			self::PROXY_ACTION_DELETE_DATASET => true,
		];

		if (!array_key_exists($action, $actions))
		{
			throw new ArgumentException('Action "' . $action . '" is not supported', 'action');
		}

		return $actions[$action];
	}
}
