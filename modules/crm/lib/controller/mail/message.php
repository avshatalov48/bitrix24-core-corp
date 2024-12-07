<?php

namespace Bitrix\Crm\Controller\Mail;

use Bitrix\Crm\Activity\Mail\SanitizedDescriptionCache;
use Bitrix\Crm\Integration\Mail\Client;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Disk\File;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Error;
use Bitrix\Crm\Activity\Provider\Email;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Mail\Helper;
use Bitrix\Main\Config;
use Bitrix\Main\Mail;
use Bitrix\UI\FileUploader\Uploader;
use Bitrix\Mobile\UI;
use Bitrix\Main\Engine\Response\Redirect;
use Bitrix\Main\HttpResponse;
use Bitrix\Mail\Helper\DownloadResponse;

class Message extends Controller
{
	protected const PERMISSION_READ = 1;
	protected const SUPPORTED_ACTIVITY_TYPE = 'CRM_EMAIL';
	protected const EMAIL_COMMUNICATION_TYPE = 'EMAIL';
	protected const ENTITIES_THAT_HAVE_CONTACTS = [
		\CCrmOwnerType::DealRecurringName,
		\CCrmOwnerType::QuoteName,
		\CCrmOwnerType::SmartInvoiceName,
		\CCrmOwnerType::CompanyName,
		\CCrmOwnerType::DealName,
		\CCrmOwnerType::LeadName,
	];
	protected const NOT_RECIPIENT_OWNER_TYPES = [
		\CCrmOwnerType::Lead,
		\CCrmOwnerType::Order,
		\CCrmOwnerType::Deal,
		\CCrmOwnerType::DealRecurring,
		\CCrmOwnerType::Quote,
		\CCrmOwnerType::SmartInvoice
	];

	protected $errorCollection = [];

	/**
	 * Controller actions configuration
	 *
	 * @return array
	 */
	public function configureActions(): array
	{
		return [
			'downloadHtmlBody' => [
				'+prefilters' => [
					new \Bitrix\Main\Engine\ActionFilter\CloseSession(),
				],
				'-prefilters' => [
					\Bitrix\Main\Engine\ActionFilter\Csrf::class,
				],
			],
			'getDescriptionAndQuote' => [
				'+prefilters' => [
					new \Bitrix\Main\Engine\ActionFilter\CloseSession(),
				],
				'-prefilters' => [
					\Bitrix\Main\Engine\ActionFilter\Csrf::class,
				],
			],
		];
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function deleteMessageAction($data)
	{
		/*
		 * @TODO: The old code has been moved to the controller. Refactor later
	 	*/
		if (!$this->checkModules())
		{
			return false;
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return false;
		}

		$ID = isset($data['ITEM_ID']) ? intval($data['ITEM_ID']) : 0;

		if($ID <= 0)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_ACTIVITY_NOT_FOUND')));
			return false;
		}

		$arActivity = \CCrmActivity::GetByID($ID);
		if(!$arActivity)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_ACTIVITY_NOT_FOUND')));
			return false;
		}

