<?php

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integration\Channel;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Localization\Loc;

if(!IsModuleInstalled('bitrix24'))
{
	IncludeModuleLangFile(__FILE__);
}
else
{
	// HACK: try to take site language instead of user language
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite->Fetch();
	IncludeModuleLangFile(__FILE__, isset($arSite['LANGUAGE_ID']) ? $arSite['LANGUAGE_ID'] : false);
}

class CCrmEMail
{
	public static function OnGetFilterList()
	{
		return array(
			'ID'					=>	'crm',
			'NAME'					=>	GetMessage('CRM_ADD_MESSAGE'),
			'ACTION_INTERFACE'		=>	$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/mail/action.php',
			'PREPARE_RESULT_FUNC'	=>	Array('CCrmEMail', 'PrepareVars'),
			'CONDITION_FUNC'		=>	Array('CCrmEMail', 'EmailMessageCheck'),
			'ACTION_FUNC'			=>	Array('CCrmEMail', 'EmailMessageAdd')
		);
	}

	public static function onGetFilterListImap()
	{
		return array(
			'ID'          => 'crm_imap',
			'NAME'        => GetMessage('CRM_ADD_MESSAGE'),
			'ACTION_FUNC' => Array('CCrmEMail', 'imapEmailMessageAdd'),
			'LAZY_ATTACHMENTS' => true,
			'SANITIZE_ON_VIEW' => true,
		);
	}

