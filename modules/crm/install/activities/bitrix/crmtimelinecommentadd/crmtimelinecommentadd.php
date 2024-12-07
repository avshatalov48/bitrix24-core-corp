<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmTimelineCommentAdd extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'CommentText' => '',
			'CommentUser' => null
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		[$ownerTypeId, $ownerId] = CCrmBizProcHelper::resolveEntityId($documentId);

		$authorId = CBPHelper::ExtractUsers($this->CommentUser, $documentId, true);
		$text = $this->getCommentText();

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(['CommentText' => $text]));
		}

		if (empty($text))
		{
			$this->WriteToTrackingService(GetMessage('BPCTLCA_NO_COMMENT'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$entryID = \Bitrix\Crm\Timeline\CommentEntry::create(
			[
				'TEXT' => $text,
				'AUTHOR_ID' => $authorId ?: 0,
				'BINDINGS' => [['ENTITY_TYPE_ID' => $ownerTypeId, 'ENTITY_ID' => $ownerId]]
			]
		);

		if($entryID <= 0)
		{
			$this->WriteToTrackingService(GetMessage('BPCTLCA_CREATION_ERROR'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$saveData = array(
			'COMMENT' => $text,
			'ENTITY_TYPE_ID' => $ownerTypeId,
			'ENTITY_ID' => $ownerId,
		);

		Bitrix\Crm\Timeline\CommentController::getInstance()->onCreate($entryID, $saveData);

		return CBPActivityExecutionStatus::Closed;
	}

	private function getCommentText(): string
	{
		$text = $this->ParseValue($this->getRawProperty('CommentText'), 'text');
		if (is_array($text))
		{
			$text = implode(', ', \CBPHelper::MakeArrayFlat($text));
		}

		return trim((string)$text);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (!array_key_exists('CommentText', $arTestProperties) || $arTestProperties['CommentText'] == '')
		{
			$errors[] = array('code' => 'NotExist', 'CommentText' => 'MessageText', 'message' => GetMessage('BPCTLCA_EMPTY_COMMENT_TEXT'));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$properties = [
			'CommentText' => $arCurrentValues['comment_text'],
			'CommentUser' => CBPHelper::UsersStringToArray($arCurrentValues["comment_user"], $documentType, $errors),
		];

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
		$currentActivity['Properties'] = $properties;

		return true;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'CommentText' => array(
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('BPCTLCA_COMMENT_TEXT'),
				'Description' => \Bitrix\Main\Localization\Loc::getMessage('BPCTLCA_COMMENT_TEXT'),
				'FieldName' => 'comment_text',
				'Type' => 'text',
				'Required' => true,
			),
			'CommentUser' => [
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('BPCTLCA_COMMENT_USER'),
				'FieldName' => 'comment_user',
				'Type' => 'user',
				'Required' => true,
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType),
			]
		];
	}
}