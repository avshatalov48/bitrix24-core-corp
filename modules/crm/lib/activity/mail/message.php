<?php

namespace Bitrix\Crm\Activity\Mail;

use Bitrix\Crm\Activity\Provider\Email;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integration\UI\EntitySelector\MailRecipientProvider;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Mail\Internals\UserSignatureTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Mail\Internals\MailboxAccessTable;
use Bitrix\Mail\MailboxTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Mail\Address;
use Bitrix\Main\Mail\Sender;
use Bitrix\Main\Mail\Converter;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\ItemCollection;

class Message
{
	protected const PERMISSION_READ = 1;
	protected const SUPPORTED_ACTIVITY_TYPE = 'CRM_EMAIL';
	protected const EMAIL_COMMUNICATION_TYPE = 'EMAIL';
	protected const typesForBinding = [
		\CCrmOwnerType::LeadName => 'leads',
		\CCrmOwnerType::DealName => 'deals',
		\CCrmOwnerType::ContactName => 'contacts',
		\CCrmOwnerType::CompanyName => 'companies',
	];
	protected const ENTITIES_THAT_HAVE_CONTACTS = [
		\CCrmOwnerType::DealRecurringName,
		\CCrmOwnerType::QuoteName,
		\CCrmOwnerType::SmartInvoiceName,
		\CCrmOwnerType::CompanyName,
		\CCrmOwnerType::DealName,
		\CCrmOwnerType::LeadName,
	];

	protected static function makeWebPathToMessageById(int $id): Uri
	{
		$uriView = new Uri('/bitrix/components/bitrix/crm.activity.planner/slider.php');
		$uriView->addParams([
			'site_id' => SITE_ID,
			'sessid' => bitrix_sessid_get(),
			'ajax_action' => 'ACTIVITY_VIEW',
			'activity_id' => $id
		]);

		return $uriView;
	}

	protected static function getNeighborActivity(string $order, array $filter, bool $requiredWebUrl = false): ?array
	{
		$dbResult = \CCrmActivity::getList(
			['ID' => $order],
			$filter,
			false,
			false,
			['ID'],
			['QUERY_OPTIONS' => ['LIMIT' => 1]],
		);

		$result = null;
		if ($row = $dbResult->fetch())
		{
			$result = [
				'ID' => (int)$row['ID'],
			];

			if ($requiredWebUrl)
			{
				$result['HREF'] = self::makeWebPathToMessageById((int)$row['ID']);
			}
		}

		return $result;
	}

	public static function getNeighbors(int $ownerId, int $ownerTypeId, int $elementId, bool $requiredWebUrl = false): ?array
	{
		$activity = \CCrmActivity::GetByID($elementId);

		if (empty($activity))
		{
			return null;
		}

		$threadId = (int)$activity['THREAD_ID'];

		$baseFilter = [
			'PROVIDER_ID' => 'CRM_EMAIL',
			'OWNER_ID' => $ownerId,
			'OWNER_TYPE_ID' => $ownerTypeId,
			'!=THREAD_ID' => $threadId,
		];

		$prevMessageFilter = $baseFilter + ['>ID' => $elementId];
		$result['PREV'] = self::getNeighborActivity('ASC', $prevMessageFilter, $requiredWebUrl);

		$nextMessageFilter = $baseFilter + ['<ID' => $elementId];
		$result['NEXT'] = self::getNeighborActivity('DESC', $nextMessageFilter, $requiredWebUrl);

		return $result;
	}

	public static function convertTypeToFormatForBinding($type)
	{
		if (is_numeric($type))
		{
			$type = \CCrmOwnerType::ResolveName($type);
		}

		if(self::typesForBinding[$type])
		{
			return self::typesForBinding[$type];
		}
		else
		{
			return self::typesForBinding[\CCrmOwnerType::ContactName];
		}
	}

	public static function checkModules(): Result
	{
		$result = new Result();
		if (!Loader::includeModule('mail'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_MAIL_CRM_MODULE_IS_NOT_INSTALLED')));
		}

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_MAIL_MAIL_MODULE_IS_NOT_INSTALLED')));
		}

		return $result;
	}

	protected static function checkActivityPermission(int $permission = self::PERMISSION_READ, array $activities = []): Main\Result
	{
		$result = new Main\Result();
		if (count($activities) === 0)
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED'), 'activity_not_specified'));
			return $result;
		}

