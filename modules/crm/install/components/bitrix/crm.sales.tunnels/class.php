<?php /** @noinspection PhpUnused */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Integration\Sender\Rc\Service;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RolePermission;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('crm');

class SalesTunnels extends Bitrix\Crm\Component\Base implements Controllerable
{
	/**
	 * @var Crm\Service\Factory
	 */
	protected $factory;
	protected $categories;
	protected $scheme;
	protected $stages;
	/** @var Crm\Automation\TunnelManager */
	protected $tunnelManager;

	protected function addError(Error $error): self
	{
		$this->errorCollection[] = $error;

		return $this;
	}

	protected function addErrors(array $errors): self
	{
		$this->errorCollection->add($errors);

		return $this;
	}

	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('entityTypeId', $arParams);

		return $arParams;
	}

	protected function init(): void
	{
		parent::init();
		// load messages from old ajax.php file
		Loc::loadMessages(Path::combine(__DIR__, 'ajax.php'));
		if($this->getErrors())
		{
			return;
		}

		$entityTypeId = $this->arParams['entityTypeId'] ?? \CCrmOwnerType::Deal;
		$this->factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$this->factory)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND')));
			return;
		}
		if (!$this->factory->isCategoriesEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_ENTITY_CATEGORY_DISABLED')));
			return;
		}
		if (!$this->userPermissions->canWriteConfig())
		{
			$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_ACCESS_DENIED')));
			return;
		}

		$this->tunnelManager = new Crm\Automation\TunnelManager($this->factory->getEntityTypeId());

		$name = $this->factory->getEntityDescription();
		$this->getApplication()->SetTitle(Loc::getMessage('CRM_SALES_TUNNELS_TITLE', [
			'#NAME#' => htmlspecialcharsbx($name),
		]));
	}

	public function isSenderSupported(): bool
	{
		return $this->factory->getEntityTypeId() === \CCrmOwnerType::Deal;
	}

	/**
	 * @return array
	 */
	public function getCategories(): array
	{
		if ($this->categories !== null)
		{
			return $this->categories;
		}

		$this->categories = [];

		$categoriesCollection = Crm\Service\Container::getInstance()->getUserPermissions()->filterAvailableForReadingCategories(
			$this->factory->getCategories()
		);
		$categories = [];
		foreach ($categoriesCollection as $category)
		{
			/** @var Crm\Category\Entity\Category $category */
			$categories[] = $category->getData();
		}

		$tunnelScheme = $this->getTunnelScheme();

		foreach ($categories as $category)
		{
			$stages = [];
			if ($this->factory->isStagesEnabled())
			{
				foreach ($this->factory->getStages($category['ID']) as $stage)
				{
					$stages[] = $stage->collectValues();
				}
				$stages = Crm\Color\PhaseColorScheme::fillDefaultColors($stages);
			}
			$categoryScheme = array_filter(
				$tunnelScheme['stages'],
				static function($stage) use ($category) {
					return $stage['categoryId'] === $category['ID'];
				}
			);

			$reducedStages = [
				PhaseSemantics::PROCESS => [],
				PhaseSemantics::SUCCESS => [],
				PhaseSemantics::FAILURE => [],
			];

			foreach ($stages as $stage)
			{
				$stage['TUNNELS'] = [];

				foreach ($categoryScheme as $stageScheme)
				{
					if ((string)$stageScheme['stageId'] === (string)$stage['STATUS_ID'])
					{
						$stage['TUNNELS'] = $stageScheme['tunnels'];
					}
				}

				$semanticId = $stage['SEMANTICS'] ?? PhaseSemantics::PROCESS;
				$semanticId = in_array((string)$semanticId, [
					PhaseSemantics::PROCESS,
					PhaseSemantics::SUCCESS,
					PhaseSemantics::FAILURE
				], true) ? $semanticId : PhaseSemantics::PROCESS;

				$reducedStages[$semanticId][] = $stage;
			}

			if ($this->isSenderSupported())
			{
				$category['RC_COUNT'] = Service::getDealWorkerCount($category['ID']);
				$category['RC_LIST_URL'] = Service::getDealWorkerUrl($category['ID']);
			}

			$category['STAGES'] = $reducedStages;

			$permissionEntityName = $this->userPermissions::getPermissionEntityType(
				$this->factory->getEntityTypeId(),
				$category['ID']
			);

			$permissions = Crm\RolePermission::getByEntityId($permissionEntityName);
			$access = null;
			array_walk_recursive ($permissions, static function ($item) use (&$access) {
				if ($access === null)
				{
					$access  = $item;
				}
				elseif ($access !== false && $access !== $item)
				{
					$access = false;
				}
			});
			$category['ACCESS'] = (
				$access === false || !in_array($access, [BX_CRM_PERM_ALL, BX_CRM_PERM_SELF, BX_CRM_PERM_NONE], true)
					? false
					: $access
			);
			$this->categories[] = $category;
		}

		return $this->categories;
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public function getStageById(int $id): ?array
	{
		if (!$this->factory->isStagesEnabled())
		{
			return null;
		}
		foreach ($this->getCategories() as $category)
		{
			$allStages = array_merge([], $category['STAGES']['P'], $category['STAGES']['S'], $category['STAGES']['F']);

			foreach ($allStages as $stage)
			{
				if ((int)$stage['ID'] === $id)
				{
					return $stage;
				}
			}
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function getStages(): array
	{
		if ($this->stages === null)
		{
			$this->stages = [];
			//todo combine into one query
			foreach ($this->factory->getCategories() as $category)
			{
				foreach ($this->factory->getStages($category->getId()) as $stage)
				{
					$this->stages[$stage->getStatusId()] = $stage->getName();
				}
			}
		}

		return $this->stages;
	}

	protected function getTunnelScheme(): array
	{
		//todo implementation for dynamic types
		if ($this->scheme === null)
		{
			if ($this->factory->isAutomationEnabled() && $this->factory->isStagesEnabled())
			{
				$this->scheme = $this->tunnelManager->getScheme();
			}
			else
			{
				$this->scheme = [
					'available' => true,
					'stages' => []
				];
			}
		}

		return $this->scheme;
	}

	public function executeComponent(): void
	{
		$this->init();
		if ($this->getErrors())
		{
			$this->showError(implode(', ', $this->getErrorMessages()));
			return;
		}

		Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

		// todo if stages disabled
		$this->arResult['entityTypeId'] = $this->factory->getEntityTypeId();
		$this->arResult['documentType'] = CCrmBizProcHelper::ResolveDocumentType($this->arResult['entityTypeId']);
		$this->arResult['isAutomationEnabled'] = $this->factory->isAutomationEnabled();
		$this->arResult['isStagesEnabled'] = $this->factory->isStagesEnabled();
		$this->arResult['isSenderSupported'] = $this->isSenderSupported();
		$this->arResult['categories'] = $this->getCategories();
		$this->arResult['stages'] = $this->getStages();
		$this->arResult['tunnelScheme'] = $this->getTunnelScheme();
		$this->arResult['canEditTunnels'] = static::canCurrentUserEditTunnels();
		$this->arResult['restrictionPopup'] = $this->getRestrictionPopup();
		$this->arResult['showRobotsRestrictionPopup'] = $this->getRobotsRestrictionPopup();
		$this->arResult['showGeneratorRestrictionPopup'] = $this->getGeneratorRestrictionPopup();
		$this->arResult['robotsUrl'] = $this->getRobotsUrl();

		[$this->arResult['canAddCategory'], $this->arResult['categoriesQuantityLimit']] = $this->getLimits();

		$this->includeComponentTemplate();
	}

	public static function canCurrentUserEditTunnels(): bool
	{
		return Crm\Service\Container::getInstance()->getUserPermissions()->canWriteConfig();
	}

	private function getLimits(): array
	{
		//todo remove if/else
		if ($this->factory->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			$restriction = Crm\Restriction\RestrictionManager::getDealCategoryLimitRestriction();
			$limit = $restriction->getQuantityLimit();
			$canAdd = ($limit <= 0 || $limit > DealCategory::getCount());
		}
		else
		{
			return [true, 0];
		}

		return [$canAdd, $limit];
	}

	private function showError($message): void
	{
		echo <<<HTML
			<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
				<span class="ui-alert-message">{$message}</span>
			</div>
HTML;
	}

	private function getRestrictionPopup()
	{
		$restriction = Crm\Restriction\RestrictionManager::getDealCategoryLimitRestriction();
		return $restriction->prepareInfoHelperScript();
	}

	private function getRobotsRestrictionPopup()
	{
		$restriction = Crm\Restriction\RestrictionManager::getAutomationRestriction();
		return $restriction->prepareInfoHelperScript();
	}
	private function getGeneratorRestrictionPopup()
	{
		$restriction = Crm\Restriction\RestrictionManager::getGeneratorRestriction();
		return $restriction->prepareInfoHelperScript();
	}

	protected function getRobotsUrl(): ?string
	{
		if (!$this->factory)
		{
			return null;
		}
		$entityTypeId = $this->factory->getEntityTypeId();
		$template = Crm\Service\Container::getInstance()->getRouter()->getAutomationUrlTemplate($entityTypeId);
		if ($template)
		{
			return str_replace([
				'#entityTypeId#',
				'#categoryId#',
			], [
				$entityTypeId,
				'{category}',
			], $template);
		}

		return null;
	}

	public function configureActions(): array
	{
		return [];
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function createCategoryAction(array $data = []): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}
		$newCategory = $this->factory->createCategory($data);
		$result = $newCategory->save();

		if ($result->isSuccess())
		{
			$categories = $this->getCategories();
			foreach ($categories as $key => $category)
			{
				if ($category['ID'] === $newCategory->getId())
				{
					return $category;
				}
			}
		}
		else
		{
			$this->addErrors($result->getErrors());
		}

		return null;
	}

	/**
	 * @param array $data
	 * @return array|null
	 */
	public function getCategoryAction(array $data = []): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}

		$category = $this->factory->getCategory($data['id']);

		return $category ? $category->getData() : null;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function updateCategoryAction(array $data = []): void
	{
		$this->init();
		if($this->getErrors())
		{
			return;
		}
		$category = $this->factory->getCategory((int)$data['id']);
		if (!$category)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR')));
			return;
		}
		$fields = $data['fields'] ?? [];
		$name = $fields['NAME'] ?? '';
		$sort = $fields['SORT'] ? (int) $fields['SORT'] : null;
		if ($name)
		{
			$category->setName($name);
		}
		if ($sort)
		{
			$category->setSort($sort);
		}

		$result = $category->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function accessCategoryAction(array $data = []): void
	{
		$this->init();
		if($this->getErrors())
		{
			return;
		}
		$permissionEntity = $this->userPermissions::getPermissionEntityType(
			$this->factory->getEntityTypeId(),
			(int)$data['id']
		);
		$isNewPermissionsCorrect = in_array(
			$data['access'],
			[$this->userPermissions::PERMISSION_ALL, $this->userPermissions::PERMISSION_SELF],
			true
		);
		$newPermissions = $isNewPermissionsCorrect
			? $data['access']
			: BX_CRM_PERM_NONE;
		$permissions = \CCrmRole::GetDefaultPermissionSet();
		foreach ($permissions as $key => $permission)
		{
			$permissions[$key]["-"] = $newPermissions;
		}

		$result = RolePermission::setByEntityIdForAllNotAdminRoles($permissionEntity, $permissions);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function copyAccessCategoryAction(array $data = []): void
	{
		$this->init();
		if($this->getErrors())
		{
			return;
		}
		$permissionEntity = $this->userPermissions::getPermissionEntityType(
			$this->factory->getEntityTypeId(),
			(int)$data['id']
		);
		$donorPermissionEntity = $this->userPermissions::getPermissionEntityType(
			$this->factory->getEntityTypeId(),
			(int)$data['donorId']
		);
		$permissionSet = RolePermission::getByEntityId($donorPermissionEntity);

		$result = RolePermission::setByEntityId($permissionEntity, $permissionSet);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function removeCategoryAction(array $data = []): void
	{
		$this->init();
		if($this->getErrors())
		{
			return;
		}
		$category = $this->factory->getCategory((int)$data['id']);
		if (!$category)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR')));
			return;
		}
		$result = $category->delete();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function createRobotAction(array $data): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}
		if (!$this->factory->isAutomationEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_ROBOTS_NOT_SUPPORTED')));
			return null;
		}

		$userId = $this->userPermissions->getUserId();
		$result = $this->tunnelManager->addTunnel(
			$userId,
			$data['from']['category'],
			$data['from']['stage'],
			$data['to']['category'],
			$data['to']['stage'],
			$data['robotAction']
		);

		if ($result->isSuccess())
		{
			return $result->getData();
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function removeRobotAction(array $data): void
	{
		$this->init();
		if($this->getErrors())
		{
			return;
		}
		if (!$this->factory->isAutomationEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_ROBOTS_NOT_SUPPORTED')));
			return;
		}

		$userId = $this->userPermissions->getUserId();
		$result = $this->tunnelManager->removeTunnel($userId, $data);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function updateRobotAction(array $data): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}
		if (!$this->factory->isAutomationEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_ROBOTS_NOT_SUPPORTED')));
			return null;
		}
		$userId = $this->userPermissions->getUserId();

		$result = $this->tunnelManager->updateTunnel($userId, $data);
		if ($result->isSuccess())
		{
			return $result->getData();
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function addStageAction(array $data): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}
		$status = new CCrmStatus($data['entityId']);

		$id = $status->Add([
			'NAME' => $data['name'],
			'SORT' => $data['sort'],
			'COLOR' => $data['color'],
			'SEMANTICS' => (isset($data['semantics']) &&
				in_array($data['semantics'], [PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE], true))
				? $data['semantics']
				: null,
			'CATEGORY_ID' => $data['categoryId'],
		]);

		if (!$id)
		{
			$this->addError(new Error(Loc::getMessage('CRM_SALES_STAGE_CREATE_ERROR')));
			return null;
		}

		return [
			'stage' => $this->getStageById($id),
		];
	}

	public function updateStageAction(array $data): array
	{
		$this->init();
		if($this->getErrors())
		{
			return [
				'success' => false,
				'errors' => $this->getErrorMessages(),
			];
		}
		$response = [
			'success' => false,
			'errors' => [],
		];
		$status = new CCrmStatus($data['entityId']);
		$stage = $status->GetStatusById($data['stageId']);

		if ($stage)
		{
			$fields = [];

			if (isset($data['name']) && is_string($data['name']))
			{
				$fields['NAME'] = $data['name'];
			}

			if (isset($data['sort']) && (int)$data['sort'] > 0)
			{
				$fields['SORT'] = (int)$data['sort'];
			}
			else
			{
				$fields['SORT'] = (int)$stage['SORT'];
			}

			$fields['COLOR'] = $data['color'] ?? '';

			$id = $status->Update($data['stageId'], $fields);

			if (!$id)
			{
				$response['errors'][] = Loc::getMessage('CRM_SALES_STAGE_UPDATE_ERROR');
				return $response;
			}

			$response['success'] = true;
			$response['stage'] = $this->getStageById($id);
		}
		else
		{
			$response['errors'][] = Loc::getMessage('CRM_SALES_TUNNELS_STAGE_NOT_FOUND');
		}

		return $response;
	}

	public function removeStageAction(array $data): void
	{
		$this->init();
		if($this->getErrors())
		{
			return;
		}
		$stage = $this->factory->getStage($data['statusId']);
		if ($stage)
		{
			if ($stage->getSystem())
			{
				$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_STAGE_IS_SYSTEM')));
				return;
			}
			$result = $stage->delete();
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}

			return;
		}
		$this->addError(new Error(Loc::getMessage('CRM_SALES_TUNNELS_STAGE_NOT_FOUND')));
	}

	public function updateStagesAction(array $data): array
	{
		return array_map(
			function($itemData)
			{
				return $this->updateStageAction($itemData);
			},
			$data
		);
	}

	public function getCategoriesAction(): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}
		return $this->getCategories();
	}
}
