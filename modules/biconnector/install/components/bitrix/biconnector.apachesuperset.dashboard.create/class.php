<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('biconnector');

class ApacheSupersetDashboardCreateComponent
	extends CBitrixComponent
	implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	use Main\ErrorableImplementation;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
	}

	public function configureActions()
	{
		return [];
	}

	public function onPrepareComponentParams($arParams)
	{
		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$checkAccessResult = $this->checkAccess();
		if (!$checkAccessResult->isSuccess())
		{
			$this->arResult['ERROR_MESSAGES'] = $checkAccessResult->getErrorMessages();
			$this->includeComponentTemplate();

			return;
		}

		$this->arResult['TITLE'] = Loc::getMessage('DASHBOARD_CREATE_FORM_TITLE');
		$this->prepareSettings();
		$this->includeComponentTemplate();
	}

	private function prepareSettings(): void
	{
		$this->arResult['SETTINGS'] = [
			'nodeId' => 'dashboard-create-form',
			'componentName' => $this->getName(),
			'signedParameters' => $this->getSignedParameters(),
			'defaultValues' => [
				'title' => $this->getDefaultDashboardTitle(),
			],
			'scopeParamsMap' => $this->getScopeParamsMap(),
		];
	}

	private function getDefaultDashboardTitle(): string
	{
		$name = Loc::getMessage('DASHBOARD_CREATE_FORM_DEFAULT_TITLE');

		$dashboard = SupersetDashboardTable::getRow([
			'select' => ['TITLE'],
			'filter' => ['%TITLE' => $name],
			'order' => ['ID' => 'DESC'],
		]);
		if ($dashboard)
		{
			$currentTitle = $dashboard['TITLE'];
			preg_match_all('/\d+/', $currentTitle, $matches);
			$number = (int)($matches[0][0] ?? 0) + 1;
			$name .= " ($number)";
		}

		return $name;
	}

	private function getScopeParamsMap(): array
	{
		$scopeParamsMap = [];
		$globalParams = UrlParameter\ScopeMap::getGlobals();
		foreach ($globalParams as $globalParam)
		{
			$scopeParamsMap['global'][] = [
				'code' => $globalParam->code(),
				'title' => $globalParam->title(),
				'description' => $globalParam->description(),
			];
		}

		$map = UrlParameter\ScopeMap::getMap();
		foreach ($map as $scopeCode => $scopeParams)
		{
			foreach ($scopeParams as $scopeParam)
			{
				$scopeParamsMap[$scopeCode][] = [
					'code' => $scopeParam->code(),
					'title' => $scopeParam->title(),
					'description' => $scopeParam->description(),
				];
			}
		}

		return $scopeParamsMap;
	}

	private function checkAccess(): Main\Result
	{
		$result = new Main\Result();

		if (Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled('bi_constructor'))
		{
			$result->addError(new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_OPEN_BIC_UNAVAILABLE_ERROR')));

			return $result;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			$result->addError(new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_OPEN_BIC_ACCESS_ERROR')));

			return $result;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_CREATE))
		{
			$result->addError(new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_OPEN_ACCESS_ERROR')));

			return $result;
		}

		return $result;
	}

	public function saveAction(array $data, Main\Engine\CurrentUser $user): ?array
	{
		$checkAccessResult = $this->checkAccess();
		if (!$checkAccessResult->isSuccess())
		{
			$this->errorCollection->add($checkAccessResult->getErrors());

			return null;
		}

		$title = $data['title'];
		if (!$title)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_EMPTY_TITLE_ERROR'));

			return null;
		}

		$integrator = Integrator::getInstance();
		$response = $integrator->createEmptyDashboard([
			'name' => $title,
		]);

		if ($response->getErrors())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_ERROR'));

			return null;
		}

		$responseData = $response->getData();
		if (empty($responseData['body']))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_ERROR'));

			return null;
		}
		$dashboard = SupersetDashboardTable::createObject();
		$dashboard
			->setExternalId((int)$responseData['body']['id'])
			->setTitle($responseData['body']['result']['dashboard_title'])
			->setType(SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM)
			->setStatus(SupersetDashboardTable::DASHBOARD_STATUS_DRAFT)
			->setCreatedById((int)$user?->getId())
			->setOwnerId((int)$user?->getId())
		;
		$saveResult = $dashboard->save();

		if (!$saveResult->isSuccess())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_ERROR'));

			return null;
		}

		$scopes = $data['scopes'] ?? [];
		$params = $data['params'] ?? [];
		$saveScopesResult = ScopeService::getInstance()->saveDashboardScopes($dashboard->getId(), $scopes);
		$saveParamsResult = (new UrlParameter\Service($dashboard))->saveDashboardParams($params, $scopes);
		if (
			!$saveScopesResult->isSuccess()
			|| !$saveParamsResult->isSuccess()
		)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DASHBOARD_CREATE_FORM_SAVE_PARAMS_ERROR'));
		}

		$data = [];
		$superset = new SupersetController(Integrator::getInstance());
		$dashboard = $superset->getDashboardRepository()->getById($saveResult->getId(), true);
		if ($dashboard)
		{
			$gridRow = \Bitrix\BIConnector\Superset\Grid\DashboardGrid::prepareRowData($dashboard);

			$data['id'] = $dashboard->getId();
			$data['title'] = $dashboard->getTitle();

			$data['columns'] = $gridRow['columns'];
			$data['actions'] = $gridRow['actions'];

			return ['dashboard' => $data];
		}

		return null;
	}
}
