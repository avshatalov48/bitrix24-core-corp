<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Main\Mail;
use Bitrix\Crm;

class CBPCrmSendEmailActivity extends CBPActivity
{
	const TEXT_TYPE_BBCODE = 'bbcode';
	const TEXT_TYPE_HTML = 'html';
	const ATTACHMENT_TYPE_FILE = 'file';
	const ATTACHMENT_TYPE_DISK = 'disk';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Subject" => "",
			"From" => null, //deprecated, WAF unstable property
			"MessageFrom" => null,
			"MessageText" => '',
			"MessageTextType" => '',
			"MessageTextEncoded" => 0,
			"EmailType" => null,
			"UseLinkTracker" => 'Y',
			'AttachmentType' => static::ATTACHMENT_TYPE_FILE,
			'Attachment' => array()
		);
	}

	public function Execute()
	{
		if (!$this->MessageText || !CModule::IncludeModule("crm") || !CModule::IncludeModule('subscribe'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		list($typeName, $ownerId) = explode('_', $this->GetDocumentId()[2]);
		$ownerTypeId = \CCrmOwnerType::ResolveID($typeName);
		$ownerId = (int)$ownerId;

		$userId = CCrmBizProcHelper::getDocumentResponsibleId($this->GetDocumentId());
		if($userId <= 0)
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
			$this->writeError(GetMessage('CRM_SEMA_NO_FROM'), $userId);
			return CBPActivityExecutionStatus::Closed;
		}

		$from = $fromInfo['from'];
		$userImap = $fromInfo['userImap'];
		$injectUrn = $fromInfo['injectUrn'];
		$reply = $fromInfo['reply'];
		$fromEmail = $fromInfo['fromEmail'];
		$fromEncoded = $fromInfo['fromEncoded'];

		list($to, $comEntityTypeId, $comEntityId) = $this->getToEmail($ownerTypeId, $ownerId);

		if (empty($to))
		{
			$this->writeError(GetMessage('CRM_SEMA_NO_ADDRESSER'), $userId);
			return CBPActivityExecutionStatus::Closed;
		}

		// Bindings & Communications -->
		$bindings = [['OWNER_TYPE_ID' => $ownerTypeId, 'OWNER_ID' => $ownerId]];
		if (!($comEntityTypeId === $ownerTypeId && $comEntityId === $ownerId))
		{
			$bindings[] = ['OWNER_TYPE_ID' => $comEntityTypeId, 'OWNER_ID' => $comEntityId];
		}

		$communications = array(array(
			'TYPE' => 'EMAIL',
			'VALUE' => $to,
			'ENTITY_TYPE_ID' => $comEntityTypeId,
			'ENTITY_ID' => $comEntityId,
		));
		// <-- Bindings & Communications

		$subject = $this->getSubject();
		$message = $this->getMessageText();
		$messageType = $this->MessageTextType;

		if($message !== '')
		{
			CCrmActivity::AddEmailSignature($message,
				$messageType === self::TEXT_TYPE_HTML ? CCrmContentType::Html : CCrmContentType::BBCode
			);
		}

		if($message === '')
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
				$messageHtml = '<html><body>'.$messageHtml.'</body></html>';
			}
		}

		$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');
		if($subject === '')
		{
			$subject = GetMessage(
				'CRM_SEMA_DEFAULT_SUBJECT',
				array('#DATE#'=> $now)
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
			$sanitizer->addTags(array('style' => array()));
			$description = $sanitizer->SanitizeHtml($description);
		}

		$activityFields = array(
			'AUTHOR_ID' => $userId,
			'OWNER_ID' => $ownerId,
			'OWNER_TYPE_ID' => $ownerTypeId,
			'TYPE_ID' =>  CCrmActivityType::Email,
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
		);

		if ($this->AttachmentType === static::ATTACHMENT_TYPE_DISK)
		{
			$attachmentStorageType = Bitrix\Crm\Integration\StorageType::Disk;
			$attachment = (array)$this->Attachment;
		}
		else
		{
			$attachmentStorageType = Bitrix\Crm\Integration\StorageType::File;
			$attachment = array();
			$attachmentFiles = (array)$this->ParseValue($this->getRawProperty('Attachment'), 'file');
			$attachmentFiles = CBPHelper::MakeArrayFlat($attachmentFiles);
			$attachmentFiles = array_filter($attachmentFiles);

			if($attachmentFiles)
			{
				foreach ($attachmentFiles as $fileId)
				{
					$arRawFile = CFile::MakeFileArray($fileId);
					if (is_array($arRawFile))
					{
						$fileId = intval(CFile::SaveFile($arRawFile, 'crm'));
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

		if(!($id = CCrmActivity::Add($activityFields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			$this->writeError(CCrmActivity::GetLastErrorMessage(), $userId);
			return CBPActivityExecutionStatus::Closed;
		}

		$arRawFiles = isset($activityFields['STORAGE_ELEMENT_IDS']) && !empty($activityFields['STORAGE_ELEMENT_IDS'])
			? \Bitrix\Crm\Integration\StorageManager::makeFileArray(
				$activityFields['STORAGE_ELEMENT_IDS'], $activityFields['STORAGE_TYPE_ID']
			)
			: array();

		$urn = CCrmActivity::PrepareUrn($activityFields);
		$messageId = sprintf(
			'<crm.activity.%s@%s>', $urn,
			defined('BX24_HOST_NAME') ? BX24_HOST_NAME : (
			defined('SITE_SERVER_NAME') && SITE_SERVER_NAME
				? SITE_SERVER_NAME : \COption::getOptionString('main', 'server_name', '')
			)
		);

		\CCrmActivity::update($id, array(
			//'DESCRIPTION' => $arFields['DESCRIPTION'],
			'URN'         => $urn,
			'SETTINGS'    => array(
				'IS_BATCH_EMAIL'  => Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y' ? false : null,
				'MESSAGE_HEADERS' => array(
					'Message-Id' => $messageId,
					'Reply-To'   => $reply ?: $from,
				),
				'EMAIL_META' => array(
					'__email' => $fromEmail,
					'from'    => $from,
					'replyTo' => $reply,
					'to'      => $to,
				),
				'BP_ACTIVITY_ID' => $this->GetName(),
				'BP_TEMPLATE_ID' => $this->GetWorkflowTemplateId()
			),
		), false, false, array('REGISTER_SONET_EVENT' => true));

		// sending email
		$rcpt = array(
			Mail\Mail::encodeHeaderFrom($to, SITE_CHARSET)
		);

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

		$attachments = array();
		foreach ($arRawFiles as $key => $item)
		{
			$attachments[] = array(
				'ID'           => $item['external_id'],
				'NAME'         => $item['ORIGINAL_NAME'] ?: $item['name'],
				'PATH'         => $item['tmp_name'],
				'CONTENT_TYPE' => $item['type'],
			);
		}

		$context = new Mail\Context();
		$context->setCategory(Mail\Context::CAT_EXTERNAL);
		$context->setPriority(Mail\Context::PRIORITY_LOW);

		$outgoingParams = [
			'CHARSET'      => SITE_CHARSET,
			'CONTENT_TYPE' => 'html',
			'ATTACHMENT'   => $attachments,
			'TO'           => join(', ', $rcpt),
			'SUBJECT'      => $outgoingSubject,
			'BODY'         => $outgoingBody,
			'HEADER'       => [
				'From'       => $fromEncoded ?: $fromEmail,
				'Reply-To'   => $reply ?: $fromEmail,
				'Message-Id' => $messageId,
			],
			'TRACK_READ' => [
				'MODULE_ID' => 'crm',
				'FIELDS'    => ['urn' => $urn],
				'URL_PAGE' => '/pub/mail/read.php',
			],
			'TRACK_CLICK' => ($this->UseLinkTracker === 'Y') ? [
					'MODULE_ID' => 'crm',
					'FIELDS'    => ['urn' => $urn],
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
				array(
					'To'      => $outgoingParams['TO'],
					'Subject' => $outgoingParams['SUBJECT'],
				)
			);

			$outgoing = new \Bitrix\Mail\DummyMail(array_merge(
				$outgoingParams,
				array(
					'HEADER' => $outgoingHeader,
				)
			));

			\Bitrix\Mail\Helper::addImapMessage($userImap, (string) $outgoing, $err);
		}

		// Try add event to entity
		$CCrmEvent = new CCrmEvent();

		$eventText  = '';
		$eventText .= GetMessage('CRM_SEMA_EMAIL_SUBJECT').': '.$subject."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_FROM').': '.$from."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_TO').': '.$to."\n\r\n\r";
		$eventText .= $messageHtml;

		$eventBindings = array();
		foreach($bindings as $item)
		{
			$bindingEntityID = $item['OWNER_ID'];
			$bindingEntityTypeID = $item['OWNER_TYPE_ID'];
			$bindingEntityTypeName = \CCrmOwnerType::resolveName($bindingEntityTypeID);

			$eventBindings["{$bindingEntityTypeName}_{$bindingEntityID}"] = array(
				'ENTITY_TYPE' => $bindingEntityTypeName,
				'ENTITY_ID' => $bindingEntityID
			);
		}

		$CCrmEvent->Add(
			array(
				'ENTITY' => $eventBindings,
				'EVENT_ID' => 'MESSAGE',
				'EVENT_TEXT_1' => $eventText,
				'FILES' => $arRawFiles
			)
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
			$fromEncoded  = $fromData['nameEncoded'];
		}
		else
		{
			$fromData = $this->parseFromString($from);
			$fromEmail = $fromData['email'];
			$fromEncoded  = $fromData['nameEncoded'];

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
				$reply = $fromEmail . ', ' . $crmEmail;
			}

			$injectUrn = true;
		}

		return array(
			'from' => $from,
			'fromEmail' => $fromEmail,
			'userImap' => $userImap,
			'reply' => $reply,
			'injectUrn' => $injectUrn,
			'fromEncoded' => $fromEncoded
		);
	}

	private function getToEmail($entityTypeId, $entityId)
	{
		$to = '';
		$comEntityTypeId = $entityTypeId;
		$comEntityId = $entityId;
		$emailType = $this->EmailType;

		if ($entityTypeId == \CCrmOwnerType::Deal)
		{
			$entity = \CCrmDeal::GetByID($entityId, false);
			$contactId = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
			$companyId = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

			if($contactId > 0)
			{
				$to = $this->getEntityEmail(\CCrmOwnerType::Contact, $contactId, $emailType);
				$comEntityTypeId = \CCrmOwnerType::Contact;
				$comEntityId = $contactId;
			}

			if (empty($to))
			{
				$dealContactIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($entityId);
				if ($dealContactIds)
				{
					foreach ($dealContactIds as $contId)
					{
						if ($contId !== $contactId)
						{
							$to = $this->getEntityEmail(\CCrmOwnerType::Contact, $contId, $emailType);
							$comEntityTypeId = \CCrmOwnerType::Contact;
							$comEntityId = $contId;
							if ($to)
							{
								break;
							}
						}
					}
				}
			}

			if (empty($to) && $companyId > 0)
			{
				$to = $this->getEntityEmail(\CCrmOwnerType::Company, $companyId, $emailType);
				$comEntityTypeId = \CCrmOwnerType::Company;
				$comEntityId = $companyId;
			}
		}
		elseif ($entityTypeId == \CCrmOwnerType::Order)
		{
			$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList(array(
				'select' => array('ENTITY_ID', 'ENTITY_TYPE_ID'),
				'filter' => array(
					'=ORDER_ID' => $entityId,
					'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
					'IS_PRIMARY' => 'Y'
				),
				'order' => ['ENTITY_TYPE_ID' => 'ASC']
			));
			while ($row = $dbRes->fetch())
			{
				$to = $this->getEntityEmail($row['ENTITY_TYPE_ID'], $row['ENTITY_ID'], $emailType);
				if ($to)
				{
					$comEntityTypeId = (int) $row['ENTITY_TYPE_ID'];
					$comEntityId = (int) $row['ENTITY_ID'];
					break;
				}
			}
		}
		elseif ($entityTypeId == \CCrmOwnerType::Lead)
		{
			$to = $this->getEntityEmail($entityTypeId, $entityId, $emailType);

			if (empty($to))
			{
				$entity = \CCrmLead::GetByID($entityId, false);
				$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
				$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

				if($entityContactID > 0)
				{
					$to = $this->getEntityEmail(\CCrmOwnerType::Contact, $entityContactID, $emailType);
					$comEntityTypeId = \CCrmOwnerType::Contact;
					$comEntityId = $entityContactID;
				}
				if (empty($to) && $entityCompanyID > 0)
				{
					$to = $this->getEntityEmail(\CCrmOwnerType::Company, $entityCompanyID, $emailType);
					$comEntityTypeId = \CCrmOwnerType::Company;
					$comEntityId = $entityCompanyID;
				}
			}
		}
		else
		{
			$to = $this->getEntityEmail($entityTypeId, $entityId, $emailType);
		}

		return [$to, $comEntityTypeId, $comEntityId];
	}

	private function getEntityEmail($entityTypeId, $entityId, $emailType = null)
	{
		$result = '';
		$filter = array(
			'ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeId),
			'ELEMENT_ID' => $entityId,
			'TYPE_ID' => \CCrmFieldMulti::EMAIL
		);

		if ($emailType)
		{
			$filter['VALUE_TYPE'] = $emailType;
		}

		$dbResFields = CCrmFieldMulti::GetList(['ID' => 'asc'], $filter);

		while($arField = $dbResFields->Fetch())
		{
			if(empty($arField['VALUE']))
			{
				continue;
			}

			$result = $arField['VALUE'];
			break;
		}

		return $result;
	}

	private function parseFromString($from)
	{
		$fromName = $fromEncoded = '';
		$fromEmail = $from;

		if (preg_match('/(.*)<(.+?)>\s*$/is', $from, $matches))
		{
			$fromName  = trim($matches[1], "\"\x20\t\n\r\0\x0b");
			$fromEmail = mb_strtolower(trim($matches[2]));

			if ($fromName != '')
			{
				$fromNameEscaped = str_replace(array('\\', '"', '<', '>'), array('/', '\'', '(', ')'), $fromName);
				$fromEncoded = sprintf(
					'%s <%s>',
					sprintf('=?%s?B?%s?=', SITE_CHARSET, base64_encode($fromNameEscaped)),
					$fromEmail
				);
			}
		}

		return array('email' => $fromEmail, 'name' => $fromName, 'nameEncoded' => $fromEncoded);
	}

	private static function makeMailboxesSelectOptions(array $mailboxes)
	{
		$options = array();
		foreach ($mailboxes as $mailbox)
		{
			$options[] = sprintf(
				$mailbox['name'] ? '%s <%s>' : '%s%s',
				$mailbox['name'], $mailbox['email']
			);
		}
		return $options;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if ($arTestProperties["MessageText"] === "")
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("CRM_SEMA_EMPTY_PROP"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

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

		$map = array(
			'Subject' => array(
				'Name' => GetMessage('CRM_SEMA_EMAIL_SUBJECT'),
				'FieldName' => 'subject',
				'Type' => 'string',
				'Required' => true
			),
			'MessageText' => array(
				'Name' => GetMessage('CRM_SEMA_MESSAGE_TEXT'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			),
			'MessageTextType' => array(
				'Name' => GetMessage('CRM_SEMA_MESSAGE_TEXT_TYPE'),
				'FieldName' => 'message_text_type',
				'Type' => 'select',
				'Options' => array(
					self::TEXT_TYPE_BBCODE => 'BBCODE',
					self::TEXT_TYPE_HTML => 'HTML'
				),
				'Default' => self::TEXT_TYPE_BBCODE
			),
			'MessageTextEncoded' => array(
				'Name' => 'MessageTextEncoded',
				'FieldName' => 'message_text_encoded',
				'Type' => 'int',
				'Default' => 0
			),
			'AttachmentType' => array(
				'Name' => GetMessage('CRM_SEMA_ATTACHMENT_TYPE'),
				'FieldName' => 'attachment_type',
				'Type' => 'select',
				'Options' => array(
					static::ATTACHMENT_TYPE_FILE => GetMessage('CRM_SEMA_ATTACHMENT_FILE'),
					static::ATTACHMENT_TYPE_DISK => GetMessage('CRM_SEMA_ATTACHMENT_DISK')
				)
			),
			'Attachment' => array(
				'Name' => GetMessage('CRM_SEMA_ATTACHMENT'),
				'FieldName' => 'attachment',
				'Type' => 'file',
				'Multiple' => true
			),
			'EmailType' => array(
				'Name' => GetMessage('CRM_SEMA_EMAIL_TYPE'),
				'FieldName' => 'email_type',
				'Type' => 'select',
				'Options' =>
					['' => GetMessage('CRM_SEMA_EMAIL_TYPE_EMPTY_OPTION')]
					+ \CCrmFieldMulti::GetEntityTypeList(\CCrmFieldMulti::EMAIL)
			),
			'UseLinkTracker' => array(
				'Name' => GetMessage('CRM_SEMA_USE_LINK_TRACKER'),
				'FieldName' => 'use_link_tracker',
				'Type' => 'bool',
				'Default' => 'Y'
			)
		);

		$mailboxes = Main\Mail\Sender::prepareUserMailboxes();

		//deprecated "From"
		$map['From'] = array(
			'Name' => GetMessage('CRM_SEMA_EMAIL_FROM'),
			'FieldName' => 'from',
			'Type' => 'string'
		);

		$map['MessageFrom'] = array(
			'Name' => GetMessage('CRM_SEMA_EMAIL_FROM'),
			'FieldName' => 'message_from',
			'Type' => 'select',
			'Options' => static::makeMailboxesSelectOptions($mailboxes)
		);

		$dialog->setRuntimeData(array(
			'mailboxes' => $mailboxes
		));

		$dialog->setMap($map);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = array();

		$properties = array(
			'Subject' => (string)$arCurrentValues["subject"],
			'MessageText' => (string)$arCurrentValues["message_text"],
			'MessageTextType' => (string)$arCurrentValues["message_text_type"],
			'AttachmentType' => (string)$arCurrentValues["attachment_type"],
			'MessageFrom' => (string)$arCurrentValues["message_from"],
			'EmailType' => (string)$arCurrentValues["email_type"],
			'UseLinkTracker' => $arCurrentValues["use_link_tracker"] === 'Y' ? 'Y' : 'N',

			'MessageTextEncoded' => 0,
			'Attachment' => array()
		);

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
			$properties['Attachment'] = isset($arCurrentValues["attachment"])
				? $arCurrentValues["attachment"] : $arCurrentValues["attachment_text"];
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

			if ($request->isAjaxRequest())
			{
				\CUtil::decodeURIComponent($rawData);
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

	private function getMessageText()
	{
		$message = $this->getRawProperty('MessageText');
		if ($this->MessageTextEncoded)
		{
			$message = self::decodeMessageText($message);
		}
		$message = $this->ParseValue($message, 'text');
		if (is_array($message))
		{
			$message = implode(', ', \CBPHelper::MakeArrayFlat($message));
		}
		return $message;
	}

	private static function encodeMessageText($text)
	{
		return 'base64,' . base64_encode($text);
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
}