<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Operation\Document\Template\Send;
use Bitrix\Sign\Result\Operation\Document\Template\SendResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Crm\Service\Context;

/**
 * @property string[] $responsible
 * @property string[] $employee
 * @property string[] $representative
 * @property string[] $reviewer
 * @property string[] $editor
 * @property int $templateId
 * @property string $isSubscriptionEnabled
 * @property int $documentId
 * @property string $documentStatus
 */
class CBPSignB2EDocumentActivity extends CBPActivity implements IBPEventActivity, IBPActivityExternalEventListener
{
	public const PARAM_TEMPLATE_ID = 'templateId';
	public const PARAM_RESPONSIBLE = 'responsible';
	public const PARAM_EMPLOYEE = 'employee';
	public const PARAM_REPRESENTATIVE = 'representative';
	public const PARAM_REVIEWER = 'reviewer';
	public const PARAM_EDITOR = 'editor';
	public const PARAM_IS_SUBSCRIPTION_ENABLED = 'isSubscriptionEnabled';
	public const RETURN_PARAM_DOCUMENT_ID = 'documentId';
	public const RETURN_PARAM_DOCUMENT_STATUS = 'documentStatus';
	public const RETURN_STATUS_SUCCESS = 'SUCCESS';
	public const RETURN_STATUS_FAIL = 'FAIL';
	private const SIGN_B2E_EVENT_TYPE_LIST = [
		EventData::TYPE_ON_DONE,
		EventData::TYPE_ON_STOPPED,
		EventData::TYPE_ON_CANCELED_BY_RESPONSIBILITY_PERSON,
		EventData::TYPE_ON_CANCELED_BY_REVIEWER,
		EventData::TYPE_ON_CANCELED_BY_EDITOR,
	];
	private const EVENT_MODULE_NAME = 'sign';

	private bool $isInEventActivityMode = false;
	private int $createdDocumentId = 0;
	private int $smartDocumentEntityId = 0;
	private int $smartDocumentEntityType = 0;
	private int $smartDocumentCreatedById = 0;

	public function __construct($name)
	{
		parent::__construct($name);

		$this->setPropertiesTypes([
			self::PARAM_TEMPLATE_ID => [
				'Type' => FieldType::INT,
			],
			self::PARAM_RESPONSIBLE => [
				'Type' => FieldType::USER,
			],
			self::PARAM_EMPLOYEE => [
				'Type' => FieldType::USER,
				'Multiple' => true,
			],
			self::PARAM_REPRESENTATIVE => [
				'Type' => FieldType::USER,
			],
			self::PARAM_REVIEWER => [
				'Type' => FieldType::USER,
				'Multiple' => true,
			],
			self::PARAM_EDITOR => [
				'Type' => FieldType::USER,
			],
			self::PARAM_IS_SUBSCRIPTION_ENABLED => [
				'Type' => FieldType::BOOL,
			],
			//return
			self::RETURN_PARAM_DOCUMENT_ID => [
				'Type' => FieldType::INT,
			],
			self::RETURN_PARAM_DOCUMENT_STATUS => [
				'Type' => FieldType::STRING,
			],
		]);

		$this->arProperties = [
			'Title' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ROBOT_TITLE'),
			self::PARAM_RESPONSIBLE => null,
			self::PARAM_EMPLOYEE => null,
			self::PARAM_REPRESENTATIVE => null,
			self::PARAM_REVIEWER => null,
			self::PARAM_EDITOR => null,
			self::PARAM_TEMPLATE_ID => null,
			self::PARAM_IS_SUBSCRIPTION_ENABLED => 'N',
			//return
			self::RETURN_PARAM_DOCUMENT_ID => null,
			self::RETURN_PARAM_DOCUMENT_STATUS => null,
		];
	}

	protected function reInitialize(): void
	{
		parent::reInitialize();

		$this->{self::RETURN_PARAM_DOCUMENT_ID} = null;
		$this->{self::RETURN_PARAM_DOCUMENT_STATUS} = null;
	}

	/**
	 * @throws Main\LoaderException
	 */
	public static function isAvailable(): bool
	{
		if (!Main\Loader::includeModule('sign'))
		{
			return false;
		}

		if (!Main\Loader::includeModule('crm'))
		{
			return false;
		}

		if (!Sign\Config\Storage::instance()->isB2eAvailable())
		{
			return false;
		}

		return true;
	}

