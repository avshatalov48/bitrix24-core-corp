<?php

namespace Bitrix\BIConnector\Controller;

use Bitrix\BIConnector\Integration\Superset\Integrator\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\DashboardGrid;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\BIConnector\Superset\Logger\Logger;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Uri;

class Dashboard extends Controller
{
	/**
	 * @return array
	 */
	protected function getDefaultPreFilters(): array
	{
		$additionalFilters = [];
		if (Loader::includeModule('intranet'))
		{
			$additionalFilters[] = new IntranetUser();
		}

		return [
			...parent::getDefaultPreFilters(),
			...$additionalFilters,
		];
	}

	public function getPrimaryAutoWiredParameter(): ExactParameter
	{
		return new ExactParameter(
			Model\Dashboard::class,
			'dashboard',
			function($className, $id)
			{
				$superset = new SupersetController(ProxyIntegrator::getInstance());
				$dashboard = $superset->getDashboardRepository()->getById($id);
				if (!$dashboard)
				{
					$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_ERROR_NOT_FOUND')));

					return null;
				}

				return $dashboard;
			}
		);
	}

	public function copyAction(Model\Dashboard $dashboard): ?array
	{
		$integrator = ProxyIntegrator::getInstance();
		$superset = new SupersetController($integrator);
		$externalId = $dashboard->getExternalId();
		$dashboardId = $dashboard->getId();
		if ((int)$externalId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_ERROR_NOT_FOUND')));

			return null;
		}
		$newTitle = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_COPIED_DASHBOARD_TITLE', [
			'#DASHBOARD_TITLE#' => $dashboard->getTitle(),
		]);
		$response = $integrator->copyDashboard($externalId, $newTitle);
		/** @var array $data */
		$data = $response->getData();
		if (!$data)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_COPY_ERROR')));

			return null;
		}

		$copiedDashboardExternalId = (int)$data['id'];
		$title = $data['title'] ?? '';
		$filter = new EmbeddedFilter\DateTime($dashboard);

		$addData = [
			'EXTERNAL_ID' => $copiedDashboardExternalId,
			'TITLE' => $title,
			'SOURCE_ID' => $dashboard->getId(),
			'APP_ID' => $dashboard->getAppId(),
			'TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM,
			'CREATED_BY_ID' => $this->getCurrentUser()?->getId(),
		];

		if ($dashboard->getField('FILTER_PERIOD'))
		{
			$addData['FILTER_PERIOD'] = $filter->getPeriod();
		}

		if ($filter->getPeriod() === EmbeddedFilter\DateTime::PERIOD_RANGE)
		{
			$addData['DATE_FILTER_START'] = new Date($filter->getDateStart());
			$addData['DATE_FILTER_END'] = new Date($filter->getDateEnd());
		}

		$addResult = SupersetDashboardTable::add($addData);
		if (!$addResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_COPY_ERROR')));

			return null;
		}

		$copiedDashboardId = $addResult->getId();
		$dashboard = $superset->getDashboardRepository()->getById($copiedDashboardId);
		if ($dashboard)
		{
			$gridRow = $this->prepareGridRow($dashboard);
			$data['id'] = $copiedDashboardId;
			$data['detail_url'] = "/bi/dashboard/detail/{$copiedDashboardId}/?SOURCE={$dashboardId}";
			$data['columns'] = $gridRow['columns'];
			$data['actions'] = $gridRow['actions'];

			return ['dashboard' => $data];
		}

		return null;
	}

	public function exportAction(Model\Dashboard $dashboard): ?array
	{
		if (!MarketDashboardManager::getInstance()->isExportEnabled())
		{
			return null;
		}

		$integrator = ProxyIntegrator::getInstance();
		$externalDashboardId = $dashboard->getExternalId();
		if ((int)$externalDashboardId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_ERROR_NOT_FOUND')));

			return null;
		}

		$response = $integrator->exportDashboard($externalDashboardId);
		if ($response->hasErrors())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_EXPORT_ERROR')));

			return null;
		}

		/** @var array $data */
		$data = $response->getData();
		if ($data)
		{
			$contentSize = $data['contentSize'];
			$filePath = $data['filePath'];
			if ((int)$contentSize <= 0)
			{
				$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_EXPORT_ERROR')));

				return null;
			}

			return ['filePath' => $filePath];
		}

		return null;
	}

	public function deleteAction(Model\Dashboard $dashboard): ?bool
	{
		$dashboardId = $dashboard->getId();
		$hasCopiedDashboards = SupersetDashboardTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'=SOURCE_ID' => $dashboardId,
			],
		])->fetch();

		if ($hasCopiedDashboards)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_DELETE_ERROR_COPIED')));

			return null;
		}

		if ($dashboard->isSystemDashboard())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_DELETE_ERROR_SYSTEM')));

			return null;
		}

		if ($dashboard->isMarketDashboard())
		{
			$appCode = $dashboard->getAppId();
			$marketManager = MarketDashboardManager::getInstance();
			$uninstallResult = $marketManager->handleUninstallMarketApp($appCode);
			if (!$uninstallResult->isSuccess())
			{
				$this->addErrors($uninstallResult->getErrors());

				return null;
			}
		}

		if ($dashboard->isEditable())
		{
			$externalDashboardId = $dashboard->getExternalId();
			$response = ProxyIntegrator::getInstance()->deleteDashboard([$externalDashboardId]);
			if ($response->hasErrors())
			{
				if ($response->getStatus() === IntegratorResponse::STATUS_NOT_FOUND)
				{
					SupersetDashboardTable::delete($dashboardId);

					return true;
				}
				$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_DELETE_ERROR')));

				return null;
			}

			SupersetDashboardTable::delete($dashboardId);
		}

		return true;
	}

	public function restartImportAction(Model\Dashboard $dashboard): ?array
	{
		if ($dashboard->getStatus() !== SupersetDashboardTable::DASHBOARD_STATUS_FAILED)
		{
			return null;
		}

		if (SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_DISABLED)
		{
			SupersetInitializer::startupSuperset();

			$marketManager = MarketDashboardManager::getInstance();
			$systemDashboards = $marketManager->getSystemDashboardApps();
			$existingDashboardInfoList = SupersetDashboardTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=APP_ID' => array_column($systemDashboards, 'CODE'),
				],
			])->fetchAll();

			$systemDashboardIds = array_column($existingDashboardInfoList, 'ID');
			return [
				'restartedDashboardIds' => $systemDashboardIds,
			];
		}

		Application::getInstance()->addBackgroundJob(function () use ($dashboard) {
			MarketDashboardManager::getInstance()->reinstallDashboard($dashboard->getId());
		});

		return [
			'restartedDashboardIds' => [$dashboard->getId()],
		];
	}

	public function getDashboardEmbeddedDataAction(Model\Dashboard $dashboard): ?array
	{
		return [
			'dashboard' => [
				'type' =>  $dashboard->getType(),
				'title' => $dashboard->getTitle(),
				'uuid' => $dashboard->getEmbeddedCredentials()->uuid,
				'id' => $dashboard->getId(),
				'guestToken' => $dashboard->getEmbeddedCredentials()->guestToken,
				'supersetDomain' => $dashboard->getEmbeddedCredentials()->supersetDomain,
				'editUrl' => $dashboard->getEditUrl(),
				'appId' => $dashboard->getAppId(),
				'nativeFilters' => $dashboard->getNativeFilter(),
			],
		];
	}

	public function createEmptyDashboardAction(): ?array
	{
		$name = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_EMPTY_DASHBOARD_NAME');

		$dashboard = SupersetDashboardTable::getRow([
			'select' => ['TITLE'],
			'filter' => ['%TITLE' => $name],
			'order' => ['ID' => 'DESC'],
		]);
		if ($dashboard)
		{
			$number = 0;

			$currentTitle = $dashboard['TITLE'];
			preg_match_all('/\d+/', $currentTitle, $matches);
			if (!empty($matches[0]))
			{
				$number = (int)$matches[0][0];
			}

			$number++;
			$name .= " ($number)";
		}

		$integrator = ProxyIntegrator::getInstance();
		$response = $integrator->createEmptyDashboard([
			'name' => $name,
		]);

		if ($response->getErrors())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_CREATE_EMPTY_ERROR')));

			return null;
		}

		$data = $response->getData();
		if (empty($data['body']))
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_CREATE_EMPTY_ERROR')));

			return null;
		}

		$addDashboardResult = SupersetDashboardTable::add([
			'EXTERNAL_ID' => $data['body']['id'],
			'TITLE' => $data['body']['result']['dashboard_title'],
			'TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM,
			'CREATED_BY_ID' => $this->getCurrentUser()?->getId(),
		]);
		if (!$addDashboardResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_CREATE_EMPTY_ERROR')));

			return null;
		}

		$data = [];
		$superset = new SupersetController(ProxyIntegrator::getInstance());
		$dashboard = $superset->getDashboardRepository()->getById($addDashboardResult->getId());
		if ($dashboard)
		{
			$gridRow = $this->prepareGridRow($dashboard);

			$data['id'] = $addDashboardResult->getId();
			$data['title'] = $dashboard->getTitle();

			$data['columns'] = $gridRow['columns'];
			$data['actions'] = $gridRow['actions'];


			return ['dashboard' => $data];
		}

		return null;
	}

	/**
	 * @example BX.ajax.runAction('biconnector.dashboard.getEditUrl', {data: {'editUrl': ''}});
	 *
	 * @param string $editUrl
	 * @return string
	 */
	public function getEditUrlAction(string $editUrl): string
	{
		$loginUrl = (new SupersetController(ProxyIntegrator::getInstance()))->getLoginUrl();

		if ($loginUrl)
		{
			$url = new Uri($loginUrl);
			$url->addParams([
				'next' => $editUrl,
			]);

			return $url->getLocator();
		}

		return $editUrl;
	}

	private function prepareGridRow(Model\Dashboard $dashboard): array
	{
		$supersetController = new SupersetController(ProxyIntegrator::getInstance());

		$settings = new DashboardSettings([
			'ID' => 'biconnector_superset_dashboard_grid',
			'IS_SUPERSET_AVAILABLE' => $supersetController->isExternalServiceAvailable(),
		]);

		$grid = new DashboardGrid($settings);

		$result = $grid->getRows()->prepareRows([$dashboard->toArray()]);
		$result = current($result);
		if (is_array($result))
		{
			$converter = new Converter(Converter::OUTPUT_JSON_FORMAT);
			$result['actions'] = $converter->process($result['actions']);

			return $result;
		}

		return [];
	}

	public function renameAction(Model\Dashboard $dashboard, string $title): void
	{
		$supersetController = new SupersetController(ProxyIntegrator::getInstance());
		if (!$supersetController->isSupersetEnabled() || !$supersetController->isExternalServiceAvailable())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_RENAME_ERROR_UNAVAILABLE')));

			return;
		}

		$editTitleResult = $this->editDashboardTitle($dashboard, $title);
		if (!$editTitleResult->isSuccess())
		{
			$this->addErrors($editTitleResult->getErrors());
		}
	}


	private function editDashboardTitle(Model\Dashboard $dashboard, string $newTitle): Result
	{
		$result = new Result();
		$newTitle = trim($newTitle);

		$currentTitle = htmlspecialcharsbx($dashboard->getTitle());

		if (empty($dashboard->getEditUrl()))
		{
			$errorMsg = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_RENAME_ERROR_CANNOT_EDIT_TITLE_NOT_FOUND');

			return $result->addError(new Error($errorMsg));
		}

		if (empty($newTitle))
		{
			$errorMsg = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_RENAME_ERROR_CANNOT_EDIT_TITLE_NOT_EMPTY');

			return $result->addError(new Error($errorMsg));
		}

		if ($dashboard->getStatus() !== SupersetDashboardTable::DASHBOARD_STATUS_READY)
		{
			$errorMsg = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_RENAME_ERROR_CANNOT_EDIT_TITLE_NOT_READY');

			return $result->addError(new Error($errorMsg));
		}

		if ($dashboard->getType() !== SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM)
		{
			$errorMsg = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_RENAME_ERROR_CANNOT_EDIT_TITLE_ONLY_CUSTOM');

			return $result->addError(new Error($errorMsg));
		}

		$changeResult = $dashboard->changeTitle($newTitle);
		if (!$changeResult->isSuccess())
		{
			$errorsMsg = [];
			foreach ($changeResult->getErrors() as $error)
			{
				$errorsMsg[] = $error->getMessage();
			}

			$errorMsgDesc = htmlspecialcharsbx(implode(', ', $errorsMsg));
			Logger::logErrors([new Error("Unhandled error while change dashboard (ID: {$dashboard->getId()}) title: {$errorMsgDesc}")]);

			$errorMsg = Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_RENAME_ERROR_CANNOT_EDIT_TITLE_UNHANDLED');

			return $result->addError(new Error($errorMsg));
		}

		return $result;
	}
}
