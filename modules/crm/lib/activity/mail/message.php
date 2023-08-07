<?php

namespace Bitrix\Crm\Activity\Mail;

use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Mail\Internals\MailboxAccessTable;
use Bitrix\Mail\MailboxTable;

class Message
{
	protected const PERMISSION_READ = 1;
	protected const typesForBinding = [
		\CCrmOwnerType::LeadName => 'leads',
		\CCrmOwnerType::DealName => 'deals',
		\CCrmOwnerType::ContactName => 'contacts',
		\CCrmOwnerType::CompanyName => 'companies',
	];

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

	protected static function checkModules(): Main\Result
	{
		$result = new Main\Result();
		if (!\Bitrix\Main\Loader::includeModule('mail'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_MAIL_CRM_MODULE_IS_NOT_INSTALLED')));
		}

		if (!\Bitrix\Main\Loader::includeModule('crm'))
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
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED', 'activity_not_specified')));
			return $result;
		}

		$activity = $activities[0];

		if (!isset($activity['OWNER_TYPE_ID']) || !isset($activity['OWNER_ID']))
		{
			$result->addError(new Error(Loc::getMessage('CRM_LIB_ACTIVITY_PERMISSION_DENIED', 'owner_data_not_specified')));
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

	protected static function buildContact(array $props): array
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

	protected static function getSenderList(): array
	{
		$mailboxes = \Bitrix\Main\Mail\Sender::prepareUserMailboxes();
		$senders = [];

		foreach ($mailboxes as $sender)
		{
			$senders[] = self::buildContact([
				'email' => $sender['email'] ?? '',
				'name' => $sender['name'] ?? '',
				'id' => $sender['userId'] ?? 0,
				'isUser' => true,
			]);
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
	protected static function parseContacts($value, array $contactsForMerge): array
	{
		/*
			todo: It should be used in the future to select the preferred email: If there is an email in the message and you can also send from it (not disabled)
		*/
		$list = is_array($value) ? $value : explode(',', $value);

		$contacts = [];

		foreach ($list as $item)
		{
			$address = new \Bitrix\Main\Mail\Address($item);
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

		if (!empty($activity['SETTINGS']['EMAIL_META']))
		{
			$activityEmailMeta = $activity['SETTINGS']['EMAIL_META'];
			$ownerEmail = false;
			if (isset($activityEmailMeta['__email']))
			{
				$ownerEmail = trim($activityEmailMeta['__email']);
			}

			$foundContacts = [];

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
