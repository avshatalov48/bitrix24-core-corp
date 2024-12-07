<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign;
use Bitrix\Crm;
use Bitrix\BizProc;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Model\DocumentGeneratorBlankTemplate;
use Bitrix\Sign\Model\SignDocumentGeneratorBlankTable;

class CBPSignDocumentActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"initiatorName" => null,
			"blankId" => null,
		];
	}

	/*
	 * Check modules
	 *
	 * */
	public static function isAvailable()
	{
		if (!Main\Loader::includeModule("sign"))
		{
			return false;
		}

		if (!Main\Loader::includeModule("crm"))
		{
			return false;
		}

		if (!Sign\Config\Storage::instance()->isAvailable())
		{
			return false;
		}

		return true;
	}

	/*
	 * On execute
	 *
	 * */
	public function Execute()
	{
		if (!static::isAvailable())
		{
			return CBPActivityExecutionStatus::Closed;
		}

		/**
		 * $activityDocument[0] - crm
		 * $activityDocument[1] - CCrmDocumentDeal
		 * $activityDocument[2] - LEAD_123
		 */
		$activityDocument = $this->GetDocumentId();
		$entity = explode('_', ($activityDocument[2] ?? ''));
		$entityTypeId = \CCrmOwnerType::ResolveID($entity[0] ?? 0);
		$entityId = (int)($entity[1] ?? 0);

		$isError = false;
		if (!$entityTypeId || !$entityId)
		{
			$isError = true;
			$this->addSigningErrorMessage('Wrong entity.');
		}

		if (!$isError)
		{
			$this->launchSigning($entityTypeId, $entityId);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function launchSigning(int $entityTypeId, int $entityId)
	{
		if ($entityTypeId !== \CCrmOwnerType::Deal)
		{
			return;
		}

		if (!$this->initiatorName)
		{
			$this->addSigningErrorMessage(Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_EMPTY_INITIATOR_NAME'));

			return;
		}
		if (!$this->blankId)
		{
			$this->addSigningErrorMessage(Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_EMPTY_BLANK_ID'));

			return;
		}

		// $entityTypeId - this is CCrmOwnerType::Deal
		// $entityId - this is dealId

		$blank = Sign\Blank::getById((int)$this->blankId);
		if (!$blank)
		{
			$this->addSigningErrorMessage(Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_BLANK_NOT_FOUND'));

			return;
		}
		$this->logDebug($this->initiatorName, $blank->getTitle());

		$smartDocResult = Main\DI\ServiceLocator::getInstance()
			->get('crm.integration.sign')
			->convertDealToSmartDocument($entityId)
		;
		$smartDocId = $smartDocResult->getData()['SMART_DOCUMENT'] ?? null;
		if (
			!$smartDocResult->isSuccess()
			|| !$smartDocResult->isConversionFinished()
			|| !$smartDocId
		)
		{
			$this->addSigningErrorMessage($smartDocResult->getErrorMessages()[0]);
			return;
		}
		if (Storage::instance()->isNewSignEnabled())
		{
			$documentService = Sign\Service\Container::instance()->getDocumentService();
			$createDocumentResult = $documentService->register(
				blankId: $blank->getId(),
				entityId: $smartDocId,
				entityType: 'SMART',
			);
			$document = $createDocumentResult->getData()['document'] ?? null;

			if (!$createDocumentResult->isSuccess() && $document)
			{
				$this->addSigningErrorMessage($createDocumentResult->getErrorMessages()[0]);
				return;
			}

			$result = $documentService->upload($document->uid);
			if (!$result->isSuccess())
			{
				$this->addSigningErrorMessage($result->getErrorMessages()[0]);
				return;
			}
			$documentService->modifyInitiator($document->uid, $this->initiatorName);
			Sign\Service\Container::instance()->getCrmSignDocumentService()->configureMembers($document);

			$members = Sign\Service\Container::instance()->getMemberRepository()->listByDocumentId($document->id);

			foreach ($members as $member)
			{
				if (!$member->channelValue || !$member->channelType)
				{
					$this->addSigningErrorMessage(Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_DOCUMENT_MEMBERS_ASSIGN_ERROR'));
					return;
				}
			}

			Sign\Service\Container::instance()->getDocumentAgentService()->addConfigureAndStartAgent($document->uid);
			return;
		}

		$doc = $blank->createDocument('SMART', $smartDocId);
		if (!$doc)
		{
			$this->addSigningErrorMessage(Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_DOCUMENT_ERROR_WHILE_CREATING'));

			return;
		}

		$doc->register(true);
		$assignResult = $doc->assignMembers();
		if (!$assignResult->isSuccess())
		{
			$this->addSigningErrorMessage(Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_DOCUMENT_MEMBERS_ASSIGN_ERROR'));
			return;
		}
		$doc->setMeta([
			'initiatorName' => $this->initiatorName,
		]);
		$doc->send();
	}

	private function addSigningErrorMessage(string $message): void
	{
		$this->WriteToTrackingService(
			$message,
			0,
			\CBPTrackingType::Error
		);
	}

	public static function ValidateProperties(
		$properties = [],
		CBPWorkflowTemplateUser $user = null
	)
	{
		$errors = [];
		if (empty($properties["initiatorName"]))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => "initiatorName",
				"message" => Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_EMPTY_INITIATOR_NAME'),
			];
		}

		if (
			!empty($arTestProperties["blankId"])
			&& !is_numeric($arTestProperties["blankId"])
		)
		{
			$errors[] = [
				"code" => "NotNumber",
				"parameter" => "blankId",
				"message" => Loc::getMessage('SIGN_DOCUMENT_ACTIVITY_ERROR_EMPTY_BLANK_ID'),
			];
		}

		return array_merge($errors, parent::ValidateProperties($properties, $user));
	}

	/*
	 * On show dialog
	 *
	 * */
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
		if (!static::isAvailable())
		{
			return '';
		}

		$dialog = new Bizproc\Activity\PropertiesDialog(__FILE__, [
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
		return [
			'initiatorName' => [
				'Name' => GetMessage('SIGN_ACTIVITIES_SIGN_DOCUMENT_INITIATOR_NAME'),
				'FieldName' => 'initiatorName',
				'Type' => 'string',
				'Required' => true,
			],
			'blankId' => [
				'Name' => GetMessage('SIGN_ACTIVITIES_SIGN_DOCUMENT_BLANK_ID_1'),
				'FieldName' => 'blankId',
				'Type' => 'string',
				'Required' => false,
			],
		];
	}

	/*
	 * On save
	 * */
	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$arErrors
	)
	{
		$arErrors = [];

		$initiatorName = $arCurrentValues['initiatorName'];
		$blankId = $arCurrentValues['blankId'];

		$arProperties = [
			'initiatorName' => $initiatorName,
			'blankId' => $blankId,
		];

		$arErrors = self::ValidateProperties(
			$arProperties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if ($arErrors)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private function logDebug($initiatorName, $blankId)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$map = $this->getDebugInfo([
			'initiatorName' => $initiatorName,
			'blankId' => $blankId,
		]);

		$this->writeDebugInfo([
			'initiatorName' => $map['initiatorName'],
			'accountId' => $map['blankId'],
		]);
	}
}
