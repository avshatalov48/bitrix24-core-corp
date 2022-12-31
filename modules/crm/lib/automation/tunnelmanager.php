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
	public const ROBOT_ACTION_COPY = 'copy';
	public const ROBOT_ACTION_MOVE = 'move';

	protected $entityTypeId;
	protected $documentType;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->documentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);
		Loc::loadMessages(Main\IO\Path::combine(__DIR__, 'TunnelManager.php'));
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

		foreach ($this->getCategories() as $category)
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
	 * @param string $robotAction
	 *
	 * @return Main\Result
	 */
	public function addTunnel(
		int $userId,
		int $srcCategory,
		string $srcStage,
		int $dstCategory,
		string $dstStage,
		string $robotAction = self::ROBOT_ACTION_COPY
	): Main\Result
	{
		$result = new Main\Result();

		if (!in_array($robotAction, [self::ROBOT_ACTION_COPY, self::ROBOT_ACTION_MOVE], true))
		{
			$result->addError(new Error('Unknown robot action'));
			return $result;
		}
		if (!$this->isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		if ($srcCategory === $dstCategory)
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY2')));
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

			$robot = $this->createRobot($dstCategory, $dstStage, $robotAction);

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
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY2')));
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
					if ($robot->getType() === $this->getRobotType() || $robot->getType() === $this->getRobotType(self::ROBOT_ACTION_MOVE))
					{
						$result['tunnels'][] = $this->prepareRobotToTunnel($robot, $stageId, $categoryId);
					}
				}
				$result['tunnels'] = array_filter($result['tunnels']);
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
	protected function prepareRobotToTunnel(Robot $robot, string $stageId, int $categoryId): ?array
	{
		$props = $robot->getProperties();

		if (!is_numeric($props['CategoryId']) || $categoryId === (int)$props['CategoryId'])
		{
			return null;
		}

		if (empty($props['StageId']))
		{
			$target = Factory::createTarget($this->entityTypeId);
			$stages = array_keys($target->getStatusInfos($props['CategoryId']));
			$props['StageId'] = reset($stages);
		}

		if (\CBPDocument::isExpression($props['StageId']))
		{
			return null;
		}

		return [
			'srcCategory' => $categoryId,
			'srcStage' => $stageId,
			'dstCategory' => $props['CategoryId'],
			'dstStage' => $props['StageId'],
			'robotAction' => $this->getRobotAction($robot),
			'robot' => $robot->toArray()
		];
	}

	/**
	 * @param int $dstCategory
	 * @param string $dstStage
	 * @return Robot
	 */
	protected function createRobot(int $dstCategory, string $dstStage, string $robotAction = self::ROBOT_ACTION_COPY)
	{
		return new Robot([
			'Name' => Robot::generateName(),
			'Type' => $this->getRobotType($robotAction),
			'Properties' => $this->getRobotProperties($dstCategory, $dstStage, $robotAction),
		]);
	}

	/**
	 * @return string
	 */
	protected function getRobotType(string $robotAction = self::ROBOT_ACTION_COPY): ?string
	{
		return $this->getRobotsActionMap()[$robotAction] ?? null;
	}

	/**
	 * @param Robot $robot
	 *
	 * @return string
	 */
	protected function getRobotAction(Robot $robot): string
	{
		$actionsMap = $this->getRobotsActionMap();
		return array_flip($actionsMap)[$robot->getType()] ?? self::ROBOT_ACTION_COPY;
	}

	/**
	 * @return string[]
	 */
	protected function getRobotsActionMap(): array
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId))
		{
			return [
				self::ROBOT_ACTION_MOVE => 'CrmChangeDynamicCategoryActivity',
				self::ROBOT_ACTION_COPY => 'CrmCopyDynamicActivity',
			];
		}
		else
		{
			return [
				self::ROBOT_ACTION_MOVE => 'CrmChangeDealCategoryActivity',
				self::ROBOT_ACTION_COPY => 'CrmCopyDealActivity',
			];
		}
	}

	/**
	 * @param int $dstCategory
	 * @param string $dstStage
	 * @param string $robotAction
	 * @return array
	 */
	protected function getRobotProperties(int $dstCategory, string $dstStage, string $robotAction): array
	{
		$robotAction = mb_strtoupper($robotAction);
		if (\CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId))
		{
			return [
				'Title' => Loc::getMessage("CRM_AUTOMATION_TUNNEL_ROBOT_{$robotAction}_DYNAMIC_TITLE"),
				'ItemTitle' => '{=Document:TITLE}',
				'CategoryId' => $dstCategory,
				'StageId' => $dstStage,
			];
		}
		else
		{
			return [
				'Title' => Loc::getMessage("CRM_AUTOMATION_TUNNEL_ROBOT_{$robotAction}_DEAL_TITLE"),
				'DealTitle' => '{=Document:TITLE}',
				'CategoryId' => $dstCategory,
				'StageId' => $dstStage,
			];
		}
	}

	public function updateStageTunnels(array $tunnels, string $stageId,  int $userId): Main\Result
	{
		$result = new Main\Result();

		if (!$this->isAvailable())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_UNAVAILABLE')));
			return $result;
		}

		$template = new Bizproc\Automation\Engine\Template($this->documentType, $stageId);

		if ($template->isExternalModified())
		{
			$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_EXTERNAL_TEMPLATE')));
			return $result;
		}

		$templateRobots = $template->getRobots();
		$robotsMap = [];
		if (!empty($templateRobots) && is_array($templateRobots))
		{
			foreach ($templateRobots as $robot)
			{
				$robotsMap[$robot->getName()] = $robot;
			}
		}
		$robots = [];
		foreach ($tunnels as $tunnel)
		{
			if ($tunnel['srcCategory'] === $tunnel['dstCategory'])
			{
				$result->addError(new Error(Loc::getMessage('CRM_AUTOMATION_TUNNEL_ADD_ERROR_SAME_CATEGORY2')));
				return $result;
			}

			if (!$tunnel['robot'])
			{
				$robot = $this->createRobot($tunnel['dstCategory'], $tunnel['dstStage']);
				$robots[] = $robot;
			}
			else
			{
				$robotName = $tunnel['robot']['Name'];
				if (isset($robotsMap[$robotName]))
				{
					$copy = clone $robotsMap[$robotName];
					$copy->setProperty('CategoryId', $tunnel['dstCategory']);
					$copy->setProperty('StageId', $tunnel['dstStage']);
					$robots[] = $copy;
				}
			}
		}

		return $template->save($robots, $userId);
	}
}