		$activity = $activities[0];

		if (!isset($activity['OWNER_TYPE_ID']) || !isset($activity['OWNER_ID']))
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED'), 'owner_data_not_specified'));
			return $result;
		}

		$ownerTypeId = $activity['OWNER_TYPE_ID'];
		$ownerId = $activity['OWNER_ID'];

		if ($permission === self::PERMISSION_READ)
		{
			if (\CCrmActivity::CheckReadPermission($ownerTypeId, $ownerId))
			{
				return $result;
			}
		}

		$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED', 'access_denied')));

		return $result;
	}

	public static function buildContact(array $props): array
	{
		$whiteListKeys = [
			'typeNameId' => \CCrmOwnerType::ResolveID(\CCrmOwnerType::ContactName),
			'typeName' => self::convertTypeToFormatForBinding(\CCrmOwnerType::ContactName),
			'email' => '',
			'name' => '',
			'id' => 0,
			'isUser' => false,
		];

		$contact = [];

		foreach ($whiteListKeys as $key => $value)
		{
			if (isset($props[$key]) && $props[$key])
			{
				if ($key === 'id')
				{
					$props[$key] = (int)$props[$key];
				}

				$contact[$key] = $props[$key];
			}
			else
			{
				$contact[$key] = $value;
			}
		}

		return $contact;
	}

	protected static function getGeneralSignature()
	{
		static $generalSignature;

		if (is_null($generalSignature))
		{
			global $USER;
			$userId = $USER->getId();

			$signatureList = UserSignatureTable::getList([
				'select' => ["SIGNATURE"],
				'order' => ['ID' => 'desc'],
				'filter' => [
					'=SENDER' => '',
					'USER_ID' => $userId,
				],
				'limit' => 1,
			])->fetchAll();

			$generalSignature = $signatureList[0]['SIGNATURE'] ?? '';
		}

		return $generalSignature;
	}

	public static function getSignature($email, $name)
	{
		$userId = CurrentUser::get()->getId();

		$signatureList = UserSignatureTable::getList([
			'select' => ["SIGNATURE"],
			'order' => ['ID' => 'desc'],
			'filter' => [
				'=SENDER' => (trim($name).' <'.trim($email).'>'),
				'=USER_ID' => $userId,
			],
			'limit' => 1,
		])->fetchAll();

		if (isset($signatureList[0]['SIGNATURE']))
		{
			return $signatureList[0]['SIGNATURE'];
		}
		else
		{
			// Old signature format
			$signatureList = UserSignatureTable::getList([
				'select' => ["SIGNATURE"],
				'order' => ['ID' => 'desc'],
				'filter' => [
					'=SENDER' => trim($email),
					'=USER_ID' => $userId,
				],
				'limit' => 1,
			])->fetchAll();
		}

		return $signatureList[0]['SIGNATURE'] ?? self::getGeneralSignature();
	}

	public static function getSenderList(): array
	{
		$mailboxes = Sender::prepareUserMailboxes();
		$senders = [];

		foreach ($mailboxes as $sender)
		{
			$builtContact = self::buildContact([
				'email' => $sender['email'] ?? '',
				'name' => $sender['name'] ?? '',
				'id' => $sender['userId'] ?? 0,
				'isUser' => true,
			]);
			$builtContact['signature'] = Converter::htmlToText(self::getSignature($sender['email'], $sender['name']));

			$senders[] = $builtContact;
		}

		/*
			todo: Choosing a preferred email based on the history of correspondence
		*/
		return $senders;
	}

	/**
	 * @param array|string $value
	 * Example:
	 * for array: ['email1@email.com','email2@email.com<LastName FirstName>']
	 * for string: 'email1@email.com, email2@email.com<LastName FirstName>'
	 *
	 * @param array $contactsForMerge
	 * @return array
	 */
	protected static function parseContacts(array|string $value, array $contactsForMerge): array
	{
		/*
			todo: It should be used in the future to select the preferred email: If there is an email in the message and you can also send from it (not disabled)
		*/
		$list = is_array($value) ? $value : explode(',', $value);

		$contacts = [];

		foreach ($list as $item)
		{
			$address = new Address($item);
			if ($address->validate())
			{
				$email = $address->getEmail();
				$isUser = false;

				$entityType = self::convertTypeToFormatForBinding(\CCrmOwnerType::ContactName);
				$entityTypeId = \CCrmOwnerType::ResolveID(\CCrmOwnerType::ContactName);

				if (isset($contactsForMerge[$email]))
				{
					$contactData = $contactsForMerge[$email];

					if (isset($contactData['isUser']))
					{
						$isUser = true;
					}

					if (isset($contactData['ENTITY_ID']))
					{
						$id = $contactData['ENTITY_ID'];
					}
					elseif (isset($contactData['id']))
					{
						$id = $contactData['id'];
					}
					else
					{
						$id = null;
					}

					if (isset($contactData['TITLE']))
					{
						$name = $contactData['TITLE'];
					}
					elseif (isset($contactData['name']))
					{
						$name = $contactData['name'];
					}
					else
					{
						$name = $contactData['TITLE'];
					}

					if (isset($contactData['ENTITY_TYPE_ID']))
					{
						$entityType = self::convertTypeToFormatForBinding($contactData['ENTITY_TYPE_ID']);
						$entityTypeId = (int)$contactData['ENTITY_TYPE_ID'];
					}
				}
				else
				{
					$id = null;
					$name = $address->getName();
				}

				$contact = self::buildContact([
					'email' => $email,
					'id' => $id,
					'name' => self::stripQuotes(trim($name)),
					'isUser' => $isUser,
					'typeName' => $entityType,
					'typeNameId' => $entityTypeId,
				]);

				$contacts[] = $contact;
			}
		}

		return $contacts;
	}

	private static function stripQuotes($text)
	{
		return preg_replace('/^("(.*)"|\'(.*)\')$/', '$2$3', $text);
	}

	/**
	 * In different mail services, if you do not specify the recipient's name,
	 * it will be framed differently in the technical header.
	 *
	 * To make sure that the recipient's name is not really there,
	 * you should check through this function.
	 *
	 * @param $name
	 * @param $email
	 * @return bool
	 */
	public static function nameIsEquivalentToEmail($name, $email): bool
	{
		if (empty($name) || $name === $email)
		{
			return true;
		}

		$emailParts = explode("@", $email);

		if (isset($emailParts[0]) && (trim($name) === trim($emailParts[0])))
		{
			return true;
		}

		return false;
	}

	public static function getSubjectById(int $id): string
	{
		$activity = ActivityTable::getList([
			'select' => [
				'SUBJECT'
			],
			'filter' => [
				'=ID' => $id
			],
			'limit' => 1,
		])->fetch();

		if (isset($activity['SUBJECT']))
		{
			return $activity['SUBJECT'];
		}

		return '';
	}

	public static function getAssociatedUser(array $activity)
	{
		$header = static::getHeader($activity)->getData();
		$employeeEmails = $header['employeeEmails'];

		if (isset($employeeEmails[0]['email']))
		{
			return $employeeEmails[0];
		}

		return [
			'id' => 0,
		];
	}

	protected static function getOwnerTypeId(string $ownerType): int
	{
		return \CCrmOwnerType::ResolveID($ownerType);
	}

	protected static function buildRecipients(array $contactIDs, string $entityTypeName, bool $onlyWithEmail = true): array
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

			$recipient = self::buildContact([
				'email' => $contactEmails,
				'name' => $contactName,
				'id' => $id,
				'typeName' => self::convertTypeToFormatForBinding($entityTypeName),
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

	protected static function getEntity(string $entityTypeName, int $ownerId): ?Item
	{
		$entityTypeId = self::getOwnerTypeId($entityTypeName);
		$entityFactory = Container::getInstance()->getFactory($entityTypeId);

		if (is_null($entityFactory))
		{
			return null;
		}

		return $entityFactory->getItem($ownerId);
	}

	protected static function getCompanies(string $entityTypeName, int $ownerId): array
	{
		$companies = [];

		if (\CCrmOwnerType::CompanyName === $entityTypeName)
		{
			return $companies;
		}

		$ownerEntity = self::getEntity($entityTypeName, $ownerId);

		if (!is_null($ownerEntity) && $ownerEntity->hasField('COMPANY') && $company = $ownerEntity->getCompany())
		{
			$companyId = $company->getId();

			if ($companyId)
			{
				$companies = self::buildRecipients([$companyId], \CCrmOwnerType::CompanyName, false);
			}
		}

		return $companies;
	}

	protected static function checkEntityCanHaveContacts($typeName): bool
	{
		$typeId = \CCrmOwnerType::ResolveID($typeName);
		return (
			in_array($typeName, self::ENTITIES_THAT_HAVE_CONTACTS) ||
			\CCrmOwnerType::isPossibleSuspendedDynamicTypeId($typeId) ||
			\CCrmOwnerType::isPossibleDynamicTypeId($typeId)
		);
	}

	protected static function getAllowedContactIds(array $contactIDs): array
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

	protected static function getContacts(string $entityTypeName, int $ownerId): array
	{
		if (!self::checkEntityCanHaveContacts($entityTypeName))
		{
			return [];
		}

		$ownerEntity = self::getEntity($entityTypeName, $ownerId);

		if (!is_null($ownerEntity))
		{
			$contacts = $ownerEntity->getContactBindings();
			$contactIds = [];

			foreach ($contacts as $binding)
			{
				$contactIds[] = (int) $binding['CONTACT_ID'];
			}

			$contactIDs = self::getAllowedContactIds($contactIds);
			return self::buildRecipients($contactIDs, \CCrmOwnerType::ContactName);
		}

		return [];
	}

	protected static function prepareRecipientsForConversionToJson($recipients, $type): array
	{
		$result = [];

		foreach ($recipients as $id)
		{
			$result[] = [$type, (int) $id];
		}

		return $result;
	}

	/**
	 * @param $recipients
	 * These parameters must be passed to search and add related elements
	 * in case processing the $recipients param will result in an empty collection:
	 * @param string|null $ownerType
	 * @param int|null $ownerId
	 * @return ItemCollection
	 * @throws \Exception
	 */
	public static function getSelectedRecipientsForDialog(array $recipients, string $ownerType, int $ownerId, bool $loadIfEmpty = false): ItemCollection
	{
		$items = [];
		$itemsIds = [];

		foreach ($recipients as $recipient)
		{
			$id = MailRecipientProvider::buildRecipientProviderId(($recipient['ENTITY_TYPE_NAME'] ?? $recipient['ENTITY_TYPE']), $recipient['ENTITY_ID'], ($recipient['VALUE_TYPE'] ?? MailRecipientProvider::EMAIL_TYPE_WORK), $recipient['VALUE']);
			$items[] = [MailRecipientProvider::PROVIDER_ENTITY_ID, $id];
			$itemsIds[] = $id;
		}

		if (count($itemsIds) !== 0)
		{
			$options = [
				'entities' => [
					[
						'id' => 'mail_recipient',
						'options' => [
							'selectedItemIds' => $itemsIds,
							'checkTheWhitelist' => false,
						],
					],
				],
			];

			$itemCollection = Dialog::getItems($items, $options);

			/*
			 * Old addresses that were removed from the CRM element card are filtered out.
			 * This may result in an empty collection.
			*/
			if ($itemCollection->count() > 0)
			{
				return $itemCollection;
			}
		}

		$itemCollection = new ItemCollection();

		if ($loadIfEmpty === false)
		{
			return $itemCollection;
		}

		$providerOptions = [
			'ownerId' => $ownerId,
			'ownerType' => $ownerType,
			'checkTheWhitelist' => true,
		];

		$mailRecipientProvider = new MailRecipientProvider($providerOptions);
		$dialog = new Dialog([]);
		$mailRecipientProvider->fillDialog($dialog);

		$fullItemCollection = $dialog->getItemCollection();
		$iterator = $fullItemCollection->getIterator();

		$selectedItemCollection = new ItemCollection();

		/** @var \Bitrix\UI\EntitySelector\Item|null $firstItem */
		$firstItem = $iterator->current();

		if (!is_null($firstItem))
		{
			$selectedItemCollection->add($firstItem);
		}

		return $selectedItemCollection;
	}

	public static function entityRecipientsToCollectionForDialog($recipients = [], $sortByType = false, string $ownerTypeName = '', int $ownerId = 0): ItemCollection
	{
		if ($sortByType)
		{
			$recipients = self::sortRecipientsByType($recipients);
		}

		$companies = [];
		$contacts = [];

		$contactCategoryId = 0;
		$companyCategoryId = 0;

		if (isset($recipients['company']))
		{
			if (is_array($recipients['company']) && count($recipients['company']) > 0)
			{
				$companyCategoryId = self::getRecipientCategoryId(\CCrmOwnerType::Company, (int)$recipients['company'][0]);
				$companies = self::prepareRecipientsForConversionToJson($recipients['company'], 'company');
			}

			unset($recipients['company']);
		}

		if (isset($recipients['contacts']))
		{
			if (is_array($recipients['contacts']) && count($recipients['contacts']) > 0)
			{
				$contactCategoryId = self::getRecipientCategoryId(\CCrmOwnerType::Contact, (int)$recipients['contacts'][0]);
				$contacts = self::prepareRecipientsForConversionToJson($recipients['contacts'], 'contact');
			}

			unset($recipients['contacts']);
		}

		$ownerFields  = [];

		if ($ownerTypeName !== '' && $ownerId !== 0)
		{
			$ownerFields  = self::prepareRecipientsForConversionToJson([$ownerId], mb_strtolower($ownerTypeName));
		}

		foreach ($recipients as $name => $recipient)
		{
			$ownerFields = array_merge($ownerFields, self::prepareRecipientsForConversionToJson($recipient, $name));
		}

		$items = [
			...$companies,
			...$contacts,
			...$ownerFields,
		];

		$options = [
			'entities' => [
				[
					'id' => 'contact',
					'options' => [
						'categoryId' => $contactCategoryId,
					],
				],
				[
					'id' => 'company',
					'options' => [
						'categoryId'=> $companyCategoryId,
					],
				],
			],
		];

		return Dialog::getItems($items, $options);
	}

	private static function getRecipientCategoryId(int $typeId, int $id): ?int
	{
		if ($typeId !== \CCrmOwnerType::Contact && $typeId !== \CCrmOwnerType::Company)
		{
			return null;
		}

		return (int)Container::getInstance()->getFactory($typeId)->getItemCategoryId($id);
	}

	public static function getEntityRecipients(int $ownerId, string $ownerTypeName, bool $uploadRecipients = true, bool $uploadSenders = true, $inCollectionForDialog = false): Result
	{
		$includedNecessaryModulesResult = self::checkModules();
		$result = new Result();
		$recipients = [];

		if ($ownerId === 0)
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_NO_RECIPIENT_OWNER')));
			return $result;
		}

		if ($inCollectionForDialog)
		{
			if (!Loader::includeModule('ui'))
			{
				$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_MAIL_CRM_UI_IS_NOT_INSTALLED')));
				return $result;
			}

			$result->setData([new ItemCollection()]);
		}
		else
		{
			$result->setData($recipients);
		}

		$result->addErrors($includedNecessaryModulesResult->getErrors());

		if (!$includedNecessaryModulesResult->isSuccess())
		{
			return $result;
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED'), 'activity_not_specified'));
			return $result;
		}

		$ownerTypeId = self::getOwnerTypeId($ownerTypeName);

		$readPermissionResult = self::checkOwnerReadPermission($ownerTypeId, $ownerId);

		if (!$readPermissionResult->isSuccess())
		{
			return $result;
		}

		if ($uploadRecipients)
		{
			$companies = self::getCompanies($ownerTypeName, $ownerId);
			$companiesWithEmail = [];

			$contacts = self::getContacts($ownerTypeName, $ownerId);

			if ($ownerTypeName !== \CCrmOwnerType::ContactName)
			{
				foreach ($companies as $company)
				{
					$contacts = array_merge($contacts, self::getContacts(\CCrmOwnerType::CompanyName, $company['id']));
				}
			}

			foreach ($companies as $company)
			{
				if (count($company['email']))
				{
					$companiesWithEmail[] = $company;
				}
			}

			$emailFields = self::buildRecipients([$ownerId], $ownerTypeName);

			$clientsByType = [
				'company' => $companiesWithEmail,
				'contacts' => $contacts,
			];

			$recipients['clients'] = array_merge(
				$clientsByType['contacts'],
				$clientsByType['company'],
				$emailFields
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
			$recipients['senders'] = self::getSenderList();
		}

		if ($uploadRecipients && $inCollectionForDialog)
		{
			$result->setData([self::entityRecipientsToCollectionForDialog($recipients['clientIdsByType'], false, $ownerTypeName, $ownerId)]);
		}
		else
		{
			$result->setData($recipients);
		}

		return $result;
	}

	public static function sortRecipientsByType($list): array
	{
		$sorted = [
			'company' => [],
			'contacts' => [],
		];

		foreach ($list as $recipient)
		{

			if (($recipient['ENTITY_TYPE_NAME'] !== null && $recipient['ENTITY_TYPE_NAME'] !== MailRecipientProvider::EMAIL_TYPE_ID) || $recipient['ENTITY_TYPE_ID'] !== null)
			{
				if ((int) $recipient['ENTITY_TYPE_ID'] == \CCrmOwnerType::Company || $recipient['ENTITY_TYPE_NAME'] == \CCrmOwnerType::CompanyName)
				{
					$sorted['company'][] = (int) $recipient['ENTITY_ID'];
				}
				else if((int) $recipient['ENTITY_TYPE_ID'] == \CCrmOwnerType::Contact || $recipient['ENTITY_TYPE_NAME'] == \CCrmOwnerType::ContactName)
				{
					$sorted['contacts'][] = (int) $recipient['ENTITY_ID'];
				}
				else
				{
					if ($recipient['ENTITY_TYPE_NAME'] !== null)
					{
						$type = $recipient['ENTITY_TYPE_NAME'];
					}
					else
					{
						$type = \CCrmOwnerType::ResolveName($recipient['ENTITY_TYPE_ID']);
					}

					$type = mb_strtolower($type);

					if (!isset($sorted[$type]))
					{
						$sorted[$type] = [];
					}

					$sorted[$type][] = (int) $recipient['ENTITY_ID'];
				}
			}
		}

		return $sorted;
	}

	protected static function checkOwnerReadPermission(int $typeId, int $id): Result
	{
		$result = new Result();

		if(!Container::getInstance()->getUserPermissions()->checkReadPermissions($typeId, $id))
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED'), 'owner_data_not_specified'));
		}

		return $result;
	}

	public static function getHeader(array $activity, $showPortalContactNames = true): Main\Result
	{
		$header = [];
		$headerResult = new Main\Result();
		$headerResult->setData($header);

		$checkModulesResult = self::checkModules();
		$headerResult->addErrors($checkModulesResult->getErrors());

		if(!$headerResult->isSuccess())
		{
			return $headerResult;
		}

		$checkActivityPermissionResult = self::checkActivityPermission(self::PERMISSION_READ, [$activity]);
		$headerResult->addErrors($checkActivityPermissionResult->getErrors());

		if(!$headerResult->isSuccess())
		{
			return $headerResult;
		}

		$contacts = [];
		$header['accessMailboxesForSending'] = array_map(
			function ($item) {
				$item['isUser'] = true;
				return $item;
			},
			self::getSenderList()
		);

		if ($showPortalContactNames)
		{
			$communicationList = \CCrmActivity::GetCommunicationList(
				[],
				['ACTIVITY_ID' => $activity['ID']],
				false,
				[],
			);

			while ($item = $communicationList->fetch())
			{
				$item['ENTITY_SETTINGS'] = isset($item['ENTITY_SETTINGS']) && $item['ENTITY_SETTINGS'] !== ''
					? unserialize($item['ENTITY_SETTINGS'], ['allowed_classes' => false]) : [];
				\CAllCrmActivity::PrepareCommunicationInfo($item, null, false);
				$contacts[$item['VALUE']] = $item;
			}
		}

		if (isset($activity['SETTINGS']['EMAIL_META']) && !empty($activity['SETTINGS']['EMAIL_META']))
		{
			$activityEmailMeta = $activity['SETTINGS']['EMAIL_META'];
			$ownerEmail = false;

			if (isset($activityEmailMeta['__email']))
			{
				$ownerEmail = trim($activityEmailMeta['__email']);

				/* @TODO: replace with: the message owner's mailbox
				 * Since clearing the fields when forwarding a letter is required only of  the owner's mailbox
				 */
				$header['employeeEmails'] = self::parseContacts($ownerEmail, $contacts);
			}
			else
			{
				$header['employeeEmails'] = [];
			}

			foreach ($activityEmailMeta as $key => $value)
			{
				if (!in_array($key, ['from', 'replyTo', 'to', 'cc', 'bcc']))
				{
					continue;
				}

				$contactsFromField = self::parseContacts($value, $contacts);

				if ($ownerEmail)
				{
					foreach ($contactsFromField as &$contact)
					{
						if ($contact['email'] === $ownerEmail)
						{
							$mailboxesWithTheSpecifiedEmail = MailboxTable::getMailboxesWithEmail($ownerEmail);
							$countMailboxesWithTheSpecifiedEmail = $mailboxesWithTheSpecifiedEmail->getCount();

							while ($mailboxOwner = $mailboxesWithTheSpecifiedEmail->fetch())
							{
								if (isset($mailboxOwner['ID']))
								{
									if (!self::nameIsEquivalentToEmail($contact['name'], $contact['email']))
									{
										$users = MailboxAccessTable::getUsersDataByName(
											(int)$mailboxOwner['ID'],
											$contact['name']
										);
										if (count($users) === 1)
										{
											$user = $users[0];
											$contact['name'] = $user['name'];
											$contact['id'] = $user['id'];
										}
									}
									elseif (
										/*
											If several users have connected the same mailbox,
											we cannot find out who the message is addressed to.
										*/
										$countMailboxesWithTheSpecifiedEmail === 1
										&& isset($mailboxOwner['USER_ID'])
									)
									{
										$user = MailboxAccessTable::getUserDataById(
											(int)$mailboxOwner['ID'],
											(int)$mailboxOwner['USER_ID'],
										);
										$contact['name'] = $user['name'];
										$contact['id'] = $user['id'];
									}
									else
									{
										$contact['name'] = '';
									}
								}
							}
							$contact['isUser'] = true;
						}
					}
				}
				$header[$key] = $contactsFromField;

				if ($key === 'from' && count($header[$key]) === 1)
				{
					$header[$key][0]['senderName'] = (new Address($value))->getName();
				}

				if (!empty($contactsFromField))
				{
					$foundContacts[] = $contactsFromField;
				}
			}

			if (isset($activityEmailMeta['__email']))
			{
				$ownerEmail = trim($activityEmailMeta['__email']);

				$header['employeeEmails'] = self::parseContacts(
					$ownerEmail,
					array_merge(
						$contacts,
						static::convertContactListToAssociativeList(
							array_map(function($item) {
								return $item[0];
							}, $foundContacts)
						)
					)
				);
			}
			else
			{
				$header['employeeEmails'] = [];
			}
		}

		$headerResult->setData($header);
		return $headerResult;
	}

	public static function getMessageBody($id): Main\Result
	{
		$checkModules = self::checkModules();
		if (!$checkModules->isSuccess())
		{
			return (new Main\Result())->addErrors($checkModules->getErrors());
		}

		$body = [
			'HTML' => '',
		];

		$activities = self::getActivities(
			[
				'ID' => $id,
			],
			self::SUPPORTED_ACTIVITY_TYPE,
			[
				'DESCRIPTION',
			]
		);

		$checkActivities = self::checkActivityPermission(self::PERMISSION_READ, $activities);
		if (!$checkActivities->isSuccess())
		{
			return (new Main\Result())->addErrors($checkActivities->getErrors());
		}

		$activity = $activities[0];

		if (is_array($activity))
		{
			Email::uncompressActivity($activity);
			if (isset($activity['DESCRIPTION']))
			{
				$body['HTML'] = $activity['DESCRIPTION'];
			}
		}

		return (new Main\Result())->setData($body);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private static function getActivities(array $filters, string $activityType, array $select = [], array $order = [], int $limit = 50): array
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

		if (!self::checkActivityIsType($activities[0], $activityType)->isSuccess())
		{
			return [];
		}

		foreach ($activities as &$activity)
		{
			\CCrmActivity::PrepareStorageElementIDs($activity);
		}

		return $activities;
	}

	private static function checkActivityIsType(array $activity, string $type = self::SUPPORTED_ACTIVITY_TYPE): Main\Result
	{
		$result = new Main\Result();

		$provider = \CCrmActivity::getActivityProvider($activity);

		if (!$provider)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_CAN_NOT_DETERMINE_THE_MESSAGE_FORMAT')));
		}

		if ($provider::getId() === $type)
		{
			return new Main\Result();
		}

		return (new Main\Result())->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_CAN_NOT_DETERMINE_THE_MESSAGE_FORMAT')));
	}

	/*
		Example:
		Input data: '[['email'=> name@example.com, name='James',...],...]
		Output data: ['name@example.com'=>['email'=> name@example.com, name='James',...],...]
	*/
	private static function convertContactListToAssociativeList($array): array
	{
		return empty($array) ? [] : array_combine(array_column($array, 'email'), $array);
	}
}
