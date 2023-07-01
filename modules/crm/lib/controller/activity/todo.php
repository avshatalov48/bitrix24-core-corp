<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\Settings\Manager;
use Bitrix\Crm\Activity\Settings\Section\Calendar;
use Bitrix\Crm\Activity\Settings\Section\Ping;
use Bitrix\Crm\Activity\TodoCreateNotification;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\FileUploader\TodoActivityUploaderController;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\Integration\UI\FileUploader;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Disk\File;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;
use CIMNotify;

class ToDo extends Base
{
	private const NOTIFY_BECOME_RESPONSIBLE = 1;
	private const NOTIFY_NO_LONGER_RESPONSIBLE = 2;

	public function getNearestAction(int $ownerTypeId, int $ownerId): ?array
	{
		$itemIdentifier = new ItemIdentifier($ownerTypeId, $ownerId);

		$todo = Entity\ToDo::loadNearest($itemIdentifier);
		if (!$todo)
		{
			return null;
		}

		return [
			'id' => $todo->getId(),
			'parentActivityId' => $todo->getParentActivityId(),
			'description' => $todo->getDescription(),
			'deadline' => $todo->getDeadline()->toString(),
			'storageElementIds' => array_map(
				'intval',
				(new HiddenStorage())->fetchFileIdsByStorageFileIds($todo->getStorageElementIds())
			),
		];
   }

	public function addAction(
		int $ownerTypeId,
		int $ownerId,
		string $deadline,
		string $description = '',
		?int $responsibleId = null,
		?int $parentActivityId = null,
		array $fileTokens = [],
		array $settings = []
	): ?array
	{
		$todo = new Entity\ToDo(
			new ItemIdentifier($ownerTypeId, $ownerId)
		);

		$todo = $this->getPreparedEntity($todo, $description, $deadline, $parentActivityId, $responsibleId);
		if (!$todo)
		{
			return null;
		}

		$settingsManager = Manager::createFromEntity($todo);
		$todo = $settingsManager->getPreparedEntity($settings);
		$options = $settingsManager->getEntityOptions($settings);

		$result = $this->saveTodo($todo, $options);
		if ($result === null)
		{
			return null;
		}

		$settingsManager->saveAll($settings);

		if (!empty($fileTokens))
		{
			// if success save - add files
			$storageElementIds = $this->saveFilesToStorage($ownerTypeId, $ownerId, $fileTokens);
			if (!empty($storageElementIds))
			{
				$todo->setStorageElementIds($storageElementIds);
			}
		}

		$result = $this->saveTodo($todo);
		if ($result === null)
		{
			return null;
		}

		$currentUserId = Container::getInstance()->getContext()->getUserId();
		if (isset($responsibleId) && $responsibleId !== $currentUserId)
		{
			$this->notifyViaIm(
				$result['id'],
				$ownerTypeId,
				$ownerId,
				$responsibleId,
				$currentUserId,
				self::NOTIFY_BECOME_RESPONSIBLE
			);
		}

		return $result;
	}

	public function updateAction(
		int $ownerTypeId,
		int $ownerId,
		string $deadline,
		int $id = null,
		string $description = '',
		?int $responsibleId = null,
		?int $parentActivityId = null,
		array $fileTokens = [],
		array $settings = []
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		if ($todo->isCompleted())
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_UPDATE_FILES_ERROR')));

			return null;
		}
		
		$prevResponsibleId = $todo->getResponsibleId();

		$todo = $this->getPreparedEntity($todo, $description, $deadline, $parentActivityId, $responsibleId);
		if (!$todo)
		{
			return null;
		}

		$currentStorageElementIds = $todo->getStorageElementIds() ?? [];
		if (!empty($fileTokens) || !empty($currentStorageElementIds))
		{
			$storageElementIds = $this->saveFilesToStorage(
				$ownerTypeId,
				$ownerId,
				$fileTokens,
				$id,
				$currentStorageElementIds
			);

			$todo->setStorageElementIds($storageElementIds);
		}

