<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Bizproc;
use Bitrix\Crm;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Tunnel
{
	private static $robotType = 'CrmCopyDealActivity';

	private static function isAvailable()
	{
		return Factory::isAutomationAvailable(\CCrmOwnerType::Deal);
	}

	public static function canUserEditTunnel($userId, $categoryId)
	{
		if (!self::isAvailable())
		{
			return false;
		}

		$tplUser = new \CBPWorkflowTemplateUser($userId);
		if ($tplUser->isAdmin())
		{
			return true;
		}

		return \CBPDocument::CanUserOperateDocumentType(
			\CBPCanUserOperateOperation::CreateAutomation,
			$userId,
			\CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal),
			['DocumentCategoryId' => $categoryId]
		);
	}

	public static function getScheme()
	{
		$result = [
			'available' => true,
			'stages' => []
		];

		if (!self::isAvailable())
		{
			$result['available'] = false;
			$result['message'] = Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE');
			return $result;
		}

		$categories = Crm\Category\DealCategory::getAll(true);

		foreach ($categories as $key => $category)
		{
			$stages = \CCrmViewHelper::getDealStageInfos($category['ID']);
			foreach ($stages as $stageId => $stage)
			{
				$result['stages'][] = self::extractStageTunnels($stageId, $category['ID']);
			}
		}

		return $result;
	}

	public static function add($userId, $srcCategory, $srcStage, $dstCategory, $dstStage)
	{
		$result = new \Bitrix\Main\Result();

		if (!self::isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		$srcCategory = (int) $srcCategory;
		$dstCategory = (int) $dstCategory;

		if ($srcCategory === $dstCategory)
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY')));
			return $result;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);

		$template = new Bizproc\Automation\Engine\Template($documentType, $srcStage);

		if ($template->isExternalModified())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_EXTERNAL_TEMPLATE')));
		}
		else
		{
			$robots = $template->getRobots();
			if (!$robots)
			{
				$robots = [];
			}

			$robot = self::createRobot($dstCategory, $dstStage);

			array_unshift($robots, $robot);
			$saveResult = $template->save($robots, $userId);

			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
			else
			{
				$result->setData(['tunnel' => self::prepareRobotToTunnel($robot, $srcStage, $srcCategory)]);
			}
		}

		return $result;
	}

	public static function remove($userId, array $tunnel)
	{
		$result = new \Bitrix\Main\Result();

		if (!self::isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);
		$template = new Bizproc\Automation\Engine\Template($documentType, $tunnel['srcStage']);

		if ($template->isExternalModified())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_EXTERNAL_TEMPLATE')));
		}
		else
		{
			$robotName = $tunnel['robot']['Name'];
			$robots = $template->getRobots();

			foreach ($robots as $i => $robot)
			{
				if ($robot->getName() === $robotName)
				{
					unset($robots[$i]);
					$saveResult = $template->save(array_values($robots), $userId);
					if (!$saveResult->isSuccess())
					{
						$result->addErrors($saveResult->getErrors());
					}
					break;
				}
			}
		}

		return $result;
	}

	public static function update($userId, array $tunnel)
	{
		$result = new \Bitrix\Main\Result();

		if (!self::isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		$srcCategory = (int) $tunnel['srcCategory'];
		$dstCategory = (int) $tunnel['robot']['Properties']['CategoryId'];

		if ($srcCategory === $dstCategory)
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY')));
			return $result;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);
		$template = new Bizproc\Automation\Engine\Template($documentType, $tunnel['srcStage']);

		if ($template->isExternalModified())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_EXTERNAL_TEMPLATE')));
		}
		else
		{
			$robotName = $tunnel['robot']['Name'];
			$robots = $template->getRobots();

			foreach ($robots as $i => $robot)
			{
				if ($robot->getName() === $robotName)
				{
					$robots[$i] = new Bizproc\Automation\Engine\Robot($tunnel['robot']);
					$saveResult = $template->save($robots, $userId);
					if (!$saveResult->isSuccess())
					{
						$result->addErrors($saveResult->getErrors());
					}
					else
					{
						$result->setData(['tunnel' => self::prepareRobotToTunnel($robot, $tunnel['srcStage'], $tunnel['srcCategory'])]);
					}
					break;
				}
			}
		}

		return $result;
	}

	private static function extractStageTunnels($stageId, $categoryId)
	{
		$result = [
			'categoryId' => $categoryId,
			'stageId' => $stageId,
			'locked' => false,
			'hasRobots' => false,
			'tunnels' => []
		];

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);

		$template = new Bizproc\Automation\Engine\Template($documentType, $stageId);

		if ($template->getId() > 0)
		{
			if ($template->isExternalModified())
			{
				$result['locked'] = true;
				$result['message'] = Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_EXTERNAL_TEMPLATE');
			}
			else
			{
				foreach ($template->getRobots() as $robot)
				{
					$result['hasRobots'] = true;
					if ($robot->getType() === self::$robotType)
					{
						$result['tunnels'][] = self::prepareRobotToTunnel($robot, $stageId, $categoryId);
					}
				}
			}
		}

		return $result;
	}

	private static function prepareRobotToTunnel(Bizproc\Automation\Engine\Robot $robot, $stageId, $categoryId)
	{
		$props = $robot->getProperties();

		if (empty($props['StageId']))
		{
			$stages = array_keys(\CCrmViewHelper::getDealStageInfos($props['CategoryId']));
			$props['StageId'] = reset($stages);
		}

		return [
			'srcCategory' => $categoryId,
			'srcStage' => $stageId,
			'dstCategory' => $props['CategoryId'],
			'dstStage' => $props['StageId'],
			'robot' => $robot->toArray()
		];
	}

	private static function createRobot($dstCategory, $dstStage)
	{
		return new Bizproc\Automation\Engine\Robot([
			'Name' => Bizproc\Automation\Engine\Robot::generateName(),
			'Type' => self::$robotType,
			'Properties' => [
				'Title' => Loc::getMessage("CRM_AUTOMATION_TUNNEL_ROBOT_TITLE"),
				'DealTitle' => '{=Document:TITLE}',
				'CategoryId' => $dstCategory,
				'StageId' => $dstStage
			]
		]);
	}
}