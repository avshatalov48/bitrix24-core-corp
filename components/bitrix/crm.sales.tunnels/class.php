<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Integration\Sender\Rc\Service;
use Bitrix\Crm\PhaseSemantics;
use \Bitrix\Main\ArgumentException;
use Bitrix\Crm;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class SalesTunnels extends CBitrixComponent
{
	/**
	 * @return array
	 * @throws ArgumentException
	 */
	public static function getCategories()
	{
		static $categories = null;

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$map = array_fill_keys(CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions), true);

		if ($categories === null)
		{
			$allCategories = DealCategory::getAll(true);

			$categories = [];
			$tunnelScheme = static::getTunnelScheme();

			foreach ($allCategories as $key => $category)
			{
				$ID = (int)$category['ID'];
				if(!isset($map[$ID]))
				{
					continue;
				}

				$stages = CCrmViewHelper::getDealStageInfos($category['ID']);

				$categoryScheme = array_filter(
					$tunnelScheme['stages'],
					function($stage) use ($category) {
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
					$stage['CATEGORY_ID'] = $category['ID'];

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

				$category['RC_COUNT'] = Service::getDealWorkerCount($category['ID']);
				$category['RC_LIST_URL'] = Service::getDealWorkerUrl($category['ID']);
				$category['STAGES'] = $reducedStages;

				$permissions = DealCategory::getPermissionById($category['ID']);
				$access = null;
				array_walk_recursive ($permissions, function ($item) use (&$access) {
					if ($access === null)
					{
						$access  = $item;
					}
					else if ($access !== false && $access !== $item)
					{
						$access = false;
					}
				});
				$category['ACCESS'] = ($access === false || !in_array($access, [BX_CRM_PERM_ALL, BX_CRM_PERM_SELF, BX_CRM_PERM_NONE]) ? false : $access);
				$categories[] = $category;
			}
		}
		return $categories;
	}

	/**
	 * @param $id
	 * @throws ArgumentException
	 */
	public static function getStageById($id)
	{
		foreach (static::getCategories() as $category)
		{
			$allStages = array_merge([], $category['STAGES']['P']);
			$allStages = array_merge($allStages, $category['STAGES']['S']);
			$allStages = array_merge($allStages, $category['STAGES']['F']);

			foreach ($allStages as $stage)
			{
				if ((int)$stage['ID'] === (int)$id)
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
	protected static function getStages()
	{
		$stagesGroupInfos = DealCategory::getStageGroupInfos();

		return array_map(
			function($item) {
				return $item['items'];
			},
			$stagesGroupInfos
		);
	}

	protected static function getTunnelScheme()
	{
		static $scheme = null;

		if ($scheme === null)
		{
			$scheme = Crm\Automation\Tunnel::getScheme();
		}

		return $scheme;
	}

	/**
	 * @throws ArgumentException
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule('crm'))
		{
			return $this->showError('Module CRM is not installed.');
		}

		if (!static::canCurrentUserEditTunnels())
		{
			return $this->showError(Loc::getMessage('CRM_SALES_TUNNELS_ACCESS_DENIED'));
		}

		$this->arResult['categories'] = static::getCategories();
		$this->arResult['stages'] = static::getStages();
		$this->arResult['tunnelScheme'] = Crm\Automation\Tunnel::getScheme();
		$this->arResult['allowWrite'] = $this->isCrmAdmin();
		$this->arResult['canEditTunnels'] = static::canCurrentUserEditTunnels();
		$this->arResult['restrictionPopup'] = $this->getRestrictionPopup();
		$this->arResult['showRobotsRestrictionPopup'] = $this->getRobotsRestrictionPopup();
		$this->arResult['showGeneratorRestrictionPopup'] = $this->getGeneratorRestrictionPopup();

		list($this->arResult['canAddCategory'], $this->arResult['categoriesQuantityLimit']) = $this->getLimits();

		parent::includeComponentTemplate();
	}

	public static function canCurrentUserEditTunnels()
	{
		$curUser = CCrmSecurityHelper::GetCurrentUser();
		$perms = new CCrmPerms($curUser->GetID());

		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	private function getLimits()
	{
		$restriction = Crm\Restriction\RestrictionManager::getDealCategoryLimitRestriction();
		$limit = $restriction->getQuantityLimit();
		$canAdd = ($limit <= 0 || $limit > DealCategory::getCount());

		return [$canAdd, $limit];
	}

	private function showError($message)
	{
		echo <<<HTML
			<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
				<span class="ui-alert-message">{$message}</span>
			</div>
HTML;
		return;
	}

	private function isCrmAdmin()
	{
		return CCrmPerms::isAdmin();
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
}