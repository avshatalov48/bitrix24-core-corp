<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

interface SupersetIntegrator
{
	/**
	 * Returns response with list of dashboards info on successful request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param array $ids External ids of dashboards.
	 * @return IntegratorResponse<Dto\DashboardList>
	 */
	public function getDashboardList(array $ids): IntegratorResponse;

	/**
	 * Returns response with dashboard with requested id.
	 *
	 * @param int $dashboardId
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function getDashboardById(int $dashboardId): IntegratorResponse;

	/**
	 * Returns response with dashboard credentials to embed on successful request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param int $dashboardId
	 * @return IntegratorResponse<Dto\DashboardEmbeddedCredentials>
	 */
	public function getDashboardEmbeddedCredentials(int $dashboardId): IntegratorResponse;

	/**
	 * Returns response with ID of copied dashboard on success request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param int $dashboardId
	 * @param string $name
	 * @return IntegratorResponse
	 */
	public function copyDashboard(int $dashboardId, string $name): IntegratorResponse;

	/**
	 * Returns stream with file of exported dashboard on success request.
	 * If response code is not OK - returns empty data.
	 *
	 * @param int $dashboardId
	 * @return IntegratorResponse<int>
	 */
	public function exportDashboard(int $dashboardId): IntegratorResponse;

	/**
	 * Uses external ids of dashboards.
	 * Returns response with result of deleting dashboards.
	 * If response code is not OK - returns empty data.
	 *
	 * @param array $dashboardIds External ids of dashboards.
	 * @return IntegratorResponse<int>
	 */
	public function deleteDashboard(array $dashboardIds): IntegratorResponse;

	/**
	 * Returns response with result of start superset.
	 * If status code is OK/IN_PROGRESS - superset was started.
	 *
	 * @param string $biconnectorToken
	 * @return IntegratorResponse<string>
	 */
	public function startSuperset(string $biconnectorToken): IntegratorResponse;

	/**
	 * Returns response with result of freeze superset.
	 * $params['reason'] - reason of freezing superset.
	 * If the reason is "TARIFF" - instanse won't activate automatically.
	 * Use unfreezeSuperset method with same reason to unfreeze instance.
	 *
	 * @param array $params
	 * @return IntegratorResponse<null>
	 */
	public function freezeSuperset(array $params = []): IntegratorResponse;

	/**
	 * Returns response with result of unfreeze superset.
	 * $params['reason'] - reason of previous freezing superset.
	 * If the reason is "TARIFF" - instance will be activated if it was freezed only with TARIFF reason.
	 *
	 * @param array $params
	 * @return IntegratorResponse<null>
	 */
	public function unfreezeSuperset(array $params = []): IntegratorResponse;

	/**
	 * Returns response with result of delete superset.
	 * If status code is OK/IN_PROGRESS - superset was deleted.
	 *
	 * @return IntegratorResponse<null>
	 */
	public function deleteSuperset(): IntegratorResponse;

	/**
	 * Returns response with result of clear cache superset.
	 * If status code is OK - superset cache was clean.
	 *
	 * @return IntegratorResponse<null>
	 */
	public function clearCache(): IntegratorResponse;

	/**
	 * Returns response with result of start superset.
	 * If status code is OK/IN_PROGRESS - superset was started.
	 *
	 * @return IntegratorResponse<Dto\UserCredentials>
	 */
	public function getSupersetCommonUserCredentials(): IntegratorResponse;

	/**
	 * Changes superset user's password
	 *
	 * @param string $password
	 * @return IntegratorResponse
	 */
	public function changeSupersetCommonUserCredentials(string $password): IntegratorResponse;

	/**
	 * Returns response with dashboard import result.
	 * If response is OK - dashboard was imported successfully.
	 *
	 * @param string $filePath
	 * @param string $appCode
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function importDashboard(string $filePath, string $appCode): IntegratorResponse;

	/**
	 * Return response with result of embedding dashboard.
	 * If response is OK - dashboard was embedded successfully.
	 *
	 * @param int $dashboardId
	 * @return IntegratorResponse
	 */
	public function embedDashboard(int $dashboardId): IntegratorResponse;

	/**
	 * Returns response with dataset import result.
	 * If response is OK - dataset was imported successfully.
	 *
	 * @param string $filePath
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function importDataset(string $filePath): IntegratorResponse;

	/**
	 * Returns response with created dashboard result.
	 * If response is OK - dashboard was created successfully.
	 *
	 * @param array $fields
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function createEmptyDashboard(array $fields): IntegratorResponse;

	/**
	 * Set option that skip required fields in request and return instance
	 *
	 * @return $this
	 */
	public function skipRequireFields(): static;

	/**
	 * Change bi token for getting data from apache superset
	 * If response is OK - the token was changed successfully.
	 *
	 * @param string $biconnectorToken
	 * @return IntegratorResponse<Dto\Dashboard>
	 */
	public function changeBiconnectorToken(string $biconnectorToken): IntegratorResponse;

	/**
	 * Returns status of superset service availability.
	 * If service available - returns true, false otherwise
	 *
	 * @return bool
	 */
	public function ping(): bool;
}
