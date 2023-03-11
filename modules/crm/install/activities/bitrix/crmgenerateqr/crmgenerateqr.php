<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm\Automation;

class CBPCrmGenerateQr extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'QrTitle' => '',
			'Description' => '',
			"CompleteActionLabel" => '',

			//return
			'PageLink' => '',
			'PageLinkBb' => '',
			'PageLinkHtml' => '',
			'QrLink' => '',
			'QrLinkBb' => '',
			'QrLinkHtml' => '',
			'QrImgHtml' => '',
		];

		$this->setPropertiesTypes([
			'PageLink' => ['Type' => 'string'],
			'PageLinkBb' => ['Type' => 'string'],
			'PageLinkHtml' => [
				'Type' => 'string',
				'ValueContentType' => 'html',
			],
			'QrLink' => ['Type' => 'string'],
			'QrLinkBB' => ['Type' => 'string'],
			'QrLinkHtml' => [
				'Type' => 'string',
				'ValueContentType' => 'html',
			],
			'QrImgHtml' => [
				'Type' => 'string',
				'ValueContentType' => 'html',
			],
		]);
	}

	public function execute()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo());
		}

		$qrId = $this->createQr();
		$this->setLinks($qrId);

		return CBPActivityExecutionStatus::Closed;
	}

	private function createQr()
	{
		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		$result = Automation\QR\QrTable::add([
			'OWNER_ID' => $this->getName(),
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId,

			'DESCRIPTION' => (string)$this->Description,
			'COMPLETE_ACTION_LABEL' => (string)$this->CompleteActionLabel,
		]);

		return $result->getId();
	}

	private function setLinks($qrId)
	{
		$hostUrl = Main\Engine\UrlManager::getInstance()->getHostUrl();
		$path = '/pub/crm/qr/';
		$page = $hostUrl . $path . '?' . $qrId;
		$shortPage = $hostUrl . \CBXShortUri::getShortUri($path . '?' . $qrId);
		$qr = $page . '&' . 'code=y';
		$shortQr = $hostUrl . \CBXShortUri::getShortUri($path . '?' . $qrId . '&' . 'code=y');
		$img = $page . '&' . 'img=y';

		$this->PageLink = $shortPage;
		$this->PageLinkBb = sprintf('[url=%s]%s[/url]', $shortPage, $shortPage);
		$this->PageLinkHtml = sprintf('<a href="%s">%s</a>', $shortPage, $shortPage);

		$this->QrLink = $shortQr;
		$this->QrLinkBb = sprintf('[url=%s]%s[/url]', $shortQr, $shortQr);;
		$this->QrLinkHtml = sprintf('<a href="%s">%s</a>', $shortQr, $shortQr);

		$this->QrImgHtml = sprintf('<img src="%s" width="300" height="300">', $img);

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(
				[
					'PageLink' => $shortPage,
					'QrLink' => $shortQr,
				],
				[
					'PageLink' => [
						'Name' => GetMessage('CRMBPGQR_RETURN_PAGE_LINK'),
						'Type' => 'string',
						'TrackType' => CBPTrackingType::DebugLink,
					],
					'QrLink' => [
						'Name' => GetMessage('CRMBPGQR_RETURN_QR_LINK'),
						'Type' => 'string',
						'TrackType' => CBPTrackingType::DebugLink,
					],
				]
			));
		}
	}

	protected function reInitialize()
	{
		parent::reInitialize();
		$this->PageLink = '';
		$this->PageLinkB = '';
		$this->PageLinkHtml = '';
		$this->QrLink = '';
		$this->QrLinkBb = '';
		$this->QrLinkHtml = '';
		$this->QrImgHtml = '';
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if (empty($arTestProperties["QrTitle"]))
		{
			$arErrors[] = [
				"code" => "emptyDescription",
				"message" => GetMessage("CRMBPGQR_EMPTY_QRTITLE"),
			];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
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

		$dialog->setMap(
			static::getPropertiesMap(
				$documentType,
				['isRobot' => $formName === 'bizproc_automation_robot_dialog']
			)
		);

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$defaultDescription = GetMessage(
			'CRMBPGQR_DESCRIPTION_DEFAULT',
			['#CONTACT_NAME#' => self::getContactNameExpression($documentType)]
		);
		if (!empty($context['isRobot']))
		{
			$defaultDescription = Automation\Helper::convertExpressions($defaultDescription, $documentType);
		}

		return [
			'QrTitle' => [
				'Name' => GetMessage('CRMBPGQR_QRTITLE_NAME'),
				'FieldName' => 'qr_title',
				'Type' => 'string',
				'Required' => true,
				'Default' => GetMessage(
					'CRMBPGQR_QRTITLE_DEFAULT',
					[
						'#DM#' => FormatDate(
							\Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getDayMonthFormat()
						),
					]
				),
			],
			'Description' => [
				'Name' => GetMessage('CRMBPGQR_DESCRIPTION_NAME'),
				'FieldName' => 'description',
				'Type' => 'text',
				'Default' => $defaultDescription,
			],
			'CompleteActionLabel' => [
				'Name' => GetMessage('CRMBPGQR_COMPLETE_ACTION_LABEL_NAME'),
				'FieldName' => 'complete_action_label',
				'Type' => 'string',
				'Default' => GetMessage('CRMBPGQR_COMPLETE_ACTION_LABEL_DEFAULT'),
			],
		];
	}

	public static function GetPropertiesDialogValues(
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

		foreach (static::getPropertiesMap($documentType) as $id => $property)
		{
			$properties[$id] = $arCurrentValues[$property['FieldName'] ?? ''];
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
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private static function getContactNameExpression(array $documentType): string
	{
		$fields = CBPRuntime::getRuntime(true)
			->getService('DocumentService')
			->getDocumentFields($documentType)
		;

		if (isset($fields['CONTACT.NAME']))
		{
			return '{=Document:CONTACT.NAME}';
		}

		if (isset($fields['NAME']))
		{
			return '{=Document:NAME}';
		}

		if (isset($fields['TITLE']))
		{
			return '{=Document:TITLE}';
		}

		return '#CONTACT_NAME#';
	}
}
