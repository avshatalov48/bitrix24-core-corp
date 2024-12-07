<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Automation\ClientCommunications\ClientCommunications;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @property-read $TemplateId
 * @property-read $Placeholders
 */
class CBPCrmSendWhatsAppMessageActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'TemplateId' => null,
			'Placeholders' => [],
		];

		$this->setPropertiesTypes([
			'TemplateId' => [
				'Type' => Bitrix\Bizproc\FieldType::INT,
				'Multiple' => false,
			],
		]);
	}

	public function execute()
	{
		if (!self::includeRequiredModules())
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!self::isWhatsAppTuned() || !self::isWhatsAppTemplateBased())
		{
			return $this->closeWithError(
				Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_NOT_TUNED') ?? ''
			);
		}

		$map = self::getPropertiesMap($this->getDocumentType());

		$templateId = $this->getTemplateId();
		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(
				['TemplateId' => $templateId],
				['TemplateId' => $map['TemplateId']]
			));
		}

		$template = self::getTemplateById($templateId);
		if (empty($template))
		{
			return $this->closeWithError(
				Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_EMPTY_TEMPLATE_ID') ?? ''
			);
		}

		$body = $this->getMessageText($template);
		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(
				['Message' => $body],
				['Message' => $map['Message']]
			));
		}
		if (empty($body))
		{
			return $this->closeWithError(
				Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_EMPTY_MESSAGE') ?? ''
			);
		}

		$from = $this->getMessageFrom();
		if (empty($from))
		{
			return $this->closeWithError(
				Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_EMPTY_FROM') ?? ''
			);
		}

		$to = $this->getMessageTo();
		if (empty($to))
		{
			return $this->closeWithError(
				Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_EMPTY_TO') ?? ''
			);
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->getDocumentId());
		$owner = new \Bitrix\Crm\ItemIdentifier($entityTypeId, $entityId);

		$message = new \Bitrix\Crm\Activity\Provider\Sms\MessageDto([
			'senderId' => \Bitrix\MessageService\Sender\Sms\Ednaru::ID,
			'body' => $body,
			'from' => $from,
			'to' => $to['phone'],
			'template' => $this->getTemplateCode($template, $body),
		]);

		$sender = (new \Bitrix\Crm\Activity\Provider\Sms\Sender($owner, $message));
		$sender->setEntityIdentifier(new \Bitrix\Crm\ItemIdentifier($to['entityTypeId'], $to['entityId']));
		$result = $sender->send(false);

		if (!$result->isSuccess())
		{
			return $this->closeWithError($result->getErrorMessages()[0] ?? '');
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getTemplateId(): int
	{
		$templateId = $this->TemplateId;
		if (is_numeric($templateId))
		{
			return (int)$templateId;
		}

		return 0;
	}

	private function getMessageText(array $template): string
	{
		$content = (string)$template['content'];
		$contentPlaceholders = $template['placeholders']['PREVIEW'] ?? [];
		if (!is_array($contentPlaceholders))
		{
			$contentPlaceholders = [];
		}
		$contentPlaceholders = array_flip($contentPlaceholders);

		$placeholderValues = $this->getRawProperty('Placeholders');
		if (!is_array($placeholderValues))
		{
			$placeholderValues = [];
		}

		$callback = function($matches) use (&$contentPlaceholders, $placeholderValues)
		{
			$placeholder = $matches[0];
			if (array_key_exists($placeholder, $contentPlaceholders))
			{
				// replace only first placeholder
				unset($contentPlaceholders[$placeholder]);

				if (array_key_exists($placeholder, $placeholderValues))
				{
					return $this->parseValue(
						$placeholderValues[$placeholder],
						\Bitrix\Bizproc\FieldType::STRING,
						static function($objectName, $fieldName, $property, $result) {
							if (is_array($result))
							{
								$result = implode(', ', CBPHelper::makeArrayFlat($result));
							}

							if (CBPHelper::isEmptyValue($result))
							{
								$result = ' ';
							}

							return $result;
						}
					);
				}

				return ' ';
			}

			return $placeholder;
		};

		return preg_replace_callback('#\{\{.*?}}#', $callback, $content);
	}

	private function getMessageTo(): ?array
	{
		$documentId = $this->getDocumentId();
		[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($documentId);

		$clientCommunications = new ClientCommunications((int)$entityTypeId, (int)$entityId, CCrmFieldMulti::PHONE);
		$communications = $clientCommunications->getFirstFilled();

		if ($communications)
		{
			return [
				'phone' => (string)($communications[0]['VALUE']),
				'entityTypeId' => $communications[0]['ENTITY_TYPE_ID'],
				'entityId' => $communications[0]['ENTITY_ID'],
			];
		}

		return null;
	}

	private function getMessageFrom(): string
	{
		$sender = self::getEdnaRuSender();
		if ($sender)
		{
			$defaultFrom = $sender->getDefaultFrom();
			if (!empty($defaultFrom))
			{
				return $defaultFrom;
			}
		}

		return '';
	}

	private function getTemplateCode(array $template, string $messageBody): ?string
	{
		if (is_string($template['templateCode']))
		{
			$data = null;
			try
			{
				$data = Json::decode($template['templateCode']);
			}
			catch (ArgumentException)
			{}

			if (is_array($data))
			{
				$data['text'] = $messageBody;

				return Json::encode($data);
			}
		}

		return null;
	}

	public static function getPropertiesDialog(
		$documentType,
		$activityName,
		$workflowTemplate,
		$workflowParameters,
		$workflowVariables,
		$currentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
	{
		if (!self::includeRequiredModules())
		{
			return '';
		}

		if (!is_array($currentValues))
		{
			$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);

			$placeholders = $currentActivity['Properties']['Placeholders'] ?? [];
			if (is_array($placeholders))
			{
				foreach ($placeholders as $placeholderKey => $value)
				{
					$placeholders[$placeholderKey] = \Bitrix\Bizproc\Automation\Helper::unConvertExpressions($value, $documentType);
				}
			}

			$currentValues = [
				'template_id' => $currentActivity['Properties']['TemplateId'] ?? null,
				'placeholders' => $placeholders,
			];
		}

		$sender = self::getEdnaRuSender();

		return (new \Bitrix\Bizproc\Activity\PropertiesDialog(
			__FILE__,
			[
				'documentType' => $documentType,
				'activityName' => $activityName,
				'workflowTemplate' => $workflowTemplate,
				'workflowParameters' => $workflowParameters,
				'workflowVariables' => $workflowVariables,
				'currentValues' => $currentValues,
				'formName' => $formName,
				'siteId' => $siteId,
			]
		))
			->setMap(self::getPropertiesMap($documentType))
			->setRuntimeData([
				'isWhatsAppTuned' => self::isWhatsAppTuned(),
				'manageUrl' => $sender?->getManageUrl(),
				'currentTemplate' => (
					isset($currentValues['template_id']) && is_numeric($currentValues['template_id'])
						? self::getTemplateById((int)$currentValues['template_id'])
						: null
				),
				'currentPlaceholders' => $currentValues['placeholders'] ?? null,
			])
		;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$templateOptions = [];
		$templates = self::getTemplateList();
		foreach ($templates as $template)
		{
			$templateOptions[$template['ORIGINAL_ID']] = $template['TITLE'];
		}

		return [
			'TemplateId' => [
				'Name' => Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_MAP_TEMPLATE_NAME'),
				'FieldName' => 'template_id',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Multiple' => false,
				'Options' => $templateOptions,
			],
			'Message' => [
				'Name' => Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_MAP_EDITOR_NAME'),
				'FieldName' => 'message_text',
				'Type' => \Bitrix\Bizproc\FieldType::TEXT,
				'Required' => true,
				'Multiple' => false,
			],
		];
	}

	protected static function getPropertiesDialogValues(
		$documentType,
		$activityName,
		&$workflowTemplate,
		&$workflowParameters,
		&$workflowVariables,
		$currentValues,
		&$errors
	)
	{
		$errors = [];

		if (!self::includeRequiredModules())
		{
			return false;
		}

		if (!self::isWhatsAppTuned() || !self::isWhatsAppTemplateBased())
		{
			$errors[] = [
				'code' => 'notTuned',
				'message' => Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_NOT_TUNED'),
			];

			return false;
		}

		$documentService = CBPRuntime::getRuntime()->getDocumentService();

		$map = static::getPropertiesMap($documentType);
		$templateId = $documentService->getFieldInputValue(
			$documentType,
			$map['TemplateId'],
			$map['TemplateId']['FieldName'],
			$currentValues,
			$errors
		);
		if (!empty($errors))
		{
			return false;
		}

		if (!is_numeric($templateId))
		{
			$templateId = 0;
		}

		$properties = [
			'TemplateId' => (int)$templateId,
			'Placeholders' => [],
		];

		$placeholderValues = $currentValues['placeholders'] ?? [];
		if (is_array($placeholderValues) && $placeholderValues)
		{
			$template = self::getTemplateById((int)$templateId);
			$templatePlaceholders = $template['placeholders'] ?? [];
			if (isset($templatePlaceholders['PREVIEW']))
			{
				foreach ($templatePlaceholders['PREVIEW'] as $placeholder)
				{
					if (
						array_key_exists($placeholder, $placeholderValues)
						&& is_string($placeholderValues[$placeholder])
					)
					{
						$errors = [];
						$properties['Placeholders'][$placeholder] = $documentService->getFieldInputValue(
							$documentType,
							['Type' => 'string', 'Multiple' => false],
							'placeholder',
							['placeholder' => $placeholderValues[$placeholder]],
							$errors
						);
					}
				}
			}
		}

		$workflowTemplateUser = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);
		$errors = self::validateProperties($properties, $workflowTemplateUser);

		if ($errors)
		{
			return false;
		}

		$currentActivity = &self::findActivityInTemplate($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	public static function validateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		if (empty($arTestProperties['TemplateId']))
		{
			$errors[] = [
				'code' => 'emptyTemplateId',
				'message' => Loc::getMessage('CRM_SEND_WHATS_APP_MESSAGE_ACTIVITY_ERROR_EMPTY_TEMPLATE_ID'),
			];
		}

		return array_merge($errors, parent::validateProperties($arTestProperties, $user));
	}

	public static function getAjaxResponse($request)
	{
		$templateId = $request['template_id'] ?? null;

		if (
			is_numeric($templateId)
			&& self::includeRequiredModules()
			&& self::isWhatsAppTuned()
			&& self::isWhatsAppTemplateBased()
		)
		{
			return self::getTemplateById((int)$templateId);
		}

		return [];
	}

	private static function getTemplateList(): array
	{
		if (self::includeRequiredModules() && self::isWhatsAppTuned() && self::isWhatsAppTemplateBased())
		{
			$sender = self::getEdnaRuSender();

			return ($sender?->getTemplatesList()) ?? [];
		}

		return [];
	}

	private static function getTemplateById(int $templateId): ?array
	{
		if ($templateId <= 0)
		{
			return null;
		}

		$templates = self::getTemplateList();
		foreach ($templates as $template)
		{
			if ($template['ORIGINAL_ID'] === $templateId)
			{
				return [
					'templateCode' => $template['ID'],
					'content' => $template['PREVIEW'] ?? '',
					'placeholders' => $template['PLACEHOLDERS'] ?? [],
				];
			}
		}

		return null;
	}

	private static function includeRequiredModules(): bool
	{
		return (
			\Bitrix\Main\Loader::includeModule('crm')
			&& \Bitrix\Main\Loader::includeModule('messageservice')
		);
	}

	private static function isWhatsAppTuned(): bool
	{
		$sender = self::getEdnaRuSender();

		return $sender?->canUse() && !empty($sender?->getFromList());
	}

	private static function isWhatsAppTemplateBased(): bool
	{
		$sender = self::getEdnaRuSender();

		return $sender?->isConfigurable() && $sender?->isTemplatesBased();
	}

	private static function getEdnaRuSender(): ?\Bitrix\MessageService\Sender\Base
	{
		return \Bitrix\MessageService\Sender\SmsManager::getSenderById(
			\Bitrix\MessageService\Sender\Sms\Ednaru::ID
		);
	}

	private function closeWithError(string $errorMessage): int
	{
		if (!empty($errorMessage))
		{
			$this->trackError($errorMessage);
		}

		return CBPActivityExecutionStatus::Closed;
	}
}
