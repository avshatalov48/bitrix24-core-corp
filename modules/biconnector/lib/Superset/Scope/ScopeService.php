<?php

namespace Bitrix\BIConnector\Superset\Scope;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable;
use Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorFactory;
use Bitrix\Crm;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Result;
use Bitrix\Main\Text\StringHelper;

final class ScopeService
{
	public const BIC_SCOPE_CRM = 'crm';
	public const BIC_SCOPE_BIZPROC = 'bizproc';
	public const BIC_SCOPE_WORKFLOW_TEMPLATE = 'workflow_template';
	public const BIC_SCOPE_TASKS = 'tasks';
	public const BIC_SCOPE_TASKS_EFFICIENCY = 'tasks_efficiency';
	public const BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX = 'automated_solution_';
	public const BIC_SCOPE_PROFILE = 'profile';
	public const BIC_SCOPE_SHOP = 'shop';
	public const BIC_SCOPE_STORE = 'store';
	public const BIC_SCOPE_TASKS_FLOWS = 'tasks_flows';
	public const BIC_SCOPE_TASKS_FLOWS_FLOW = 'tasks_flows_flow';

	private static ?ScopeService $instance = null;
	private static array $scopeNameMap = [];

	public static function getInstance(): ScopeService
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get array of dashboard scopes.
	 * @param int $dashboardId Dashboard id.
	 *
	 * @return string[]
	 */
	public function getDashboardScopes(int $dashboardId): array
	{
		$result = [];
		$scopeCollection = SupersetScopeTable::getList([
			'filter' => [
				'=DASHBOARD_ID' => $dashboardId,
			],
			'order' => ['IS_AUTOMATED_SOLUTION' => 'asc', 'SCOPE_CODE' => 'asc'],
			'runtime' => [
				new ExpressionField(
					'IS_AUTOMATED_SOLUTION',
					"CASE WHEN %s LIKE 'automated_solution_%%' THEN 1 ELSE 0 END",
					['SCOPE_CODE'],
					['data_type' => 'integer']
				),
			],
		])->fetchCollection();

		foreach ($scopeCollection as $scope)
		{
			$result[] = $scope->getScopeCode();
		}

		return $result;
	}

