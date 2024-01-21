<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

class CBPCrmGoToChat extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
		];
	}

	public function execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!\Bitrix\Crm\Integration\ImOpenLines\GoToChat::isActive())
		{
			$this->trackError(Loc::getMessage('BP_CRM_GO_TO_CHAT_NOT_AVAILABLE'));

			return CBPActivityExecutionStatus::Closed;
		}

		[$ownerTypeId, $ownerId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());
		$owner = new \Bitrix\Crm\ItemIdentifier($ownerTypeId, $ownerId);
		$goToChat = new \Bitrix\Crm\Integration\ImOpenLines\GoToChat();

		$channel = \Bitrix\Crm\MessageSender\Channel\ChannelRepository::create($owner)
			->getBestUsableBySender('bitrix24')
		;

		$toListItem = current($channel?->getToList() ?? []);
		/* @var \Bitrix\Crm\MessageSender\Channel\Correspondents\To $toListItem */
		$to = $toListItem ? $toListItem->getAddress()->getId() : 0;

		$result = $goToChat->setOwner($owner)->send('bitrix24', (int)$to);

		if (!$result->isSuccess())
		{
			foreach ($result->getErrorMessages() as $errorMessage)
			{
				$this->trackError($errorMessage);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function getPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
	{
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

	public static function getPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$errors
	)
	{
		$errors = [];
		$properties = [];

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}
}
