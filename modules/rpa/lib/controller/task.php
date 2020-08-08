<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Rpa\Driver;
use Bitrix\Main\Localization\Loc;

class Task extends Base
{
	/** @var \Bitrix\Rpa\Model\Timeline */
	protected $createdTimeline;

	public function deleteAction($typeId, $stageId, $robotName, string $eventId = '')
	{
		$isRobotDeleted = false;
		$documentType = \Bitrix\Rpa\Integration\Bizproc\Document\Item::makeComplexType($typeId);
		$template = new \Bitrix\Bizproc\Automation\Engine\Template($documentType, $stageId);

		$robots = [];

		foreach ($template->getRobots() as $robot)
		{
			if ($robotName !== $robot->getName())
			{
				$robots[] = $robot;
			}
			else
			{
				$isRobotDeleted = true;
			}
		}

		if($isRobotDeleted)
		{
			$tplUser = new \CBPWorkflowTemplateUser(\CBPWorkflowTemplateUser::CurrentUser);
			$result = $template->save($robots, $tplUser->getId());

			if($result->isSuccess())
			{
				Driver::getInstance()->getPullManager()->sendRobotDeletedEvent($typeId, $stageId, $robotName, $eventId);
			}
		}

		return true;
	}

	public function addUserAction($typeId, $stageId, $robotName, string $userValue)
	{
		$updatedRobot = null;
		$documentType = \Bitrix\Rpa\Integration\Bizproc\Document\Item::makeComplexType($typeId);

		$user = reset(\CBPHelper::UsersStringToArray($userValue, $documentType, $errors));

		if (!$user)
		{
			return false;
		}

		$template = new \Bitrix\Bizproc\Automation\Engine\Template($documentType, $stageId);
		$robots = [];

		foreach ($template->getRobots() as $robot)
		{
			if ($robotName === $robot->getName())
			{
				$robotUsers = $robot->getProperty('Responsible');
				if (!in_array($user, $robotUsers))
				{
					$robotUsers[] = $user;
					$robot->setProperty('Responsible', $robotUsers);
					$updatedRobot = $robot;
				}
			}
			$robots[] = $robot;
		}

		if($updatedRobot)
		{
			$tplUser = new \CBPWorkflowTemplateUser(\CBPWorkflowTemplateUser::CurrentUser);
			$result = $template->save($robots, $tplUser->getId());

			if($result->isSuccess())
			{
				Driver::getInstance()->getTaskManager()->onTaskPropertiesChanged(
					$template->getDocumentType(),
					$template->getId(),
					$updatedRobot->toArray()
				);

				Driver::getInstance()->getPullManager()->sendRobotUpdatedEvent($typeId, $stageId, ['robotName' => $robotName]);
			}
		}

		return true;
	}

	public function doAction(): ?array
	{
		$result = [];
		$formData = $this->getRequest()->getPostList()->getValues();

		$taskManager = Driver::getInstance()->getTaskManager();
		if(!$taskManager)
		{
			$this->addError(new Error(Loc::getMessage("RPA_CONTROLLER_TASK_NOT_FOUND")));
			return null;
		}
		$task = $taskManager->getTaskById($formData['taskId']);
		if ($task)
		{
			$eventHandlerKey = $this->subscribeOnTimelineAddEvent((int)$formData['taskId']);
			$userId = $this->getCurrentUser()->getId();
			if (\CBPDocument::PostTaskForm($task, $userId, $formData, $errors))
			{
				$result = ['completed' => 'ok'];
			}
			else
			{
				$error = reset($errors);
				if ($error['code'] === \CBPRuntime::EXCEPTION_CODE_INSTANCE_TERMINATED)
				{
					$result = ['completed' => 'ok', 'stageUpdated' => true];
				}
				else
				{
					$this->addError(new Error($error['message']));
					return null;
				}
			}

			if($this->createdTimeline)
			{
				$item = $this->createdTimeline->getItem();
				if($item)
				{
					$result['item'] = (new Item())->prepareItemData($item);
				}
				$result['timeline'] = $this->createdTimeline->preparePublicData();
			}
			$this->unSubscribeOnTimelineAddEvent($eventHandlerKey);

		}
		else
		{
			$this->addError(new Error(Loc::getMessage("RPA_CONTROLLER_TASK_NOT_FOUND")));
		}

		return $result;
	}

	protected function subscribeOnTimelineAddEvent(int $taskId): ?int
	{
		return EventManager::getInstance()->addEventHandler(
			Driver::MODULE_ID,
			'\Bitrix\Rpa\Model\Timeline::OnAfterAdd',
			function(Event $event) use ($taskId)
			{
				$timeline = $event->getParameter('object');
				if($timeline instanceof \Bitrix\Rpa\Model\Timeline)
				{
					$data = $timeline->getData();
					if(!empty($data['task']) && (int)$data['task']['ID'] === $taskId)
					{
						$this->createdTimeline = $timeline;
					}
				}
			}
		);
	}

	protected function unSubscribeOnTimelineAddEvent(int $eventHandlerKey): void
	{
		EventManager::getInstance()->removeEventHandler(
			Driver::MODULE_ID,
			'\Bitrix\Rpa\Model\Timeline::OnAfterAdd',
			$eventHandlerKey
		);
	}
}