		$settingsManager = Manager::createFromEntity($todo);
		$todo = $settingsManager->getPreparedEntity($settings);
		$options = $settingsManager->getEntityOptions($settings);

		$result = $this->saveTodo($todo, $options);
		if ($result === null)
		{
			return null;
		}

		$this->tryNotifyWhenUpdate(
			$id,
			$ownerTypeId,
			$ownerId,
			$responsibleId,
			$prevResponsibleId
		);

		return $result;
	}

	public function updateSettingsAction(int $ownerTypeId, int $ownerId, int $id, array $settings = []): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		$settingsManager = Manager::createFromEntity($todo);
		$todo = $settingsManager->getPreparedEntity($settings);
		$options = $settingsManager->getEntityOptions($settings);

		return $this->saveTodo($todo, $options);
	}

	protected function getPreparedEntity(
		Entity\ToDo $todo,
		string $description,
		string $deadline,
		?int $parentActivityId,
		?int $responsibleId = null
	): ?Entity\ToDo
	{
		$todo->setDescription($description);

		$deadline = $this->prepareDatetime($deadline);
		if (!$deadline)
		{
			return null;
		}
		$todo->setDeadline($deadline);

		$todo->setParentActivityId($parentActivityId);

		if ($responsibleId)
		{
			$todo->setResponsibleId($responsibleId);
		}

		return $todo;
	}

	public function updateDeadlineAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		$deadline = $this->prepareDatetime($value);
		if (!$deadline)
		{
			return null;
		}
		$todo->setDeadline($deadline);

		$todo = (Manager::createFromEntity($todo))->getPreparedEntity([], true);

		return $this->saveTodo($todo);
	}

	public function updateDescriptionAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		$todo->setDescription($value);
		$todo = (Manager::createFromEntity($todo))->getPreparedEntity([], true);

		return $this->saveTodo($todo);
	}

	public function updateFilesAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		array $fileTokens = []
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		if ($todo->isCompleted())
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_UPDATE_FILES_ERROR')));

			return null;
		}

		$todo->setStorageElementIds(
			$this->saveFilesToStorage(
				$ownerTypeId,
				$ownerId,
				$fileTokens,
				$id,
				$todo->getStorageElementIds() ?? []
			)
		);

		return $this->saveTodo($todo);
	}

	public function updateResponsibleUserAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		int $responsibleId
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		if ($todo->isCompleted())
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_UPDATE_RESPONSIBLE_USER_ERROR')));

			return null;
		}

		if ($responsibleId <= 0)
		{
			$this->addError(new Error('Parameter "responsibleId" must be greater than 0'));

			return null;
		}

		$prevResponsibleId = $todo->getResponsibleId();

		$todo->setResponsibleId($responsibleId);
		$todo = (Manager::createFromEntity($todo))->getPreparedEntity([], true);

		$result = $this->saveTodo($todo);
		if ($result === null)
		{
			return null;
		}

		$this->tryNotifyWhenUpdate(
			$id,
			$ownerTypeId,
			$ownerId,
			$responsibleId,
			$prevResponsibleId
		);

		return $result;
	}

	protected function loadEntity(int $ownerTypeId, int $ownerId, int $id): ?Entity\ToDo
	{
		$itemIdentifier = new ItemIdentifier($ownerTypeId, $ownerId);
		$todo = Entity\ToDo::load($itemIdentifier, $id);

		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return $todo;
	}

	public function skipEntityDetailsNotificationAction(int $entityTypeId, string $period): bool
	{
		if (!CCrmOwnerType::ResolveName($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));
		}

		$result = (new TodoCreateNotification($entityTypeId))->skipForPeriod($period);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	private function saveTodo(Entity\ToDo $todo, array $options = []): ?array
	{
		$saveResult = $todo->save($options);
		if ($saveResult->isSuccess())
		{
			return [
				'id' => $todo->getId(),
			];
		}

		$this->addErrors($saveResult->getErrors());

		return null;
	}

	private function saveFilesToStorage(
		int $ownerTypeId,
		int $ownerId,
		array $fileUploaderIds,
		?int $activityId = null,
		array $currentStorageElementIds = []
	): array
	{
		if (!Loader::includeModule('disk'))
		{
			$this->addError(new Error('"disk" module is required.'));

			return [];
		}

		$idsOfNewFiles = array_values(
			array_unique(
				array_filter(
					array_map(static function($item) {
						if (is_numeric($item))
						{
							return (int) $item;
						}

						if (is_string($item))
						{
							return $item;
						}
					}, $fileUploaderIds)
				)
			)
		);
		$idsNotChanged = [];
		$hiddenStorage = (new HiddenStorage())->setSecurityContextOptions([
			'entityTypeId' => $ownerTypeId,
			'entityId' => $ownerId,
		]);

		$currentFileIds = $hiddenStorage->fetchFileIdsByStorageFileIds(
			$currentStorageElementIds,
			HiddenStorage::USE_DISK_OBJ_ID_AS_KEY
		);
		if (!empty($currentFileIds))
		{
			$idsOfNewFiles = array_values(array_diff($fileUploaderIds, $currentFileIds));
			$idsToRemove = array_diff($currentFileIds, $fileUploaderIds);
			$idsNotChanged = array_keys(array_diff($currentFileIds, $idsToRemove));

			$hiddenStorage->deleteFiles(array_keys($idsToRemove));
		}

		$fileUploader = new FileUploader(
			new TodoActivityUploaderController([
				'entityTypeId' => $ownerTypeId,
				'entityId' => $ownerId,
				'activityId' => $activityId,
			])
		);
		$fileIds = $fileUploader->getPendingFiles($idsOfNewFiles);
		$hiddenStorageFiles = $hiddenStorage->addFilesToFolder($fileIds, HiddenStorage::FOLDER_CODE_ACTIVITY);
		$hiddenStorageFileIds = array_map(fn(File $file) => $file->getId(), $hiddenStorageFiles);
		$fileUploader->makePersistentFiles($idsOfNewFiles);

		return array_values(array_merge($idsNotChanged, $hiddenStorageFileIds));
	}

	public function fetchSettingsAction(int $ownerTypeId, int $ownerId, int $id): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);

		if (!$todo)
		{
			return null;
		}

 		return Manager::createFromEntity($todo)->fetch();
	}

	private function getSettingsSectionNames(): array
	{
		return [
			Calendar::TYPE_NAME,
			Ping::TYPE_NAME,
		];
	}

	private function notifyViaIm(int $activityId, int $ownerTypeId, int $ownerId, int $responsibleId, int $authorId, int $options = 0): void
	{
		if ($options === 0 || !Loader::includeModule('im'))
		{
			return;
		}

		$todo = $this->loadEntity($ownerTypeId, $ownerId, $activityId);
		if (!$todo)
		{
			return;
		}

		[$message, $messageOut] = $this->createNotificationMessages(
			$activityId,
			$ownerTypeId,
			$ownerId,
			$todo->getSubject(),
			$options
		);
		if (!isset($message, $messageOut))
		{
			return;
		}

		CIMNotify::Add([
			'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_MODULE' => 'crm',
			'TO_USER_ID' =>$responsibleId,
			'FROM_USER_ID' => $authorId,
			'NOTIFY_EVENT' => 'changeAssignedBy',
			'NOTIFY_TAG' => 'CRM|TODO_ACTIVITY|' . $activityId,
			"NOTIFY_MESSAGE" => $message,
			"NOTIFY_MESSAGE_OUT" => $messageOut,
		]);
	}

	private function tryNotifyWhenUpdate(int $activityId, int $ownerTypeId, int $ownerId, int $responsibleId, int $prevResponsibleId): void
	{
		if ($responsibleId === $prevResponsibleId)
		{
			return;
		}

		$currentUserId = Container::getInstance()->getContext()->getUserId();

		$this->notifyViaIm(
			$activityId,
			$ownerTypeId,
			$ownerId,
			$prevResponsibleId,
			$currentUserId,
			self::NOTIFY_NO_LONGER_RESPONSIBLE
		);

		$this->notifyViaIm(
			$activityId,
			$ownerTypeId,
			$ownerId,
			$responsibleId,
			$currentUserId,
			self::NOTIFY_BECOME_RESPONSIBLE
		);
	}

	private function createNotificationMessages(int $activityId, int $ownerTypeId, int $ownerId, string $subject, int $options): array
	{
		$url = Container::getInstance()->getRouter()->getItemDetailUrl($ownerTypeId, $ownerId);
		if (!isset($url))
		{
			return [];
		}

		// get phase code by input parameters
		$phaseCodeSuffix = '';
		if ($options & self::NOTIFY_BECOME_RESPONSIBLE)
		{
			$phaseCodeSuffix = 'BECOME';
		}
		elseif ($options & self::NOTIFY_NO_LONGER_RESPONSIBLE)
		{
			$phaseCodeSuffix = 'NO_LONGER';
		}

		if (empty($subject))
		{
			// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE
			// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE
			return [
				Loc::getMessage('CRM_ACTIVITY_TODO_' . $phaseCodeSuffix . '_RESPONSIBLE', [
					'#todoId#' =>  '<a href="' . $url . '">' . $activityId . '</a>'
				]),
				Loc::getMessage('CRM_ACTIVITY_TODO_' . $phaseCodeSuffix . '_RESPONSIBLE', [
					'#todoId#' => $activityId
				])
			];
		}

		$entityName = '';
		if (CCrmOwnerType::isUseFactoryBasedApproach($ownerTypeId))
		{
			$factory = Container::getInstance()->getFactory($ownerTypeId);
			if ($factory)
			{
				$item = $factory->getItem($ownerId);
				if ($item)
				{
					$entityName = $item->getHeading();
				}
			}
		}
		
		if (empty($entityName))
		{
			// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_EX
			// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_EX
			return [
				Loc::getMessage('CRM_ACTIVITY_TODO_' . $phaseCodeSuffix . '_RESPONSIBLE_EX', [
					'#subject#' =>  '<a href="' . $url . '">' . htmlspecialcharsbx($subject) . '</a>'
				]),
				Loc::getMessage('CRM_ACTIVITY_TODO_' . $phaseCodeSuffix . '_RESPONSIBLE_EX', [
					'#subject#' => htmlspecialcharsbx($subject)
				])
			];
		}

		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_LEAD
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_DEAL
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_CONTACT
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_COMPANY
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_QUOTE
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_ORDER
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_SMART_INVOICE
		// CRM_ACTIVITY_TODO_BECOME_RESPONSIBLE_DYNAMIC
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_LEAD
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_DEAL
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_CONTACT
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_COMPANY
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_QUOTE
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_ORDER
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_SMART_INVOICE
		// CRM_ACTIVITY_TODO_NO_LONGER_RESPONSIBLE_DYNAMIC

		$phaseCodeEnd = CCrmOwnerType::isPossibleDynamicTypeId($ownerTypeId)
			? CCrmOwnerType::CommonDynamicName
			: CCrmOwnerType::ResolveName($ownerTypeId);

		return [
			Loc::getMessage('CRM_ACTIVITY_TODO_' . $phaseCodeSuffix . '_RESPONSIBLE_' . $phaseCodeEnd, [
				'#subject#' => htmlspecialcharsbx($subject),
				'#entityName#' => '<a href="' . $url . '">' . htmlspecialcharsbx($entityName) . '</a>',
			]),
			Loc::getMessage('CRM_ACTIVITY_TODO_' . $phaseCodeSuffix . '_RESPONSIBLE_' . $phaseCodeEnd, [
				'#subject#' => htmlspecialcharsbx($subject),
				'#entityName#' => htmlspecialcharsbx($entityName),
			])
		];
	}
}
