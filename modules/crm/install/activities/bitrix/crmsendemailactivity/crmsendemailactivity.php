<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Mail;

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
		$crmImap = $fromInfo['crmImap'];
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

			if (strpos($messageHtml, '</html>') === false)
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
				'IS_BATCH_EMAIL'  => false,
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

		$outgoingParams = array(
			'CHARSET'      => SITE_CHARSET,
			'CONTENT_TYPE' => 'html',
			'ATTACHMENT'   => $attachments,
			'TO'           => join(', ', $rcpt),
			'SUBJECT'      => $outgoingSubject,
			'BODY'         => $outgoingBody,
			'HEADER'       => array(
				'From'       => $fromEncoded ?: $fromEmail,
				'Reply-To'   => $reply ?: $fromEmail,
				'Message-Id' => $messageId,
			),
			'TRACK_READ' => array(
				'MODULE_ID' => 'crm',
				'FIELDS'    => array('urn' => $urn),
				'URL_PAGE' => '/pub/mail/read.php',
			),
			'TRACK_CLICK' => array(
				'MODULE_ID' => 'crm',
				'FIELDS'    => array('urn' => $urn),
				'URL_PAGE' => '/pub/mail/click.php',
			),
			'CONTEXT' => $context,
		);

		$sendResult = Mail\Mail::send($outgoingParams);

		if (!$sendResult)
		{
			$this->writeError(GetMessage('CRM_SEMA_EMAIL_CREATION_CANCELED'), $userId);
			\CCrmActivity::delete($id);
			return CBPActivityExecutionStatus::Closed;
		}

		if (!empty($userImap['need_sync']) || !empty($crmImap['need_sync']))
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

			if (!empty($userImap['need_sync']))
				\Bitrix\Mail\Helper::addImapMessage($userImap, (string) $outgoing, $err);
			if (!empty($crmImap['need_sync']))
				\Bitrix\Mail\Helper::addImapMessage($crmImap, (string) $outgoing, $err);
		}

		// Try add event to entity
		$CCrmEvent = new CCrmEvent();

		$eventText  = '';
		$eventText .= GetMessage('CRM_SEMA_EMAIL_SUBJECT').': '.$subject."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_FROM').': '.$from."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_TO').': '.$to."\n\r\n\r";
		$eventText .= $messageHtml;
		// Register event only for owner
		$CCrmEvent->Add(
			array(
				'ENTITY' => array(
					$ownerId => array(
						'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($ownerTypeId),
						'ENTITY_ID' => $ownerId
					)
				),
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
		$userImap = $crmImap = $defaultFrom = null;
		$injectUrn = false;
		$reply = '';
		$from = trim((string)$from);
		$fromEmail = '';
		$fromEncoded = '';
		$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail', ''));

		if (CModule::includeModule('mail'))
		{
			$res = \Bitrix\Mail\MailboxTable::getList(array(
				'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET'),
				'filter' => array(
					'=LID'    => SITE_ID,
					'=ACTIVE' => 'Y',
					array(
						'LOGIC'    => 'OR',
						'=USER_ID' => $userId,
						array(
							'USER_ID'      => 0,
							'=SERVER_TYPE' => 'imap',
						),
					),
				),
				'order'  => array('ID' => 'DESC'),
			));

			while ($mailbox = $res->fetch())
			{
				if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', (array)$mailbox['OPTIONS']['flags']))
				{
					$mailbox['EMAIL_FROM'] = null;
					if (check_email($mailbox['NAME'], true))
						$mailbox['EMAIL_FROM'] = strtolower($mailbox['NAME']);
					elseif (check_email($mailbox['LOGIN'], true))
						$mailbox['EMAIL_FROM'] = strtolower($mailbox['LOGIN']);

					if ($mailbox['USER_ID'] > 0)
						$userImap = $mailbox;
					else
						$crmImap = $mailbox;
				}
			}

			$defaultFrom = \Bitrix\Mail\User::getDefaultEmailFrom();
		}

		if ($from === '')
		{
			if (!empty($userImap))
			{
				$from = $userImap['EMAIL_FROM'] ?: $defaultFrom;
				$userImap['need_sync'] = true;
			}
			elseif (!empty($crmImap))
			{
				$from = $crmImap['EMAIL_FROM'] ?: $defaultFrom;
				$crmImap['need_sync'] = true;
			}
			else
			{
				$from = $crmEmail;
			}

			if ($from === '')
				$from = CUserOptions::GetOption('crm', 'activity_email_addresser', '', $userId);

			if ($from === '')
				$from = $defaultFrom;

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
			else
			{
				if (!empty($userImap['EMAIL_FROM']) && $userImap['EMAIL_FROM'] === $fromEmail)
					$userImap['need_sync'] = true;
				if (!empty($crmImap['EMAIL_FROM']) && $crmImap['EMAIL_FROM'] === $fromEmail)
					$crmImap['need_sync'] = true;

				if (empty($userImap['need_sync']) && empty($crmImap['need_sync']))
				{
					if ($crmEmail == '' || $crmEmail != $fromEmail)
					{
						if (!empty($userImap['EMAIL_FROM']))
							$reply = $fromEmail . ', ' . $userImap['EMAIL_FROM'];
						else if (!empty($crmImap['EMAIL_FROM']))
							$reply = $fromEmail . ', ' . $crmImap['EMAIL_FROM'];
						else if ($crmEmail != '')
							$reply = $fromEmail . ', ' . $crmEmail;
					}

					$injectUrn = true;
				}
			}

		}

		if (empty($from))
		{
			return false;
		}

		return array(
			'from' => $from,
			'fromEmail' => $fromEmail,
			'userImap' => $userImap,
			'crmImap' => $crmImap,
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
			$fromEmail = strtolower(trim($matches[2]));

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

	private static function getMailboxes()
	{
		$mailboxes = array();

		ob_start(); //prevent error showing when component is not found
		CBitrixComponent::includeComponentClass('bitrix:main.mail.confirm');
		ob_end_clean();

		if (
			class_exists('MainMailConfirmComponent')
			&& method_exists('MainMailConfirmComponent', 'prepareMailboxes')
		)
		{
			$mailboxes = (array)MainMailConfirmComponent::prepareMailboxes();
		}

		return $mailboxes;
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

		if (empty($arTestProperties["MessageText"]))
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
			)
		);

		$mailboxes = static::getMailboxes();

		if (!empty($mailboxes))
		{
			//deprecated
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
		}
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
		if (strpos($text, 'base64,') === 0)
		{
			$text = substr($text, 7);
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