	private static function FindUserIDByEmail($email)
	{
		$email = trim(strval($email));
		if($email === '')
		{
			return 0;
		}

		$dbUsers = CUser::GetList(
			'ID',
			'ASC',
			array('=EMAIL' => $email),
			array(
				'FIELDS' => array('ID'),
				'NAV_PARAMS' => array('nTopCount' => 1)
			)
		);

		$arUser = $dbUsers ? $dbUsers->Fetch() : null;
		return $arUser ? intval($arUser['ID']) : 0;
	}
	private static function PrepareEntityKey($entityTypeID, $entityID)
	{
		return "{$entityTypeID}-{$entityID}";
	}
	private static function CreateBinding($entityTypeID, $entityID)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		return array(
			'ID' => $entityID,
			'TYPE_ID' => $entityTypeID,
			'TYPE_NAME' => CCrmOwnerType::ResolveName($entityTypeID)
		);
	}
	private static function CreateComm($entityTypeID, $entityID, $value)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);
		$value = strval($value);

		return array(
			'ENTITY_ID' => $entityID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'VALUE' => $value,
			'TYPE' => 'EMAIL'
		);
	}
	private static function ExtractCommsFromEmails($emails, $arIgnored = array())
	{
		if(!is_array($emails))
		{
			$emails = array($emails);
		}

		if(count($emails) === 0)
		{
			return array();
		}

		$arFilter = array();
		foreach ($emails as $email)
		{
			//Process valid emails only
			if(!($email !== '' && CCrmMailHelper::IsEmail($email)))
			{
				continue;
			}

			if(in_array($email, $arIgnored, true))
			{
				continue;
			}

			$arFilter[] = array('RAW_VALUE' => $email);
		}

		if(empty($arFilter))
		{
			return array();
		}

		$dbFieldMulti = CCrmFieldMulti::GetList(
			array(),
			array(
				'ENTITY_ID' => 'LEAD|CONTACT|COMPANY',
				'TYPE_ID' => 'EMAIL',
				'FILTER' => $arFilter
			)
		);

		$result = array();
		while($arFieldMulti = $dbFieldMulti->Fetch())
		{
			$entityTypeID = CCrmOwnerType::ResolveID($arFieldMulti['ENTITY_ID']);
			$entityID = intval($arFieldMulti['ELEMENT_ID']);
			$result[] = self::CreateComm($entityTypeID, $entityID, $arFieldMulti['VALUE']);
		}
		return $result;
	}
	private static function ConvertCommsToBindings(&$arCommData)
	{
		$result = array();
		foreach($arCommData as &$arComm)
		{
			$entityTypeID = $arComm['ENTITY_TYPE_ID'];
			$entityID = $arComm['ENTITY_ID'];
			// Key to avoid dublicated entities
			$key = self::PrepareEntityKey($entityTypeID, $entityID);
			if(isset($result[$key]))
			{
				continue;
			}
			$result[$key] = self::CreateBinding($entityTypeID, $entityID);
		}
		unset($arComm);

		return $result;
	}
	private static function ExtractEmailsFromBody($body)
	{
		$body = strval($body);

		$out = array();
		if (!preg_match_all('/\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $body, $out))
		{
			return array();
		}

		$result = array();
		foreach($out[0] as $email)
		{
			$email = mb_strtolower($email);
			if (!in_array($email, $result, true))
			{
				$result[] = $email;
			}
		}

		return $result;
	}
	private static function GetResponsibleID(&$entityFields)
	{
		$result = isset($entityFields['ASSIGNED_BY_ID']) ? intval($entityFields['ASSIGNED_BY_ID']) : 0;
		if($result <= 0)
		{
			$result = isset($entityFields['CREATED_BY_ID']) ? intval($entityFields['CREATED_BY_ID']) : 0;
		}
		return $result;
	}
	private static function GetEntity($entityTypeID, $entityID, $select = array())
	{

		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		$dbRes = null;
		if($entityTypeID === CCrmOwnerType::Company)
		{
			$dbRes = CCrmCompany::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			$dbRes = CCrmContact::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Lead)
		{
			$dbRes = CCrmLead::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			$dbRes = CCrmDeal::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}

		return $dbRes ? $dbRes->Fetch() : null;
	}
	private static function IsEntityExists($entityTypeID, $entityID)
	{
		$arFields = self::GetEntity(
			$entityTypeID,
			$entityID,
			array('ID')
		);

		return is_array($arFields);
	}
	private static function GetDefaultResponsibleID($entityTypeID)
	{
		$entityTypeID = (int)$entityTypeID;
		if($entityTypeID === CCrmOwnerType::Lead)
		{
			return (int)COption::GetOptionString('crm', 'email_lead_responsible_id', 0);
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			return (int)COption::GetOptionString('crm', 'email_contact_responsible_id', 0);
		}
		return 0;
	}
	private static function ResolveResponsibleID($entityTypeID, $entityID)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		$arFields = self::GetEntity(
			$entityTypeID,
			$entityID,
			array('ASSIGNED_BY_ID', 'CREATED_BY_ID')
		);

		return $arFields ? self::GetResponsibleID($arFields) : 0;
	}
	private static function TryImportVCard(&$fileData, $responsible = null)
	{
		$CCrmVCard = new CCrmVCard();
		$arContact = $CCrmVCard->ReadCard(false, $fileData);

		if (empty($arContact['NAME']) && empty($arContact['LAST_NAME']))
		{
			return false;
		}

		$arFilter = array();
		if (!empty($arContact['NAME']))
		{
			$arFilter['NAME'] = $arContact['NAME'];
		}
		if (!empty($arContact['LAST_NAME']))
		{
			$arFilter['LAST_NAME'] = $arContact['LAST_NAME'];
		}
		if (!empty($arContact['SECOND_NAME']))
		{
			$arFilter['SECOND_NAME'] = $arContact['SECOND_NAME'];
		}

		$arFilter['CHECK_PERMISSIONS'] = 'N';

		$dbContact = CCrmContact::GetListEx(array(), $arFilter, false, false, array('ID'));
		if ($dbContact->Fetch())
		{
			return false;
		}

		$arContact['SOURCE_ID'] = 'EMAIL';
		if (!empty($arContact['COMPANY_TITLE']))
		{
			$dbCompany = CCrmCompany::GetListEx(
				array(),
				array(
					'TITLE' => $arContact['COMPANY_TITLE'],
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				false,
				array('ID')
			);

			if (($arCompany = $dbCompany->Fetch()) !== false)
			{
				$arContact['COMPANY_ID'] = $arCompany['ID'];
			}
			else
			{
				if(!empty($arContact['COMMENTS']))
				{
					$arContact['COMMENTS'] .= PHP_EOL;
				}
				$arContact['COMMENTS'] .=
					GetMessage('CRM_MAIL_COMPANY_NAME', array('%TITLE%' => $arContact['COMPANY_TITLE']));
			}
		}

		if ($responsible <= 0)
		{
			$responsible = self::getDefaultResponsibleId(CCrmOwnerType::Contact);
			if ($responsible <= 0)
				$responsible = self::getDefaultResponsibleId(CCrmOwnerType::Lead);
		}

		if ($responsible > 0)
			$arContact['CREATED_BY_ID'] = $arContact['MODIFY_BY_ID'] = $arContact['ASSIGNED_BY_ID'] = $responsible;

		$CCrmContact = new CCrmContact(false);
		$CCrmContact->Add(
			$arContact,
			true,
			array('DISABLE_USER_FIELD_CHECK' => true)
		);

		return true;
	}
	protected static function ExtractPostingID(&$arMessageFields)
	{
		$header = isset($arMessageFields['HEADER']) ? $arMessageFields['HEADER'] : '';
		$match = array();
		return preg_match('/^X-Bitrix-Posting:\s*(?P<id>[0-9]+)\s*$/im', $header, $match) === 1
			? (isset($match['id']) ? intval($match['id']) : 0)
			: 0;
	}

	protected static function getAdminsList()
	{
		static $adminList;

		if (is_null($adminList))
		{
			$adminList = array();

			$res = \CUser::getList(
				'', '',
				array('GROUPS_ID' => 1),
				array('FIELDS' => array('ID', 'ACTIVE'))
			);
			while ($admin = $res->fetch())
				$adminList[] = $admin;

			usort($adminList, function($a, $b)
			{
				if ($a['ACTIVE'] == 'Y' xor $b['ACTIVE'] == 'Y')
					return $a['ACTIVE'] == 'Y' ? -1 : 1;

				return $a['ID']-$b['ID'];
			});
		}

		return $adminList;
	}

	protected static function log($tag, $message, array $data = null)
	{
		//temporary turned off
		return;

		$logEntry = "$tag: $message";

		if (!empty($data))
		{
			$logEntry .= "\r\n" . Bitrix\Main\Web\Json::encode($data);
		}

		addMessage2Log($logEntry, 'crm', 3);
	}

	public static function imapEmailMessageAdd($msgFields, $actionVars = null, &$error = null)
	{
		global $DB;

		$error = null;

		if (!\CModule::includeModule('crm'))
			return false;

		$eventTag = sprintf('%x%x', time(), rand(0, 0xffffffff));

		$messageId = isset($msgFields['ID']) ? intval($msgFields['ID']) : 0;
		$mailboxId = isset($msgFields['MAILBOX_ID']) ? intval($msgFields['MAILBOX_ID']) : 0;

		if (empty($mailboxId))
		{
			static::log(
				$eventTag,
				'CCrmEmail: empty MAILBOX_ID',
				array(
					'ID' => $msgFields['ID'],
					'MAILBOX_ID' => $msgFields['MAILBOX_ID'],
				)
			);

			return false;
		}

		$mailbox = \CMailBox::getById($mailboxId)->fetch();

		if (empty($mailbox))
		{
			static::log(
				$eventTag,
				'CCrmEmail: empty mailbox',
				array(
					'ID' => $msgFields['ID'],
					'MAILBOX_ID' => $msgFields['MAILBOX_ID'],
				)
			);

			return false;
		}

		$crmNewEntityOptionName = empty($msgFields['IS_OUTCOME']) ? 'crm_new_entity_in' : 'crm_new_entity_out';
		static::log(
			$eventTag,
			'CCrmEmail: IMAP email message received',
			array(
				'ID' => $msgFields['ID'],
				'MAILBOX_ID' => $msgFields['MAILBOX_ID'],
				'MSG_HASH' => $msgFields['MSG_HASH'],
				'OPTIONS' => array_filter(array(
					'flags' => $mailbox['OPTIONS']['flags'],
					'crm_sync_from' => $mailbox['OPTIONS']['crm_sync_from'],
					//'crm_lead_resp' => $mailbox['OPTIONS']['crm_lead_resp'],
					//'crm_new_lead_for' => $mailbox['OPTIONS']['crm_new_lead_for'],
					$crmNewEntityOptionName => $mailbox['OPTIONS'][$crmNewEntityOptionName],
				)),
				'TAGS' => array_keys(array_filter(array(
					'IS_INCOME' => empty($msgFields['IS_OUTCOME']),
					'IS_OUTCOME' => !empty($msgFields['IS_OUTCOME']),
					'IS_DRAFT' => !empty($msgFields['IS_DRAFT']),
					'IS_TRASH' => !empty($msgFields['IS_TRASH']),
					'IS_SPAM' => !empty($msgFields['IS_SPAM']),
					'__forced' => !empty($msgFields['__forced']),
				))),
			)
		);

		$isForced = !empty($msgFields['__forced']);

		if (!$isForced && !empty($mailbox['OPTIONS']['crm_sync_from']))
		{
			$timestamp = makeTimestamp($msgFields['FIELD_DATE'], FORMAT_DATETIME) - \CTimeZone::getOffset();
			if ($timestamp < (int) $mailbox['OPTIONS']['crm_sync_from'])
			{
				static::log($eventTag, 'CCrmEmail: too old');

				return false;
			}
		}

		$mailbox['__email'] = '';
		if (check_email($mailbox['NAME'], true))
			$mailbox['__email'] = $mailbox['NAME'];
		else if (check_email($mailbox['LOGIN'], true))
			$mailbox['__email'] = $mailbox['LOGIN'];

		$publicBindings = false;
		$denyNewEntityIn = false;
		$denyNewEntityOut = false;
		$denyNewContact = false;

		if (!empty($mailbox['OPTIONS']['flags']) && is_array($mailbox['OPTIONS']['flags']))
		{
			$publicBindings = in_array('crm_public_bind', $mailbox['OPTIONS']['flags']);
			$denyNewContact = in_array('crm_deny_new_contact', $mailbox['OPTIONS']['flags']);

			if (!$isForced)
			{
				$denyNewEntityIn = in_array('crm_deny_new_lead', $mailbox['OPTIONS']['flags']);
				$denyNewEntityIn = $denyNewEntityIn || in_array('crm_deny_entity_in', $mailbox['OPTIONS']['flags']);
				$denyNewEntityOut = in_array('crm_deny_new_lead', $mailbox['OPTIONS']['flags']);
				$denyNewEntityOut = $denyNewEntityOut || in_array('crm_deny_entity_out', $mailbox['OPTIONS']['flags']);
			}
		}

		$isIncome = empty($msgFields['IS_OUTCOME']);
		$isDraft = !empty($msgFields['IS_DRAFT']);
		$isTrash = !empty($msgFields['IS_TRASH']);
		$isSpam = !empty($msgFields['IS_SPAM']);
		$isUnseen = empty($msgFields['IS_SEEN']);

		if (!$isForced and $isDraft || $isTrash || $isSpam)
		{
			static::log($eventTag, 'CCrmEmail: draft|trash|spam');

			return false;
		}

		$userId = 0;

		$ownerTypeId = 0;
		$ownerId     = 0;

		$parentId = 0;

		$msgId     = isset($msgFields['MSG_ID']) ? $msgFields['MSG_ID'] : '';
		$inReplyTo = isset($msgFields['IN_REPLY_TO']) ? $msgFields['IN_REPLY_TO'] : '';

		$from    = isset($msgFields['FIELD_FROM']) ? $msgFields['FIELD_FROM'] : '';
		$replyTo = isset($msgFields['FIELD_REPLY_TO']) ? $msgFields['FIELD_REPLY_TO'] : '';

		$senderAddress = array();
		$sender = array();
		foreach (array_merge(explode(',', $replyTo), explode(',', $from)) as $item)
		{
			if (trim($item))
			{
				$address = new \Bitrix\Main\Mail\Address($item);
				if ($address->validate() && !in_array($address->getEmail(), $sender))
				{
					$senderAddress[] = $address;
					$sender[] = $address->getEmail();
				}
			}
		}

		$to  = isset($msgFields['FIELD_TO']) ? $msgFields['FIELD_TO'] : '';
		$cc  = isset($msgFields['FIELD_CC']) ? $msgFields['FIELD_CC'] : '';
		$bcc = isset($msgFields['FIELD_BCC']) ? $msgFields['FIELD_BCC'] : '';

		$rcptAddress = array();
		$rcpt = array();
		foreach (array_merge(explode(',', $to), explode(',', $cc), explode(',', $bcc)) as $item)
		{
			if (trim($item))
			{
				$address = new \Bitrix\Main\Mail\Address($item);
				if ($address->validate() && !in_array($address->getEmail(), $rcpt))
				{
					$rcptAddress[] = $address;
					$rcpt[] = $address->getEmail();
				}
			}
		}

		$subject   = trim($msgFields['SUBJECT']) ?: getMessage('CRM_EMAIL_DEFAULT_SUBJECT');

		$emailFacility = new Bitrix\Crm\Activity\EmailFacility();

		if (!$isForced && $isIncome && preg_match('/\nX-EVENT_NAME:/i', $msgFields['HEADER']))
		{
			$defaultEmailFrom = \Bitrix\Main\Config\Option::get('main', 'email_from', 'admin@'.$GLOBALS['SERVER_NAME']);
			$defaultEmailFrom = mb_strtolower(trim($defaultEmailFrom));

			foreach ($sender as $item)
			{
				if (mb_strtolower(trim($item)) == $defaultEmailFrom)
				{
					static::log($eventTag, 'CCrmEmail: system email');

					return false;
				}
			}
		}

		// @TODO: killmail

		if (!empty($msgId))
		{
			if (!$isIncome && preg_match('/<crm\.activity\.((\d+)-[0-9a-z]+)@[^>]+>/i', sprintf('<%s>', $msgId), $matches))
			{
				$matchActivity = \CCrmActivity::getList(
					array(),
					array(
						'ID' => $matches[2],
						'CHECK_PERMISSIONS' => 'N',
					),
					false,
					false,
					array('ID', 'URN', 'UF_MAIL_MESSAGE')
				)->fetch();
				if ($matchActivity && mb_strtolower($matchActivity['URN']) == mb_strtolower($matches[1]))
				{
					\CCrmActivity::update(
						$matchActivity['ID'],
						array(
							'UF_MAIL_MESSAGE' => $messageId,
						),
						false,
						false
					);

					static::log($eventTag, 'CCrmEmail: matches outgoing');

					return true;
				}
			}
		}

		// skip employees
		$employeesEmails = array();
		{
			$filter = array(
				'=ACTIVE' => 'Y',
				'=EMAIL' => $isIncome ? $sender : $rcpt,
				'IS_REAL_USER' => 'Y',
			);

			$res = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID', 'EMAIL', 'UF_DEPARTMENT'),
				'filter' => $filter,
			));

			if ($isIncome)
			{
				while ($employee = $res->fetch())
				{
					$departments = empty($employee['UF_DEPARTMENT']) ? array() : (array) $employee['UF_DEPARTMENT'];
					if (reset($departments) > 0)
					{
						static::log(
							$eventTag,
							'CCrmEmail: from employee',
							array(
								'USER_ID' => $employee['ID'],
								'USER_EMAIL' => $employee['EMAIL'],
							)
						);

						$error = new \Bitrix\Main\Error(Loc::getMessage('CRM_EMAIL_IMAP_ERROR_EMPLOYE_IN'));
						return false;
					}
				}

				if (CModule::includeModule('mail'))
				{
					// @TODO: index
					$employee = \Bitrix\Mail\MailboxTable::getList(array(
						'select' => array('ID', 'EMAIL', 'NAME', 'LOGIN', 'USER_ID'),
						'filter' => array(
							'=ACTIVE'  => 'Y',
							array(
								'LOGIC' => 'OR',
								'=EMAIL' => $sender,
								'=NAME'  => $sender,
								'=LOGIN' => $sender,
							),
						),
						'limit' => 1,
					))->fetch();

					if (!empty($employee))
					{
						static::log(
							$eventTag,
							'CCrmEmail: from employee',
							array(
								'USER_ID' => $employee['USER_ID'],
								'MAILBOX_ID' => $employee['ID'],
								'MAILBOX_EMAIL' => $employee['EMAIL'],
								'MAILBOX_NAME' => $employee['NAME'],
								'MAILBOX_LOGIN' => $employee['LOGIN'],
							)
						);

						$error = new \Bitrix\Main\Error(Loc::getMessage('CRM_EMAIL_IMAP_ERROR_EMPLOYE_IN'));
						return false;
					}
				}
			}
			else
			{
				$employees = array();

				while ($employee = $res->fetch())
				{
					$departments = empty($employee['UF_DEPARTMENT']) ? array() : (array) $employee['UF_DEPARTMENT'];
					if (reset($departments) > 0)
					{
						$employees[] = array(
							'USER_ID' => $employee['ID'],
							'USER_EMAIL' => $employee['EMAIL'],
						);
						$employeesEmails[] = $employee['EMAIL'];
					}
				}

				$employeesEmails = array_unique(array_map('mb_strtolower', $employeesEmails));

				if (count($employeesEmails) >= count($rcpt))
				{
					static::log(
						$eventTag,
						'CCrmEmail: to employees',
						array(
							'LIST' => $employees,
						)
					);

					$error = new \Bitrix\Main\Error(Loc::getMessage('CRM_EMAIL_IMAP_ERROR_EMPLOYE_OUT'));
					return false;
				}

				if (CModule::includeModule('mail'))
				{
					// @TODO: index
					$res = \Bitrix\Mail\MailboxTable::getList(array(
						'select' => array('ID', 'EMAIL',  'NAME', 'LOGIN', 'USER_ID'),
						'filter' => array(
							'=ACTIVE'  => 'Y',
							array(
								'LOGIC' => 'OR',
								'EMAIL' => $rcpt,
								'NAME'  => $rcpt,
								'LOGIN' => $rcpt,
							),
						),
					));

					$employees = array();

					while ($employee = $res->fetch())
					{
						$employees[] = array(
							'USER_ID' => $employee['USER_ID'],
							'MAILBOX_ID' => $employee['ID'],
							'MAILBOX_EMAIL' => $employee['EMAIL'],
							'MAILBOX_NAME' => $employee['NAME'],
							'MAILBOX_LOGIN' => $employee['LOGIN'],
						);
						$employeesEmails[] = (check_email($employee['EMAIL'], true) ? $employee['EMAIL'] : (check_email($employee['NAME'], true) ? $employee['NAME'] : $employee['LOGIN']));
					}

					$employeesEmails = array_unique(array_map('mb_strtolower', $employeesEmails));

					if (count($employeesEmails) >= count($rcpt))
					{
						static::log(
							$eventTag,
							'CCrmEmail: to employees',
							array(
								'LIST' => $employees,
							)
						);

						$error = new \Bitrix\Main\Error(Loc::getMessage('CRM_EMAIL_IMAP_ERROR_EMPLOYE_OUT'));
						return false;
					}
				}
			}
		}

		$mailboxOwnerId = (int)$mailbox['USER_ID'] ?? 0;

		// initialize responsible queue
		$respQueue = array();
		{
			$respOption = array_values(array_unique((array) $mailbox['OPTIONS']['crm_lead_resp']));
			if (empty($respOption) && $mailboxOwnerId > 0)
			{
				$respOption = array($mailboxOwnerId);
			}

			if (!empty($respOption))
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'ACTIVE'),
					'filter' => array(
						'@ID' => $respOption,
						'=ACTIVE' => 'Y',
					),
				));

				$respActive = array();

				while ($resp = $res->fetch())
				{
					$respQueue[] = $resp['ID'];

					if ($resp['ACTIVE'] == 'Y')
					{
						$respActive[] = $resp['ID'];
					}
				}

				if (!empty($respActive))
				{
					$respQueue = $respActive;
				}

				$respOrder = array_flip($respOption);
				usort(
					$respQueue,
					function ($a, $b) use (&$respOrder)
					{
						return isset($respOrder[$a], $respOrder[$b]) ? $respOrder[$a]-$respOrder[$b] : 0;
					}
				);

				if (count($respOption) > 0 && count($respActive) == count($respOption))
				{
					\Bitrix\Main\Config\Option::set('crm', 'email_resp_queue_ok_'.$mailboxId, 'Y');
				}
				else
				{
					$shouldNotify = \Bitrix\Main\Config\Option::get('crm', 'email_resp_queue_ok_' . $mailboxId, 'Y') == 'Y';
					if ($shouldNotify && \CModule::includeModule('im'))
					{
						\Bitrix\Main\Config\Option::set('crm', 'email_resp_queue_ok_' . $mailboxId, 'N');

						$configUrl = str_replace(
							'#id#',
							$mailboxId,
							\Bitrix\Main\Config\Option::get('intranet', 'path_mail_config', '/mail/', $mailbox['LID'])
						);
						$absoluteConfigUrl = CCrmUrlUtil::ToAbsoluteUrl($configUrl);

						$getMessageCallback = static function (string $url) use ($mailbox) {
							$code = 'CRM_EMAIL_BAD_RESP_QUEUE';
							$replace = [
								'#EMAIL#' => htmlspecialcharsbx($mailbox['NAME']),
								'#CONFIG_URL#' => htmlspecialcharsbx($url),
							];

							return static fn (?string $languageId = null) =>
								Loc::getMessage($code, $replace, $languageId)
							;
						};

						\CCrmNotifier::notify(
							$mailbox['USER_ID'],
							$getMessageCallback($configUrl),
							$getMessageCallback($absoluteConfigUrl),
							0,
							'email_resp_queue_ok_' . $mailboxId
						);
					}
				}
			}

			if (empty($respQueue))
			{
				$respQueue = array_column(self::getAdminsList(), 'ID') ?: array(1);
			}
		}

		$targetActivity = Bitrix\Crm\Activity\Provider\Email::getParentByEmail($msgFields);
		if (!empty($targetActivity))
		{
			static::log(
				$eventTag,
				'CCrmEmail: parent found',
				array(
					'ACTIVITY_ID' => $targetActivity['ID'],
				)
			);

			$parentId = $targetActivity['ID'];

			$isForced = true;

			switch ($targetActivity['OWNER_TYPE_ID'])
			{
				case \CCrmOwnerType::Deal:
					$owner = \Bitrix\Crm\DealTable::getList(array(
						'select' => array('ID', 'ASSIGNED_BY_ID'),
						'filter' => array(
							'=ID' => $targetActivity['OWNER_ID'],
							$publicBindings ? array() : array('@ASSIGNED_BY_ID' => $respQueue),
							'=STAGE_SEMANTIC_ID' => \Bitrix\Crm\PhaseSemantics::PROCESS,
						),
					))->fetch();
					break;
				case \CCrmOwnerType::Lead:
					$owner = \Bitrix\Crm\LeadTable::getList(array(
						'select' => array('ID', 'ASSIGNED_BY_ID'),
						'filter' => array(
							'=ID' => $targetActivity['OWNER_ID'],
							$publicBindings ? array() : array('@ASSIGNED_BY_ID' => $respQueue),
							'=STATUS_SEMANTIC_ID' => \Bitrix\Crm\PhaseSemantics::PROCESS,
						),
					))->fetch();
					break;
			}

			// @TODO: converted lead

			if (!empty($owner))
			{
				static::log(
					$eventTag,
					'CCrmEmail: parent owner is active lead|deal',
					array(
						'OWNER_TYPE_ID' => $targetActivity['OWNER_TYPE_ID'],
						'OWNER_ID' => $targetActivity['OWNER_ID'],
					)
				);

				$ownerTypeId = $targetActivity['OWNER_TYPE_ID'];
				$ownerId = $targetActivity['OWNER_ID'];
			}
		}

		$forceNewLead = false;
		if ($isIncome && empty($targetActivity)) // @TODO: do not check $targetActivity?
		{
			if (!empty($mailbox['OPTIONS']['crm_new_lead_for']) && is_array($mailbox['OPTIONS']['crm_new_lead_for']))
			{
				$matches = array_intersect(
					array_map('mb_strtolower', array_map('trim', $sender)),
					array_map('mb_strtolower', array_map('trim', $mailbox['OPTIONS']['crm_new_lead_for']))
				);

				if ($forceNewLead = (boolean) $matches)
				{
					static::log($eventTag, 'CCrmEmail: force new lead', $matches);
				}
			}
		}

		$rankingModifier = function ($ranking) use (&$respQueue)
		{
			$knownTypes = array(
				\CCrmOwnerType::Contact => 'CCrmContact',
				\CCrmOwnerType::Company => 'CCrmCompany',
				\CCrmOwnerType::Lead => 'CCrmLead',
			);

			$typeId = $ranking->getEntityTypeId();
			$list = array();

			if (array_key_exists($typeId, $knownTypes) && !empty($ranking->getRankedList()))
			{
				$res = $knownTypes[$typeId]::getListEx(
					array(),
					array(
						'ID' => (array) $ranking->getRankedList(),
						'ASSIGNED_BY_ID' => $respQueue,
						'CHECK_PERMISSIONS' => 'N',
					),
					false,
					false,
					array('ID')
				);

				while ($item = $res->fetch())
				{
					$list[] = $item['ID'];
				}
			}

			$ranking->setModifiedList($list);
			$ranking->setRankedList($list);
		};

		$newEntityTypeId = \CCrmOwnerType::Lead;

		$filteredAddress = array_filter(
			$isIncome ? $senderAddress : $rcptAddress,
			function ($item) use (&$employeesEmails)
			{
				return !in_array($item->getEmail(), $employeesEmails);
			}
		);

		$trace = Tracking\Trace::create()->addChannel(
			new Tracking\Channel\Mail(!empty($rcptAddress) ?
				reset($rcptAddress)->getEmail() : null
			)
		);

		if ($forceNewLead)
		{
			$commAddress = $filteredAddress;

			$facility = new \Bitrix\Crm\EntityManageFacility();
			$facility->setDirection($isIncome ? $facility::DIRECTION_INCOMING : $facility::DIRECTION_OUTGOING);
			$facility->setTrace($trace);

			if ($isForced)
			{
				$facility->getSelector()->disableExclusionChecking();
			}
		}
		else
		{
			$commAddress = array();
			foreach ($filteredAddress as $item)
			{
				$itemSelector = new \Bitrix\Crm\Integrity\ActualEntitySelector();
				$itemSelector->appendEmailCriterion($item->getEmail());

				if (!$publicBindings)
				{
					$itemSelector->getRanking()->addModifier($rankingModifier);
				}

				if ($isForced)
				{
					$itemSelector->disableExclusionChecking();
				}

				$itemSelector->search();

				if ($itemSelector->hasExclusions())
				{
					if ($isIncome)
					{
						$selector = $itemSelector;

						break;
					}
					else
					{
						continue;
					}
				}

				if (empty($firstSelector))
				{
					$firstSelector = $itemSelector;
				}

				if ($itemSelector->hasEntities())
				{
					if (empty($selector))
					{
						$selector = $itemSelector;
						array_unshift($commAddress, $item);
					}
				}
				else
				{
					$commAddress[] = $item;
				}
			}

			if (empty($selector))
			{
				if (empty($firstSelector))
				{
					return false;
				}

				$selector = $firstSelector;
			}
			else
			{
				if (!$isIncome)
				{
					$commAddress = array();
				}
			}

			$facility = new \Bitrix\Crm\EntityManageFacility($selector);
			$facility->setDirection($isIncome ? $facility::DIRECTION_INCOMING : $facility::DIRECTION_OUTGOING);
			$facility->setTrace($trace);

			$emailFacility->setBindings($facility->getActivityBindings());

			$criterions = $selector->getCriteria();
			static::log(
				$eventTag,
				'CCrmEmail: search results',
				array(
					'SEARCH_VALUE' => reset($criterions)->getValue(),
					'IS_EXCLUSION' => $selector->hasExclusions(),
					'LIST' => $facility->getActivityBindings(),
				)
			);

			if ($parentId > 0 && ($ownerTypeId > 0 && $ownerId > 0))
			{
				$cantCreate = true;

				$emailFacility->setOwner($ownerTypeId, $ownerId);
				$emailFacility->setBindings(array_filter(
					$emailFacility->getBindings(),
					function ($item)
					{
						return !in_array($item['OWNER_TYPE_ID'], array(\CCrmOwnerType::Deal, \CCrmOwnerType::Lead));
					}
				));
			}
			else
			{
				$cantCreate = $isIncome
					? ($denyNewEntityIn && empty($emailFacility->getBindings()))
					: ($denyNewEntityOut || !empty($emailFacility->getBindings()));
			}

			if ($cantCreate)
			{
				$facility->setRegisterMode($facility::REGISTER_MODE_ONLY_UPDATE);
			}

			$newEntityType = $mailbox['OPTIONS'][$isIncome ? 'crm_new_entity_in' : 'crm_new_entity_out'];
			if (\CCrmOwnerType::ContactName == $newEntityType && empty($emailFacility->getBindings()))
			{
				$newEntityTypeId = \CCrmOwnerType::Contact;
			}
		}

		if (!empty($emailFacility->getBindings()))
		{
			$userId = $emailFacility->getOwnerResponsibleId();
		}
		else if ($facility->canAddEntity($newEntityTypeId))
		{
			$luckyOne = \Bitrix\Main\Config\Option::get('crm', 'last_resp_' . $mailboxId, -1) + 1;
			if ($luckyOne > count($respQueue) - 1)
			{
				$luckyOne = 0;
			}

			\Bitrix\Main\Config\Option::set('crm', 'last_resp_' . $mailboxId, $luckyOne);

			$userId = $respQueue[$luckyOne];
		}
		else
		{
			return false;
		}

		$contactFields = array();

		$contactTypes = \CCrmStatus::getStatusList('CONTACT_TYPE');
		if (isset($contactTypes['CLIENT']))
		{
			$contactFields['TYPE_ID'] = 'CLIENT';
		}
		else if (isset($contactTypes['OTHER']))
		{
			$contactFields['TYPE_ID'] = 'OTHER';
		}

		$leadFields = array(
			'COMPANY_TITLE' => \CCrmCompany::getDefaultTitle(),
		);

		if (trim($msgFields['SUBJECT']))
		{
			$leadFields['TITLE'] = trim($msgFields['SUBJECT']);
		}
		else
		{
			$leadFields['TITLE'] = getMessage(
				($isIncome ? 'CRM_MAIL_LEAD_FROM_EMAIL_TITLE' : 'CRM_MAIL_LEAD_FROM_USER_EMAIL_TITLE'),
				array('%SENDER%' => $replyTo ?: $from)
			);
		}

		$entityFields = array(
			'COMMENTS' => htmlspecialcharsbx($subject),
			'ORIGINATOR_ID' => 'email-tracker',
			'ORIGIN_ID' => $mailboxId,
		);

		$sourceList = \CCrmStatus::getStatusList('SOURCE');
		$sourceId   = $mailbox['OPTIONS']['crm_lead_source'];
		if (empty($sourceId) || !isset($sourceList[$sourceId]))
		{
			if (isset($sourceList['EMAIL']))
			{
				$sourceId = 'EMAIL';
			}
			else if (isset($sourceList['OTHER']))
			{
				$sourceId = 'OTHER';
			}
		}

		if ($sourceId != '')
			$entityFields['SOURCE_ID'] = $sourceId;

		if (!empty($commAddress))
		{
			$entityFields['FM'] = array('EMAIL' => array());
			foreach ($commAddress as $i => $item)
			{
				$entityFields['FM']['EMAIL'][sprintf('n%u', $i+1)] = array(
					'VALUE_TYPE' => 'WORK',
					'VALUE'      => $item->getEmail(),
				);

				if (empty($entityFields['NAME']) && !empty($item->getName()))
				{
					$entityFields['NAME'] = $item->getName();
				}
			}

			if (empty($entityFields['NAME']))
			{
				$entityFields['NAME'] = reset($commAddress)->getEmail();
			}
		}

		$entitiesFields = array(
			\CCrmOwnerType::Lead => $leadFields + $entityFields,
			\CCrmOwnerType::Contact => $contactFields + $entityFields,
		);

		// @TODO: update lead

		$facility->registerTouch(
			$newEntityTypeId,
			$entitiesFields,
			true,
			array(
				'CURRENT_USER' => $userId,
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
			)
		);

		if ($facility->getRegisteredId() > 0)
		{
			static::log(
				$eventTag,
				'CCrmEmail: created entity',
				array(
					'ENTITY_TYPE' => \CCrmOwnerType::resolveName($facility->getRegisteredTypeId()),
					'ENTITY_TYPE_ID' => $facility->getRegisteredTypeId(),
					'ENTITY_ID' => $facility->getRegisteredId(),
				)
			);

			$emailFacility->setBindings($facility->getActivityBindings(), true);

			$channelTrackerParams = array(
				'ORIGIN_ID' => sprintf('%u|%u', $mailbox['USER_ID'], $mailbox['ID'])
			);

			if ($facility->getRegisteredTypeId() == \CCrmOwnerType::Lead)
			{
				Channel\EmailTracker::getInstance()->registerLead($facility->getRegisteredId(), $channelTrackerParams);
			}
			else if ($facility->getRegisteredTypeId() == \CCrmOwnerType::Deal)
			{
				Channel\EmailTracker::getInstance()->registerDeal($facility->getRegisteredId(), $channelTrackerParams);
			}
		}

		if (empty($emailFacility->getBindings()))
		{
			return false;
		}

		$ownerTypeId = $emailFacility->getOwnerTypeId();
		$ownerId     = $emailFacility->getOwnerId();

		$attachmentMaxSizeMb = (int) \COption::getOptionString('crm', 'email_attachment_max_size', 24);
		$attachmentMaxSize = $attachmentMaxSizeMb > 0 ? $attachmentMaxSizeMb*1024*1024 : 0;

		// @TODO: update $msgFields
		if (\Bitrix\Mail\Helper\Message::ensureAttachments($msgFields) > 0)
		{
			if ($message = \CMailMessage::getById($messageId)->fetch())
			{
				$msgFields = $message + $msgFields;
			}
		}

		$body = isset($msgFields['BODY']) ? $msgFields['BODY'] : '';
		$body_html = isset($msgFields['BODY_HTML']) ? $msgFields['BODY_HTML'] : '';

		$filesData = array();
		$bannedAttachments = array();
		$res = \CMailAttachment::getList(array(), array('MESSAGE_ID' => $messageId));
		while ($attachment = $res->fetch())
		{
			$attachment['FILE_NAME'] = str_replace("\0", '', $attachment['FILE_NAME']);

			if (getFileExtension(mb_strtolower($attachment['FILE_NAME'])) == 'vcf' && !$denyNewContact)
			{
				if ($attachment['FILE_ID'])
					$attachment['FILE_DATA'] = \CMailAttachment::getContents($attachment);
				self::tryImportVCard($attachment['FILE_DATA'], $userId);
			}

			$fileSize = isset($attachment['FILE_SIZE']) ? intval($attachment['FILE_SIZE']) : 0;
			if ($fileSize <= 0)
				continue;

			if ($attachmentMaxSize > 0 && $fileSize > $attachmentMaxSize)
			{
				$bannedAttachments[] = array(
					'name' => $attachment['FILE_NAME'],
					'size' => $fileSize
				);

				continue;
			}

			if ($attachment['FILE_ID'] && empty($attachment['FILE_DATA']))
				$attachment['FILE_DATA'] = \CMailAttachment::getContents($attachment);

			$filesData[] = array(
				'name'      => $attachment['FILE_NAME'],
				'type'      => $attachment['CONTENT_TYPE'],
				'content'   => $attachment['FILE_DATA'],
				'MODULE_ID' => 'crm',
				'attachment_id' => $attachment['ID'],
			);
		}

		$eventBindings = array();
		foreach ($emailFacility->getBindings() as $item)
		{
			$eventBindings[] = array(
				'USER_ID'     => $userId,
				'ENTITY_TYPE' => \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']),
				'ENTITY_ID'   => $item['OWNER_ID'],
			);
		}

		$eventText  = '<b>'.getMessage('CRM_EMAIL_SUBJECT').'</b>: '.$subject.PHP_EOL;
		$eventText .= '<b>'.getMessage('CRM_EMAIL_FROM').'</b>: '.join(', ', $sender).PHP_EOL;
		$eventText .= '<b>'.getMessage('CRM_EMAIL_TO').'</b>: '.join(', ', $rcpt).PHP_EOL;

		if (!empty($bannedAttachments))
		{
			$eventText .= '<b>'.getMessage('CRM_EMAIL_BANNENED_ATTACHMENTS', array('%MAX_SIZE%' => $attachmentMaxSizeMb)).'</b>: ';
			foreach ($bannedAttachments as $attachmentInfo)
			{
				$eventText .= getMessage(
					'CRM_EMAIL_BANNENED_ATTACHMENT_INFO',
					array(
						'%NAME%' => $attachmentInfo['name'],
						'%SIZE%' => round($attachmentInfo['size']/1024/1024, 1)
					)
				);
			}

			$eventText .= PHP_EOL;
		}

		$eventText .= preg_replace('/(\r\n|\n|\r)+/', PHP_EOL, htmlspecialcharsbx($body));

		$crmEvent = new \CCrmEvent();
		$crmEvent->add(
			array(
				'USER_ID'      => $mailbox['USER_ID'],
				'ENTITY'       => $eventBindings,
				'ENTITY_TYPE'  => \CCrmOwnerType::resolveName($ownerTypeId),
				'ENTITY_ID'    => $ownerId,
				'EVENT_NAME'   => getMessage('CRM_EMAIL_GET_EMAIL'),
				'EVENT_TYPE'   => 2,
				'EVENT_TEXT_1' => $eventText,
				'FILES'        => $filesData,
			),
			false
		);

		$storageTypeId = \CCrmActivity::getDefaultStorageTypeID();
		$elementIds = array();
		foreach ($filesData as $i => $fileData)
		{
			$fileId = \CFile::saveFile($fileData, 'crm', true);
			if (!($fileId > 0))
				continue;

			$fileData = \CFile::getFileArray($fileId);
			if (empty($fileData))
				continue;

			if (trim($fileData['ORIGINAL_NAME']) == '')
				$fileData['ORIGINAL_NAME'] = $fileData['FILE_NAME'];
			$elementId = StorageManager::saveEmailAttachment(
				$fileData, $storageTypeId, '',
				array('USER_ID' => $userId)
			);
			if ($elementId > 0)
			{
				$elementIds[] = (int) $elementId;
				$filesData[$i]['element_id'] = (int) $elementId;
			}
		}

		if (!empty($body_html))
		{
			$checkInlineFiles = true;
			$descr = $body_html;
		}
		else
		{
			$descr = preg_replace('/\r\n|\n|\r/', '<br>', htmlspecialcharsbx($body));
		}

		$isIncomingChannel = false;
		if ($isIncome)
		{
			$direction = \CCrmActivityDirection::Incoming;
			$completed = $isUnseen ? 'N' : 'Y';
			$isIncomingChannel = \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled();
		}
		else
		{
			$direction = \CCrmActivityDirection::Outgoing;
			$completed = 'Y';
		}

		$currentUserOffset = \CTimeZone::getOffset();
		$userOffset = \CTimeZone::getOffset($userId);

		$nowTimestamp = time();

		$siteId = \Bitrix\Crm\Integration\Main\Site::getPortalSiteId();
		$datetime = convertTimeStamp($nowTimestamp + $currentUserOffset, 'FULL', $siteId);
		if (!empty($msgFields['FIELD_DATE']) && $DB->isDate($msgFields['FIELD_DATE'], FORMAT_DATETIME))
		{
			$datetime = $msgFields['FIELD_DATE'];
		}

		$deadlineTimestamp = strtotime('tomorrow') + $currentUserOffset - $userOffset;
		$deadline = convertTimeStamp($deadlineTimestamp, 'FULL', $siteId);
		if (CModule::includeModule('calendar'))
		{
			$calendarSettings = \CCalendar::getSettings();

			$workTimeEndHour = $calendarSettings['work_time_end'] > 0 ? $calendarSettings['work_time_end'] : 19;
			$dummyDeadline = new \Bitrix\Main\Type\DateTime();
			$dummyDeadline->setTime(
				$workTimeEndHour,
				0,
				$currentUserOffset - $userOffset
			);
			$deadlineTimestamp += $workTimeEndHour * 60 * 60; // work time end in tomorrow
			$deadline = convertTimeStamp($deadlineTimestamp, 'FULL', $siteId);

			if ($dummyDeadline->getTimestamp() > $nowTimestamp + $currentUserOffset)
			{
				$deadline = $dummyDeadline->format(\Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
			}
		}

		$activityFields = array(
			'OWNER_ID'             => $ownerId,
			'OWNER_TYPE_ID'        => $ownerTypeId,
			'BINDINGS'             => $emailFacility->getBindings(),
			'TYPE_ID'              => \CCrmActivityType::Email,
			'ASSOCIATED_ENTITY_ID' => 0,
			'PARENT_ID'            => $parentId,
			'SUBJECT'              => \Bitrix\Main\Text\Emoji::encode($subject),
			'START_TIME'           => (string) $datetime,
			'END_TIME'             => (string) $deadline,
			'COMPLETED'            => $completed,
			'AUTHOR_ID'            => $mailbox['USER_ID'],
			'RESPONSIBLE_ID'       => $userId,
			'EDITOR_ID' => $userId,
			'PRIORITY'             => \CCrmActivityPriority::Medium,
			'DESCRIPTION'          => \Bitrix\Main\Text\Emoji::encode($descr),
			'DESCRIPTION_TYPE'     => \CCrmContentType::Html,
			'DIRECTION'            => $direction,
			'LOCATION'             => '',
			'NOTIFY_TYPE'          => \CCrmActivityNotifyType::None,
			'STORAGE_TYPE_ID'      => $storageTypeId,
			'STORAGE_ELEMENT_IDS'  => $elementIds,
			'SETTINGS'             => array(
				'EMAIL_META' => array(
					'__email' => $mailbox['__email'],
					'from'    => $from,
					'replyTo' => $replyTo,
					'to'      => $to,
					'cc'      => $cc,
					'bcc'     => $bcc,
				),
				'SANITIZE_ON_VIEW' => (int)($msgFields['SANITIZE_ON_VIEW'] ?? 0),
			),
			'UF_MAIL_MESSAGE' => $messageId,
			'IS_INCOMING_CHANNEL' => $isIncomingChannel ? 'Y' : 'N',
		);

		if (!empty($isIncome ? $sender : $rcpt))
		{
			$subfilter = array(
				'LOGIC' => 'OR',
			);

			foreach ($activityFields['BINDINGS'] as $item)
			{
				$subfilter[] = array(
					'=ENTITY_ID'  => \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']),
					'=ELEMENT_ID' => $item['OWNER_ID'],
				);
			}

			$res = \Bitrix\Crm\FieldMultiTable::getList(array(
				'select' => array('ENTITY_ID', 'ELEMENT_ID', 'VALUE'),
				'group'  => array('ENTITY_ID', 'ELEMENT_ID', 'VALUE'),
				'filter' => array(
					$subfilter,
					'=TYPE_ID' => 'EMAIL',
					'@VALUE'   => $isIncome ? $sender : $rcpt,
				),
			));

			while ($item = $res->fetch())
			{
				$activityFields['COMMUNICATIONS'][] = array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::resolveId($item['ENTITY_ID']),
					'ENTITY_ID'      => $item['ELEMENT_ID'],
					'VALUE'          => $item['VALUE'],
					'TYPE'           => 'EMAIL',
				);
			}
		}

		$activityId = \CCrmActivity::add($activityFields, false, false, array('REGISTER_SONET_EVENT' => true));
		if ($activityId > 0)
		{
			static::log(
				$eventTag,
				'CCrmEmail: created activity',
				array(
					'ID' => $activityId,
					'OWNER_ID' => $ownerId,
					'OWNER_TYPE_ID' => $ownerTypeId,
					'RESPONSIBLE_ID' => $userId,
				)
			);

			if (!empty($checkInlineFiles))
			{
				foreach ($filesData as $item)
				{
					$info = \Bitrix\Crm\Integration\DiskManager::getFileInfo(
						$item['element_id'], false,
						array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $activityId)
					);

					$descr = preg_replace(
						sprintf('/<img([^>]+)src\s*=\s*(\'|\")?\s*(aid:%u)\s*\2([^>]*)>/is', $item['attachment_id']),
						sprintf('<img\1src="%s"\4>', $info['VIEW_URL']),
						$descr, -1, $count
					);

					if ($count > 0)
						$descrUpdated = true;
				}

				if (!empty($descrUpdated))
				{
					\CCrmActivity::update($activityId, array(
						'DESCRIPTION' => $descr,
					), false, false);
				}
			}

			\Bitrix\Crm\Activity\MailMetaTable::add(array(
				'ACTIVITY_ID'      => $activityId,
				'MSG_ID_HASH'      => !empty($msgId) ? md5(mb_strtolower($msgId)) : '',
				'MSG_INREPLY_HASH' => !empty($inReplyTo) ? md5(mb_strtolower($inReplyTo)) : '',
				'MSG_HEADER_HASH'  => $msgFields['MSG_HASH'],
			));

			$res = \Bitrix\Crm\Activity\MailMetaTable::getList(array(
				'select' => array('ACTIVITY_ID'),
				'filter' => array(
					'=MSG_INREPLY_HASH' => md5(mb_strtolower($msgId)),
				),
			));
			while ($mailMeta = $res->fetch())
			{
				\CCrmActivity::update($mailMeta['ACTIVITY_ID'], array(
					'PARENT_ID' => $activityId,
				), false, false);
			}

			if ($isIncome)
			{
				\Bitrix\Crm\Automation\Trigger\EmailTrigger::execute(
					bindings: $activityFields['BINDINGS'],
					inputData: $activityFields,
					useEntitySearch: false
				);

				$bindings = \CCrmActivity::GetBindings($activityId);
				$logMessageController = Timeline\LogMessageController::getInstance();
				foreach ($bindings as $binding)
				{
					$logMessageController->onCreate([
							'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
							'ENTITY_ID' => $binding['OWNER_ID'],
							'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
							'ASSOCIATED_ENTITY_ID' => $activityId,
						],
						Timeline\LogMessageType::EMAIL_INCOMING_MESSAGE,
						$activityFields['AUTHOR_ID'] ?? null
					);
				}
			}
			Channel\EmailTracker::getInstance()->registerActivity($activityId, array('ORIGIN_ID' => sprintf('%u|%u', $mailbox['USER_ID'], $mailbox['ID'])));
		}

		//Notify the responsible user if a message has been added from ajax sync to CRM
		if ($userId > 0 && $isIncome && $completed != 'Y')
		{
			\CCrmActivity::notify(
				$activityFields,
				\CCrmNotifierSchemeType::IncomingEmail,
				sprintf('crm_email_%u_%u', $activityFields['OWNER_TYPE_ID'], $activityFields['OWNER_ID']),
				$isForced,
				[],
			);
		}

		return true;
	}

	public static function EmailMessageAdd($arMessageFields, $ACTION_VARS)
	{
		if(!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$eventTag = sprintf('%x%x', time(), rand(0, 0xffffffff));

		$date = $arMessageFields['FIELD_DATE'] ?? '';
		$maxAgeDays = intval(COption::GetOptionString('crm', 'email_max_age', 7));
		$maxAge = $maxAgeDays > 0 ? ($maxAgeDays * 86400) : 0;
		if($maxAge > 0 && $date !== '')
		{
			$now = time() + CTimeZone::GetOffset();
			$timestamp = MakeTimeStamp($date, FORMAT_DATETIME);
			if( ($now - $timestamp) > $maxAge)
			{
				//Time threshold is exceeded
				return false;
			}
		}

		$crmEmail = mb_strtolower(trim(COption::GetOptionString('crm', 'mail', '')));

		$messageId = isset($arMessageFields['ID']) ? intval($arMessageFields['ID']) : 0;
		$mailboxId = isset($arMessageFields['MAILBOX_ID']) ? intval($arMessageFields['MAILBOX_ID']) : 0;

		if (empty($mailboxId))
		{
			static::log(
				$eventTag,
				'CCrmEmail: empty MAILBOX_ID',
				array(
					'ID' => $messageId,
					'MAILBOX_ID' => $mailboxId,
				)
			);

			return false;
		}

		$from = $arMessageFields['FIELD_FROM'] ?? '';
		$replyTo = $arMessageFields['FIELD_REPLY_TO'] ?? '';
		if($replyTo !== '')
		{
			// Ignore FROM if REPLY_TO EXISTS
			$from = $replyTo;
		}
		$addresserInfo = CCrmMailHelper::ParseEmail($from);
		if($crmEmail !== '' && strcasecmp($addresserInfo['EMAIL'], $crmEmail) === 0)
		{
			// Ignore emails from ourselves
			return false;
		}

		$to = $arMessageFields['FIELD_TO'] ?? '';
		$cc = $arMessageFields['FIELD_CC'] ?? '';
		$bcc = $arMessageFields['FIELD_BCC'] ?? '';

		$addresseeEmails = array_unique(
			array_merge(
				$to !== '' ? CMailUtil::ExtractAllMailAddresses($to) : array(),
				$cc !== '' ? CMailUtil::ExtractAllMailAddresses($cc) : array(),
				$bcc !== '' ? CMailUtil::ExtractAllMailAddresses($bcc) : array()),
			SORT_STRING
		);

		$mailbox = \CMailBox::getById($mailboxId)->fetch();

		if (empty($mailbox))
		{
			static::log(
				$eventTag,
				'CCrmEmail: empty mailbox',
				array(
					'ID' => $messageId,
					'MAILBOX_ID' => $mailboxId,
				)
			);

			return false;
		}

		$mailboxOwnerId = (int)$mailbox['USER_ID'] ?? 0;

		// POP3 mailboxes are ignored - they bound to single email
		if (
			$mailbox
			&& $mailbox['SERVER_TYPE'] === 'smtp'
			&& (empty($crmEmail) || !in_array($crmEmail, $addresseeEmails, true))
		)
		{
			return false;
		}

		$subject = trim($arMessageFields['SUBJECT']) ?: getMessage('CRM_EMAIL_DEFAULT_SUBJECT');
		$body = $arMessageFields['BODY'] ?? '';
		$arBodyEmails = null;

		$userID = 0;
		$parentID = 0;
		$ownerTypeID = CCrmOwnerType::Undefined;
		$ownerID = 0;

		$addresserID = self::FindUserIDByEmail($addresserInfo['EMAIL']);
		if($addresserID > 0 && Bitrix\Crm\Integration\IntranetManager::isExternalUser($addresserID))
		{
			//Forget about extranet user
			$addresserID = 0;
		}

		$arCommEmails = $addresserID <= 0
			? array($addresserInfo['EMAIL'])
			: ($crmEmail !== ''
				? array_diff($addresseeEmails, array($crmEmail))
				: $addresseeEmails);
		//Trying to fix strange behaviour of array_diff under OPcache (issue #60862)
		$arCommEmails = array_filter($arCommEmails);

		$targInfo = CCrmActivity::ParseUrn(
			CCrmActivity::ExtractUrnFromMessage(
				$arMessageFields,
				CCrmEMailCodeAllocation::GetCurrent()
			)
		);
		$targActivity = $targInfo['ID'] > 0 ? CCrmActivity::GetByID($targInfo['ID'], false) : null;

		// Check URN
		if ($targActivity
			&& (!isset($targActivity['URN']) || mb_strtoupper($targActivity['URN']) !== mb_strtoupper($targInfo['URN'])))
		{
			$targActivity = null;
		}

		if($targActivity)
		{
			$postingID = self::ExtractPostingID($arMessageFields);
			if($postingID > 0 && isset($targActivity['ASSOCIATED_ENTITY_ID']) && intval($targActivity['ASSOCIATED_ENTITY_ID']) === $postingID)
			{
				// Ignore - it is our message.
				return false;
			}

			$parentID = $targActivity['ID'];
			$subject = CCrmActivity::ClearUrn($subject);

			if($addresserID > 0)
			{
				$userID = $addresserID;
			}
			elseif(isset($targActivity['RESPONSIBLE_ID']))
			{
				$userID = $targActivity['RESPONSIBLE_ID'];
			}

			if(isset($targActivity['OWNER_TYPE_ID']))
			{
				$ownerTypeID = intval($targActivity['OWNER_TYPE_ID']);
			}

			if(isset($targActivity['OWNER_ID']))
			{
				$ownerID = intval($targActivity['OWNER_ID']);
			}

			$arCommData = self::ExtractCommsFromEmails($arCommEmails);

			if($ownerTypeID > 0 && $ownerID > 0)
			{
				if(empty($arCommData))
				{
					if($addresserID > 0)
					{
						foreach($addresseeEmails as $email)
						{
							if($email === $crmEmail)
							{
								continue;
							}

							$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $email));
						}
					}
					else
					{
						$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $addresserInfo['EMAIL']));
					}
				}
				elseif($ownerTypeID !== CCrmOwnerType::Deal)
				{
					//Check if owner in communications. If owner is found then clear outsider communications. Otherwise reset owner.
					//There is only one exception for DEAL - it entity does not have communications
					$ownerCommunications = array();
					foreach($arCommData as $arCommItem)
					{
						$commEntityTypeID = isset($arCommItem['ENTITY_TYPE_ID']) ? $arCommItem['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
						$commEntityID = isset($arCommItem['ENTITY_ID']) ? $arCommItem['ENTITY_ID'] : 0;

						if($commEntityTypeID === $ownerTypeID && $commEntityID === $ownerID)
						{
							$ownerCommunications[] = $arCommItem;
						}
					}

					if(empty($ownerCommunications))
					{
						$ownerTypeID = CCrmOwnerType::Undefined;
						$ownerID = 0;
					}
					else
					{
						$arCommData = $ownerCommunications;
					}
				}
			}
		}
		else
		{
			if($addresserID > 0)
			{
				//It is email from registered user
				$userID = $addresserID;

				if(empty($arCommEmails))
				{
					$arBodyEmails = self::ExtractEmailsFromBody($body);
					//Clear system user emails and CRM email
					if(!empty($arBodyEmails))
					{
						foreach($arBodyEmails as $email)
						{
							if(strcasecmp($email, $crmEmail) !== 0 && self::FindUserIDByEmail($email) <= 0)
							{
								$arCommEmails[] = $email;
							}
						}
					}
				}

				// Try to resolve communications
				$arCommData = self::ExtractCommsFromEmails($arCommEmails);
			}
			else
			{
				//It is email from unknown user

				//Try to resolve bindings from addresser
				$arCommData = self::ExtractCommsFromEmails($arCommEmails);
				if(!empty($arCommData))
				{
					// Try to resolve responsible user
					foreach($arCommData as &$arComm)
					{
						$userID = self::ResolveResponsibleID(
							$arComm['ENTITY_TYPE_ID'],
							$arComm['ENTITY_ID']
						);

						if($userID > 0)
						{
							break;
						}
					}
					unset($arComm);
				}
			}

			// Try to resolve owner by old-style method-->
			$arACTION_VARS = explode('&', $ACTION_VARS);
			for ($i=0, $ic=count($arACTION_VARS); $i < $ic ; $i++)
			{
				$v = $arACTION_VARS[$i];
				if($pos = mb_strpos($v, '='))
				{
					$name = mb_substr($v, 0, $pos);
					${$name} = urldecode(mb_substr($v, $pos + 1));
				}
			}

			$arTypeNames = CCrmOwnerType::GetNames(
				array(
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company
				)
			);
			foreach ($arTypeNames as $typeName)
			{
				$regexVar = 'W_CRM_ENTITY_REGEXP_'.$typeName;

				if (empty(${$regexVar}))
				{
					continue;
				}

				$regexp = '/'.${$regexVar}.'/iu';
				$match = array();
				if (preg_match($regexp, $subject, $match) === 1)
				{
					$ownerID = (int)$match[1];
					$ownerTypeID = CCrmOwnerType::ResolveID($typeName);
					$subject = preg_replace($regexp, '', $subject);
					break;
				}
			}
			// <-- Try to resolve owner by old-style method

			if($ownerID > 0 && CCrmOwnerType::IsDefined($ownerTypeID))
			{
				// Filter communications by owner
				if($ownerTypeID !== CCrmOwnerType::Deal)
				{
					if(!empty($arCommData))
					{
						foreach($arCommData as $commKey => $arComm)
						{
							if($arComm['ENTITY_TYPE_ID'] === $ownerTypeID && $arComm['ENTITY_ID'] === $ownerID)
							{
								continue;
							}

							unset($arCommData[$commKey]);
						}

						$arCommData = array_values($arCommData);
					}

					if(empty($arCommData))
					{
						if($addresserID > 0)
						{
							foreach($addresseeEmails as $email)
							{
								if($email === $crmEmail)
								{
									continue;
								}

								$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $email));
							}
						}
						else
						{
							$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $addresserInfo['EMAIL']));
						}
					}
				}
				else
				{
					// Deal does not have communications. But lead communications are strange for this context.
					// It is important for explicit binding mode (like text [DID#100] in subject). Try to get rid of lead communications.
					$arCommTypeMap = array();
					foreach($arCommData as $commKey => $arComm)
					{
						$commTypeID = $arComm['ENTITY_TYPE_ID'];
						if(!isset($arCommTypeMap[$commTypeID]))
						{
							$arCommTypeMap[$commTypeID] = array();
						}
						$arCommTypeMap[$commTypeID][] = $arComm;
					}
					if(isset($arCommTypeMap[CCrmOwnerType::Contact]) || isset($arCommTypeMap[CCrmOwnerType::Company]))
					{
						if(isset($arCommTypeMap[CCrmOwnerType::Contact]) && isset($arCommTypeMap[CCrmOwnerType::Company]))
						{
							$arCommData = array_merge($arCommTypeMap[CCrmOwnerType::Contact], $arCommTypeMap[CCrmOwnerType::Company]);
						}
						elseif(isset($arCommTypeMap[CCrmOwnerType::Contact]))
						{
							$arCommData = $arCommTypeMap[CCrmOwnerType::Contact];
						}
						else//if(isset($arCommTypeMap[CCrmOwnerType::Company]))
						{
							$arCommData = $arCommTypeMap[CCrmOwnerType::Company];
						}
					}
				}
			}
		}

		$arBindingData = self::ConvertCommsToBindings($arCommData);

		// Check bindings for converted leads -->
		// Not Existed entities are ignored. Converted leads are ignored if their associated entities (contacts, companies, deals) are contained in bindings.
		$arCorrectedBindingData = array();
		$arConvertedLeadData = array();
		foreach($arBindingData as $bindingKey => &$arBinding)
		{
			if($arBinding['TYPE_ID'] !== CCrmOwnerType::Lead)
			{
				if(self::IsEntityExists($arBinding['TYPE_ID'], $arBinding['ID']))
				{
					$arCorrectedBindingData[$bindingKey] = $arBinding;
				}
				continue;
			}

			$arFields = self::GetEntity(
				CCrmOwnerType::Lead,
				$arBinding['ID'],
				array('STATUS_ID')
			);

			if(!is_array($arFields))
			{
				continue;
			}

			if(isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] === 'CONVERTED')
			{
				$arConvertedLeadData[$bindingKey] = $arBinding;
			}
			else
			{
				$arCorrectedBindingData[$bindingKey] = $arBinding;
			}
		}
		unset($arBinding);

		foreach($arConvertedLeadData as &$arConvertedLead)
		{
			$leadID = $arConvertedLead['ID'];
			$exists = false;

			$dbRes = CCrmCompany::GetListEx(
				array(),
				array('LEAD_ID' => $leadID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			if($dbRes)
			{
				while($arRes = $dbRes->Fetch())
				{
					if(isset($arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Company, $arRes['ID'])]))
					{
						$exists = true;
						break;
					}
				}
			}

			if($exists)
			{
				continue;
			}

			$dbRes = CCrmContact::GetListEx(
				array(),
				array('LEAD_ID' => $leadID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			if($dbRes)
			{
				while($arRes = $dbRes->Fetch())
				{
					if(isset($arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Contact, $arRes['ID'])]))
					{
						$exists = true;
						break;
					}
				}
			}

			if($exists)
			{
				continue;
			}

			$dbRes = CCrmDeal::GetListEx(
				array(),
				array('LEAD_ID' => $leadID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			if($dbRes)
			{
				while($arRes = $dbRes->Fetch())
				{
					if(isset($arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Deal, $arRes['ID'])]))
					{
						$exists = true;
						break;
					}
				}
			}

			if($exists)
			{
				continue;
			}

			$arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Lead, $leadID)] = $arConvertedLead;
		}
		unset($arConvertedLead);

		$arBindingData = $arCorrectedBindingData;
		// <-- Check bindings for converted leads

		// If no bindings are found then create new lead from this message
		// Skip lead creation if email list is empty. Otherwise we will create lead with no email-addresses. It is absolutely useless.
		$emailQty = count($arCommEmails);
		if(empty($arBindingData) && $emailQty > 0)
		{
			if(mb_strtoupper(COption::GetOptionString('crm', 'email_create_lead_for_new_addresser', 'Y')) !== 'Y')
			{
				// Creation of new lead is not allowed
				return true;
			}

			//"Lead from forwarded email..." or "Lead from email..."
			$title = trim($arMessageFields['SUBJECT'])
				?: GetMessage(
					$addresserID > 0
						? 'CRM_MAIL_LEAD_FROM_USER_EMAIL_TITLE'
						: 'CRM_MAIL_LEAD_FROM_EMAIL_TITLE',
					array('%SENDER%' => $addresserInfo['ORIGINAL'])
				);

			$comment = '';
			if($body !== '')
			{
				// Remove extra new lines (fix for #31807)
				$comment = preg_replace("/(\r\n|\n|\r)+/", '<br/>', htmlspecialcharsbx($body));
			}
			if($comment === '')
			{
				$comment = htmlspecialcharsbx($subject);
			}

			$name = '';
			if($addresserID <= 0)
			{
				$name = $addresserInfo['NAME'];
			}
			else
			{
				//Try get name from body
				for($i = 0; $i < $emailQty; $i++)
				{
					$email = $arCommEmails[$i];
					$match = array();
					if(preg_match('/"([^"]+)"\s*<'.$email.'>/iu', $body, $match) === 1 && count($match) > 1)
					{
						$name = $match[1];
						break;
					}

					if(preg_match('/"([^"]+)"\s*[\s*mailto\:\s*'.$email.']/iu', $body, $match) === 1 && count($match) > 1)
					{
						$name = $match[1];
						break;
					}
				}

				if($name === '')
				{
					$name = $arCommEmails[0];
				}
			}

			$arLeadFields = array(
				'TITLE' =>  $title,
				'NAME' => $name,
				'STATUS_ID' => 'NEW',
				'COMMENTS' => $comment,
				'SOURCE_DESCRIPTION' => GetMessage('CRM_MAIL_LEAD_FROM_EMAIL_SOURCE', array('%SENDER%' => $addresserInfo['ORIGINAL'])),
				'FM' => array(
					'EMAIL' => array()
				)
			);

			$sourceList = CCrmStatus::GetStatusList('SOURCE');
			$sourceID = COption::GetOptionString('crm', 'email_lead_source_id', '');
			if($sourceID === '' || !isset($sourceList[$sourceID]))
			{
				if(isset($sourceList['EMAIL']))
				{
					$sourceID = 'EMAIL';
				}
				elseif(isset($sourceList['OTHER']))
				{
					$sourceID = 'OTHER';
				}
			}

			if($sourceID !== '')
			{
				$arLeadFields['SOURCE_ID'] = $sourceID;
			}

			$responsibleID = self::GetDefaultResponsibleID(CCrmOwnerType::Lead);
			if($responsibleID > 0)
			{
				$arLeadFields['CREATED_BY_ID'] = $arLeadFields['MODIFY_BY_ID'] = $arLeadFields['ASSIGNED_BY_ID'] = $responsibleID;

				if($userID === 0)
				{
					$userID = $responsibleID;
				}
			}

			for($i = 0; $i < $emailQty; $i++)
			{
				$arLeadFields['FM']['EMAIL']['n'.($i + 1)] =
				array(
					'VALUE_TYPE' => 'WORK',
					'VALUE' => $arCommEmails[$i]
				);
			}

			$leadEntity = new CCrmLead(false);
			$leadID = $leadEntity->Add(
				$arLeadFields,
				true,
				array(
					'DISABLE_USER_FIELD_CHECK' => true,
					'REGISTER_SONET_EVENT' => true,
					'CURRENT_USER' => $responsibleID
				)
			);
			// TODO: log error
			if($leadID > 0)
			{
				$arBizProcErrors = array();
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$leadID,
					CCrmBizProcEventType::Create,
					$arBizProcErrors
				);

				//Region automation
				$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $leadID);
				$starter->setUserId($userID)->runOnAdd();
				//End region

				$arCommData = array();
				for($i = 0; $i < $emailQty; $i++)
				{
					$arCommData[] = self::CreateComm(
						CCrmOwnerType::Lead,
						$leadID,
						$arCommEmails[$i]
					);
				}

				$arBindingData = array(
					self::PrepareEntityKey(CCrmOwnerType::Lead, $leadID) =>
					self::CreateBinding(CCrmOwnerType::Lead, $leadID)
				);
			}
		}

		// Terminate processing if no bindings are found.
		if(empty($arBindingData))
		{
			// Try to export vcf-files before exit if email from registered user
			if($addresserID > 0)
			{
				$dbAttachment = CMailAttachment::GetList(array(), array('MESSAGE_ID' => $messageId));
				while ($arAttachment = $dbAttachment->Fetch())
				{
					if(GetFileExtension(mb_strtolower($arAttachment['FILE_NAME'])) === 'vcf')
					{
						if ($arAttachment['FILE_ID'])
							$arAttachment['FILE_DATA'] = CMailAttachment::getContents($arAttachment);
						self::TryImportVCard($arAttachment['FILE_DATA']);
					}
				}
			}
			return false;
		}

		// If owner info not defined set it by default
		if($ownerID <= 0 || $ownerTypeID <= 0)
		{
			if(count($arBindingData) > 1)
			{
				// Search owner in specified order: Contact, Company, Lead.
				$arTypeIDs = array(
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company,
					CCrmOwnerType::Lead
				);

				foreach($arTypeIDs as $typeID)
				{
					foreach($arBindingData as &$arBinding)
					{
						if($arBinding['TYPE_ID'] === $typeID)
						{
							$ownerTypeID = $typeID;
							$ownerID = $arBinding['ID'];
							break;
						}
					}
					unset($arBinding);

					if($ownerID > 0 && $ownerTypeID > 0)
					{
						break;
					}
				}
			}

			if($ownerID <= 0 || $ownerTypeID <= 0)
			{
				$arBinding = array_shift(array_values($arBindingData));
				$ownerTypeID = $arBinding['TYPE_ID'];
				$ownerID = $arBinding['ID'];
			}
		}

		// Precessing of attachments -->
		$attachmentMaxSizeMb = intval(COption::GetOptionString('crm', 'email_attachment_max_size', 24));
		$attachmentMaxSize = $attachmentMaxSizeMb > 0 ? ($attachmentMaxSizeMb * 1048576) : 0;

		$arFilesData = array();
		$dbAttachment = CMailAttachment::GetList(array(), array('MESSAGE_ID' => $messageId));
		$arBannedAttachments = array();
		while ($arAttachment = $dbAttachment->Fetch())
		{
			if (GetFileExtension(mb_strtolower($arAttachment['FILE_NAME'])) === 'vcf')
			{
				if ($arAttachment['FILE_ID'])
					$arAttachment['FILE_DATA'] = CMailAttachment::getContents($arAttachment);
				self::TryImportVCard($arAttachment['FILE_DATA']);
			}

			$fileSize = isset($arAttachment['FILE_SIZE']) ? intval($arAttachment['FILE_SIZE']) : 0;
			if($fileSize <= 0)
			{
				//Skip zero lenth files
				continue;
			}

			if($attachmentMaxSize > 0 && $fileSize > $attachmentMaxSize)
			{
				//File size limit  is exceeded
				$arBannedAttachments[] = array(
					'name' => $arAttachment['FILE_NAME'],
					'size' => $fileSize
				);
				continue;
			}

			if ($arAttachment['FILE_ID'] && empty($arAttachment['FILE_DATA']))
				$arAttachment['FILE_DATA'] = CMailAttachment::getContents($arAttachment);

			$arFilesData[] = array(
				'name' => $arAttachment['FILE_NAME'],
				'type' => $arAttachment['CONTENT_TYPE'],
				'content' => $arAttachment['FILE_DATA'],
				//'size' => $arAttachment['FILE_SIZE'], // HACK: Must be commented if use CFile:SaveForDB
				'MODULE_ID' => 'crm'
			);
		}
		//<-- Precessing of attachments

		// Remove extra new lines (fix for #31807)
		$body = preg_replace("/(\r\n|\n|\r)+/", PHP_EOL, $body);
		$encodedBody = htmlspecialcharsbx($body);

		// Creating of new event -->
		$arEventBindings = array();
		foreach($arBindingData as &$arBinding)
		{
			$arEventBindings[] = array(
				'ENTITY_TYPE' => $arBinding['TYPE_NAME'],
				'ENTITY_ID' => $arBinding['ID']
			);
		}
		unset($arBinding);

		$eventText  = '';
		$eventText .= '<b>'.GetMessage('CRM_EMAIL_SUBJECT').'</b>: '.$subject.PHP_EOL;
		$eventText .= '<b>'.GetMessage('CRM_EMAIL_FROM').'</b>: '.$addresserInfo['EMAIL'].PHP_EOL;
		$eventText .= '<b>'.GetMessage('CRM_EMAIL_TO').'</b>: '.implode('; ', $addresseeEmails).PHP_EOL;
		if(!empty($arBannedAttachments))
		{
			$eventText .= '<b>'.GetMessage('CRM_EMAIL_BANNENED_ATTACHMENTS', array('%MAX_SIZE%' => $attachmentMaxSizeMb)).'</b>: ';
			foreach($arBannedAttachments as &$attachmentInfo)
			{
				$eventText .= GetMessage(
					'CRM_EMAIL_BANNENED_ATTACHMENT_INFO',
					array(
						'%NAME%' => $attachmentInfo['name'],
						'%SIZE%' => round($attachmentInfo['size'] / 1048576, 1)
					)
				);
			}
			unset($attachmentInfo);
			$eventText .= PHP_EOL;
		}
		$eventText .= $encodedBody;

		$CCrmEvent = new CCrmEvent();
		$CCrmEvent->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY' => array_values($arEventBindings),
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName($ownerTypeID),
				'ENTITY_ID' => $ownerID,
				'EVENT_NAME' => GetMessage('CRM_EMAIL_GET_EMAIL'),
				'EVENT_TYPE' => 2,
				'EVENT_TEXT_1' => $eventText,
				'FILES' => $arFilesData,
			),
			false
		);
		// <-- Creating of new event

		// Creating new activity -->

		$storageTypeID =  CCrmActivity::GetDefaultStorageTypeID();
		$arElementIDs = array();
		foreach($arFilesData as $fileData)
		{
			$fileID = CFile::SaveFile($fileData, 'crm', true);
			if (!($fileID > 0))
				continue;

			$fileData = \CFile::getFileArray($fileID);
			if (empty($fileData))
				continue;

			if (trim($fileData['ORIGINAL_NAME']) == '')
				$fileData['ORIGINAL_NAME'] = $fileData['FILE_NAME'];
			$elementID = StorageManager::saveEmailAttachment(
				$fileData, $storageTypeID, '',
				array('USER_ID' => $userID)
			);
			if($elementID > 0)
			{
				$arElementIDs[] = (int)$elementID;
			}
		}

		$descr = preg_replace("/(\r\n|\n|\r)/", '<br/>', htmlspecialcharsbx($body));
		$now = (string) (new \Bitrix\Main\Type\DateTime());

		$direction = CCrmActivityDirection::Incoming;
		$completed = 'N'; // Incomming emails must be marked as 'Not Completed'.

		if ($addresserID > 0)
		{
			if (ActivitySettings::getValue(ActivitySettings::MARK_FORWARDED_EMAIL_AS_OUTGOING))
			{
				$direction = CCrmActivityDirection::Outgoing;
				$completed = 'Y';
			}

			\Bitrix\Main\Config\Option::set(
				'crm', 'email_forwarded_cnt',
				\Bitrix\Main\Config\Option::get('crm', 'email_forwarded_cnt', 0) + 1
			);
		}

		$arActivityFields = array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'TYPE_ID' =>  CCrmActivityType::Email,
			'ASSOCIATED_ENTITY_ID' => 0,
			'PARENT_ID' => $parentID,
			'SUBJECT' => $subject,
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => $completed,
			'AUTHOR_ID' => $userID,
			'RESPONSIBLE_ID' => $userID,
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $descr,
			'DESCRIPTION_TYPE' => CCrmContentType::Html,
			'DIRECTION' => $direction,
			'LOCATION' => '',
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'STORAGE_TYPE_ID' => $storageTypeID,
			'STORAGE_ELEMENT_IDS' => $arElementIDs
		);

		$arActivityFields['BINDINGS'] = array();
		foreach($arBindingData as &$arBinding)
		{
			$entityTypeID = $arBinding['TYPE_ID'];
			$entityID = $arBinding['ID'];

			if($entityTypeID <= 0 || $entityID <= 0)
			{
				continue;
			}

			$arActivityFields['BINDINGS'][] =
				array(
					'OWNER_TYPE_ID' => $entityTypeID,
					'OWNER_ID' => $entityID
				);
		}
		unset($arBinding);

		if (!empty($arCommData))
			$arActivityFields['COMMUNICATIONS'] = $arCommData;

		$activityID = CCrmActivity::Add($arActivityFields, false, false, array('REGISTER_SONET_EVENT' => true));
		if ($activityID > 0)
		{
			if ($direction === CCrmActivityDirection::Incoming)
			{
				\Bitrix\Crm\Automation\Trigger\EmailTrigger::execute($arActivityFields['BINDINGS'], $arActivityFields);
			}
		}

		//Notify the responsible user that the message was added automatically(when syncing a mailbox) to CRM
		if($userID > 0 && $direction === CCrmActivityDirection::Incoming)
		{
			CCrmActivity::Notify($arActivityFields, CCrmNotifierSchemeType::IncomingEmail, '', false, []);
		}

		return true;
	}

	/**
	 * Creates email activity.
	 *
	 * @param array $messageFields Email params.
	 * @param array $activityFields Activity params.
	 *
	 * @return bool
	 *
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function createOutgoingMessageActivity(&$messageFields, &$activityFields)
	{
		if(!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$userId = isset($activityFields['RESPONSIBLE_ID']) ? (int)$activityFields['RESPONSIBLE_ID'] : \CCrmSecurityHelper::GetCurrentUserID();
		$authorId = isset($activityFields['AUTHOR_ID']) ? (int)$activityFields['AUTHOR_ID'] : $userId;
		$editorId = isset($activityFields['EDITOR_ID']) ? (int)$activityFields['EDITOR_ID'] : $userId;

		$now = convertTimeStamp(time() + \CTimeZone::getOffset(), 'FULL', SITE_ID);

		$from    = isset($messageFields['FROM']) ? $messageFields['FROM'] : '';
		$replyTo = isset($messageFields['REPLY_TO']) ? $messageFields['REPLY_TO'] : '';

		$to  = isset($messageFields['TO']) ? $messageFields['TO'] : array();
		$cc  = isset($messageFields['CC']) ? $messageFields['CC'] : array();
		$bcc = isset($messageFields['BCC']) ? $messageFields['BCC'] : array();

		$subject   = trim($messageFields['SUBJECT']) ?: getMessage('CRM_EMAIL_DEFAULT_SUBJECT');
		$body = isset($messageFields['BODY']) ? $messageFields['BODY'] : '';

		// Bindings & Communications
		$arCommunications = isset($activityFields['COMMUNICATIONS']) ? $activityFields['COMMUNICATIONS'] : array();

		$arBindings = array();
		$arComms = array();

		foreach ($arCommunications as &$commDatum)
		{
			$commEntityID = isset($commDatum['id']) ? intval($commDatum['id']) : 0;;
			$commEntityType = isset($commDatum['entityType'])? mb_strtolower(strval($commDatum['entityType'])) : '';

			$commType = isset($commDatum['type'])? mb_strtoupper(strval($commDatum['type'])) : '';
			if($commType === '')
			{
				$commType = 'EMAIL';
			}
			$commValue = isset($commDatum['value']) ? strval($commDatum['value']) : '';

			if($commType === 'EMAIL' && $commValue !== '')
			{
				if(!check_email($commValue))
				{
					// ignoring
					continue;
				}

				$rcptFieldName = 'to';
				if (isset($commDatum['__field']))
				{
					$commDatum['__field'] = mb_strtolower($commDatum['__field']);
					if (in_array($commDatum['__field'], array('to', 'cc', 'bcc')))
					{
						$rcptFieldName = $commDatum['__field'];
					}
				}

				${$rcptFieldName}[] = mb_strtolower(trim($commValue));
			}

			$key = md5(sprintf(
				'%s_%u_%s_%s',
				$commEntityType,
				$commEntityID,
				$commType,
				mb_strtolower(trim($commValue))
			));
			$arComms[$key] = array(
				'TYPE' => $commType,
				'VALUE' => $commValue,
				'ENTITY_ID' => $commEntityID,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ResolveID($commEntityType)
			);

			if($commEntityType !== '')
			{
				$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
				if(!isset($arBindings[$bindingKey]))
				{
					$arBindings[$bindingKey] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($commEntityType),
						'OWNER_ID' => $commEntityID
					);
				}
			}
		}
		unset($commDatum);

		$to  = array_unique($to);
		$cc  = array_unique($cc);
		$bcc = array_unique($bcc);

		// owner entity
		$ownerBounded = false;

		$ownerTypeId = 0;
		$ownerId     = 0;

		$typesPriority = array(
			\CCrmOwnerType::Contact => 2,
			\CCrmOwnerType::Company => 3,
			\CCrmOwnerType::Lead    => 4,
		);

		foreach ($arBindings as $item)
		{
			if ($ownerTypeId <= 0 || $typesPriority[$item['OWNER_TYPE_ID']] < $typesPriority[$ownerTypeId])
			{
				if (\CCrmActivity::checkUpdatePermission($item['OWNER_TYPE_ID'], $item['OWNER_ID']))
				{
					$ownerTypeId = $item['OWNER_TYPE_ID'];
					$ownerId     = $item['OWNER_ID'];

					$ownerBounded = true;
				}
			}
		}

		if (!$ownerBounded)
		{
			if (empty($arComms))
			{
				$activityFields['ERROR_CODE'] = 'ACTIVITY_COMMUNICATIONS_EMPTY_ERROR';
			}
			else
			{
				$activityFields['ERROR_CODE'] = 'ACTIVITY_PERMISSION_DENIED_ERROR';
			}

			return false;
		}


		\CCrmActivity::addEmailSignature($body, \CCrmContentType::Html);

		$activityFields = array(
			'AUTHOR_ID' => $authorId,
			'OWNER_ID' => $ownerId,
			'OWNER_TYPE_ID' => $ownerTypeId,
			'TYPE_ID' => \CCrmActivityType::Email,
			'SUBJECT' => $subject,
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => 'Y',
			'RESPONSIBLE_ID' => $userId,
			'EDITOR_ID' => $editorId,
			'PRIORITY' => !empty($messageFields['IMPORTANT']) && $messageFields['IMPORTANT'] ? \CCrmActivityPriority::High : \CCrmActivityPriority::Medium,
			'DESCRIPTION' => $body,
			'DESCRIPTION_TYPE' => \CCrmContentType::Html,
			'DIRECTION' => \CCrmActivityDirection::Outgoing,
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'BINDINGS' => array_values($arBindings),
			'COMMUNICATIONS' => $arComms,
		);


		$storageTypeID = isset($messageFields['STORAGE_TYPE_ID']) ? $messageFields['STORAGE_TYPE_ID'] : \CCrmActivity::GetDefaultStorageTypeID();
		$activityFields['STORAGE_TYPE_ID'] = $storageTypeID;

		if ($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk)
		{
			if (isset($messageFields['STORAGE_ELEMENT_IDS']) && is_array($messageFields['STORAGE_ELEMENT_IDS']))
			{
				$activityFields['STORAGE_ELEMENT_IDS'] =
					\Bitrix\Crm\Integration\StorageManager::filterFiles($messageFields['STORAGE_ELEMENT_IDS'], $storageTypeID, $userId);
			}
		}

		if (!($activityId = \CCrmActivity::Add($activityFields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			$activityFields['ERROR_CODE'] = 'ACTIVITY_CREATE_ERROR';
			$activityFields['ERROR_TEXT'] = \CCrmActivity::GetLastErrorMessage();

			return false;
		}



		$activityFields['ID'] = $activityId;
		$urn = \CCrmActivity::prepareUrn($activityFields);

		$hostname = \COption::getOptionString('main', 'server_name', '') ?: 'localhost';
		if (defined('BX24_HOST_NAME') && BX24_HOST_NAME != '')
		{
			$hostname = BX24_HOST_NAME;
		}
		elseif (defined('SITE_SERVER_NAME') && SITE_SERVER_NAME != '')
		{
			$hostname = SITE_SERVER_NAME;
		}

		$messageId = sprintf('<crm.activity.%s@%s>', $urn, $hostname);
		$messageFields['MSG_ID'] = $messageId;


		$arRawFiles = array();
		if (isset($activityFields['STORAGE_ELEMENT_IDS']) && !empty($activityFields['STORAGE_ELEMENT_IDS']))
		{
			foreach ((array)$activityFields['STORAGE_ELEMENT_IDS'] as $item)
			{
				$arRawFiles[$item] = \Bitrix\Crm\Integration\StorageManager::makeFileArray($item, $storageTypeID);

				$fileInfo = \Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$item,
					$storageTypeID,
					false,
					array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $activityId)
				);

				$body = preg_replace(
					sprintf('/(https?:\/\/)?bxacid:n?%u/i', $item),
					htmlspecialcharsbx($fileInfo['VIEW_URL']),
					$body
				);
			}
		}

		\CCrmActivity::update($activityId, array(
			'DESCRIPTION' => $body,
			'URN' => $urn,
			'SETTINGS' => array(
				'MESSAGE_HEADERS' => array(
					'Message-Id' => $messageId,
					'Reply-To' => $replyTo,
				),
				'EMAIL_META' => array(
					'__email' => $from,
					'from' => $from,
					'replyTo' => $replyTo,
					'to' => implode(', ', $to),
					'cc' => implode(', ', $cc),
					'bcc' => implode(', ', $bcc),
				),
			),
		), false, false, array('REGISTER_SONET_EVENT' => true));


		//----------
		// Try add event to entity
		$crmEvent = new \CCrmEvent();

		$eventText = '';
		$eventText .= getMessage('CRM_EMAIL_SUBJECT').': '.$subject."\n\r";
		$eventText .= getMessage('CRM_EMAIL_FROM').': '.$from."\n\r";
		if (!empty($to))
		{
			$eventText .= getMessage('CRM_EMAIL_TO').': '.implode(',', $to)."\n\r";
		}
		if (!empty($cc))
		{
			$eventText .= 'Cc: '.implode(',', $cc)."\n\r";
		}
		if (!empty($bcc))
		{
			$eventText .= 'Bcc: '.implode(',', $bcc)."\n\r";
		}
		$eventText .= "\n\r";
		$eventText .= $body;

		$eventBindings = array();
		foreach($arBindings as $item)
		{
			$bindingEntityID = $item['OWNER_ID'];
			$bindingEntityTypeID = $item['OWNER_TYPE_ID'];
			$bindingEntityTypeName = \CCrmOwnerType::resolveName($bindingEntityTypeID);

			$eventBindings["{$bindingEntityTypeName}_{$bindingEntityID}"] = array(
				'ENTITY_TYPE' => $bindingEntityTypeName,
				'ENTITY_ID' => $bindingEntityID
			);
		}

		$crmEvent->Add(
			array(
				'ENTITY' => $eventBindings,
				'EVENT_ID' => 'MESSAGE',
				'EVENT_NAME' => $subject,
				'EVENT_TEXT_1' => $eventText,
				'FILES' => array_values($arRawFiles),
			)
		);

		return ($activityId > 0);
	}

	public static function EmailMessageCheck($arFields, $ACTION_VARS)
	{
		$arACTION_VARS = explode('&', $ACTION_VARS);
		for ($i=0, $ic=count($arACTION_VARS); $i < $ic ; $i++)
		{
			$v = $arACTION_VARS[$i];
			if($pos = mb_strpos($v, '='))
			{
				$name = mb_substr($v, 0, $pos);
				${$name} = urldecode(mb_substr($v, $pos + 1));
			}
		}
		return true;
	}
	public static function PrepareVars()
	{
		$str = 'W_CRM_ENTITY_REGEXP_LEAD='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_LEAD']).
			'&W_CRM_ENTITY_REGEXP_CONTACT='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_CONTACT']).
			'&W_CRM_ENTITY_REGEXP_COMPANY='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_COMPANY']).
			'&W_CRM_ENTITY_REGEXP_DEAL='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_DEAL']);
		return $str;
	}
	public static function BeforeSendMail($arMessageFields)
	{
		// ADD ADDITIONAL HEADERS
		$postingID = self::ExtractPostingID($arMessageFields);
		if($postingID <= 0)
		{
			return $arMessageFields;
		}

		$dbActivity = \CCrmActivity::getList(
			array('ID' => 'DESC'),
			array(
				'=TYPE_ID' => CCrmActivityType::Email,
				'=ASSOCIATED_ENTITY_ID' => $postingID,
				'CHECK_PERMISSIONS'=>'N'
			),
			false,
			false,
			array('SETTINGS'),
			array()
		);

		$arActivity = $dbActivity ? $dbActivity->Fetch() : null;

		if(!$arActivity)
		{
			return $arMessageFields;
		}

		$settings = isset($arActivity['SETTINGS']) && is_array($arActivity['SETTINGS']) ? $arActivity['SETTINGS'] : array();
		$messageHeaders = isset($settings['MESSAGE_HEADERS']) ? $settings['MESSAGE_HEADERS'] : array();
		if(empty($messageHeaders))
		{
			return $arMessageFields;
		}

		$header = isset($arMessageFields['HEADER']) ? $arMessageFields['HEADER'] : '';
		$eol = CEvent::GetMailEOL();
		foreach($messageHeaders as $headerName => &$headerValue)
		{
			if($header <> '')
			{
				$header .= $eol;
			}

			$header .= $headerName.': '.$headerValue;
		}
		unset($headerValue);
		$arMessageFields['HEADER'] = $header;

		$cidRegex = sprintf(
			'/Content-Disposition: attachment; filename="(.+?)_(bxacid.[0-9a-f]{2,8}@[0-9a-f]{2,8}.crm)"(%s)/i',
			'\x'.join('\x', str_split(bin2hex(\Bitrix\Main\Mail\Mail::getMailEol()), 2))
		);
		if (preg_match_all($cidRegex, $arMessageFields['BODY'], $matches, PREG_SET_ORDER) > 0)
		{
			foreach ($matches as $set)
			{
				$arMessageFields['BODY'] = str_replace(
					$set[0],
					sprintf(
						'Content-Disposition: attachment; filename="%s"%sContent-ID: <%s>%s',
						$set[1], $set[3], $set[2], $set[3]
					),
					$arMessageFields['BODY']
				);

				$arMessageFields['BODY'] = str_replace(
					sprintf('%s_%s', $set[1], $set[2]),
					$set[1],
					$arMessageFields['BODY']
				);
			}
		}

		return $arMessageFields;
	}

	public static function OnImapEmailMessageObsolete(\Bitrix\Main\Event $event)
	{
		global $DB;

		$resp = $event->getParameter('user');
		$hash = $event->getParameter('hash');

		$res = \Bitrix\Crm\Activity\MailMetaTable::getList(array(
			'select' => array('ACTIVITY_ID'),
			'filter' => array('=MSG_HEADER_HASH' => $hash),
		));

		while ($mailMeta = $res->fetch())
		{
			if ($activity = \CCrmActivity::getById($mailMeta['ACTIVITY_ID'], false))
			{
				if ($activity['TYPE_ID'] != \CCrmActivityType::Email || $activity['DIRECTION'] != \CCrmActivityDirection::Incoming)
					break;

				if ($resp > 0 && $activity['RESPONSIBLE_ID'] != $resp)
					break;

				$response = $DB->query(sprintf('SELECT 1 FROM b_crm_act WHERE PARENT_ID = %u', $activity['ID']))->fetch();
				if (!$response)
				{
					$bindRes = $DB->query(sprintf(
						'SELECT OWNER_ID FROM b_crm_act_bind WHERE ACTIVITY_ID = %u AND OWNER_TYPE_ID = %u',
						$activity['ID'], \CCrmOwnerType::Lead
					));

					$leadIds = array();
					while ($bind = $bindRes->fetch())
						$leadIds[] = $bind['OWNER_ID'];

					\CCrmActivity::delete($activity['ID'], false, false);
					\Bitrix\Crm\Activity\MailMetaTable::delete($activity['ID']);

					if (!empty($leadIds))
					{
						$leadRes = \CCrmLead::getListEx(
							array(),
							array(
								'ID'                => $leadIds,
								'ORIGINATOR_ID'     => 'email-tracker',
								'STATUS_ID'         => 'NEW',
								'CHECK_PERMISSIONS' => 'N'
							),
							false, false,
							array('ID', 'DATE_CREATE', 'DATE_MODIFY')
						);

						while ($lead = $leadRes->fetch())
						{
							if ($lead['DATE_CREATE'] == $lead['DATE_MODIFY'])
							{
								$response = $DB->query(sprintf(
									'SELECT 1 FROM b_crm_act_bind WHERE OWNER_ID = %u AND OWNER_TYPE_ID = %u',
									$lead['ID'], \CCrmOwnerType::Lead
								))->fetch();
								if (!$response)
								{
									$obsoleteLead = new \CCrmLead(false);
									$obsoleteLead->delete($lead['ID']);
								}
							}
						}
					}
				}

				break;
			}
		}
	}

	public static function OnActivityModified(\Bitrix\Main\Event $event)
	{
		$before  = $event->getParameter('before');
		$current = $event->getParameter('current');

		if ($before['COMPLETED'] != $current['COMPLETED'])
		{
			if ($current['TYPE_ID'] == \CCrmActivityType::Email && $current['DIRECTION'] == \CCrmActivityDirection::Incoming)
			{
				$mailMeta = \Bitrix\Crm\Activity\MailMetaTable::getList(array(
					'select' => array('HASH' => 'MSG_HEADER_HASH'),
					'filter' => array('=ACTIVITY_ID' => $current['ID']),
				))->fetch();

				if ($mailMeta && \CModule::includeModule('mail'))
				{
					\Bitrix\Mail\Helper::updateImapMessage($current['RESPONSIBLE_ID'], $mailMeta['HASH'], array(
						'seen' => $current['COMPLETED'] == 'Y',
					), $error);
				}
			}
		}
	}

	public static function OnActivityDelete($id)
	{
		\Bitrix\Crm\Activity\MailMetaTable::delete($id);
	}

	public static function OnOutgoingMessageRead($fields)
	{
		if (preg_match('/^(\d+)-[0-9a-z]+$/i', trim($fields['urn']), $matches))
		{
			$activity = \CCrmActivity::getList(
				array(),
				array(
					'ID' => $matches[1],
					'=%URN' => $matches[0],
					'DIRECTION' => \CCrmActivityDirection::Outgoing,
					'CHECK_PERMISSIONS' => 'N',
				),
				false,
				false,
				array('ID', 'RESPONSIBLE_ID', 'SETTINGS', 'OWNER_TYPE_ID', 'OWNER_ID')
			)->fetch();

			if (!empty($activity) and empty($activity['SETTINGS']['READ_CONFIRMED']) || $activity['SETTINGS']['READ_CONFIRMED'] <= 0)
			{
				\Bitrix\Crm\Timeline\EmailActivityStatuses\Entry::create([
					'ACTIVITY_ID' => $activity['ID'],
					'AUTHOR_ID' => $activity['RESPONSIBLE_ID'],
					'OWNER_TYPE_ID' => $activity['OWNER_TYPE_ID'],
					'OWNER_ID' => $activity['OWNER_ID'],
				]);

				$activity['SETTINGS']['READ_CONFIRMED'] = time();

				ActivityTable::update($activity['ID'],
					[
						'SETTINGS' => $activity['SETTINGS'],
					]
				);

				if (\Bitrix\Main\Loader::includeModule('pull'))
				{
					//$datetimeFormat = \Bitrix\Main\Loader::includeModule('intranet')
					//	? \CIntranetUtils::getCurrentDatetimeFormat() : false;
					\Bitrix\Pull\Event::add($activity['RESPONSIBLE_ID'], array(
						'module_id' => 'crm',
						'command' => 'activity_email_read_confirmed',
						'params' => array(
							'ID' => $activity['ID'],
							'READ_CONFIRMED' => $activity['SETTINGS']['READ_CONFIRMED'],
							//'READ_CONFIRMED_FORMATTED' => \CComponentUtil::getDateTimeFormatted(
							//	$activity['SETTINGS']['READ_CONFIRMED']+\CTimeZone::getOffset(),
							//	$datetimeFormat,
							//	\CTimeZone::getOffset()
							//),
						),
					));
				}

				//Execute automation trigger - EmailReadTrigger
				$bindings = \CCrmActivity::GetBindings($activity['ID']);
				if ($bindings)
				{
					\Bitrix\Crm\Automation\Trigger\EmailReadTrigger::execute($bindings, $activity);
				}
			}
		}
	}

	public static function OnOutgoingMessageClick($fields)
	{
		if (preg_match('/^(\d+)-[0-9a-z]+$/i', trim($fields['urn']), $matches))
		{
			$activity = \CCrmActivity::getList(
				array(),
				array(
					'ID' => $matches[1],
					'=%URN' => $matches[0],
					'DIRECTION' => \CCrmActivityDirection::Outgoing,
					'CHECK_PERMISSIONS' => 'N',
				),
				false,
				false,
				array('ID', 'SETTINGS')
			)->fetch();

			if (!empty($activity))
			{
				if (empty($activity['SETTINGS']['CLICK_CONFIRMED']) || $activity['SETTINGS']['CLICK_CONFIRMED'] <= 0)
				{
					$activity['SETTINGS']['CLICK_CONFIRMED'] = time();
					\CCrmActivity::update($activity['ID'], array('SETTINGS' => $activity['SETTINGS']), false, false);
				}

				//Execute automation trigger - EmailLinkTrigger
				$bindings = \CCrmActivity::GetBindings($activity['ID']);
				if ($bindings)
				{
					\Bitrix\Crm\Automation\Trigger\EmailLinkTrigger::execute($bindings, $fields);
				}
			}
		}
	}

	public static function GetEOL()
	{
		return CEvent::GetMailEOL();
	}
}

class CCrmEMailCodeAllocation
{
	const None = 0;
	const Subject = 1;
	const Body = 2;
	private static $ALL_DESCRIPTIONS = null;
	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Body => GetMessage('CRM_EMAIL_CODE_ALLOCATION_BODY'),
				self::Subject => GetMessage('CRM_EMAIL_CODE_ALLOCATION_SUBJECT'),
				self::None => GetMessage('CRM_EMAIL_CODE_ALLOCATION_NONE')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}
	public static function PrepareListItems()
	{
		return CCrmEnumeration::PrepareListItems(self::GetAllDescriptions());
	}
	public static function IsDefined($value)
	{
		$value = intval($value);
		return $value >= self::None && $value <= self::Body;
	}
	public static function SetCurrent($value)
	{
		if(!self::IsDefined($value))
		{
			$value = self::Body;
		}

		COption::SetOptionString('crm', 'email_service_code_allocation', $value);
	}
	public static function GetCurrent()
	{
		$value = intval(COption::GetOptionString('crm', 'email_service_code_allocation', self::Body));
		return self::IsDefined($value) ? $value : self::Body;
	}
}