    /**
     * @throws Main\LoaderException
     */
    public function execute(): int
    {
		if ($this->isInEventActivityMode)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!static::isAvailable())
		{
			$this->trackError(
                Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_UNAVAILABLE'),
            );

            return CBPActivityExecutionStatus::Closed;
		}

		$updateSmartDocumentEntityIdAndTypeResult = $this->updateSmartDocumentEntityIdAndType();

		if (!$updateSmartDocumentEntityIdAndTypeResult->isSuccess())
		{
			foreach ($updateSmartDocumentEntityIdAndTypeResult->getErrors() as $error)
			{
				$this->trackError(
					$error->getMessage(),
				);
			}

			return CBPActivityExecutionStatus::Closed;
		}

		$template = Container::instance()
			->getDocumentTemplateService()
			->getCompletedAndVisibleCompanyTemplateByUid($this->templateId)
		;

		if ($template === null)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_TEMPLATE_NOT_FOUND'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$document = Container::instance()->getDocumentService()->getByTemplateId((int)$template->id);
		if ($document === null)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_TEMPLATE_NOT_FOUND'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$representativeUserId = null;
		$companyId = null;

        $docId = $this->getDocumentId();
		$representativeRoleName = $this->getRoleName($this->representative);
		$editorRoleName = $this->getRoleName($this->editor);

		if ($representativeRoleName === null)
		{
			$representativeUserId = CBPHelper::extractFirstUser($this->representative, $docId);
		}

		if ((int)$representativeUserId > 0 || $representativeRoleName !== null)
		{
			$companyId = Container::instance()->getDocumentService()->getMyCompanyIdByDocument($document);
		}

		$editorUserId = $editorRoleName === null ? CBPHelper::extractFirstUser($this->editor, $docId) : null;

		$signerUserIdList = CBPHelper::extractUsers($this->employee, $docId);
        $prepareMemberListForTemplateSendOperationResult = (new Sign\Operation\Document\Template\PrepareMemberListForTemplateSend(
            signerUserIdList: $signerUserIdList,
			companyId: $companyId,
			initiatedByType: $document->initiatedByType,
			reviewerUserList: $this->getValueList($this->reviewer, $docId),
			editorUserId: $editorUserId,
			representativeRoleName: $representativeRoleName,
			editorRoleName: $editorRoleName,
        ))->launch();

        if (!$prepareMemberListForTemplateSendOperationResult instanceof Sign\Result\Operation\Document\Template\PrepareMemberListForTemplateSendResult)
        {
            $this->trackError(
                Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_MEMBERS_NOT_SET'),
            );

			foreach ($prepareMemberListForTemplateSendOperationResult->getErrors() as $error)
			{
				$this->trackError(
					$error->getMessage(),
				);
			}

            return CBPActivityExecutionStatus::Closed;
        }

		if ($prepareMemberListForTemplateSendOperationResult->representativeRoleId !== null)
		{
			$document->representativeId = $prepareMemberListForTemplateSendOperationResult->representativeRoleId;
			$updateResult = Container::instance()->getDocumentService()->update($document);
			if (!$updateResult->isSuccess())
			{
				$this->trackError(
					Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_UPDATE_FAILED'),
				);

				return CBPActivityExecutionStatus::Closed;
			}
		}

		$sendFromUserId = null;
		if ($document->initiatedByType === Sign\Type\Document\InitiatedByType::EMPLOYEE)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_INVALID_TEMPLATE_TYPE'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$responsibleUserId = CBPHelper::extractFirstUser($this->responsible, $docId);
		if ($responsibleUserId === null)
		{
			$memberService = Container::instance()->getMemberService();
			$assignee = $memberService->getAssignee($document);
			$responsibleUserId = $memberService->getUserIdForMember($assignee) ?? $this->smartDocumentCreatedById;
		}

