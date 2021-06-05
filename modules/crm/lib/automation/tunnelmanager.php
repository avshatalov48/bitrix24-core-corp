<?php

namespace Bitrix\Crm\Automation;

use Bitrix\Bizproc;
use Bitrix\Bizproc\Automation\Engine\Robot;
use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

/**
 * Class TunnelManager
 *
 * @package Bitrix\Crm\Automation
 */
class TunnelManager
{
	protected $entityTypeId;
	protected $documentType;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->documentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);
	}

	/**
	 * @return bool
	 */
	protected function isAvailable(): bool
	{
		return Factory::isAutomationAvailable($this->entityTypeId);
	}

	/**
	 * @param int $userId
	 * @param int $categoryId
	 * @return bool
	 */
	public function canUserEditTunnel(int $userId, int $categoryId): bool
	{
		if (!$this->isAvailable())
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
			$this->documentType,
			['DocumentCategoryId' => $categoryId]
		);
	}

	/**
	 * @return array
	 */
	public function getScheme(): array
	{
		$result = [
			'available' => true,
			'stages' => []
		];

		if (!$this->isAvailable())
		{
			$result['available'] = false;
			$result['message'] = Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE');
			return $result;
		}

		$target = Factory::createTarget($this->entityTypeId);

		foreach ($this->getCategories() as $key => $category)
		{
			$categoryId = is_array($category) ? $category['ID'] : $category->getId();
			$stages = $target->getStatusInfos($categoryId);
			foreach ($stages as $stageId => $stage)
			{
				$result['stages'][] = $this->extractStageTunnels($stageId, $categoryId);
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getCategories(): array
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->entityTypeId);
		return isset($factory) && $factory->isCategoriesSupported() ? $factory->getCategories() : [];
	}

	/**
	 * @param int $userId
	 * @param int $srcCategory
	 * @param string $srcStage
	 * @param int $dstCategory
	 * @param string $dstStage
	 * @return Main\Result
	 */
	public function addTunnel(
		int $userId,
		int $srcCategory,
		string $srcStage,
		int $dstCategory,
		string $dstStage
	): Main\Result
	{
		$result = new Main\Result();

		if (!$this->isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		if ($srcCategory === $dstCategory)
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY')));
			return $result;
		}

		$template = new Bizproc\Automation\Engine\Template($this->documentType, $srcStage);

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

			$robot = $this->createRobot($dstCategory, $dstStage);

			array_unshift($robots, $robot);
			$saveResult = $template->save($robots, $userId);

			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
			else
			{
				$result->setData(['tunnel' => $this->prepareRobotToTunnel($robot, $srcStage, $srcCategory)]);
			}
		}

		return $result;
	}

	/**
	 * @param int $userId
	 * @param array $tunnel
	 * @return Main\Result
	 */
	public function removeTunnel(int $userId, array $tunnel): Main\Result
	{
		$result = new Main\Result();

		if (!$this->isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		$template = new Bizproc\Automation\Engine\Template($this->documentType, $tunnel['srcStage']);

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

	/**
	 * @param int $userId
	 * @param array $tunnel
	 * @return Main\Result
	 */
	public function updateTunnel(int $userId, array $tunnel): Main\Result
	{
		$result = new Main\Result();

		if (!$this->isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		$srcCategory = (int)$tunnel['srcCategory'];
		$dstCategory = (int)$tunnel['robot']['Properties']['CategoryId'];

		if ($srcCategory === $dstCategory)
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY')));
			return $result;
		}

		$template = new Bizproc\Automation\Engine\Template($this->documentType, $tunnel['srcStage']);

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
						$result->setData([
							'tunnel' => $this->prepareRobotToTunnel($robot, $tunnel['srcStage'], $tunnel['srcCategory'])
						]);
					}

					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $stageId
	 * @param int $categoryId
	 * @return array
	 */
	protected function extractStageTunnels(string $stageId, int $categoryId): array
	{
		$result = [
			'categoryId' => $categoryId,
			'stageId' => $stageId,
			'locked' => false,
			'hasRobots' => false,
			'tunnels' => []
		];

		$template = new Bizproc\Automation\Engine\Template($this->documentType, $stageId);

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
					if ($robot->getType() === $this->getRobotType())
					{
						$result['tunnels'][] = $this->prepareRobotToTunnel($robot, $stageId, $categoryId);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param Robot $robot
	 * @param string $stageId
	 * @param int $categoryId
	 * @return array
	 */
	protected function prepareRobotToTunnel(Robot $robot, string $stageId, int $categoryId): array
	{
		$props = $robot->getProperties();

		if (empty($props['StageId']))
		{
			$target = Factory::createTarget($this->entityTypeId);
			$stages = array_keys($target->getStatusInfos($props['CategoryId']));
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

	/**
	 * @param int $dstCategory
	 * @param string $dstStage
	 * @return Robot
	 */
	protected function createRobot(int $dstCategory, string $dstStage)
	{
		return new Robot([
			'Name' => Robot::generateName(),
			'Type' => $this->getRobotType(),
			'Properties' => $this->getRobotProperties($dstCategory, $dstStage),
		]);
	}

	/**
	 * @return string
	 */
	protected function getRobotType(): string
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId))
		{
			return 'CrmCopyDynamicActivity';
		}
		else
		{
			return 'CrmCopyDealActivity';
		}
	}

	/**
	 * @param int $dstCategory
	 * @param string $dstStage
	 * @return array
	 */
	protected function getRobotProperties(int $dstCategory, string $dstStage): array
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId))
		{
			return [
				'Title' => Loc::getMessage('CRM_AUTOMATION_TUNNEL_ROBOT_DYNAMIC_TITLE'),
				'ItemTitle' => '{=Document:TITLE}',
				'CategoryId' => $dstCategory,
				'StageId' => $dstStage,
			];
		}
		else
		{
			return [
				'Title' => Loc::getMessage('CRM_AUTOMATION_TUNNEL_ROBOT_DEAL_TITLE'),
				'DealTitle' => '{=Document:TITLE}',
				'CategoryId' => $dstCategory,
				'StageId' => $dstStage,
			];
		}
	}
}