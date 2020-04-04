<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Localization\Loc;

final class Sharing extends Engine\Controller
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\Sharing::class, 'sharing', function($className, $id){
			return Disk\Sharing::loadById($id);
		});
	}

	public function deleteAction(Disk\Sharing $sharing)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$baseObject = $sharing->getRealObject();
		if (!$baseObject)
		{
			$this->errorCollection[] = new Error('Could not find object');

			return;
		}

		$securityContext = $baseObject->getStorage()->getSecurityContext($currentUserId);
		if (!$baseObject->canChangeRights($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$sharing->delete($currentUserId))
		{
			$this->errorCollection->add($sharing->getErrors());
		}
	}

	public function changeTaskNameAction(Disk\Sharing $sharing, $newTaskName)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$baseObject = $sharing->getRealObject();
		if (!$baseObject)
		{
			$this->errorCollection[] = new Error('Could not find object');

			return;
		}

		$securityContext = $baseObject->getStorage()->getSecurityContext($currentUserId);
		if (!$baseObject->canShare($securityContext) || !$this->validateTaskName($newTaskName))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		$rightsManager = Disk\Driver::getInstance()->getRightsManager();
		$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($baseObject, $currentUserId);

		if ($rightsManager->pseudoCompareTaskName($newTaskName, $maxTaskName) > 0)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		$rightsManager = Disk\Driver::getInstance()->getRightsManager();

		$domain = $rightsManager->getSharingDomain($sharing->getId());
		$rightsManager->deleteByDomain($baseObject->getRealObject(), $domain);

		if (!$sharing->changeTaskName($newTaskName))
		{
			$this->errorCollection->add($sharing->getErrors());

			return;
		}

		$newRights = [
			[
				'ACCESS_CODE' => $sharing->getToEntity(),
				'TASK_ID' => $rightsManager->getTaskIdByName($sharing->getTaskName()),
				'DOMAIN' => $domain,
			]
		];

		$rightsManager->append($baseObject->getRealObject(), $newRights);
	}

	private function validateTaskName($taskName)
	{
		return in_array(
			$taskName,
			[
				Disk\RightsManager::TASK_READ,
				Disk\RightsManager::TASK_ADD,
				Disk\RightsManager::TASK_EDIT,
				Disk\RightsManager::TASK_FULL,
			]
		);
	}
}