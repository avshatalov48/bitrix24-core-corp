<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Crm;
use \Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Entry\DeleteException;
use Bitrix\Crm\Entry\UpdateException;
use Bitrix\Crm\Entry\AddException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('crm');
CBitrixComponent::includeComponentClass("bitrix:crm.sales.tunnels");

class CCrmSalesTunnelsController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param array $data
	 * @return array
	 * @throws Bitrix\Main\ArgumentException
	 */
	public function createCategoryAction($data = [])
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		try
		{
			$categoryId = DealCategory::add([
				'NAME' => $data['name'],
				'SORT' => $data['sort'],
			]);
		}
		catch(AddException $ex)
		{
			return ['success' => false, 'errors' => [$ex->getLocalizedMessage()]];
		}

		$result = [];
		$categories = SalesTunnels::getCategories();

		foreach ($categories as $key => $category)
		{
			if ($category['ID'] == $categoryId)
			{
				$result = $category;
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return array|null
	 */
	public function getCategoryAction($data = [])
	{
		return DealCategory::get($data['id']);
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function updateCategoryAction($data = [])
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		if ((int)$data['id'] === 0)
		{
			try
			{
				if (isset($data['fields']['SORT']))
				{
					DealCategory::setDefaultCategorySort($data['fields']['SORT']);
				}

				if (isset($data['fields']['NAME']) && $data['fields']['SORT'] !== '')
				{
					DealCategory::setDefaultCategoryName($data['fields']['NAME']);
				}
			}
			catch (ArgumentNullException $ex)
			{
				return ['success' => false, 'errors' => [$ex->getMessage()]];
			}
			catch (ArgumentOutOfRangeException $ex)
			{
				return ['success' => false, 'errors' => [$ex->getMessage()]];
			}
		}
		else
		{
			try
			{
				DealCategory::update($data['id'], $data['fields']);
			}
			catch(UpdateException $ex)
			{
				return ['success' => false, 'errors' => [$ex->getLocalizedMessage()]];
			}
		}

		return ['success' => true];
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function accessCategoryAction($data = [])
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		try
		{
			$result = DealCategory::setPermissionById($data['id'], $data['access']);
			if (!$result->isSuccess())
			{
				throw new \Bitrix\Main\ArgumentException(implode('', $result->getErrorMessages()));
			}
		}
		catch (\Exception $ex)
		{
			return ['success' => false, 'errors' => [$ex->getMessage()]];
		}

		return ['success' => true];
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function copyAccessCategoryAction($data = [])
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		try
		{
			$result = DealCategory::copyPermissionById($data['id'], $data['donorId']);
			if (!$result->isSuccess())
			{
				throw new \Bitrix\Main\ArgumentException(implode('', $result->getErrorMessages()));
			}
		}
		catch (\Exception $ex)
		{
			return ['success' => false, 'errors' => [$ex->getMessage()]];
		}

		return ['success' => true];
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function removeCategoryAction($data = [])
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		try
		{
			DealCategory::delete($data['id']);
		}
		catch(DeleteException $ex)
		{
			return ['success' => false, 'errors' => [$ex->getLocalizedMessage()]];
		}

		return ['success' => true];
	}

	public function createRobotAction($data)
	{
		$userId = $this->getCurrentUser()->getId();
		if (!Crm\Automation\Tunnel::canUserEditTunnel($userId, $data['from']['category']))
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		$result = Crm\Automation\Tunnel::add(
			$userId,
			$data['from']['category'],
			$data['from']['stage'],
			$data['to']['category'],
			$data['to']['stage']
		);

		$response = ['success' => $result->isSuccess()];

		if ($result->isSuccess())
		{
			$response['tunnel'] = $result->getData()['tunnel'];
		}
		else
		{
			$response['errors'] = $result->getErrorMessages();
		}

		return $response;
	}

	public function removeRobotAction($data)
	{
		$userId = $this->getCurrentUser()->getId();
		if (!Crm\Automation\Tunnel::canUserEditTunnel($userId, $data['srcCategory']))
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		$result = Crm\Automation\Tunnel::remove($userId, $data);

		$response = ['success' => $result->isSuccess()];

		if (!$result->isSuccess())
		{
			$response['errors'] = $result->getErrorMessages();
		}

		return $response;
	}

	public function updateRobotAction($data)
	{
		$userId = $this->getCurrentUser()->getId();
		if (!Crm\Automation\Tunnel::canUserEditTunnel($userId, $data['srcCategory']))
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		$result = Crm\Automation\Tunnel::update($userId, $data);

		$response = ['success' => $result->isSuccess()];

		if ($result->isSuccess())
		{
			$response['tunnel'] = $result->getData()['tunnel'];
		}
		else
		{
			$response['errors'] = $result->getErrorMessages();
		}

		return $response;
	}

	public function addStageAction($data)
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		$response = ['success' => false, 'errors' => []];
		$status = new CCrmStatus($data['entityId']);

		$id = $status->Add([
			'NAME' => $data['name'],
			'SORT' => $data['sort'],
			'COLOR' => $data['color'],
			'SEMANTICS' => (isset($data['semantics']) &&
				in_array($data['semantics'], [\Bitrix\Crm\PhaseSemantics::SUCCESS, \Bitrix\Crm\PhaseSemantics::FAILURE]))
					? $data['semantics']
					: null
		]);

		if (!$id)
		{
			$response['errors'][] = Loc::getMessage('CRM_SALES_STAGE_CREATE_ERROR');
			return $response;
		}

		$response['success'] = true;
		$response['stage'] = SalesTunnels::getStageById($id);

		return $response;
	}

	public function updateStageAction($data)
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		$response = ['success' => false, 'errors' => []];
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
			$response['stage'] = SalesTunnels::getStageById($id);
		}
		else
		{
			$response['errors'][] = Loc::getMessage('CRM_SALES_TUNNELS_STAGE_NOT_FOUND');
		}

		return $response;
	}

	public function removeStageAction($data)
	{
		if (!SalesTunnels::canCurrentUserEditTunnels())
		{
			return ['success' => false, 'errors' => [Loc::getMessage('CRM_ST_ACCESS_ERROR')]];
		}

		$response = ['success' => false, 'errors' => []];
		$status = new CCrmStatus($data['entityId']);

		$stage = $status->GetStatusById($data['stageId']);

		if ($stage)
		{
			if ($stage['SYSTEM'] === 'Y')
			{
				$response['errors'][] = Loc::getMessage('CRM_SALES_TUNNELS_STAGE_IS_SYSTEM');
			}
			elseif ($status->existsEntityWithStatus($stage['STATUS_ID']))
			{
				$response['errors'][] = Loc::getMessage('CRM_SALES_TUNNELS_STAGE_HAS_DEALS');
			}
			else
			{
				$response['success'] = ($status->delete($data['stageId']) !== false);
			}
		}
		else
		{
			$response['errors'][] = Loc::getMessage('CRM_SALES_TUNNELS_STAGE_NOT_FOUND');
		}

		return $response;
	}

	public function updateStagesAction($data)
	{
		return array_map(
			function($itemData)
			{
				return $this->updateStageAction($itemData);
			},
			$data
		);
	}

	public function getCategoriesAction()
	{
		return SalesTunnels::getCategories();
	}

	public function getGeneratorsCount($data)
	{
		return [
			'count' => Crm\Integration\Sender\Rc\Service::getDealWorkerCount($data['categoryId']),
			'success' => true,
		];
	}
}