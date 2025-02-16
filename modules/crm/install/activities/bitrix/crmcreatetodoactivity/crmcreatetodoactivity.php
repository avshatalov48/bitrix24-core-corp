<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc;
use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider\ToDo\BlocksManager;
use Bitrix\Crm\Entity\MessageBuilder\ProcessToDoActivityResponsible;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\Integration\Im\ProcessEntity\ToDoResponsibleNotification;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class CBPCrmCreateToDoActivity extends CBPActivity
{

	private const ATTACHMENT_TYPE_FILE = 'file';
	private const ATTACHMENT_TYPE_DISK = 'disk';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => '',
			"Description" => null,
			"Deadline" => null,
			"Responsible" => null,
			"AutoComplete" => null,
			"Subject" => '',
			"ColorId" => '',
			'AttachmentType' => static::ATTACHMENT_TYPE_FILE,
			'Attachment' => [],
			"Address" => '',
			'LocationId' => '',
			'Colleagues' => [],
			'Client' => '',
			'Link' => '',
			'Notification' => [],
			'Duration' => null,
			//return
			"Id" => null,
		];

		$this->SetPropertiesTypes(['Id' => ['Type' => 'int']]);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$ownerTypeId, $ownerId] = \CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		$responsibleId = $this->getResponsibleId($ownerTypeId, $ownerId);
		$deadline = $this->getDateTimeObjectFromProperty($this->Deadline, $responsibleId);
		$description = CBPHelper::stringify($this->Description);
		$subject =
			$this->Subject
				? html_entity_decode(CBPHelper::stringify($this->Subject))
				: Loc::getMessage('CRM_BP_CREATE_TODO_SUBJECT_DEFAULT')
		;
		$colorId = $this->ColorId ? (string)$this->ColorId : 'default';
		$address = CBPHelper::stringify($this->Address);
		$link = CBPHelper::stringify($this->Link);
		$locationId = $this->getNumFromProperty($this->LocationId);
		$colleaguesIds = (array)CBPHelper::extractUsers($this->Colleagues, $this->getDocumentId());
		$colleaguesIds[] = (string)$responsibleId;
		$duration = $this->getNumFromProperty($this->Duration);
		$notification = $this->getIntValuesArray($this->Notification);
		if (!isset(self::getDurationOptions()[$duration]))
		{
			$duration = null;
		}

		$todo = new \Bitrix\Crm\Activity\Entity\ToDo(
			new Bitrix\Crm\ItemIdentifier($ownerTypeId, $ownerId),
			new Crm\Activity\Provider\ToDo\ToDo(),
		);

		$todo->setDeadline($deadline);
		$todo->setDescription($description);
		$todo->setSubject($subject);
		$todo->setCheckPermissions(false);
		$todo->setColorId($colorId);

		if ($responsibleId)
		{
			$todo->setResponsibleId($responsibleId);
		}

		$attachment = [];
		$fileIds = $this->getIntValuesArray($this->Attachment);
		if (\Bitrix\Main\Loader::includeModule('disk'))
		{
			if ($this->AttachmentType === static::ATTACHMENT_TYPE_DISK)
			{
				$attachment = $fileIds;
				$fileIds = self::getRealFilesIds($fileIds);
			}
			else
			{
				$attachment = self::saveFilesToStorage($ownerTypeId, $ownerId, $fileIds);
			}
		}

		if ($attachment)
		{
			$todo->setStorageElementIds($attachment);
		}

		$additionalFields = [];
		if ($address)
		{
			$additionalFields['SETTINGS'] = ['ADDRESS_FORMATTED' => $address];
		}
		$additionalFields['PING_OFFSETS'] = $notification;
		$todo->setAdditionalFields($additionalFields);

		$settings = [];

		if($link)
		{
			$settings[] = [
				'link' => $link,
				'id' => 'link',
			];
		}

		if ($duration)
		{
			$settings[] = $this->prepareCalendarSettings($deadline->getTimestamp(), $duration, $colleaguesIds, $locationId);
		}

		if ($this->Client === 'Y')
		{
			$clientSettings = $this->prepareClientSettings();
			if ($clientSettings)
			{
				$settings[] = $clientSettings;
			}
		}

		$blocksManager = BlocksManager::createFromEntity($todo);
		$options = $blocksManager->getEntityOptions($settings);
		$todo = $blocksManager->enrichEntityWithBlocks($settings, false, false);

		if ($this->AutoComplete === 'Y')
		{
			$todo->setAutocompleteRule(\Bitrix\Crm\Activity\AutocompleteRule::AUTOMATION_ON_STATUS_CHANGED);
		}

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo(
				$this->getDebugInfo(
					[
						'Description' => $description,
						'Subject' => $subject,
						'Deadline' => $deadline,
						'Duration' => $duration,
						'LocationId' => $locationId,
						'Attachment' => $fileIds,
						'Notification' => $notification,
						'Address' => $address,
						'Link' => $link,
					]
				)
			);
		}

		$context = clone Crm\Service\Container::getInstance()->getContext();
		$context->setScope(Crm\Service\Context::SCOPE_AUTOMATION);
		$todo->setContext($context);

		$saveResult = $todo->save($options);
		if (!$saveResult->isSuccess())
		{
			$this->writeToTrackingService(
				$saveResult->getErrorMessages()[0],
				0,
				CBPTrackingType::Error,
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$this->Id = $todo->getId();
		$this->notifyAboutResponsible($todo);

		return CBPActivityExecutionStatus::Closed;
	}

	private function getResponsibleId($ownerTypeId, $ownerId)
	{
		$id = $this->Responsible;
		if (!$id)
		{
			return CCrmOwnerType::GetResponsibleID($ownerTypeId, $ownerId, false);
		}

		return CBPHelper::ExtractUsers($id, $this->GetDocumentId(), true);
	}

	private function getDateTimeObjectFromProperty($property, $userId): \Bitrix\Main\Type\DateTime
	{
		$offset = $userId ? CTimeZone::GetOffset($userId, true) : 0;
		if ($property instanceof Bitrix\Bizproc\BaseType\Value\Time)
		{
			$time = $property->toSystemObject();
			$currentDate = new \Bitrix\Main\Type\DateTime();
			$timestamp  = $time->setDate(
				$currentDate->format('Y'),
				$currentDate->format('m'),
				$currentDate->format('d')
			)->getTimestamp();
		}
		else
		{
			$timestamp = CBPHelper::makeTimestamp($property) ? : time();
		}

		return (new Bizproc\BaseType\Value\DateTime($timestamp, $offset))->toSystemObject();
	}

	private function getClients(?Item $entity): array
	{
		if (!$entity)
		{
			return [];
		}
		$clients = [];
		if ($entity->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
		{
			$contacts = [];
			foreach ($entity->getContactIds() as $contactId)
			{
				$contacts[] = [
					'entityId' => $contactId,
					'entityTypeId' => 3,
				];
			}
			$clients = array_merge($clients, $contacts);
		}

		if (
			$entity->getEntityTypeId() === \CCrmOwnerType::Contact
			|| $entity->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS)
		)
		{
			$companies = [];
			foreach ($entity->getCompanies() as $company)
			{
				$companies[] = [
					'entityId' => $company->getId(),
					'entityTypeId' => 4,
				];
			}
			$clients = array_merge($clients, $companies);
		}

		if ($entity->hasField(Item::FIELD_NAME_COMPANY))
		{
			$clients[] = [
				'entityId' => $entity->getCompanyId(),
				'entityTypeId' => 4,
			];
		}

		return $clients;
	}

	private function getIntValuesArray(mixed $array): array
	{
		return array_map('intval', array_filter(CBPHelper::flatten($array), 'is_numeric'));
	}

	private function getNumFromProperty(mixed $property): ?int
	{
		return is_numeric($property) ? (int)$property : null;
	}

	private function prepareCalendarSettings(int $startTime, int $duration, array $colleaguesIds, ?int $locationId): array
	{
		$startTimeJs = $startTime * 1000;
		$calendarSettings = [
			'from' => (string)$startTimeJs,
			'to' => (string)($startTimeJs + $duration),
			'duration' => (string)($duration),
			'selectedUserIds' => array_unique($colleaguesIds),
			'location' => "",
			'id' => 'calendar',
		];
		if ($locationId && self::isLocationFeatureEnabled())
		{
			$calendarSettings['location'] = $this->setCalendarPrefix($locationId);
		}

		return $calendarSettings;
	}

	private function prepareClientSettings(): ?array
	{
		[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		$entity = $factory?->getItem($entityId);
		$clients = $this->getClients($entity);
		if ($clients)
		{
			$clientSettings = [];
			foreach ($clients as $client)
			{
				$client['isAvailable'] = true;
				$clientSettings['selectedClients'][] = $client;
			}
			$clientSettings['id'] = 'client';

			return $clientSettings;
		}

		return null;
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		$fieldsMap = static::getPropertiesMap([]);

		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			if (
				CBPHelper::getBool($fieldProperties['Required'] ?? null)
				&& CBPHelper::isEmptyValue($testProperties[$propertyKey])
			)
			{
				$errors[] = [
					"code" => "NotExist",
					"parameter" => $propertyKey,
					"message" => GetMessage("CRM_BP_CREATE_TODO_EMPTY_PROP", ['#PROPERTY#' => $fieldProperties['Name']]
					),
				];
			}
		}
		if (
			CBPHelper::isEmptyValue($testProperties['Duration']) &&
			(
				!CBPHelper::isEmptyValue($testProperties['LocationId'])
				|| !CBPHelper::isEmptyValue($testProperties['Colleagues'])
			)
		)
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'Duration',
				'message' => Loc::getMessage('CRM_BP_CREATE_TODO_EMPTY_DURATION'),
			];
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = "",
		$popupWindow = null,
		$siteId = ''
	)
	{
		if (!CModule::IncludeModule("crm"))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		if (!CModule::IncludeModule("crm"))
		{
			return [];
		}

		$map = [
			'Subject' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_SUBJECT'),
				'Description' => Loc::getMessage('CRM_BP_CREATE_TODO_SUBJECT_DESCRIPTION'),
				'FieldName' => 'subject',
				'Type' => 'string',
				'Required' => false,
				'Default' => Loc::getMessage('CRM_BP_CREATE_TODO_SUBJECT_DEFAULT'),
			],
			'Description' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_DESCRIPTION_1'),
				'Description' => Loc::getMessage('CRM_BP_CREATE_TODO_DESCRIPTION_DESCRIPTION'),
				'FieldName' => 'description',
				'Type' => 'text',
				'Required' => false,
			],
			'Deadline' => [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_DEADLINE_1'),
				'FieldName' => 'deadline',
				'Type' => 'datetime',
				'Default' => \Bitrix\Bizproc\Automation\Helper::getDateTimeIntervalString(['inTime' => [12, 00]]),
			],
			'Responsible' => [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_RESPONSIBLE_ID'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Default' => ($documentType ? \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression(
					$documentType
				) : 'author'),
			],
			'ColorId' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR'),
				'FieldName' => 'color_id',
				'Type' => 'select',
				'Required' => false,
				'Multiple' => false,
				'Options' => self::getDefaultColorsOptions(),
				'Default' => 'default',
			],
			'Duration' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_DURATION'),
				'FieldName' => 'duration',
				'Type' => 'select',
				'Options' => self::getDurationOptions(),
				'Additional' => true,
			],
			'Client' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_CLIENT'),
				'FieldName' => 'client',
				'Type' => 'bool',
				'Required' => false,
				'Additional' => true,
			],
			'Colleagues' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_COLLEAGUES'),
				'FieldName' => 'colleagues',
				'Type' => 'user',
				'Required' => false,
				'Multiple' => true,
				'Additional' => true,
			],
			'Address' => [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_ADDRESS'),
				'Description' => Loc::getMessage('CRM_BP_CREATE_TODO_ADDRESS'),
				'FieldName' => 'address',
				'Type' => 'string',
				'Required' => false,
				'Multiple' => false,
				'Additional' => true,
			],
		];

		if (self::isLocationFeatureEnabled())
		{
			$map['LocationId'] = [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_LOCATION'),
				'Description' => Loc::getMessage('CRM_BP_CREATE_TODO_LOCATION'),
				'FieldName' => 'location_id',
				'Type' => 'select',
				'Required' => false,
				'Multiple' => false,
				'Additional' => true,
				'Options' => self::getRoomsOptions(),
			];
		}

		$map['Link'] = [
			'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_LINK'),
			'Description' => Loc::getMessage('CRM_BP_CREATE_TODO_LINK'),
			'FieldName' => 'link',
			'Type' => 'string',
			'Required' => false,
			'Multiple' => false,
			'Additional' => true,
		];

		if (\Bitrix\Main\Loader::includeModule('disk'))
		{
			$map['AttachmentType']  = [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_ATTACHMENT_TYPE'),
				'FieldName' => 'attachment_type',
				'Type' => 'select',
				'Options' => [
					static::ATTACHMENT_TYPE_FILE => Loc::getMessage('CRM_BP_CREATE_TODO_ATTACHMENT_TYPE_FILE'),
					static::ATTACHMENT_TYPE_DISK => Loc::getMessage('CRM_BP_CREATE_TODO_ATTACHMENT_TYPE_DISK'),
				],
			];
			$map['Attachment'] = [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_ATTACHMENT'),
				'FieldName' => 'attachment',
				'Type' => 'file',
				'Multiple' => true,
				'Additional' => true,
			];
		}

		if (defined('Bitrix\Bizproc\BaseType\Select::VIEW_TYPE_MENU'))
		{
			$map['Notification'] = [
				'Name' => Loc::getMessage('CRM_BP_CREATE_TODO_NOTIFICATION'),
				'FieldName' => 'notification',
				'Type' => 'select',
				'Multiple' => true,
				'Options' => self::getDefaultPingOffsets(),
				'Default' => ['0', '15'],
				'Settings' => [
					'ViewType' => Bizproc\BaseType\Select::VIEW_TYPE_MENU,
				],
			];
		}

		$entityTypeId = CCrmOwnerType::ResolveID($documentType[2]);
		if (Crm\Automation\Factory::isAutomationAvailable($entityTypeId))
		{
			$map['AutoComplete'] = [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_AUTO_COMPLETE_ON_ENTITY_ST_CHG_MSGVER_1'),
				'FieldName' => 'auto_completed',
				'Type' => 'bool',
				'Default' => 'N',
			];
		}

		return $map;
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$currentValues,
		&$errors
	)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$errors = $properties = [];
		/** @var CBPDocumentService $documentService */
		$documentService = $runtime->GetService('DocumentService');

		$fieldsMap = static::getPropertiesMap($documentType);
		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if (!$field)
			{
				continue;
			}

			$properties[$propertyKey] = $field->extractValue(
				['Field' => $fieldProperties['FieldName']],
				$currentValues,
				$errors
			);
		}

		if ($properties['AttachmentType'] === static::ATTACHMENT_TYPE_DISK)
		{
			foreach ((array)$currentValues["attachment"] as $attachmentId)
			{
				$attachmentId = (int)$attachmentId;
				if ($attachmentId > 0)
				{
					$properties['Attachment'][] = $attachmentId;
				}
			}
		}
		else
		{
			$properties['Attachment'] = $currentValues["attachment"] ?? ($currentValues["attachment_text"] ?? '');
		}

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	private function notifyAboutResponsible(Crm\Activity\Entity\ToDo $todo): void
	{
		$messageBuilder = new ProcessToDoActivityResponsible($todo->getOwner()->getEntityTypeId());
		$notification = new ToDoResponsibleNotification($todo, $messageBuilder);

		$notification->sendWhenAdd($todo->getResponsibleId());
	}

	private function setCalendarPrefix(string $locationId): string
	{
		return 'calendar_' . $locationId;
	}

	private static function getDefaultPingOffsets(): array
	{
		$pingOffsets = Bitrix\Crm\Activity\TodoPingSettingsProvider::getDefaultOffsetList();
		$pingOffsetsOptions = [];
		foreach ($pingOffsets as $pingOffset)
		{
			$pingOffsetsOptions[(string)$pingOffset['offset']] = $pingOffset['title'];
		}
		return $pingOffsetsOptions;
	}

	private static function saveFilesToStorage(int $ownerTypeId, int $ownerId, array $fileUploaderIds)
	{

		$item = new ItemIdentifier($ownerTypeId, $ownerId);
		if (!Loader::includeModule('disk'))
		{
			return [];
		}

		$hiddenStorage = (new HiddenStorage())->setSecurityContextOptions(
			[
				'entityTypeId' => $item->getEntityTypeId(),
				'entityId' => $item->getEntityId()]
		);
		$files = $hiddenStorage->addFilesToFolder($fileUploaderIds, HiddenStorage::FOLDER_CODE_ACTIVITY);

		return self::getFilesIds($files);
	}

	private static function getFilesIds(array $files): array
	{
		$ids = [];
		/* @var \Bitrix\Disk\File $file*/
		foreach ($files as $file)
		{
			$ids[] = $file->getId();
		}
		return $ids;
	}

	private static function getRealFilesIds(array $fileIds): array
	{
		/* @var \Bitrix\Disk\Internals\EO_File_Collection $files*/
		$files = \Bitrix\Disk\File::getList(
			[
				'select' => ['ID', 'FILE_ID'],
				'filter' => ['ID' => $fileIds],
			]
		)->fetchCollection();

		return $files->getFileIdList();
	}

	private static function isLocationFeatureEnabled(): bool
	{
		return \Bitrix\Crm\Integration\Bitrix24Manager::isFeatureEnabled('calendar_location') && CModule::IncludeModule("calendar");
	}

	private static function getDurationOptions():array
	{
		$options = [
			15 * 60 * 1000 => Loc::getMessage('CRM_BP_CREATE_TODO_DURATION_MINUTES', ['#MINUTES#' => 15]),
			30 * 60 * 1000 => Loc::getMessage('CRM_BP_CREATE_TODO_DURATION_MINUTES', ['#MINUTES#' => 30]),
			45 * 60 * 1000 => Loc::getMessage('CRM_BP_CREATE_TODO_DURATION_MINUTES', ['#MINUTES#' => 45]),
			60 * 60 * 1000 => Loc::getMessage('CRM_BP_CREATE_TODO_DURATION_60'),
		];

		for ($minutes = 90; $minutes <= 480; $minutes += 30) {
			$milliseconds = $minutes * 60 * 1000;
			if ($minutes / 60 < 5)
			{
				$options[$milliseconds] = Loc::getMessage('CRM_BP_CREATE_TODO_DURATION_HOUR', ['#HOUR#' => $minutes / 60]);
			}
			else
			{
				$options[$milliseconds] = Loc::getMessage('CRM_BP_CREATE_TODO_DURATION_HOUR_MORE_THEN_5', ['#HOUR#' => $minutes / 60]);
			}
		}

		return $options;
	}

	private static function getDefaultColorsOptions()
	{
		return [
			'default' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_YELLOW'),
			'1' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_BLUE'),
			'2' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_TURQUOISE'),
			'3' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_ORANGE'),
			'4' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_GREEN'),
			'5' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_LILAC'),
			'6' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_GREY'),
			'7' => Loc::getMessage('CRM_BP_CREATE_TODO_COLOR_PINK'),
		];
	}

	private static function getRoomsOptions()
	{
		$roomList= Bitrix\Calendar\Rooms\Manager::getRoomsList();
		$options = [];
		foreach ($roomList as $room)
		{
			$options[$room['ID']] = $room['NAME'];
		}

		return $options;
	}
}