		if ((int)$responsibleUserId < 1)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_RESPONSIBLE_NOT_SET'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$sendOperation = new Send(
			template: $template,
			responsibleUserId: $responsibleUserId,
			sendFromUserId: $sendFromUserId,
			representativeUserId: $representativeUserId,
			memberList: $prepareMemberListForTemplateSendOperationResult->members,
		);

		$bindingCollection = Document\BindingCollection::emptyList();
		$bindingCollection->add(new Document\Binding($this->smartDocumentEntityId, $this->smartDocumentEntityType));
		$sendOperation->setBindings($bindingCollection);

		$sendOperationResult = $sendOperation->launch();

		if (!$sendOperationResult instanceof SendResult)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_UNABLE_TO_CREATE_DOC'),
			);

			foreach ($sendOperationResult->getErrors() as $error)
			{
				$this->trackError(
					$error->getMessage(),
				);
			}

			return CBPActivityExecutionStatus::Closed;
		}

		$newDocumentEntityTypeId = (int)$sendOperationResult->newDocument->entityTypeId;
		if ($newDocumentEntityTypeId === \CCrmOwnerType::Undefined)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_INVALID_SMART_DOCUMENT_ENTITY_TYPE'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$newDocumentEntityId = (int)$sendOperationResult->newDocument->entityId;
		if ($newDocumentEntityId < 1)
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_INVALID_SMART_DOCUMENT_ENTITY_ID'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$result = Sign\Integration\CRM\Entity::updateEntityResponsible(
			$newDocumentEntityTypeId,
			$newDocumentEntityId,
			$responsibleUserId
			);
		if (!$result->isSuccess())
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_RESPONSIBLE_FOR_SMART_DOCUMENT_NOT_SET'),
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$addCrmActivityResult = $this->addCrmActivity($sendOperationResult->newDocument);

		if (!$addCrmActivityResult->isSuccess())
		{
			$this->trackError(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_UNABLE_TO_CREATE_DOC_ACTIVITY'),
			);

			foreach ($addCrmActivityResult->getErrors() as $error)
			{
				$this->trackError(
					$error->getMessage(),
				);
			}

			return CBPActivityExecutionStatus::Closed;
		}

		if ($this->isSubscriptionEnabled() === false)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->createdDocumentId = (int)$sendOperationResult->newDocument->id;

		if ($this->createdDocumentId < 1)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->Subscribe($this);
		$this->isInEventActivityMode = false;

		return CBPActivityExecutionStatus::Executing;
	}

	/**
	 * @return string[]|int[]
	 */
	private function getValueList(array $values, array $docId): array
	{
		$result = [];
		foreach ($values as $value)
		{
			if ($value === null)
			{
				continue;
			}

			$currentValue = [$value];
			$roleName = $this->getRoleName($currentValue);
			if ($roleName)
			{
				$result[] = $roleName;

				continue;
			}

			$userId = CBPHelper::extractFirstUser($currentValue, $docId);
			if ($userId === null)
			{
				continue;
			}

			$result[] = $userId;
		}

		return $result;
	}

	private function getRoleName(?array $property): ?string
	{
		$value = $property[0] ?? '';

		if (self::getStructureNodeService()->getRoleIdByName($value) !== null)
		{
			return $value;
		}

		return null;
	}

	public static function validateProperties($properties = [], CBPWorkflowTemplateUser $user = null): array
    {
		$errors = [];
		if (empty($properties[self::PARAM_RESPONSIBLE]))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => self::PARAM_RESPONSIBLE,
				"message" => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_RESPONSIBLE_NOT_SET'),
			];
		}
		if (empty($properties[self::PARAM_EMPLOYEE]))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => self::PARAM_EMPLOYEE,
				"message" => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_EMPLOYEE_NOT_SET'),
			];
		}

		if (empty($properties[self::PARAM_TEMPLATE_ID]))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => self::PARAM_TEMPLATE_ID,
				"message" => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_EMPTY_TEMPLATE_ID'),
			];
		}

		if (count($properties[self::PARAM_REVIEWER] ?? []) > Sign\Service\Sign\MemberService::MAX_REVIEWERS_COUNT)
		{
			$errors[] = [
				"code" => "MaxReviewerLimitExceeded",
				"parameter" => self::PARAM_TEMPLATE_ID,
				"message" => Loc::getMessage(
					'SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_MAX_REVIEWER_LIMIT_EXCEEDED',
					['#LIMIT#' => Sign\Service\Sign\MemberService::MAX_REVIEWERS_COUNT],
				),
			];
		}

		return array_merge($errors, parent::validateProperties($properties, $user));
	}

	public static function getPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = "",
		$popupWindow = null,
		$siteId = '',
	): PropertiesDialog|string
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		}

		return (new PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]))
			->setMap(static::getPropertiesMap($documentType))
		;
	}

	/**
	 * @return array<string,array{title: string, document_uid: string}>
	 */
	protected static function getTemplateSelectOptions(): array
	{
		$container = Container::instance();
		$templates = $container->getDocumentTemplateRepository()->getCompletedAndVisibleCompanyTemplateList();
		$documents = $container->getDocumentRepository()
			->listByTemplateIds($templates->getIdsWithoutNull())
			->sortByTemplateIdsDesc()
		;
		$result = [];

		foreach ($documents as $document)
		{
			$template = $templates->findById($document->templateId);
			if ($template === null)
			{
				continue;
			}

			$result[$template->uid] = [
				'title' => $template->title,
				'document_uid' => $document->uid,
				'responsibleSelectorValue' => self::getSelectorCurrentValue($document, Role::ASSIGNEE),
				'assigneeSelectorValue' => self::getSelectorCurrentValue($document, Role::ASSIGNEE),
				'reviewerSelectorValue' => self::getSelectorCurrentValues($document, Role::REVIEWER),
				'editorSelectorValue' => self::getSelectorCurrentValue($document, Role::EDITOR),
			];
		}

		return $result;
	}

	private static function getSelectorCurrentValue(Item\Document $document, string $role): ?string
	{
		if ($document->id === null || !in_array($role, Role::getAll(), true))
		{
			return null;
		}

		$container = Container::instance();
		$memberService = $container->getMemberService();

		$member = $memberService->getByDocumentIdWithRole($document->id, $role);
		if ($member === null)
		{
			return null;
		}

		return $member->entityType === Sign\Type\Member\EntityType::ROLE
			? self::getSignRoleNameForMember($member, $document)
			: (string)$memberService->getUserIdForMember($member);
	}

	/**
	 * @return string[]|int[]
	 */
	private static function getSelectorCurrentValues(Item\Document $document, string $role): array
	{
		if ($document->id === null)
		{
			return [];
		}

		if (!in_array($role, Role::getAll(), true))
		{
			return [];
		}

		$container = Container::instance();
		$memberService = $container->getMemberService();

		$result = [];
		$members = $memberService->listByDocumentIdWithRole($document, $role);
		foreach ($members as $member)
		{
			if ($member === null)
			{
				continue;
			}

			$resultValue = $member->entityType === Sign\Type\Member\EntityType::ROLE
				? self::getSignRoleNameForMember($member, $document)
				: (string)$memberService->getUserIdForMember($member);

			if ($resultValue === null)
			{
				continue;
			}

			$result[] = $resultValue;
		}

		return $result;
	}

	private static function getSignRoleNameForMember(Item\Member $member, Item\Document $document): ?string
	{
		$roleId = (int)($member->role === Role::ASSIGNEE ? $document->representativeId : $member->entityId);

		return self::getStructureNodeService()->getRoleNameById($roleId);
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			self::PARAM_TEMPLATE_ID => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_TEMPLATE_ID'),
				'FieldName' => self::PARAM_TEMPLATE_ID,
				'Type' => FieldType::SELECT,
				'Options' => [], // gets content with AJAX
				'Required' => true,
			],
			self::PARAM_RESPONSIBLE => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_RESPONSIBLE'),
				'FieldName' => self::PARAM_RESPONSIBLE,
				'Type' => FieldType::USER,
				'Required' => true,
			],
			self::PARAM_EMPLOYEE => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_EMPLOYEE'),
				'FieldName' => self::PARAM_EMPLOYEE,
				'Type' => FieldType::USER,
				'Required' => true,
				'Multiple' => true,
			],
			self::PARAM_REPRESENTATIVE => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_REPRESENTATIVE'),
				'FieldName' => self::PARAM_REPRESENTATIVE,
				'Type' => FieldType::USER,
				'Required' => false,
				'Settings' => self::getSignRoleFields(),
			],
			self::PARAM_REVIEWER => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_REVIEWER'),
				'FieldName' => self::PARAM_REVIEWER,
				'Type' => FieldType::USER,
				'Required' => false,
				'Settings' => self::getSignRoleFields(),
				'Multiple' => true,
			],
			self::PARAM_EDITOR => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_EDITOR'),
				'FieldName' => self::PARAM_EDITOR,
				'Type' => FieldType::USER,
				'Required' => false,
				'Settings' => self::getSignRoleFields(),
			],
			self::PARAM_IS_SUBSCRIPTION_ENABLED => [
				'Name' => Loc::getMessage('SIGN_ACTIVITIES_IS_SUBSCRIPTION_ENABLED'),
				'FieldName' => self::PARAM_IS_SUBSCRIPTION_ENABLED,
				'Multiple' => false,
				'Type' => FieldType::BOOL,
				'Required' => false,
			],
		];
	}

	private static function getSignRoleFields(): array
	{
		$result = [
			'additionalFields' => [],
		];
		foreach (self::getStructureNodeService()->getRoleList() as $roleName => $roleId)
		{
			$result['additionalFields'][] = [
				'id' => $roleName,
				'name' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ROBOT_ROLE_' . $roleName . '_TITLE') ?? '',
				'entityId' => $roleId,
				'entityType' => 'roles',
			];
		}

		return $result;
	}

	public static function getPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$arErrors,
	): bool
    {
		$arErrors = [];
		$arProperties = [
			self::PARAM_RESPONSIBLE => self::getPropertyValue(
				self::PARAM_RESPONSIBLE,
				$arCurrentValues,
				$documentType,
				$arErrors,
			),
			self::PARAM_EMPLOYEE => CBPHelper::usersStringToArray(
				$arCurrentValues[self::PARAM_EMPLOYEE] ?? '',
				$documentType,
				$arErrors,
			),
			self::PARAM_REPRESENTATIVE => self::getPropertyValue(
				self::PARAM_REPRESENTATIVE,
				$arCurrentValues,
				$documentType,
				$arErrors,
			),
			self::PARAM_REVIEWER => self::getPropertyValue(
				self::PARAM_REVIEWER,
				$arCurrentValues,
				$documentType,
				$arErrors,
			),
			self::PARAM_EDITOR => self::getPropertyValue(
				self::PARAM_EDITOR,
				$arCurrentValues,
				$documentType,
				$arErrors,
			),
			self::PARAM_TEMPLATE_ID => $arCurrentValues[self::PARAM_TEMPLATE_ID] ?? '',
			self::PARAM_IS_SUBSCRIPTION_ENABLED => in_array(
				$arCurrentValues[self::PARAM_IS_SUBSCRIPTION_ENABLED] ?? null,
				['Y', 'N'],
				true
			) ? $arCurrentValues[self::PARAM_IS_SUBSCRIPTION_ENABLED] : '',
		];

		$arErrors = self::validateProperties(
			$arProperties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser),
		);

		if ($arErrors)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
			$arWorkflowTemplate, $activityName,
		);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private static function getPropertyValue(
		string $propertyName,
		array $arCurrentValues,
		array $documentType,
		array &$arErrors
	): array
	{
		$result = [];
		$selectedValues = explode(',', $arCurrentValues[$propertyName] ?? '');
		foreach ($selectedValues as $selectedValue)
		{
			if (self::getStructureNodeService()->getRoleIdByName($selectedValue) !== null)
			{
				$result[] = $selectedValue;

				continue;
			}

			$userArray = CBPHelper::usersStringToArray(
				$selectedValue,
				$documentType,
				$arErrors,
			);
			$userValue = $userArray[0] ?? null;
			if ($userValue === null)
			{
				continue;
			}

			$result[] = $userValue;
		}

		return $result;
	}

	public static function getAjaxResponse(): array
	{
		if (!static::isAvailable())
		{
			return [];
		}

		return self::getTemplateSelectOptions();
	}

	public function subscribe(IBPActivityExternalEventListener $eventHandler): void
	{
		if ($this->createdDocumentId < 1)
		{
			return;
		}

		$this->isInEventActivityMode = true;
		$schedulerService = $this->workflow->GetService('SchedulerService');
		foreach (self::SIGN_B2E_EVENT_TYPE_LIST as $eventType)
		{
			$schedulerService->SubscribeOnEvent(
				$this->workflow->GetInstanceId(),
				$this->name,
				self::EVENT_MODULE_NAME,
				$eventType,
				$this->createdDocumentId,
			);
		}
		$this->workflow->AddEventHandler($this->name, $eventHandler);
		$this->WriteToTrackingService(Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_WAITING_FOR_SIGN'));
	}

	public function unsubscribe(IBPActivityExternalEventListener $eventHandler): void
	{
		if ($this->createdDocumentId < 1)
		{
			return;
		}

		$schedulerService = $this->workflow->GetService("SchedulerService");
		foreach (self::SIGN_B2E_EVENT_TYPE_LIST as $eventType)
		{
			$schedulerService->UnSubscribeOnEvent(
				$this->workflow->GetInstanceId(),
				self::EVENT_MODULE_NAME,
				$eventType,
				$this->createdDocumentId,
			);
		}
		$this->workflow->removeEventHandler($this->name, $eventHandler);
	}

	public function onExternalEvent($arEventParameters = array()): bool
	{
		if ($this->createdDocumentId < 1)
		{
			return false;
		}

		if ($this->executionStatus === CBPActivityExecutionStatus::Closed)
		{
			return false;
		}

		$eventName = $arEventParameters['eventName'] ?? '';
		if (!in_array($eventName, self::SIGN_B2E_EVENT_TYPE_LIST, true))
		{
			$this->writeToTrackingService(Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_INVALID_EVENT_TYPE'));

			return false;
		}

		$this->{self::RETURN_PARAM_DOCUMENT_ID} = $this->createdDocumentId;
		$this->{self::RETURN_PARAM_DOCUMENT_STATUS} = $eventName === EventData::TYPE_ON_DONE
			? self::RETURN_STATUS_SUCCESS : self::RETURN_STATUS_FAIL;

		$this->unsubscribe($this);
		$this->workflow->closeActivity($this);

		return true;
	}

	private function isSubscriptionEnabled(): bool
	{
		return ($this->{self::PARAM_IS_SUBSCRIPTION_ENABLED} ?? 'N') === 'Y';
	}

	private static function getStructureNodeService(): Sign\Service\Integration\HumanResources\StructureNodeService
	{
		return Container::instance()->getHumanResourcesStructureNodeService();
	}

	private function addCrmActivity(Document $document): Main\Result
	{
		$result = new Main\Result();
		if ($this->smartDocumentEntityType === \CCrmOwnerType::Undefined)
		{
			return $result->addError(new Main\Error('Invalid smart document entity type'));
		}

		if ($this->smartDocumentEntityId < 1)
		{
			return $result->addError(new Main\Error('Invalid smart document entity id'));
		}

		$signDocumentEntityId = (int)$document->entityId;

		if ($signDocumentEntityId < 1)
		{
			return $result->addError(new Main\Error('Invalid sign document entity id'));
		}

		$activityId = $this->getCrmActivityId($signDocumentEntityId);

		if ($activityId < 1)
		{
			return $result->addError(new Main\Error('Crm activity not found'));
		}

		$bindings = \CCrmActivity::GetBindings($activityId);
		$bindings[] = [
			'OWNER_ID' => $this->smartDocumentEntityId,
    		'OWNER_TYPE_ID' => $this->smartDocumentEntityType,
		];

		\CCrmActivity::SaveBindings($activityId, $bindings, false, false);

		return $result;
	}

	private function getCrmActivityId(int $entityId): int
	{
		if ($entityId < 1)
		{
			return 0;
		}

		$result = ActivityTable::query()
			->where('BINDINGS.OWNER_ID', $entityId)
			->where('BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::SmartB2eDocument)
			->where('PROVIDER_ID', \Bitrix\Crm\Activity\Provider\SignB2eDocument::getId())
			->setSelect(['ID'])
			->setLimit(1)
			->fetch()
		;

		return (int)($result['ID'] ?? null);
	}

	private function updateSmartDocumentEntityIdAndType(): Main\Result
	{
		$result = new Main\Result();
		$activityDocument = $this->GetDocumentId();
		$entity = explode('_', ($activityDocument[2] ?? ''));
		$type = sprintf(
			'%s_%s',
			$entity[0] ?? 0,
			$entity[1] ?? 0,
		);
		$this->smartDocumentEntityType = \CCrmOwnerType::ResolveID($type);
		$this->smartDocumentEntityId = (int)($entity[2] ?? 0);

		if ($this->smartDocumentEntityType === \CCrmOwnerType::Undefined)
		{
			return $result->addError(new Main\Error(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_INVALID_SMART_DOCUMENT_ENTITY_TYPE'),
			));
		}

		if ($this->smartDocumentEntityId < 1)
		{
			return $result->addError(new Main\Error(
				Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ERROR_INVALID_SMART_DOCUMENT_ENTITY_ID'),
			));
		}

		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->smartDocumentEntityType);
		$this->smartDocumentCreatedById = (int)$factory?->getItem($this->smartDocumentEntityId)?->getCreatedBy();

		return $result;
	}
}