	/**
	 * Saves scope codes of dashboard given by id.
	 *
	 * @param int $dashboardId Dashboard id.
	 * @param string[] $scopeCodes Array of stringified scope codes.
	 *
	 * @return Result
	 */
	public function saveDashboardScopes(int $dashboardId, array $scopeCodes): Result
	{
		$result = new Result();
		$db = Application::getConnection();
		try
		{
			$db->startTransaction();
			$existingScopes = SupersetScopeTable::getList([
				'filter' => [
					'=DASHBOARD_ID' => $dashboardId,
				],
			])->fetchCollection();

			foreach ($existingScopes as $scope)
			{
				$scope->delete();
			}

			$availableScopeCodes = $this->getScopeList(checkPermissions: false);
			foreach ($scopeCodes as $scopeCode)
			{
				if (in_array($scopeCode, $availableScopeCodes))
				{
					SupersetScopeTable::createObject()
						->setDashboardId($dashboardId)
						->setScopeCode($scopeCode)
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
	 * Returns ORM Dashboard collection by scope code.
	 * @param string $scopeCode Code of scope.
	 *
	 * @return EO_SupersetDashboard_Collection
	 */
	public function getDashboardListByScope(string $scopeCode): EO_SupersetDashboard_Collection
	{
		$accessFilter = AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
			SupersetDashboardTable::class
		);

		return SupersetDashboardTable::getList([
				'select' => ['*', 'SCOPE', 'URL_PARAMS'],
				'filter' => [
					...$accessFilter,
					'=SCOPE.SCOPE_CODE' => $scopeCode,
				],
				'cache' => ['ttl' => 86400],
			])
			->fetchCollection()
		;
	}

	/**
	 * Get array of menu items to embed in zone top menu.
	 * @param string $scopeCode Code of zone where BI Builder menu item will be added.
	 *
	 * @return array
	 */
	public function prepareScopeMenuItem(string $scopeCode, array $urlParams = []): array
	{
		$menuItemCreator = MenuItemCreatorFactory::getMenuItemCreator($scopeCode);
		$menuItem = $menuItemCreator?->createMenuItem($urlParams);

		return $menuItem ?? [];
	}

	/**
	 * Get array of menu items to embed in top menu.
	 * @param string $code Code of CRM automated solution.
	 *
	 * @return array
	 */
	public function prepareAutomatedSolutionMenuItem(string $code): array
	{
		$result = Crm\AutomatedSolution\Entity\AutomatedSolutionTable::getList([
			'filter' => ['=CODE' => $code],
			'limit' => 1,
			'cache' => ['ttl' => 86400],
		])
			->fetchObject()
		;

		if (!$result)
		{
			return [];
		}

		$scopeCode = self::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX . $result->getId();

		return $this->prepareScopeMenuItem($scopeCode);
	}

	/**
	 * Get available scopes.
	 *
	 * @return string[]
	 */
	public function getScopeList(bool $checkPermissions = true): array
	{
		$result = [
			self::BIC_SCOPE_BIZPROC,
			self::BIC_SCOPE_CRM,
			self::BIC_SCOPE_TASKS,
			self::BIC_SCOPE_TASKS_EFFICIENCY,
			self::BIC_SCOPE_WORKFLOW_TEMPLATE,
			self::BIC_SCOPE_PROFILE,
			self::BIC_SCOPE_SHOP,
			self::BIC_SCOPE_STORE,
			self::BIC_SCOPE_TASKS_FLOWS,
			self::BIC_SCOPE_TASKS_FLOWS_FLOW,
		];

		if (Loader::includeModule('crm'))
		{
			$container = Crm\Service\Container::getInstance();
			$automatedSolutionManager = $container->getAutomatedSolutionManager();
			$userPermissions = $container->getUserPermissions(CurrentUser::get()->getId());
			foreach ($automatedSolutionManager->getExistingAutomatedSolutions() as $automatedSolution)
			{
				if (!$checkPermissions && empty($automatedSolution['TYPE_IDS']))
				{
					$code = self::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX . $automatedSolution['ID'];
					$result[] = $code;
					if (!isset(self::$scopeNameMap[$code]))
					{
						self::$scopeNameMap[$code] = $automatedSolution['TITLE'];
					}
				}

				foreach ($automatedSolution['TYPE_IDS'] as $typeId)
				{
					$smartProcess = $container->getType($typeId);
					if ($smartProcess)
					{
						if (!$checkPermissions || $userPermissions->canReadType($smartProcess->getEntityTypeId()))
						{
							$code = self::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX . $automatedSolution['ID'];
							$result[] = $code;
							if (!isset(self::$scopeNameMap[$code]))
							{
								self::$scopeNameMap[$code] = $automatedSolution['TITLE'];
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Gets readable scope name by code.
	 * @param string $scopeCode Scope code.
	 *
	 * @return string
	 */
	public function getScopeName(string $scopeCode): string
	{
		if (isset(self::$scopeNameMap[$scopeCode]))
		{
			return self::$scopeNameMap[$scopeCode];
		}

		foreach ($this->getScopeList() as $scope)
		{
			if (!str_starts_with($scope, self::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX))
			{
				$langCode = 'BIC_SCOPE_NAME_' . StringHelper::strtoupper($scope);
				self::$scopeNameMap[$scope] = Loc::getMessage($langCode);
			}
		}

		return self::$scopeNameMap[$scopeCode] ?? '';
	}

	/**
	 * Converts array of scope codes to array of readable names.
	 * With checking automated solutions permissions.
	 * Doesn't contain scope name if user doesn't have permission to view this automated solution.
	 *
	 * @param string[] $scopeCodes Array with scope codes.
	 * @return array
	 */
	public function getScopeNameList(array $scopeCodes): array
	{
		$result = [];
		foreach ($scopeCodes as $scopeCode)
		{
			$scopeName = $this->getScopeName($scopeCode);
			if ($scopeName)
			{
				$result[] = $scopeName;
			}
		}

		return $result;
	}

	/**
	 * Returns entity selector's item title of automation solutions.
	 *
	 * @return string
	 */
	public function getAutomationSolutionsTitle(): string
	{
		return Loc::getMessage('BIC_SCOPE_NAME_AUTOMATED_SOLUTIONS');
	}

	/**
	 * Event crm onAfterAutomatedSolutionDelete handler.
	 *
	 * @param Event $event Event with id of automated solution that was deleted.
	 *
	 * @return void
	 */
	public static function deleteAutomatedSolutionBinding(Event $event): void
	{
		$data = $event->getParameter('automatedSolution');
		if (is_array($data) && isset($data['ID']))
		{
			$automatedSolutionId = (int)$data['ID'];
			$scopeCode = self::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX . $automatedSolutionId;
			$scopes = SupersetScopeTable::getList(['filter' => ['=SCOPE_CODE' => $scopeCode]])->fetchCollection();
			foreach ($scopes as $scope)
			{
				$scope->delete();
			}
		}
	}
}
