<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Main\Mail;
use Bitrix\Crm\Automation\ClientCommunications\ClientCommunications;
use Bitrix\Mail\Helper;

class CBPCrmSendEmailActivity extends CBPActivity
{
	public const TEXT_TYPE_BBCODE = 'bbcode';
	public const TEXT_TYPE_HTML = 'html';
	public const ATTACHMENT_TYPE_FILE = 'file';
	public const ATTACHMENT_TYPE_DISK = 'disk';

	private const SELECT_RULE_FIRST = 'first';
	private const SELECT_RULE_LAST = 'last';
	//private const SELECT_RULE_ALL = 'all';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'Subject' => '',
			'From' => null, //deprecated, WAF unstable property
			'MessageFrom' => null,
			'MessageText' => '',
			'MessageTextType' => '',
			'MessageTextEncoded' => 0,
			'EmailType' => null,
			'EmailSelectRule' => null,
			'UseLinkTracker' => 'Y',
			'AttachmentType' => static::ATTACHMENT_TYPE_FILE,
			'Attachment' => [],
		];
	}

	public function Execute()
	{
		if (
			$this->MessageText === ''
			|| !CModule::IncludeModule("crm")
			|| !CModule::IncludeModule('subscribe')
		)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$typeName, $ownerId] = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2]);
		$ownerTypeId = \CCrmOwnerType::ResolveID($typeName);
		$ownerId = (int)$ownerId;

		$userId = CCrmBizProcHelper::getDocumentResponsibleId($this->GetDocumentId());
		if ($userId <= 0)
		{
			$this->writeError(GetMessage('CRM_SEMA_NO_RESPONSIBLE'), $userId);

			return CBPActivityExecutionStatus::Closed;
		}

		$fromProperty = $this->MessageFrom;
		if (!$fromProperty) //compatibility
		{
			$fromProperty = $this->From;
		}

		$fromInfo = $this->getFromEmail($userId, $fromProperty);

		if (!$fromInfo)
		{
			$this->logDebug();
			$this->writeError(GetMessage('CRM_SEMA_NO_FROM'), $userId);

			return CBPActivityExecutionStatus::Closed;
		}

		$from = $fromInfo['from'];
		$userImap = $fromInfo['userImap'];
		$injectUrn = $fromInfo['injectUrn'];
		$reply = $fromInfo['reply'];
		$fromEmail = $fromInfo['fromEmail'];
		$fromEncoded = $fromInfo['fromEncoded'];

		[$to, $comEntityTypeId, $comEntityId] = $this->getToEmail($ownerTypeId, $ownerId);

		$this->logDebug([
			'MessageFrom' => $from,
			'MessageTo' => $to,
		]);

		if (empty($to))
		{
			$this->writeError(GetMessage('CRM_SEMA_NO_ADDRESSER'), $userId);

			return CBPActivityExecutionStatus::Closed;
		}

		// Bindings & Communications -->
		$bindings = [
			[
				'OWNER_TYPE_ID' => $ownerTypeId,
				'OWNER_ID' => $ownerId
			]
		];
		if (!($comEntityTypeId === $ownerTypeId && $comEntityId === $ownerId))
		{
			$bindings[] = [
				'OWNER_TYPE_ID' => $comEntityTypeId,
				'OWNER_ID' => $comEntityId
			];
		}

		$communications = [
			[
				'TYPE' => 'EMAIL',
				'VALUE' => $to,
				'ENTITY_TYPE_ID' => $comEntityTypeId,
				'ENTITY_ID' => $comEntityId,
			]
		];
		// <-- Bindings & Communications

		$subject = $this->getSubject();
		$messageType = $this->MessageTextType;
		$message = $this->getMessageText($messageType);

		if ($message !== '')
		{
			CCrmActivity::AddEmailSignature(
				$message,
				$messageType === self::TEXT_TYPE_HTML ? CCrmContentType::Html : CCrmContentType::BBCode
			);
		}

		if ($message === '')
		{
			$messageHtml = '';
		}
		else
		{
			if ($messageType !== self::TEXT_TYPE_HTML)
			{
				//Convert BBCODE to HTML
				$parser = new CTextParser();
				$parser->allow['SMILES'] = 'N';
				$messageHtml = $parser->convertText($message);
			}
			else
			{
				$messageHtml = $message;
			}

			if (mb_strpos($messageHtml, '</html>') === false)
			{
				$messageHtml = '<html><body>' . $messageHtml . '</body></html>';
			}
		}

		$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');
		if ($subject === '')
		{
			$subject = GetMessage(
				'CRM_SEMA_DEFAULT_SUBJECT',
				['#DATE#' => $now]
			);
		}

		$description = $message;

		if ($messageType === self::TEXT_TYPE_HTML)
		{
			//$description = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $description);
			$description = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $description);
			$description = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $description);

			$sanitizer = new CBXSanitizer();
			$sanitizer->setLevel(CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->applyDoubleEncode(false);
			$sanitizer->addTags(['style' => []]);
			$description = $sanitizer->SanitizeHtml($description);
		}

		$activityFields = [
			'AUTHOR_ID' => $userId,
			'OWNER_ID' => $ownerId,
			'OWNER_TYPE_ID' => $ownerTypeId,
			'TYPE_ID' => CCrmActivityType::Email,
			'SUBJECT' => $subject,
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => 'Y',
			'RESPONSIBLE_ID' => $userId,
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $description,
			'DESCRIPTION_TYPE' => $messageType === self::TEXT_TYPE_HTML ? CCrmContentType::Html : CCrmContentType::BBCode,
			'DIRECTION' => CCrmActivityDirection::Outgoing,
			'BINDINGS' => array_values($bindings),
			'COMMUNICATIONS' => $communications,
		];

		if ($this->AttachmentType === static::ATTACHMENT_TYPE_DISK)
		{
			$attachmentStorageType = Bitrix\Crm\Integration\StorageType::Disk;
			$attachment = (array)$this->Attachment;
		}
		else
		{
			$attachmentStorageType = Bitrix\Crm\Integration\StorageType::File;
			$attachment = [];
			$attachmentFiles = (array)$this->ParseValue($this->getRawProperty('Attachment'), 'file');
			$attachmentFiles = CBPHelper::MakeArrayFlat($attachmentFiles);
			$attachmentFiles = array_filter($attachmentFiles);

			if ($attachmentFiles)
			{
				foreach ($attachmentFiles as $fileId)
				{
					$arRawFile = CFile::MakeFileArray($fileId);

					if (is_array($arRawFile))
					{
						$fileId = (int)CFile::SaveFile($arRawFile, 'crm');
						if ($fileId > 0)
						{
							$attachment[] = $fileId;
						}
					}
				}
			}
		}

		if ($attachment)
		{
			$activityFields['STORAGE_TYPE_ID'] = $attachmentStorageType;
			$activityFields['STORAGE_ELEMENT_IDS'] = $attachment;
		}

		$addOptions = [
			'REGISTER_SONET_EVENT' => true,
		];

		$arRawFiles =
			isset($activityFields['STORAGE_ELEMENT_IDS']) && !empty($activityFields['STORAGE_ELEMENT_IDS'])
				? \Bitrix\Crm\Integration\StorageManager::makeFileArray(
				$activityFields['STORAGE_ELEMENT_IDS'],
				$activityFields['STORAGE_TYPE_ID']
			)
				: []
		;
		$totalSize = 0;

		foreach ($arRawFiles as $arRawFile)
		{
			$totalSize += $arRawFile['size'];
		}

		$maxSize = Helper\Message::getMaxAttachedFilesSize();
		if ($maxSize > 0 && $maxSize <= ceil($totalSize / 3) * 4) // base64 coef.
		{
			$this->writeError(GetMessage('CRM_SEMA_ACTIVITY_EMAIL_MAX_SIZE_EXCEED',
										 ['#SIZE#' => \CFile::formatSize(Helper\Message::getMaxAttachedFilesSizeAfterEncoding())]
							  ), $userId);

			return CBPActivityExecutionStatus::Closed;
		}

		Crm\Activity\Provider\Email::compressActivity($activityFields);

		$id = CCrmActivity::Add($activityFields, false, false, $addOptions);
		if (!$id)
		{
			$this->writeError(CCrmActivity::GetLastErrorMessage(), $userId);

			return CBPActivityExecutionStatus::Closed;
		}

		$urn = CCrmActivity::PrepareUrn($activityFields);
		$messageId = sprintf(
			'<crm.activity.%s@%s>', $urn,
			defined('BX24_HOST_NAME') ? BX24_HOST_NAME : (
			defined('SITE_SERVER_NAME') && SITE_SERVER_NAME
				? SITE_SERVER_NAME : \COption::getOptionString('main', 'server_name', '')
			)
		);

		\CCrmActivity::update(
			$id,
			[
				//'DESCRIPTION' => $arFields['DESCRIPTION'],
				'URN' => $urn,
				'SETTINGS' => [
					'IS_BATCH_EMAIL' => Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y' ? false : null,
					'MESSAGE_HEADERS' => [
						'Message-Id' => $messageId,
						'Reply-To' => $reply ?: $from,
					],
					'EMAIL_META' => [
						'__email' => $fromEmail,
						'from' => $from,
						'replyTo' => $reply,
						'to' => $to,
					],
					'BP_ACTIVITY_ID' => $this->GetName(),
					'BP_TEMPLATE_ID' => $this->GetWorkflowTemplateId()
				],
			],
			false,
			false,
			['REGISTER_SONET_EVENT' => true]
		);

		// sending email
		$rcpt = [
			Mail\Mail::encodeHeaderFrom($to, SITE_CHARSET)
		];

		$outgoingSubject = $subject;
		$outgoingBody = $messageHtml ?: getMessage('CRM_SEMA_DEFAULT_BODY');

		if (!empty($injectUrn))
		{
			switch (\CCrmEMailCodeAllocation::getCurrent())
			{
				case \CCrmEMailCodeAllocation::Subject:
					$outgoingSubject = \CCrmActivity::injectUrnInSubject($urn, $outgoingSubject);
					break;
				case \CCrmEMailCodeAllocation::Body:
					$outgoingBody = \CCrmActivity::injectUrnInBody($urn, $outgoingBody, 'html');
					break;
			}
		}

		$attachments = [];
		foreach ($arRawFiles as $key => $item)
		{
			$attachments[] = [
				'ID' => $item['external_id'],
				'NAME' => $item['ORIGINAL_NAME'] ?: $item['name'],
				'PATH' => $item['tmp_name'],
				'CONTENT_TYPE' => $item['type'],
			];
		}

		$context = new Mail\Context();
		$context->setCategory(Mail\Context::CAT_EXTERNAL)
			->setPriority(Mail\Context::PRIORITY_LOW)
			->setCallback(
				(new \Bitrix\Main\Mail\Callback\Config())
					->setModuleId("crm")
					->setEntityType("rpa")
					->setEntityId($urn)
			);

		$outgoingParams = [
			'CHARSET' => SITE_CHARSET,
			'CONTENT_TYPE' => 'html',
			'ATTACHMENT' => $attachments,
			'TO' => join(', ', $rcpt),
			'SUBJECT' => $outgoingSubject,
			'BODY' => $outgoingBody,
			'HEADER' => [
				'From' => $fromEncoded ?: $fromEmail,
				'Reply-To' => $reply ?: $fromEmail,
				'Message-Id' => $messageId,
			],
			'TRACK_READ' => [
				'MODULE_ID' => 'crm',
				'FIELDS' => ['urn' => $urn],
				'URL_PAGE' => '/pub/mail/read.php',
			],
			'TRACK_CLICK' => ($this->UseLinkTracker === 'Y') ? [
				'MODULE_ID' => 'crm',
				'FIELDS' => ['urn' => $urn],
				'URL_PAGE' => '/pub/mail/click.php',
				'URL_PARAMS' => [],
			] : null,
			'CONTEXT' => $context,
		];

		if (Crm\WebForm\Manager::isEmbeddingAvailable())
		{
			$outgoingParams['TRACK_CLICK']['URL_PARAMS'][Crm\WebForm\Embed\Sign::uriParameterName] = (new Crm\WebForm\Embed\Sign())
				->addEntity($ownerTypeId, $ownerId)
				->pack();
		}

		$sendResult = Mail\Mail::send($outgoingParams);

		if (!$sendResult)
		{
			$this->writeError(GetMessage('CRM_SEMA_EMAIL_CREATION_CANCELED'), $userId);
			\CCrmActivity::delete($id);

			return CBPActivityExecutionStatus::Closed;
		}

		$this->logDebugActivity($id);

		addEventToStatFile(
			'crm',
			'send_email_message',
			sprintf('bp_%s_%s', $this->getWorkflowTemplateId(), $this->getName()),
			trim(trim($messageId), '<>')
		);

		if (!empty($userImap))
		{
			class_exists('Bitrix\Mail\Helper');

			$outgoingHeader = array_merge(
				$outgoingParams['HEADER'],
				[
					'To' => $outgoingParams['TO'],
					'Subject' => $outgoingParams['SUBJECT'],
				]
			);

			$outgoing = new \Bitrix\Mail\DummyMail(array_merge(
				$outgoingParams,
				[
					'HEADER' => $outgoingHeader,
				]
			));

			\Bitrix\Mail\Helper::addImapMessage($userImap, (string)$outgoing, $err);
		}

		// Try add event to entity
		$CCrmEvent = new CCrmEvent();

		$eventText = '';
		$eventText .= GetMessage('CRM_SEMA_EMAIL_SUBJECT').': '.$subject."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_FROM').': '.$from."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_TO').': '.$to."\n\r\n\r";
		$eventText .= $messageHtml;

		$eventBindings = [];
		foreach ($bindings as $item)
		{
			$bindingEntityID = $item['OWNER_ID'];
			$bindingEntityTypeID = $item['OWNER_TYPE_ID'];
			$bindingEntityTypeName = \CCrmOwnerType::resolveName($bindingEntityTypeID);

			$eventBindings["{$bindingEntityTypeName}_{$bindingEntityID}"] = [
				'ENTITY_TYPE' => $bindingEntityTypeName,
				'ENTITY_ID' => $bindingEntityID
			];
		}

		$CCrmEvent->Add(
			[
				'ENTITY' => $eventBindings,
				'EVENT_ID' => 'MESSAGE',
				'EVENT_TEXT_1' => $eventText,
				'FILES' => $arRawFiles
			]
		);

		// <-- Sending Email

		return CBPActivityExecutionStatus::Closed;
	}

	private function getFromEmail($userId, $from = '')
	{
		$userImap = null;
		$injectUrn = false;
		$reply = '';
		$from = trim((string)$from);
		$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail', ''));

		$mailboxes = [];
		if (CModule::includeModule('mail'))
		{
			$mailboxes = \Bitrix\Mail\MailboxTable::getUserMailboxes($userId);
		}

		if ($from === '')
		{
			$availableMailboxes = Main\Mail\Sender::prepareUserMailboxes($userId);
			if (!empty($availableMailboxes))
			{
				$from = reset($availableMailboxes)['email'];
			}

			if ($from === '')
			{
				$from = CUserOptions::GetOption('crm', 'activity_email_addresser', '', $userId);
			}

			$fromData = $this->parseFromString($from);
			$fromEmail = $fromData['email'];
			$fromEncoded = $fromData['nameEncoded'];
		}
		else
		{
			$fromData = $this->parseFromString($from);
			$fromEmail = $fromData['email'];
			$fromEncoded = $fromData['nameEncoded'];

			if (!check_email($fromEmail, true))
			{
				$from = '';
			}
		}

		if (empty($from))
		{
			return false;
		}

		foreach ($mailboxes as $mailbox)
		{
			if ($fromEmail === $mailbox['EMAIL'])
			{
				$userImap = $mailbox;
				break;
			}
		}

		if (empty($userImap))
		{
			if ($crmEmail != '' && $crmEmail != $fromEmail)
			{
				$reply = $fromEmail.', '.$crmEmail;
			}

			$injectUrn = true;
		}

		return [
			'from' => $from,
			'fromEmail' => $fromEmail,
			'userImap' => $userImap,
			'reply' => $reply,
			'injectUrn' => $injectUrn,
			'fromEncoded' => $fromEncoded
		];
	}

	private function getToEmail($entityTypeId, $entityId)
	{
		$to = '';
		$comEntityTypeId = $entityTypeId;
		$comEntityId = $entityId;

		$emailType = $this->EmailType;

		$clientCommunications = new ClientCommunications((int)$entityTypeId, (int)$entityId, CCrmFieldMulti::EMAIL);
		$communications = (
			$this->EmailSelectRule === self::SELECT_RULE_LAST
				? $clientCommunications->getLastFilled($emailType ? (string)$emailType : null)
				: $clientCommunications->getFirstFilled($emailType ? (string)$emailType : null)
		);

		if ($communications)
		{
			$email = $communications[0] ?? [];
			if ($email)
			{
				$to = $email['VALUE'];
				$comEntityTypeId = $email['ENTITY_TYPE_ID'];
				$comEntityId = $email['ENTITY_ID'];
			}
		}

		return [$to, $comEntityTypeId, $comEntityId];
	}

	private function parseFromString($from)
	{
		$fromName = $fromEncoded = '';
		$fromEmail = $from;

		$address = new Mail\Address($from);

		if ($address->validate())
		{
			$fromName = $address->getName();
			$fromEmail = $address->getEmail();

			if ($fromName)
			{
				$fromEncoded = $address->getEncoded();
			}
		}

		return [
			'email' => $fromEmail,
			'name' => $fromName,
			'nameEncoded' => $fromEncoded
		];
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if ($arTestProperties["MessageText"] === "")
		{
			$arErrors[] = ["code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("CRM_SEMA_EMPTY_PROP")];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
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
			'siteId' => $siteId
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'Subject' => [
				'Name' => GetMessage('CRM_SEMA_EMAIL_SUBJECT'),
				'Description' => GetMessage('CRM_SEMA_EMAIL_SUBJECT'),
				'FieldName' => 'subject',
				'Type' => 'string',
				'Required' => true
			],
			'MessageText' => [
				'Name' => GetMessage('CRM_SEMA_MESSAGE_TEXT'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			],
			'MessageTextType' => [
				'Name' => GetMessage('CRM_SEMA_MESSAGE_TEXT_TYPE'),
				'FieldName' => 'message_text_type',
				'Type' => 'select',
				'Options' => [
					self::TEXT_TYPE_BBCODE => 'BBCODE',
					self::TEXT_TYPE_HTML => 'HTML'
				],
				'Default' => self::TEXT_TYPE_BBCODE
			],
			'MessageTextEncoded' => [
				'Name' => 'MessageTextEncoded',
				'FieldName' => 'message_text_encoded',
				'Type' => 'int',
				'Default' => 0
			],
			'AttachmentType' => [
				'Name' => GetMessage('CRM_SEMA_ATTACHMENT_TYPE_1'),
				'FieldName' => 'attachment_type',
				'Type' => 'select',
				'Options' => [
					static::ATTACHMENT_TYPE_FILE => GetMessage('CRM_SEMA_ATTACHMENT_FILE_2'),
					static::ATTACHMENT_TYPE_DISK => GetMessage('CRM_SEMA_ATTACHMENT_DISK')
				]
			],
			'Attachment' => [
				'Name' => GetMessage('CRM_SEMA_ATTACHMENT_1'),
				'FieldName' => 'attachment',
				'Type' => 'file',
				'Multiple' => true
			],
			'EmailType' => [
				'Name' => GetMessage('CRM_SEMA_EMAIL_TYPE'),
				'FieldName' => 'email_type',
				'Type' => 'select',
				'Options' =>
					['' => GetMessage('CRM_SEMA_EMAIL_TYPE_EMPTY_OPTION')]
					+ \CCrmFieldMulti::GetEntityTypeList(\CCrmFieldMulti::EMAIL)
			],
			'EmailSelectRule' => [
				'Name' => GetMessage('CRM_SEMA_EMAIL_SELECT_RULE'),
				'FieldName' => 'email_select_rule',
				'Type' => 'select',
				'Options' => [
					self::SELECT_RULE_FIRST => GetMessage('CRM_SEMA_EMAIL_SELECT_RULE_FIRST'),
					self::SELECT_RULE_LAST => GetMessage('CRM_SEMA_EMAIL_SELECT_RULE_LAST'),
				],
			],
			'UseLinkTracker' => [
				'Name' => GetMessage('CRM_SEMA_USE_LINK_TRACKER'),
				'FieldName' => 'use_link_tracker',
				'Type' => 'bool',
				'Default' => 'Y'
			],
			'MessageFrom' => [
				'Name' => GetMessage('CRM_SEMA_EMAIL_FROM'),
				'FieldName' => 'message_from',
				'Type' => 'mail_sender',
			]
		];
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$properties = [
			'Subject' => (string)($arCurrentValues['subject'] ?? ''),
			'MessageText' => (string)($arCurrentValues['message_text'] ?? ''),
			'MessageTextType' => (string)($arCurrentValues['message_text_type'] ?? ''),
			'AttachmentType' => (string)($arCurrentValues['attachment_type'] ?? ''),
			'MessageFrom' => (string)($arCurrentValues['message_from'] ?? ''),
			'EmailType' => (string)($arCurrentValues['email_type'] ?? ''),
			'EmailSelectRule' => (string)($arCurrentValues['email_select_rule'] ?? ''),
			'UseLinkTracker' => $arCurrentValues['use_link_tracker'] === 'Y' ? 'Y' : 'N',
			'MessageTextEncoded' => 0,
			'Attachment' => []
		];

		if (
			isset($arCurrentValues['message_from'])
			&& $arCurrentValues['message_from'] === ''
			&& static::isExpression(($arCurrentValues['message_from_text'] ?? null))
		)
		{
			$properties['MessageFrom'] = $arCurrentValues['message_from_text'];
		}

		if ($properties['AttachmentType'] === static::ATTACHMENT_TYPE_DISK)
		{
			foreach ((array)$arCurrentValues["attachment"] as $attachmentId)
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
			$properties['Attachment'] = $arCurrentValues["attachment"] ?? ($arCurrentValues["attachment_text"] ?? '');
		}

		if (
			$properties['MessageTextType'] !== self::TEXT_TYPE_BBCODE
			&& $properties['MessageTextType'] !== self::TEXT_TYPE_HTML
		)
		{
			$properties['MessageTextType'] = self::TEXT_TYPE_BBCODE;
		}

		if ($properties['MessageTextType'] === self::TEXT_TYPE_HTML)
		{
			$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
			$rawData = $request->getPostList()->getRaw('message_text');
			if ($rawData === null)
			{
				$rawData = (array)$request->getPostList()->getRaw('form_data');
				$rawData = $rawData['message_text'];
			}

			//TODO: fix for WAF, needs refactoring.
			$rawData = \Bitrix\Crm\Automation\Helper::unConvertExpressions($rawData, $documentType);

			$properties['MessageText'] = self::encodeMessageText($rawData);
			$properties['MessageTextEncoded'] = 1;
		}

		if (count($errors) > 0)
			return false;

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	private function getSubject()
	{
		$subject = $this->Subject;
		if (is_array($subject))
		{
			$subject = implode(', ', \CBPHelper::MakeArrayFlat($subject));
		}

		return $subject;
	}

	private function getMessageText($messageType)
	{
		$message = $this->getRawProperty('MessageText');
		if ($this->MessageTextEncoded)
		{
			$message = self::decodeMessageText($message);
		}

		return $this->ParseValue(
			$message,
			'text',
			function($objectName, $fieldName, $property, $result) use ($messageType)
			{
				if (is_array($result))
				{
					$result = implode(', ', CBPHelper::makeArrayFlat($result));
				}

				if ($messageType === 'html' && isset($property['ValueContentType']))
				{
					if ($property['ValueContentType'] === 'bb')
					{
						$result = Crm\Format\TextHelper::sanitizeHtml(
							Crm\Format\TextHelper::convertBbCodeToHtml($result)
						);
					}
					elseif ($property['ValueContentType'] !== 'html' && isset($property['Type']) && $property['Type'] !== 'S:HTML')
					{
						$result = htmlspecialcharsbx($result);
					}
				}

				return $result;
			}
		);
	}

	private static function encodeMessageText($text)
	{
		return 'base64,'.base64_encode($text);
	}

	public static function decodeMessageText($text)
	{
		if (mb_strpos($text, 'base64,') === 0)
		{
			$text = mb_substr($text, 7);

			return base64_decode($text);
		}

		//compatible encode type
		return htmlspecialcharsback($text);
	}

	private function writeError($errorText, $userId)
	{
		$this->WriteToTrackingService($errorText, 0, CBPTrackingType::Error);
		$timelineText = GetMessage('CRM_SEMA_TIMELINE_ERROR', ['#ERROR_TEXT#' => $errorText]);
		\Bitrix\Crm\Timeline\BizprocController::getInstance()->onActivityError($this, $userId, $timelineText);
	}

	private function logDebug(array $values = [])
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$fullMap = static::getPropertiesMap($this->getDocumentType());
		$map = [
			'MessageFrom' => $fullMap['MessageFrom']['Name'],
			'MessageTo' => GetMessage('CRM_SEMA_EMAIL_TO'),
			'Subject' => $fullMap['Subject'],
		];

		$debugInfo = $this->getDebugInfo($values, $map);
		$this->writeDebugInfo($debugInfo);
	}

	private function logDebugActivity($id)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$value = sprintf(
			'/bitrix/components/bitrix/crm.activity.planner/slider.php?site_id='
			. SITE_ID . '&ajax_action=ACTIVITY_VIEW&activity_id=%d',
			$id
		);

		$toWrite = [
			'propertyName' => GetMessage("CRM_SEMA_MESSAGE_TEXT"),
			'propertyValue' => $value,
			'propertyLinkName' => GetMessage('CRM_SEMA_ACTIVITY_LINK_LABEL'),
		];

		$this->writeDebugTrack(
			$this->getWorkflowInstanceId(),
			$this->getName(),
			$this->executionStatus,
			$this->executionResult,
			$this->Title ?? '',
			$toWrite,
			CBPTrackingType::DebugLink
		);
	}
}
