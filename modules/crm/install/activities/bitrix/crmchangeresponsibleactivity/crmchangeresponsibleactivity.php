<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

/**
 * Class CBPCrmChangeResponsibleActivity
 * @property-read string Responsible
 * @property-read string ModifiedBy
 * @property-read string GetterType
 * @property-read string SkipAbsent
 */
class CBPCrmChangeResponsibleActivity extends CBPActivity
{
	private const GETTER_TYPE_RANDOM = 'r';
	private const GETTER_TYPE_FIRST = 'f';
	private const GETTER_TYPE_SEQUENCE = 's';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'Responsible' => null,
			'ModifiedBy' => null,
			'GetterType' => self::GETTER_TYPE_RANDOM,
			'SkipAbsent' => 'N',
		];
	}

	public function Execute()
	{
		if ($this->Responsible == null || !CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$responsibleFieldName = $this->getResponsibleFieldName($documentId);
		$currentResponsibleId = (int)$this->getCurrentResponsibleId($responsibleFieldName);

		$newResponsibleId = $this->getTargetResponsibleId($currentResponsibleId);
		$this->writeDebugInfo($this->getDebugInfo([
			'NewResponsibleId' => isset($newResponsibleId) ? "user_{$newResponsibleId}" : null,
		]));

		if ($newResponsibleId)
		{
			$ds = $this->workflow->GetRuntime()->getDocumentService();
			$ds->UpdateDocument(
				$documentId,
				[$responsibleFieldName => 'user_' . $newResponsibleId],
				$this->ModifiedBy
			);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getCurrentResponsibleId($responsibleFieldName): ?int
	{
		$ds = $this->workflow->GetRuntime()->getDocumentService();
		$documentId = $this->GetDocumentId();
		$document = $ds->GetDocument($documentId);

		if (isset($document[$responsibleFieldName]))
		{
			return CBPHelper::ExtractUsers($document[$responsibleFieldName], $documentId, true);
		}

		return null;
	}

	private function getTargetResponsibleId($current): ?int
	{
		$documentId = $this->GetDocumentId();
		$target = CBPHelper::ExtractUsers($this->Responsible, $documentId);

		if (!$target)
		{
			return null;
		}

		$getterType = $this->GetterType;
		$skipAbsent = CBPHelper::getBool($this->SkipAbsent) && Main\Loader::includeModule('intranet');

		$canUseAbsence = self::canUseAbsence();
		if (!$canUseAbsence)
		{
			$skipAbsent = false;
		}

		if ($getterType === self::GETTER_TYPE_FIRST)
		{
			$user = $this->getFirstUser($target, $skipAbsent);
		}
		elseif ($getterType === self::GETTER_TYPE_SEQUENCE)
		{
			$user = $this->getNextUser($target, $skipAbsent);
		}
		else // default self::GETTER_TYPE_RANDOM
		{
			$user = $this->getRandomUser($current, $target, $skipAbsent);
		}

		return $user !== $current ? $user : null;
	}

	private function getFirstUser(array $target, bool $skipAbsent): ?int
	{
		foreach ($target as $user)
		{
			if ($skipAbsent && \CIntranetUtils::IsUserAbsent($user))
			{
				continue;
			}

			return $user;
		}

		return null;
	}

	private function getRandomUser($current, array $target, bool $skipAbsent): ?int
	{
		$searchKey = array_search($current, $target);
		if ($searchKey !== false)
		{
			unset($target[$searchKey]);
		}
		shuffle($target);

		return $this->getFirstUser($target, $skipAbsent);
	}

	private function getNextUser(array $target, bool $skipAbsent): ?int
	{
		$lastUserId = $this->getStorage()->getValue('lastUserId');

		if ($lastUserId && count($target) > 1)
		{
			$searchKey = array_search($lastUserId, $target);
			if ($searchKey !== false)
			{
				$target = array_merge(
					array_slice($target, $searchKey + 1),
					array_slice($target, 0, $searchKey),
				);
			}
		}

		$nextUserId = $this->getFirstUser($target, $skipAbsent);
		if ($nextUserId)
		{
			$this->getStorage()->setValue('lastUserId', $nextUserId);
		}

		return $nextUserId;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		if (empty($arTestProperties["Responsible"]))
		{
			$errors[] = ["code" => "NotExist", "parameter" => "Responsible", "message" => GetMessage("CRM_CHANGE_RESPONSIBLE_EMPTY_PROP")];
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
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

		$dialog->setMap(static::getPropertiesDialogMap($documentType));
		$dialog->setRuntimeData([
			'CanUseAbsence' => self::canUseAbsence(),
		]);

		return $dialog;
	}

	protected static function getPropertiesDialogMap(array $documentType): array
	{
		$map = [
			'Responsible' => [
				'Name' => GetMessage('CRM_CHANGE_RESPONSIBLE_NEW'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Required' => true,
				'Multiple' => true,
			],
			'ModifiedBy' => [
				'Name' => GetMessage('CRM_CHANGE_RESPONSIBLE_MODIFIED_BY'),
				'FieldName' => 'modified_by',
				'Type' => 'user',
				'Required' => true,
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType),
			],
		];

		if (method_exists(static::class, 'getStorage'))
		{
			$map['GetterType'] = [
				'Name' => GetMessage('CRM_CHANGE_RESPONSIBLE_GETTER_TYPE'),
				'FieldName' => 'getter_type',
				'Type' => 'select',
				'Required' => true,
				'Options' => [
					self::GETTER_TYPE_RANDOM => GetMessage('CRM_CHANGE_RESPONSIBLE_GETTER_TYPE_R'),
					self::GETTER_TYPE_FIRST => GetMessage('CRM_CHANGE_RESPONSIBLE_GETTER_TYPE_F'),
					self::GETTER_TYPE_SEQUENCE => GetMessage('CRM_CHANGE_RESPONSIBLE_GETTER_TYPE_S'),
				],
				'Default' => self::GETTER_TYPE_RANDOM,
				'Settings' => [
					'ShowEmptyValue' => false,
				],
			];

			if (Main\ModuleManager::isModuleInstalled('intranet'))
			{
				$map['SkipAbsent'] = [
					'Name' => GetMessage('CRM_CHANGE_RESPONSIBLE_SKIP_ABSENT'),
					'FieldName' => 'skip_absent',
					'Type' => 'bool',
					'Default' => 'N',
					'Required' => true,
					'Getter' => static::getSkipAbsentPropertyGetter(),
				];
			}
		}

		return $map;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$map = static::getPropertiesDialogMap($documentType);
		unset($map['ModifiedBy']);

		$map['NewResponsibleId'] = [
			'Name' => Main\Localization\Loc::getMessage('CRM_CHANGE_NEW_RESPONSIBLE_ID'),
			'FieldName' => 'new_responsible_id',
			'Type' => \Bitrix\Bizproc\FieldType::USER,
		];

		return $map;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = [
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $errors),
			'ModifiedBy' => CBPHelper::UsersStringToArray($arCurrentValues["modified_by"], $documentType, $errors),
			'GetterType' => $arCurrentValues["getter_type"],
			'SkipAbsent' => $arCurrentValues["skip_absent"],
		];

		if (empty($properties['GetterType']) && static::isExpression($arCurrentValues["getter_type_text"]))
		{
			$properties['GetterType'] = $arCurrentValues["getter_type_text"];
		}

		$canUseAbsence = self::canUseAbsence();
		if (!$canUseAbsence)
		{
			$properties['SkipAbsent'] = 'N';
		}

		if (empty($properties['SkipAbsent']) && static::isExpression($arCurrentValues["skip_absent_text"]))
		{
			$properties['SkipAbsent'] = $arCurrentValues["skip_absent_text"];
		}

		if (count($errors) > 0)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private function getResponsibleFieldName($documentId)
	{
		if (mb_strpos($documentId[2], 'ORDER_') === 0 || mb_strpos($documentId[2], 'INVOICE_') === 0)
		{
			return 'RESPONSIBLE_ID';
		}

		return 'ASSIGNED_BY_ID';
	}

	private static function getSkipAbsentPropertyGetter()
	{
		return function($dialog, $property, $arCurrentActivity, $compatible = false)
		{
			$canUseAbsence = self::canUseAbsence();
			if (!$canUseAbsence)
			{
				return 'N';
			}

			return $arCurrentActivity['Properties']['SkipAbsent'];
		};
	}

	private static function canUseAbsence(): bool
	{
		return (
			(CModule::IncludeModule('bitrix24') === false)
			|| (\Bitrix\Bitrix24\Feature::isFeatureEnabled('absence') === true)
		);
	}
}