		$provider = \CCrmActivity::GetActivityProvider($arActivity);
		if(!$provider)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PROVIDER_NOT_FOUND')));
			return false;
		}

		$ownerTypeName = isset($data['OWNER_TYPE'])? mb_strtoupper(strval($data['OWNER_TYPE'])) : '';
		if($provider::checkOwner() && !isset($ownerTypeName[0]))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_OWNER_TYPE_IS_NOT_DEFINED')));
			return false;
		}

		$ownerID = isset($data['OWNER_ID']) ? intval($data['OWNER_ID']) : 0;
		if($provider::checkOwner() && $ownerID <= 0)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_OWNER_TYPE_IS_NOT_DEFINED')));
			return false;
		}

		if($provider::checkOwner() && !\CCrmActivity::CheckUpdatePermission(\CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return false;
		}

		$currentUser = \CCrmSecurityHelper::getCurrentUserId();

		$isOutgoing = \CCrmActivityDirection::Outgoing == $arActivity['DIRECTION'];
		$isSkiplist = $isBlacklist = false;

		$isSkip = !empty($_REQUEST['IS_SKIP']) && $_REQUEST['IS_SKIP'] == 'Y';
		if ($isSkip && !$isOutgoing)
		{
			$exclusionAccess = new \Bitrix\Crm\Exclusion\Access($currentUser);

			$isSkiplist = $exclusionAccess->canWrite();
		}

		$isSpam = !empty($_REQUEST['IS_SPAM']) && $_REQUEST['IS_SPAM'] == 'Y';
		if ($isSpam && !$isOutgoing && \CModule::includeModule('mail'))
		{
			$res = \Bitrix\Mail\MailboxTable::getList(array(
				'select' => array('ID', 'OPTIONS'),
				'filter' => array(
					'=LID'     => SITE_ID,
					'=ACTIVE'  => 'Y',
					'=USER_ID' => $currentUser,
				),
				'order' => array('ID' => 'DESC'),
			));

			while ($mailbox = $res->fetch())
			{
				if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', (array) $mailbox['OPTIONS']['flags']))
				{
					$isBlacklist = true;
					break;
				}
			}
		}

		if ($isSkiplist || $isBlacklist)
		{
			$communications = \CCrmActivity::getCommunications($ID);
			if (!empty($communications))
			{
				$blacklist = array();
				foreach ($communications as $item)
				{
					if ($item['TYPE'] == 'EMAIL' && !empty($item['VALUE']) && check_email($item['VALUE']))
					{
						// copied from check_email
						if (preg_match('/.*?[<\[\(](.+?)[>\]\)].*/i', $item['VALUE'], $matches))
							$item['VALUE'] = $matches[1];

						$blacklist[] = trim($item['VALUE']);
					}
				}

				$blacklist = array_unique($blacklist);
			}
		}

		if(\CCrmActivity::Delete($ID))
		{
			if (!empty($blacklist))
			{
				if ($isSkiplist)
				{
					foreach ($blacklist as $item)
					{
						\Bitrix\Crm\Exclusion\Store::add(\Bitrix\Crm\Communication\Type::EMAIL, $item);
					}
				}

				if ($isBlacklist)
				{
					$existsEntries = \Bitrix\Mail\BlacklistTable::getList(array(
						'select' => array('ITEM_VALUE'),
						'filter' => array(
							'MAILBOX_ID'  => $mailbox['ID'],
							'ITEM_TYPE'   => \Bitrix\Mail\Blacklist\ItemType::EMAIL,
							'@ITEM_VALUE' => $blacklist,
						),
					));
					foreach ($existsEntries as $item)
					{
						if (($k = array_search($item['ITEM_VALUE'], $blacklist)) !== false)
							unset($blacklist[$k]);
					}

					if (!empty($blacklist))
					{
						foreach ($blacklist as $item)
						{
							\Bitrix\Mail\BlacklistTable::add(array(
								'SITE_ID'    => SITE_ID,
								'MAILBOX_ID' => $mailbox['ID'],
								'ITEM_TYPE'  => \Bitrix\Mail\Blacklist\ItemType::EMAIL,
								'ITEM_VALUE' => $item,
							));
						}
					}
				}
			}

			return(['DELETED_ITEM_ID'=> $ID]);
		}

		$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_COULD_NOT_DELETE_MESSAGE', ['#ID#'=> $ID])));
		return false;
	}

	private function getMessageAsQuote($activityId): string
	{
		if (!$this->checkModules())
		{
			return '';
		}

		$activities = $this->getActivities(
			[
				'ID' => $activityId,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			[
				'ID',
				'SUBJECT',
				'OWNER_ID',
				'SETTINGS',
				'START_TIME',
				'DESCRIPTION',
				'OWNER_TYPE_ID',
			],
			limit: 1,
		);

		if (!$this->checkActivityPermission(self::PERMISSION_READ, $activities))
		{
			return '';
		}

		$activity = $activities[0];

		if (!$activity)
		{
			return '';
		}

		return Email::getMessageQuote($activity, $activity['DESCRIPTION'] ?? '');
	}

	protected function checkNotRecipientOwnerTypes($typeId): bool
	{
		return (
			in_array($typeId, self::NOT_RECIPIENT_OWNER_TYPES) ||
			\CCrmOwnerType::isPossibleSuspendedDynamicTypeId($typeId) ||
			\CCrmOwnerType::isPossibleDynamicTypeId($typeId)
		);
	}

	/**
	 * @throws \Bitrix\Main\NotSupportedException
	 * @throws ObjectPropertyException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function sendMessageAction($data)
	{
		/*
		 * @TODO: The old code has been moved to the controller. Refactor later
		 */

		if(!$this->checkModules())
		{
			return false;
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return false;
		}

		$ID = isset($data['ID']) ? intval($data['ID']) : 0;
		$isNew = $ID <= 0;

		$userID = \CCrmSecurityHelper::GetCurrentUser()->GetID();
		$responsibleId = (int) $userID;
		$mailboxOwnerId = null;

		if ($userID <= 0)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_USER_NOT_FOUND')));
			return false;
		}

		$now = convertTimeStamp(time() + \CTimeZone::getOffset(), 'FULL', SITE_ID);

		$subject = isset($data['subject']) ? strval($data['subject']) : '';

		if ($subject == '')
		{
			$subject = Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_DEFAULT_SUBJECT', ['#DATE#'=> $now]);
		}

		$arErrors = [];

		$socNetLogDestTypes = [
			\CCrmOwnerType::LeadName    => 'leads',
			\CCrmOwnerType::DealName    => 'deals',
			\CCrmOwnerType::ContactName => 'contacts',
			\CCrmOwnerType::CompanyName => 'companies',
		];

		$acceptableTypes = [
			\CCrmOwnerType::LeadName,
			\CCrmOwnerType::DealName,
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::CompanyName,
		];

		$to  = array();
		$cc  = array();
		$bcc = array();

		$countCc = 0;
		$countBcc = 0;
		$countTo = 0;

		// Bindings & Communications -->
		$arBindings = array();
		$arComms = array();
		$commData = $data['communications'] ?? [];
		foreach (array('to', 'cc', 'bcc') as $field)
		{
			if (!empty($data[$field]) && is_array($data[$field]))
			{
				foreach ($data[$field] as $item)
				{
					try
					{
						$item = \Bitrix\Main\Web\Json::decode($item);

						if (!in_array($item['entityType'], $acceptableTypes, true))
						{
							$item['entityType'] = array_search($item['entityType'], $socNetLogDestTypes);
						}

						$item['type'] = 'EMAIL';
						$item['value'] = $item['email'];
						$item['__field'] = $field;

						$commData[] = $item;

						if($field === 'to')
						{
							$countTo++;
						}
						else if($field === 'cc')
						{
							$countCc++;
						}
						else if($field === 'bcc')
						{
							$countBcc++;
						}
					}
					catch (\Exception $e)
					{
					}
				}
			}
		}

		$emailsLimitToSendMessage = Helper\LicenseManager::getEmailsLimitToSendMessage();

		if($emailsLimitToSendMessage !== -1 && ($countTo > $emailsLimitToSendMessage || $countCc > $emailsLimitToSendMessage || $countBcc > $emailsLimitToSendMessage))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_TARIFF_RESTRICTION',["#COUNT#" => $emailsLimitToSendMessage])));
			return false;
		}

		if (count($commData) > 10)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_TO_MANY_RECIPIENTS')));
			return false;
		}

		$sourceList = \CCrmStatus::getStatusList('SOURCE');
		if (isset($sourceList['EMAIL']))
		{
			$sourceId = 'EMAIL';
		}
		else if (isset($sourceList['OTHER']))
		{
			$sourceId = 'OTHER';
		}

		$contactTypes = \CCrmStatus::getStatusList('CONTACT_TYPE');
		if (isset($contactTypes['CLIENT']))
		{
			$contactType = 'CLIENT';
		}
		else if (isset($contactTypes['OTHER']))
		{
			$contactType = 'OTHER';
		}

		foreach ($commData as &$commDatum)
		{
			$commID = isset($commData['id']) ? intval($commData['id']) : 0;
			$commEntityType = isset($commDatum['entityType'])? mb_strtoupper(strval($commDatum['entityType'])) : '';
			$commEntityID = isset($commDatum['entityId']) ? intval($commDatum['entityId']) : 0;

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
					$arErrors[] = new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_INVALID_EMAIL', ['#VALUE#' => $commValue]));
					continue;
				}

				$rcptFieldName = 'to';
				if (isset($commDatum['__field']))
				{
					$commDatum['__field'] = mb_strtolower($commDatum['__field']);
					if (in_array($commDatum['__field'], array('to', 'cc', 'bcc')))
						$rcptFieldName = $commDatum['__field'];
				}

				${$rcptFieldName}[] = mb_strtolower(trim($commValue));
			}

			if (isset($commDatum['isEmail']) && $commDatum['isEmail'] == 'Y' && mb_strtolower(trim($commValue)))
			{
				$newEntityTypeId = \Bitrix\Crm\Settings\ActivitySettings::getCurrent()->getOutgoingEmailOwnerTypeId();
				if (\CCrmOwnerType::Contact == $newEntityTypeId && \CCrmContact::checkCreatePermission())
				{
					$contactFields = array(
						'NAME'           => $commDatum['params']['name'] ?? '',
						'LAST_NAME'      => $commDatum['params']['lastName'] ?? '',
						'RESPONSIBLE_ID' => $userID,
						'FM'             => array(
							'EMAIL' => array(
								'n1' => array(
									'VALUE_TYPE' => 'WORK',
									'VALUE'      => mb_strtolower(trim($commValue))
								)
							)
						),
					);

					if ('' != $contactType)
					{
						$contactFields['TYPE_ID'] = $contactType;
					}

					if ('' != $sourceId)
					{
						$contactFields['SOURCE_ID'] = $sourceId;
					}

					if ($contactFields['NAME'] == '' && $contactFields['LAST_NAME'] == '')
						$contactFields['NAME'] = mb_strtolower(trim($commValue));

					$contactEntity = new \CCrmContact();
					$contactId = $contactEntity->add(
						$contactFields, true,
						array(
							'DISABLE_USER_FIELD_CHECK' => true,
							'REGISTER_SONET_EVENT'     => true,
							'CURRENT_USER'             => $userID,
						)
					);

					if ($contactId > 0)
					{
						$commEntityType = \CCrmOwnerType::ContactName;
						$commEntityID   = $contactId;

						$bizprocErrors = array();
						\CCrmBizProcHelper::autostartWorkflows(
							\CCrmOwnerType::Contact, $contactId,
							\CCrmBizProcEventType::Create,
							$bizprocErrors
						);
					}
				}
				else if (\CCrmLead::checkCreatePermission())
				{
					$leadFields = array(
						'TITLE'          => $subject,
						'NAME'           => $commDatum['params']['name'] ?? '',
						'LAST_NAME'      => $commDatum['params']['lastName'] ?? '',
						'STATUS_ID'      => 'NEW',
						'OPENED'         => 'Y',
						'FM'             => array(
							'EMAIL' => array(
								'n1' => array(
									'VALUE_TYPE' => 'WORK',
									'VALUE'      => mb_strtolower(trim($commValue))
								)
							)
						),
					);

					if ('' != $sourceId)
					{
						$leadFields['SOURCE_ID'] = $sourceId;
					}

					$leadEntity = new \CCrmLead(false);
					$leadId = $leadEntity->add(
						$leadFields, true,
						array(
							'DISABLE_USER_FIELD_CHECK' => true,
							'REGISTER_SONET_EVENT'     => true,
							'CURRENT_USER'             => $userID,
						)
					);

					if ($leadId > 0)
					{
						$commEntityType = \CCrmOwnerType::LeadName;
						$commEntityID   = $leadId;

						$bizprocErrors = [];
						\CCrmBizProcHelper::autostartWorkflows(
							\CCrmOwnerType::Lead, $leadId,
							\CCrmBizProcEventType::Create,
							$bizprocErrors
						);

						$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $leadId);
						$starter->setUserIdFromCurrent()->runOnAdd();
					}
				}
			}

			$key = md5(sprintf(
				'%s_%u_%s_%s',
				$commEntityType,
				$commEntityID,
				$commType,
				mb_strtolower(trim($commValue))
			));
			$arComms[$key] = array(
				'ID' => $commID,
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

		$blackListed =
			Mail\Internal\BlacklistTable::query()
				->setSelect(["CODE"])
				->whereIn("CODE",$array = array_merge_recursive($to,$cc,$bcc))
				->exec()
				->fetchAll()
		;

		if (!empty($blackListed = array_column($blackListed,"CODE")))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_EMAILS_IN_BLACKLISTED', ['#EMAILS#' => implode("; ",$blackListed)])));
			return false;
		}
		elseif (empty($to))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_EMPTY_TO_FIELD')));
			return false;
		}
		elseif (!empty($arErrors))
		{
			$this->addErrors($arErrors);
			return false;
		}

		$ownerTypeName = isset($data['ownerType'])? mb_strtoupper(strval($data['ownerType'])) : '';
		$ownerTypeID = !empty($ownerTypeName) ? \CCrmOwnerType::resolveId($ownerTypeName) : 0;
		$ownerID = isset($data['ownerId']) ? intval($data['ownerId']) : 0;

		$bindData = $data['bindings'] ?? [];
		if (!empty($data['docs']) && is_array($data['docs']))
		{
			foreach ($data['docs'] as $item)
			{
				try
				{
					$item = \Bitrix\Main\Web\Json::decode($item);
					$item['entityType'] = array_search($item['entityType'], $socNetLogDestTypes);

					$bindData[] = $item;
				}
				catch (\Exception $e)
				{
				}
			}
		}

		foreach ($bindData as $item)
		{
			$item['entityTypeId'] = \CCrmOwnerType::resolveID($item['entityType']);
			if ($item['entityTypeId'] > 0 && $item['entityId'] > 0)
			{
				$key = sprintf('%u_%u', $item['entityType'], $item['entityId']);
				if ($this->checkNotRecipientOwnerTypes($item['entityTypeId']) && !isset($arBindings[$key]))
				{
					$ownerTypeID = $item['entityTypeId'];
					$ownerID = (int) $item['entityId'];

					$arBindings[$key] = array(
						'OWNER_TYPE_ID' => $item['entityTypeId'],
						'OWNER_ID'      => $item['entityId']
					);
				}
			}
		}

		$ownerBinded = false;
		if ($ownerTypeID > 0 && $ownerID > 0)
		{
			foreach ($arBindings as $item)
			{
				if ($ownerTypeID == $item['OWNER_TYPE_ID'] && $ownerID == $item['OWNER_ID'])
				{
					$ownerBinded = true;
					break;
				}
			}
		}

		if ($ownerBinded)
		{
			$checkedOwnerType = $ownerTypeID;
			if ($ownerTypeID == \CCrmOwnerType::DealRecurring)
			{
				$checkedOwnerType = \CCrmOwnerType::Deal;
			}
			if (!\CCrmActivity::checkUpdatePermission($checkedOwnerType, $ownerID))
			{
				$errorMsg = Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED');

				$entityTitle = \CCrmOwnerType::getCaption($ownerTypeID, $ownerID, false);

				if (\CCrmOwnerType::Contact == $ownerTypeID)
					$errorMsg = Loc::getMessage('CRM_MAIL_CONTROLLER_CONTACT_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
				else if (\CCrmOwnerType::Company == $ownerTypeID)
					$errorMsg = Loc::getMessage('CRM_MAIL_CONTROLLER_COMPANY_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
				else if (\CCrmOwnerType::Lead == $ownerTypeID)
					$errorMsg = Loc::getMessage('CRM_MAIL_CONTROLLER_LEAD_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
				else if (\CCrmOwnerType::Deal == $ownerTypeID || \CCrmOwnerType::DealRecurring == $ownerTypeID)
					$errorMsg = Loc::getMessage('CRM_MAIL_CONTROLLER_DEAL_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));

				$this->addError(new Error($errorMsg));
				return false;
			}
		}
		else
		{
			$ownerTypeID = 0;
			$ownerID = 0;

			$typesPriority = array(
				\CCrmOwnerType::Deal    => 1,
				\CCrmOwnerType::Order   => 2,
				\CCrmOwnerType::Contact => 3,
				\CCrmOwnerType::Company => 4,
				\CCrmOwnerType::Lead    => 5,
			);

			foreach ($arBindings as $item)
			{
				if ($ownerTypeID <= 0 || $typesPriority[$item['OWNER_TYPE_ID']] < $typesPriority[$ownerTypeID])
				{
					if (\CCrmActivity::checkUpdatePermission($item['OWNER_TYPE_ID'], $item['OWNER_ID']))
					{
						$ownerTypeID = $item['OWNER_TYPE_ID'];
						$ownerID     = $item['OWNER_ID'];
						$ownerBinded = true;
					}
				}
			}

			if (!$ownerBinded)
			{
				$this->addError(new Error(Loc::getMessage(
					empty($arBindings)
						? 'CRM_MAIL_CONTROLLER_MESSAGE_EMAIL_EMPTY_TO_FIELD'
						: 'CRM_MAIL_CONTROLLER_PERMISSION_DENIED'
				)));
				return false;
			}
		}

		// single deal binding
		$dealBinded = \CCrmOwnerType::Deal == $ownerTypeID;
		foreach ($arBindings as $key => $item)
		{
			if (\CCrmOwnerType::Deal == $item['OWNER_TYPE_ID'])
			{
				if ($dealBinded)
					unset($arBindings[$key]);

				$dealBinded = true;
			}
		}

		$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail', ''));

		$from  = '';
		$reply = '';
		$rawCc = $cc;

		if (isset($data['from']))
			$from = trim(strval($data['from']));

		if ($from == '')
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_EMAIL_EMPTY_FROM_FIELD')));
			return false;
		}
		else
		{
			$fromEmail = $from;
			$fromAddress = new \Bitrix\Main\Mail\Address($fromEmail);

			if ($fromAddress->validate())
			{
				$fromEmail = $fromAddress->getEmail();

				\CBitrixComponent::includeComponentClass('bitrix:main.mail.confirm');
				if (!in_array($fromEmail, array_column(\MainMailConfirmComponent::prepareMailboxes(), 'email')))
				{
					$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_EMAIL_PERMISSION_DENIED_FROM_FIELD')));
					return false;
				}

				if ($fromAddress->getName())
				{
					$fromEncoded = sprintf(
						'%s <%s>',
						sprintf('=?%s?B?%s?=', SITE_CHARSET, base64_encode($fromAddress->getName())),
						$fromEmail
					);
				}
			}
			else
			{
				$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_INVALID_EMAIL', ['#VALUE#' => $from])));
				return false;
			}

			if (\CModule::includeModule('mail'))
			{
				/**
				 * @todo Explicitly enter the ID. This will increase the productivity of selection.
				 */
				$mailboxHelper = \Bitrix\Mail\Helper\Mailbox::findBy(null, $fromEmail);

				if (!empty($mailboxHelper))
				{
					$mailboxOwnerId = $mailboxHelper->getMailboxOwnerId();
				}
			}

			if (empty($mailboxHelper))
			{
				if ($crmEmail != '' && $crmEmail != $fromEmail)
				{
					$reply = $fromEmail . ', ' . $crmEmail;
				}

				$injectUrn = true;
			}

			if ('Y' === ($data['from_copy'] ?? null))
			{
				$cc[] = $fromEmail;
			}
		}

		$messageBody = '';
		$contentType = isset($data['content_type']) && \CCrmContentType::isDefined($data['content_type'])
			? (int)$data['content_type'] : \CCrmContentType::BBCode;

		$parentId = isset($data['REPLIED_ID']) ? (int)$data['REPLIED_ID'] : 0;
		$messageQuote = '';

		if ($parentId > 0)
		{
			$messageQuote = $this->getMessageAsQuote($parentId);
		}

		if (isset($data['message']))
		{
			$messageBody = (string)$data['message'];

			if (\CCrmContentType::Html == $contentType)
			{
				$messageBody = Helper\Message::sanitizeHtml($messageBody);
			}
			else
			{
				if (\CCrmContentType::PlainText == $contentType)
				{
					$messageBody = sprintf(
						'<html><body>%s</body></html>',
						preg_replace('/[\r\n]+/u', '<br>', htmlspecialcharsbx($messageBody))
					);
				}
				elseif (\CCrmContentType::BBCode == $contentType)
				{
					//Convert BBCODE to HTML
					$parser = new \CTextParser();
					$parser->allow['SMILES'] = 'N';
					$messageBody = '<html><body>' . $parser->convertText($messageBody) . '</body></html>';
				}
			}
		}

		$messageBody = nl2br($messageBody);
		$messageBody .= $messageQuote;

		if (($messageHtml = $messageBody) != '')
		{
			\CCrmActivity::addEmailSignature($messageHtml, \CCrmContentType::Html);
		}

		if ($parentId > 0 && !$dealBinded)
		{
			$parentBindings = \CCrmActivity::getBindings($parentId);
			foreach ($parentBindings as $item)
			{
				$key = sprintf('%u_%u', \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']), $item['OWNER_ID']);
				if (\CCrmOwnerType::Deal == $item['OWNER_TYPE_ID'] && !isset($arBindings[$key]))
				{
					$arBindings[$key] = array(
						'OWNER_TYPE_ID' => $item['OWNER_TYPE_ID'],
						'OWNER_ID'      => $item['OWNER_ID'],
					);

					break;
				}
			}
		}

		$arBindings = array_merge(
			array(
				sprintf('%u_%u', \CCrmOwnerType::resolveName($ownerTypeID), $ownerID) => array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID,
				),
			),
			$arBindings
		);

		$arFields = [
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'TYPE_ID' =>  \CCrmActivityType::Email,
			'SUBJECT' => $subject,
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => 'Y',
			'AUTHOR_ID' => $mailboxOwnerId,
			'RESPONSIBLE_ID' => $responsibleId,
			'EDITOR_ID' => $responsibleId,
			'PRIORITY' => !empty($data['important']) ? \CCrmActivityPriority::High : \CCrmActivityPriority::Medium,
			'DESCRIPTION' => ($description = $messageHtml),
			'DESCRIPTION_TYPE' => \CCrmContentType::Html,
			'DIRECTION' => \CCrmActivityDirection::Outgoing,
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'BINDINGS' => array_values($arBindings),
			'COMMUNICATIONS' => $arComms,
			'PARENT_ID' => $parentId,
		];

		$arFileIDs = [];
		$storageTypeID = \CCrmActivityStorageType::Disk;
		$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
		$fileTokens = isset($data['fileTokens']) && is_array($data['fileTokens']) ? $data['fileTokens'] : [];

		$currentFilesIds = array_values(
			array_unique(
				array_filter(
					array_map(static function($item) {
						if (is_numeric($item))
						{
							return (int) $item;
						}
					}, $fileTokens)
				)
			)
		);

		$pendingFilesIds = array_values(
			array_unique(
				array_filter(
					array_map(static function($item) {
						if (!is_numeric($item) && is_string($item))
						{
							return $item;
						}
					}, $fileTokens)
				)
			)
		);

		$fileController = new Uploader(
			new \Bitrix\Crm\FileUploader\MailUploaderController([
				'ownerId' => (int) $data['ownerId'],
				'ownerType' => $data['ownerType'],
			])
		);

		if(count($currentFilesIds))
		{
			$currentFilesIds = $this->checkMessageBFileIds(
				$parentId,
				new ItemIdentifier(\CCrmOwnerType::resolveId($data['ownerType']), (int)$data['ownerId']),
				$currentFilesIds
			);
		}

		$pendingFiles = $fileController->getPendingFiles($pendingFilesIds);

		foreach ($currentFilesIds as $id)
		{
			$copyFileId = \CFile::CloneFile($id);
			$fileData = \CFile::getFileArray($copyFileId);

			if($fileData)
			{
				$diskFileId = \Bitrix\Crm\Integration\DiskManager::saveFile($fileData);

				if($diskFileId)
				{
					$arFileIDs[] = $diskFileId;
				}
			}
		}

		foreach ($pendingFiles as $pendingFile)
		{
			$fileData = \CFile::getFileArray($pendingFile->getFileId());

			if($fileData)
			{
				$diskFileId = \Bitrix\Crm\Integration\DiskManager::saveFile($fileData);

				if($diskFileId)
				{
					$pendingFile->makePersistent();
					$arFileIDs[] = $diskFileId;
				}
				else
				{
					$pendingFile->remove();
				}
			}
			else
			{
				$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_ERROR_IN_FILES')));
				return false;
			}
		}

		$arFileIDs = array_filter($arFileIDs);
		if(!empty($arFileIDs) || !$isNew)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = \Bitrix\Crm\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);

			if (!is_array($arFileIDs) || !is_array($arFields['STORAGE_ELEMENT_IDS']))
			{
				addMessage2Log(
					sprintf(
						"crm.activity.editor\ajax.php: Invalid email attachments list\r\n(%s) -> (%s)",
						$arFileIDs,
						$arFields['STORAGE_ELEMENT_IDS']
					),
					'crm',
					0
				);
			}
			else if (count($arFileIDs) > count($arFields['STORAGE_ELEMENT_IDS']))
			{
				addMessage2Log(
					sprintf(
						"crm.activity.editor\ajax.php: Email attachments list had been filtered\r\n(%s) -> (%s)",
						join(',', $arFileIDs),
						join(',', $arFields['STORAGE_ELEMENT_IDS'])
					),
					'crm',
					0
				);
			}
		}

		$totalSize = 0;

		$arRawFiles = array();
		if (isset($arFields['STORAGE_ELEMENT_IDS']) && !empty($arFields['STORAGE_ELEMENT_IDS']))
		{
			foreach ((array) $arFields['STORAGE_ELEMENT_IDS'] as $item)
			{
				$arRawFiles[$item] = \Bitrix\Crm\Integration\StorageManager::makeFileArray($item, $storageTypeID);

				$totalSize += $arRawFiles[$item]['size'];

				if (\CCrmContentType::Html == $contentType)
				{
					$fileInfo = \Bitrix\Crm\Integration\StorageManager::getFileInfo(
						$item, $storageTypeID, false,
						array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $ID)
					);

					$description = preg_replace(
						sprintf('/(https?:\/\/)?bxacid:n?%u/i', $item),
						htmlspecialcharsbx($fileInfo['VIEW_URL']),
						$description
					);
				}
			}
		}

		$maxSize = Helper\Message::getMaxAttachedFilesSize();
		if ($maxSize > 0 && $maxSize <= ceil($totalSize / 3) * 4) // base64 coef.
		{
			$this->addError(new Error(Loc::getMessage(
				'CRM_MAIL_CONTROLLER_MESSAGE_MAX_SIZE_EXCEED',
				['#SIZE#' => \CFile::formatSize(Helper\Message::getMaxAttachedFilesSizeAfterEncoding())]
			)));
			return false;
		}

		if ($isNew)
		{
			if(!($ID = \CCrmActivity::Add($arFields, false, false, array('REGISTER_SONET_EVENT' => true))))
			{
				$this->addError(new Error(\CCrmActivity::GetLastErrorMessage()));
				return false;
			}
		}
		else
		{
			if(!\CCrmActivity::Update($ID, $arFields, false, false))
			{
				$this->addError(new Error(\CCrmActivity::GetLastErrorMessage()));
				return false;
			}
		}

		$hostname = \COption::getOptionString('main', 'server_name', '') ?: 'localhost';
		if (defined('BX24_HOST_NAME') && BX24_HOST_NAME != '')
			$hostname = BX24_HOST_NAME;
		else if (defined('SITE_SERVER_NAME') && SITE_SERVER_NAME != '')
			$hostname = SITE_SERVER_NAME;

		$urn = \CCrmActivity::prepareUrn($arFields);
		$messageId = sprintf('<crm.activity.%s@%s>', $urn, $hostname);

		\CCrmActivity::update($ID, array(
			'DESCRIPTION' => $description,
			'URN'         => $urn,
			'SETTINGS'    => array(
				'IS_BATCH_EMAIL'  => Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y' ? false : null,
				'MESSAGE_HEADERS' => array(
					'Message-Id' => $messageId,
					'Reply-To'   => $reply ?: $fromEmail,
				),
				'EMAIL_META' => array(
					'__email' => $fromEmail,
					'from'    => $from,
					'replyTo' => $reply,
					'to'      => join(', ', $to),
					'cc'      => join(', ', $rawCc),
					'bcc'     => join(', ', $bcc),
				),
			),
		), false, false, array('REGISTER_SONET_EVENT' => true));

		if (!empty($_REQUEST['save_as_template']))
		{
			$templateFields = array(
				'TITLE'          => $subject,
				'IS_ACTIVE'      => 'Y',
				'OWNER_ID'       => $userID,
				'SCOPE'          => \CCrmMailTemplateScope::Personal,
				'ENTITY_TYPE_ID' => 0,
				'EMAIL_FROM'     => $from,
				'SUBJECT'        => $subject,
				'BODY_TYPE'      => \CCrmContentType::Html,
				'BODY'           => $messageBody,
				'UF_ATTACHMENT' => array_map(
					function ($item)
					{
						return is_scalar($item) ? sprintf('n%u', $item) : $item;
					},
					$arFields['STORAGE_ELEMENT_IDS']
				),
				'SORT'           => 100,
			);
			\CCrmMailTemplate::add($templateFields);
		}

		//Save user email in settings -->
		if($from !== \CUserOptions::GetOption('crm', 'activity_email_addresser', ''))
		{
			\CUserOptions::SetOption('crm', 'activity_email_addresser', $from);
		}
		//<-- Save user email in settings
		if(!empty($arErrors))
		{
			$this->addErrors($arErrors);
			return false;
		}

		// sending email
		$rcpt    = array();
		$rcptCc  = array();
		$rcptBcc = array();
		foreach ($to as $item)
			$rcpt[] = Mail\Mail::encodeHeaderFrom($item, SITE_CHARSET);
		foreach ($cc as $item)
			$rcptCc[] = Mail\Mail::encodeHeaderFrom($item, SITE_CHARSET);
		foreach ($bcc as $item)
			$rcptBcc[] = Mail\Mail::encodeHeaderFrom($item, SITE_CHARSET);

		$outgoingSubject = $subject;
		$outgoingBody = $messageHtml ?: '';

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
			$contentId = sprintf(
				'bxacid.%s@%s.crm',
				hash('crc32b', $item['external_id'].$item['size'].$item['name']),
				hash('crc32b', $hostname)
			);

			$attachments[] = array(
				'ID'           => $contentId,
				'NAME'         => $item['ORIGINAL_NAME'] ?: $item['name'],
				'PATH'         => $item['tmp_name'],
				'CONTENT_TYPE' => $item['type'],
			);

			$outgoingBody = preg_replace(
				sprintf('/(https?:\/\/)?bxacid:n?%u/i', $key),
				sprintf('cid:%s', $contentId),
				$outgoingBody
			);
		}

		$outgoingParams = array(
			'CHARSET'      => 'UTF-8',
			'CONTENT_TYPE' => 'html',
			'ATTACHMENT'   => $attachments,
			'TO'           => join(', ', $rcpt),
			'SUBJECT'      => $outgoingSubject,
			'BODY'         => $outgoingBody,
			'HEADER'       => array(
				'From'       => $fromEncoded ?: $fromEmail,
				'Reply-To'   => $reply ?: $fromEmail,
				//'To'         => join(', ', $rcpt),
				'Cc'         => join(', ', $rcptCc),
				'Bcc'        => join(', ', $rcptBcc),
				//'Subject'    => $outgoingSubject,
				'Message-Id' => $messageId,
			),
		);

		$context = new Mail\Context();
		$context->setCategory(Mail\Context::CAT_EXTERNAL);
		$context->setPriority(count($commData) > 2 ? Mail\Context::PRIORITY_LOW : Mail\Context::PRIORITY_NORMAL);
		$context->setCallback(
			(new Mail\Callback\Config())
				->setModuleId('crm')
				->setEntityType('act')
				->setEntityId($urn)
		);

		$sendResult = Mail\Mail::send(array_merge(
			$outgoingParams,
			array(
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
			)
		));

		if (!$sendResult)
		{
			if ($isNew)
			{
				if (\CModule::includeModule('bitrix24'))
				{
					if (
						method_exists(\Bitrix\Bitrix24\MailCounter::class, 'isLimited')
						&& \Bitrix\Bitrix24\MailCounter::isLimited()
					)
					{
						$arErrors[] = new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_CREATION_LIMITED'));
						\CCrmActivity::delete($ID);
						$this->addErrors($arErrors);
						return false;
					}
					elseif (
						method_exists(\Bitrix\Bitrix24\MailCounter::class, 'isCustomLimited')
						&& \Bitrix\Bitrix24\MailCounter::isCustomLimited())
					{
						$arErrors[] = new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MESSAGE_CUSTOM_LIMITED'));
						\CCrmActivity::delete($ID);
						$this->addErrors($arErrors);
						return false;
					}
				}
				$arErrors[] = new Error(Loc::getMessage('CRM_ACTIVITY_EMAIL_CREATION_CANCELED'));
			}

			$this->addErrors($arErrors);
			return false;
		}

		addEventToStatFile('crm', 'send_email_message', $_REQUEST['context'], trim(trim($messageId), '<>'));

		$needUpload = !empty($mailboxHelper);

		if ($context->getSmtp() && in_array(mb_strtolower($context->getSmtp()->getHost()), array('smtp.gmail.com', 'smtp.office365.com')))
		{
			$needUpload = false;
		}

		if ($needUpload)
		{
			class_exists('Bitrix\Mail\Helper');

			$outgoing = new \Bitrix\Mail\DummyMail(array_merge(
				$outgoingParams,
				array(
					'HEADER' => array_merge(
						$outgoingParams['HEADER'],
						array(
							'To'      => $outgoingParams['TO'],
							'Subject' => $outgoingParams['SUBJECT'],
						)
					),
				)
			));

			$mailboxHelper->uploadMessage($outgoing);
		}

		// Try add event to entity
		$CCrmEvent = new \CCrmEvent();

		$eventText  = '';
		$eventText .= GetMessage('CRM_TITLE_EMAIL_SUBJECT').': '.$subject."\n\r";
		$eventText .= GetMessage('CRM_TITLE_EMAIL_FROM').': '.$from."\n\r";
		if (!empty($to))
			$eventText .= getMessage('CRM_TITLE_EMAIL_TO').': '.implode(',', $to)."\n\r";
		if (!empty($rawCc))
			$eventText .= 'Cc: '.implode(',', $rawCc)."\n\r";
		if (!empty($bcc))
			$eventText .= 'Bcc: '.implode(',', $bcc)."\n\r";
		$eventText .= "\n\r";
		$eventText .= $description;

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
		$CCrmEvent->Add(
			array(
				'ENTITY' => $eventBindings,
				'EVENT_ID' => 'MESSAGE',
				'EVENT_TEXT_1' => $eventText,
				'FILES' => array_values($arRawFiles),
			)
		);
		// <-- Sending Email

		$commData = array();
		$communications = \CCrmActivity::GetCommunications($ID);
		foreach($communications as &$arComm)
		{
			\CCrmActivity::PrepareCommunicationInfo($arComm);
			$commData[] = array(
				'type' => $arComm['TYPE'],
				'value' => $arComm['VALUE'],
				'entityId' => $arComm['ENTITY_ID'],
				'entityType' => \CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
				'entityTitle' => $arComm['TITLE'],
				'entityUrl' => \CCrmOwnerType::GetEntityShowPath($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
			);
		}
		unset($arComm);

		$userName = '';
		if($userID > 0)
		{
			$dbResUser = \CUser::GetByID($userID);
			$userName = is_array(($user = $dbResUser->Fetch()))
				? \CUser::FormatName(\CSite::GetNameFormat(false), $user, true, false) : '';
		}

		$nowStr = ConvertTimeStamp(MakeTimeStamp($now), 'FULL', SITE_ID);

		\CCrmActivity::PrepareStorageElementIDs($arFields);
		\CCrmActivity::PrepareStorageElementInfo($arFields);

		$jsonFields = array(
			'ID' => $ID,
			'typeID' => \CCrmActivityType::Email,
			'ownerID' => $arFields['OWNER_ID'],
			'ownerType' => \CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
			'ownerTitle' => \CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
			'ownerUrl' => \CCrmOwnerType::GetEntityShowPath($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
			'subject' => $subject,
			'description' => $description,
			'descriptionHtml' => $description,
			'location' => '',
			'start' => $nowStr,
			'end' => $nowStr,
			'deadline' => $nowStr,
			'completed' => true,
			'notifyType' => \CCrmActivityNotifyType::None,
			'notifyValue' => 0,
			'priority' => \CCrmActivityPriority::Medium,
			'responsibleName' => $userName,
			'responsibleUrl' =>
				\CComponentEngine::MakePathFromTemplate(
					'/company/personal/user/#user_id#/',
					array('user_id' => $userID)
				),
			'storageTypeID' => $storageTypeID,
			'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
			'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
			'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
			'communications' => $commData
		);

		return ['ACTIVITY' => $jsonFields];
	}

	/**
	 * @throws LoaderException
	 */
	private function checkModules(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('mail'))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MAIL_MODULE_NOT_INSTALLED')));
			return false;
		}

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_CRM_MODULE_NOT_INSTALLED')));
			return false;
		}

		return true;
	}

	protected function getOwnerTypeId(string $ownerType): int
	{
		return \CCrmOwnerType::ResolveID($ownerType);
	}

	protected function getFieldsByType(string $entityTypeName, int $entityId, string $communicationType): array
	{
		$communications = [];

		$fields = \CCrmFieldMulti::GetList(
			['ID' => 'asc'],
			[
				'ENTITY_ID' => $entityTypeName,
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => $communicationType,
			]
		);

		while ($row = $fields->fetch())
		{
			if (empty($row['VALUE']))
			{
				continue;
			}

			$communications[] = [
				'ENTITY_ID' => $row['ELEMENT_ID'],
				'TYPE' => $communicationType,
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE'],
			];
		}

		return $communications;
	}

	public function canUseMailAction(): bool
	{
		return Client::isReadyToUse();
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 */
	public function getEntityContactsAction(int $ownerId, string $ownerTypeName, bool $uploadClients = true, bool $uploadSenders = true): array
	{
		$recipients = [];

		if(!$this->checkModules())
		{
			return $recipients;
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return $recipients;
		}

		$ownerTypeId = $this->getOwnerTypeId($ownerTypeName);

		if (!$this->checkOwnerReadPermission($ownerTypeId, $ownerId))
		{
			return $recipients;
		}

		if($uploadClients)
		{
			$companies = $this->getCompanies($ownerTypeName, $ownerId);
			$companiesWithEmail = [];

			$contacts = $this->getContacts($ownerTypeName, $ownerId);

			if ($ownerTypeName !== \CCrmOwnerType::ContactName)
			{
				foreach ($companies as $company)
				{
					$contacts = array_merge($contacts, $this->getContacts(\CCrmOwnerType::CompanyName, $company['id']));
				}
			}

			foreach ($companies as $company)
			{
				if (count($company['email']))
				{
					$companiesWithEmail[] = $company;
				}
			}

			$clientsByType = [
				'company' => $companiesWithEmail,
				'contacts' => $contacts,
			];

			$emailFields = $this->buildRecipients([$ownerId], $ownerTypeName);

			$recipients['clients'] = array_merge(
				$clientsByType['contacts'],
				$clientsByType['company'],
				$emailFields,
			);

			/*
			 	Iterate through the arrays and leave only the ID.
			 */
			$recipients['clientIdsByType'] = array_map(
				function($item)
				{
					if (!is_array($item))
					{
						return [];
					}

					return array_map(
						function($item)
						{
							if (isset($item['id']))
							{
								return (int)$item['id'];
							}
							return [];
						},
						$item,
					);
				},
				$clientsByType,
			);
		}

		if ($uploadSenders)
		{
			$recipients['senders'] = \Bitrix\Crm\Activity\Mail\Message::getSenderList();
		}

		return $recipients;
	}

	protected function buildRecipients(array $contactIDs, string $entityTypeName, bool $onlyWithEmail = true): array
	{
		$recipients = [];

		foreach ($contactIDs as $id)
		{
			$contactName = '';

			if ($entityTypeName === \CCrmOwnerType::CompanyName)
			{
				$contactName = \CCrmCompany::GetByID($id, false)['TITLE'];
			}
			else if($entityTypeName === \CCrmOwnerType::ContactName)
			{
				$contactName = \CCrmContact::GetByID($id, false)['FULL_NAME'];
			}
			else if($entityTypeName === \CCrmOwnerType::LeadName)
			{
				$contactName = \CCrmLead::GetByID($id, false)['TITLE'];
			}

			$contactEmailsField = \CCrmFieldMulti::GetEntityFields(
				$entityTypeName,
				$id,
				\CCrmFieldMulti::EMAIL,
			);

			$contactEmails = array_map(function($item) {
				return ['value' => $item['VALUE']];
			}, $contactEmailsField);

			$recipient = \Bitrix\Crm\Activity\Mail\Message::buildContact([
				'email' => $contactEmails,
				'name' => $contactName,
				'id' => $id,
				'typeName' => \Bitrix\Crm\Activity\Mail\Message::convertTypeToFormatForBinding($entityTypeName),
			]);

			if ($recipient['email'] === '')
			{
				$recipient['email'] = [];
			}

			if ($onlyWithEmail === false || count($recipient['email']))
			{
				$recipients[] = $recipient;
			}

		}

		return $recipients;
	}

	protected function getAllowedContactIds(array $contactIDs): array
	{
		$contactIdsPermissions = [];

		foreach ($contactIDs as $id)
		{
			if(Container::getInstance()->getUserPermissions()->checkReadPermissions(\CCrmOwnerType::Contact, $id))
			{
				$contactIdsPermissions[] = $id;
			};
		}

		return $contactIdsPermissions;
	}

	protected function getEntity(string $entityTypeName, int $ownerId): ?\Bitrix\Crm\Item
	{
		$entityTypeId = $this->getOwnerTypeId($entityTypeName);
		$entityFactory = Container::getInstance()->getFactory($entityTypeId);
		return $entityFactory->getItem($ownerId);
	}

	protected function checkEntityCanHaveContacts($typeName): bool
	{
		$typeId = \CCrmOwnerType::ResolveID($typeName);
		return (
			in_array($typeName, self::ENTITIES_THAT_HAVE_CONTACTS) ||
			\CCrmOwnerType::isPossibleSuspendedDynamicTypeId($typeId) ||
			\CCrmOwnerType::isPossibleDynamicTypeId($typeId)
		);
	}

	protected function getContacts(string $entityTypeName, int $ownerId): array
	{
		if (!$this->checkEntityCanHaveContacts($entityTypeName))
		{
			return [];
		}

		$ownerEntity = $this->getEntity($entityTypeName, $ownerId);

		if (!is_null($ownerEntity))
		{
			$contacts = $ownerEntity->getContactBindings();
			$contactIds = [];

			foreach($contacts as $binding)
			{
				$contactIds[] = (int) $binding['CONTACT_ID'];
			}

			$contactIDs = $this->getAllowedContactIds($contactIds);
			return $this->buildRecipients($contactIDs, \CCrmOwnerType::ContactName);
		}

		return [];
	}

	protected function getCompanies(string $entityTypeName, int $ownerId): array
	{
		$companies = [];

		if (\CCrmOwnerType::CompanyName === $entityTypeName)
		{
			return $companies;
		}

		$ownerEntity = $this->getEntity($entityTypeName, $ownerId);

		$company = $ownerEntity->getCompany();

		if ($company)
		{
			$companyId = $company->getId();

			if ($companyId)
			{
				$companies = $this->buildRecipients([$companyId], \CCrmOwnerType::CompanyName, false);
			}
		}

		return $companies;
	}

	protected function getHeader(array $activity): array
	{
		$headerResult = \Bitrix\Crm\Activity\Mail\Message::getHeader($activity);
		$this->addErrors($headerResult->getErrors());
		return $headerResult->getData();
	}

	public function getHeaderAction(array $activity): array
	{
		if(!$this->checkModules())
		{
			return [];
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return [];
		}

		return $this->getHeader($activity);
	}

	protected function getTimeFormat(): string
	{
		return \Bitrix\Main\Context::getCurrent()->getCulture()->getShortTimeFormat();
	}

	protected function getDateFormat(): string
	{
		return \Bitrix\Main\Context::getCurrent()->getCulture()->getDayShortMonthFormat();
	}

	protected function getMessageDate(array $activity)
	{
		return $activity['START_TIME']->getTimestamp();
	}

	protected function checkOwnerReadPermission(int $typeId, int $id): bool
	{
		if(!Container::getInstance()->getUserPermissions()->checkReadPermissions($typeId, $id))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED', 'activity_not_specified')));
			return false;
		}

		return true;
	}

	protected function checkActivityPermission(int $permission = self::PERMISSION_READ, array $activities = []): bool
	{
		if (count($activities) === 0)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED', 'activity_not_specified')));
			return false;
		}

		$activity = $activities[0];

		if (!isset($activity['OWNER_TYPE_ID']) || !isset($activity['OWNER_ID']))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED', 'owner_data_not_specified')));
			return false;
		}

		$ownerTypeId = $activity['OWNER_TYPE_ID'];
		$ownerId = $activity['OWNER_ID'];

		if ($permission === self::PERMISSION_READ)
		{
			if (\CCrmActivity::CheckReadPermission($ownerTypeId, $ownerId))
			{
				return true;
			}
		}

		$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED', 'access_denied')));
		return false;
	}

	private function checkActivityIsType(array $activity, string $type = self::SUPPORTED_ACTIVITY_TYPE): bool
	{
		$provider = \CCrmActivity::getActivityProvider($activity);

		if (!$provider)
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_CAN_NOT_DETERMINE_THE_MESSAGE_FORMAT')));
			return false;
		}

		if ($provider::getId() === $type)
		{
			return true;
		}

		return false;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getActivities(array $filters, string $activityType, array $select = [], array $order = [], int $limit = 50): array
	{
		$requiredFieldsForChecks = [
			'ID',
			'TYPE_ID',
			'PROVIDER_ID',
			'OWNER_TYPE_ID',
			'OWNER_ID',
		];

		$activities = ActivityTable::getList([
			'select' => array_merge($select, $requiredFieldsForChecks),
			'filter' => $filters,
			'order' => $order,
			'limit' => $limit,
		])->fetchAll();

		if (empty($activities))
		{
			return [];
		}

		if (!$this->checkActivityIsType($activities[0], $activityType))
		{
			return [];
		}

		foreach ($activities as &$activity)
		{
			\CCrmActivity::PrepareStorageElementIDs($activity);
		}

		return $activities;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getChainAction(int $id): array
	{
		/**
		 * todo: Develop the method:
		 * Write a function for obtaining a chain in parts (By parent id/ for long chains).
		 * The chain can be taken both up and down from the original message.
		 */

		if (!$this->checkModules())
		{
			return [];
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return [];
		}

		$activities = $this->getActivities(
			[
				'ID' => $id,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			[
				'THREAD_ID',
			],
			limit: 1
		);

		if (count($activities) === 0 || !$this->checkActivityPermission(self::PERMISSION_READ, $activities))
		{
			return [];
		}

		$activity = $activities[0];
		$threadId = $activity['THREAD_ID'];
		$select = [
			'SETTINGS',
			'PARENT_ID',
			'THREAD_ID',
			'SUBJECT',
			'START_TIME',
			'DIRECTION',
		];

		$order = [
			'START_TIME' => 'DESC',
		];

		/*
			We need to make two selections and sort the messages by date
			in order to limit the number of messages in long chains(in the future)
			while preserving the open email in the middle of the chain.
		 */
		$messageBeforeCurrent = $this->getActivities(
			[
				'=THREAD_ID' => $threadId,
				'<=PARENT_ID' => $id,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			$select,
			$order
		);

		$messageAfterCurrent = $this->getActivities(
			[
				'=THREAD_ID' => $threadId,
				'>PARENT_ID' => $id,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			$select,
			$order
		);

		$chainActivities = array_merge($messageBeforeCurrent, $messageAfterCurrent);

		/*
			We need to sort the messages by time again.
			For example, message number 5 may be a response to the first message, not the fourth.
		*/
		usort($chainActivities, function($a, $b) {
			return $b['START_TIME']->getTimestamp() <=> $a['START_TIME']->getTimestamp();
		});

		$chain = [];

		$lastIncomingId = null;
		$lastIncomingKey = null;

		foreach ($chainActivities as $key => $item)
		{
			$buildItem = [
				'ID' => $item['ID'],
				'OWNER_ID' => $item['OWNER_ID'],
				'SUBJECT' => $item['SUBJECT'],
				'DATE' => $this->getMessageDate(
					$item
				),
				'HEADER' => $this->getHeader(
					$item
				),
				'OWNER_TYPE_ID' => $item['OWNER_TYPE_ID'],
				'OWNER_TYPE' => \CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']),
				'DIRECTION' => $item['DIRECTION'],
			];

			if (is_null($lastIncomingId) && $item['DIRECTION'] === strval(\CCrmActivityDirection::Incoming))
			{
				$lastIncomingId = $item['ID'];
				$lastIncomingKey = $key;
			}

			if ($item['ID'] == $id)
			{
				$buildItem['DESCRIPTION'] = $this->getMessageBody($id);
				$buildItem['FILES'] = $this->getMessageFilesLinkMessages($id)['FILES'];
			}
			$chain[] = $buildItem;
		}

		/*
			Upload the content of the last read message to be able to transfer it to the sending component for citation
		 */
		if (!is_null($lastIncomingKey))
		{
			$chain[$lastIncomingKey]['DESCRIPTION'] = $this->getMessageBody($lastIncomingId);
			$chain[$lastIncomingKey]['FILES'] = $this->getMessageFilesLinkMessages($lastIncomingId)['FILES'];
		}

		return [
			'list' => $chain,
			'properties' => [
				'lastIncomingId' => $lastIncomingId,
			],
		];
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getMessageFilesLinkMessagesAction(int $id): array
	{
		if (!$this->checkModules())
		{
			return [];
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return [];
		}

		return $this->getMessageFilesLinkMessages($id);
	}

	public function getNeighborsAction(int $ownerId, int $ownerTypeId, int $elementId, bool $requiredWebUrl = false): ?array
	{
		return \Bitrix\Crm\Activity\Mail\Message::getNeighbors($ownerId, $ownerTypeId, $elementId, $requiredWebUrl);
	}

	protected function checkMessageBFileIds(int $messageId, ItemIdentifier $itemIdentifier, array $fileIds): array
	{
		if (!$this->checkModules())
		{
			return [];
		}

		$allowedFileIds = [];

		if ($messageId > 0)
		{
			$allowedFileIds = array_merge($allowedFileIds, $this->getFileIdsFromActivities($messageId));
		}

		$allowedFileIds = array_merge($allowedFileIds, $this->getFileIdsFromEntityDocuments($itemIdentifier));

		return array_intersect($fileIds, $allowedFileIds);
	}

	protected function getFileIdsFromActivities(int $messageId): array
	{
		$activities = $this->getActivities(
			[
				'ID' => $messageId,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			[
				'STORAGE_ELEMENT_IDS',
			]
		);

		if (!$this->checkActivityPermission(self::PERMISSION_READ, $activities))
		{
			return [];
		}

		if ($activities[0])
		{
			$activity = $activities[0];

			$filesIDs = array_unique($activity['STORAGE_ELEMENT_IDS'], SORT_NUMERIC);

			$activityBFileIds = [];

			foreach ($filesIDs as $fileID)
			{
				$file = File::loadById($fileID);
				if ($file)
				{
					$activityBFileIds[] = (int)$file->getFileId();
				}
			}

			return $activityBFileIds;
		}

		return [];
	}

	protected function getFileIdsFromEntityDocuments(ItemIdentifier $itemIdentifier): array
	{
		$documentGeneratorManager = \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance();
		if (!$documentGeneratorManager->isEnabled() || !Loader::includeModule('disk'))
		{
			return [];
		}

		$documents = $documentGeneratorManager->getDocumentsByIdentifier($itemIdentifier);
		$allowedFileIds = [];
		$diskFileIds = [];
		foreach ($documents as $documentDto)
		{
			$document = \Bitrix\DocumentGenerator\Document::loadById($documentDto->getId());
			if (!$document)
			{
				continue;
			}
			if (!$document->hasAccess())
			{
				continue;
			}
			$diskFileIds[] = $document->getEmailDiskFile(true);
		}
		if (empty($diskFileIds))
		{
			return [];
		}

		$diskFiles = \Bitrix\Disk\File::getModelList([
			'filter' => ['@ID' => $diskFileIds],
		]);
		foreach ($diskFiles as $diskFile)
		{
			$allowedFileIds[] = (int)$diskFile->getFileId();
		}
		return $allowedFileIds;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function getMessageFilesLinkMessages(int $id, bool $forMobile = true): array
	{
		if (!$this->checkModules())
		{
			return [];
		}

		$activities = $this->getActivities(
			[
				'ID' => $id,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			[
				'STORAGE_ELEMENT_IDS',
			]
		);

		if (!$this->checkActivityPermission(self::PERMISSION_READ, $activities))
		{
			return [];
		}

		if ($activities[0])
		{
			$activity = $activities[0];

			$filesInfo = [
				'ID' => $activity['ID'],
				'FILES' => [],
			];

			$filesIDs = [];

			if(is_array($activity['STORAGE_ELEMENT_IDS']))
			{
				$filesIDs = array_unique($activity['STORAGE_ELEMENT_IDS'], SORT_NUMERIC);
			}

			foreach ($filesIDs as $fileID)
			{
				if($forMobile)
				{
					$file = File::loadById($fileID);
					if($file)
					{
						if (\Bitrix\Main\Loader::includeModule('mobile'))
						{
							$diskFileInfo = UI\File::loadWithPreview($file->getFileId());

							if($diskFileInfo)
							{
								$filesInfo['FILES'][] = $diskFileInfo->getInfo();
							}
						}
					}
				}
				else
				{
					$diskFileInfo = \Bitrix\Crm\Integration\DiskManager::getFileInfo(
						(int)$fileID,
						false,
						[
							'OWNER_TYPE_ID' => \CCrmOwnerType::Activity,
							'OWNER_ID' => $activity['ID'],
						]
					);

					if ($diskFileInfo)
					{
						$fileName = explode(".", $diskFileInfo['NAME']);
						$clearedInfo = [
							'ID' => (int)$fileID,
							'NAME' => $diskFileInfo['NAME'],
							'VIEW_URL' => $diskFileInfo['VIEW_URL'],
							'PREVIEW_URL' => $diskFileInfo['PREVIEW_URL'],
							'TYPE' => end($fileName),
						];

						$filesInfo['FILES'][] = $clearedInfo;
					}
				}
			}

			return $filesInfo;
		}
		else
		{
			$this->addError(new Error('Entity not found'));
			return [];
		}
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getMessageBodyAction(int $id): array
	{
		if (!$this->checkModules())
		{
			return [];
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return [];
		}

		return [
			'HTML' => $this->getMessageBody($id),
		];
	}

	/**
	 * Get activity description html
	 *
	 * @param int $id Activity ID
	 *
	 * @return string
	 */
	protected function getMessageBody(int $id): string
	{
		$activity = $this->getActivityForDescription($id);

		if ($activity)
		{
			return Email::getDescriptionHtmlByActivityFields($activity);
		}

		return '';
	}

	/**
	 * Get activity by id with description fields
	 *
	 * @param int $id Activity ID
	 *
	 * @return array
	 */
	private function getActivityForDescription(int $id): array
	{
		if (!$this->checkModules())
		{
			return [];
		}

		$activities = $this->getActivities(
			[
				'ID' => $id,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			[
				'DESCRIPTION',
				'DESCRIPTION_TYPE',
				'SETTINGS',
			]
		);

		if ($this->checkActivityPermission(self::PERMISSION_READ, $activities))
		{
			$activity = $activities[0] ?? [];
			Email::uncompressActivity($activity);
			return $activity;
		}

		return [];
	}

	/**
	 * Get HTML description and quote for editor
	 *
	 * @param int $id Activity ID
	 *
	 * @return array
	 */
	public function getDescriptionAndQuoteAction(int $id): array
	{
		if (!$this->checkModules())
		{
			return [];
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return [];
		}

		$activity = $this->getActivityForDescription($id);
		if (!$activity)
		{
			return [];
		}

		$descriptionHtml = Email::getDescriptionHtmlByActivityFields($activity);
		(new SanitizedDescriptionCache())->set($id, $descriptionHtml);
		$quote = Email::getMessageQuote($activity, $descriptionHtml, true, true);

		return [
			'descriptionHtml' => $descriptionHtml,
			'quote' => $quote,
		];
	}

	/**
	 * Download html description as file
	 *
	 * @param int $id Activity ID
	 *
	 * @return HttpResponse
	 */
	public function downloadHtmlBodyAction(int $id): HttpResponse
	{
		$activity = $this->getActivityForDescription($id);

		if (empty($activity))
		{
			return new Redirect('/404.php');
		}

		$content = $activity['DESCRIPTION'] ?? '';
		$name = "crm_activity_description_$id.html";
		$contentType = 'text/html';

		return new DownloadResponse($content, $name, $contentType);
	}

}
