<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\UserTable;
use CCrmFieldMulti;
use CCrmOwnerType;
use CCrmStatus;
use CUser;

class SearchMap
{
	private static array $users = [];
	private array $data = [];

	/**
	 * Cache specified users.
	 * @param array $userIds User IDs.
	 * @return void
	 */
	public static function cacheUsers(array $userIds): void
	{
		if (empty($userIds))
		{
			return;
		}

		$dbResult = CUser::GetList(
			'ID',
			'ASC',
			['ID' => implode('|', array_filter(array_map('intval', $userIds)))],
			[
				'FIELDS' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'],
			]
		);

		while ($user = $dbResult->Fetch())
		{
			self::$users[$user['ID']] = $user;
		}
	}

	public function add($value): void
	{
		if (!is_string($value))
		{
			$value = (string)$value;
		}

		$value = SearchEnvironment::prepareToken($value);
		if ($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}

	public function addField(array $fields, string $name): void
	{
		$value = $fields[$name] ?? '';
		if (!is_string($value))
		{
			$value = (string)$value;
		}

		$value = SearchEnvironment::prepareToken($value);
		if ($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}

	public function addText($value, int $length = null): void
	{
		if (!is_string($value))
		{
			$value = (string)$value;
		}

		if ($length > 0)
		{
			$value = mb_substr($value, 0, $length);
		}

		$value = SearchEnvironment::prepareToken($value);
		if ($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}

	public function addHtml($value, int $length = null): void
	{
		$this->addText(strip_tags(html_entity_decode($value)), $length);
	}
	
	public function addUserByID($userId): void
	{
		global $USER;

		if ((int)$userId <= 0)
		{
			return;
		}

		if (isset(self::$users[(int)$userId]))
		{
			$user = self::$users[(int)$userId];
		}
		elseif (isset($USER) && $USER instanceof \CUser && (int)$userId === (int)$USER->GetID())
		{
			$user = [
				'ID' => $USER->GetID(),
				'LOGIN' => $USER->GetLogin(),
				'NAME' => $USER->GetFullName(),
				'LAST_NAME' => $USER->GetLastName(),
				'SECOND_NAME' => $USER->GetSecondName(),
				'TITLE' => $USER->GetParam("TITLE"),
			];
		}
		else
		{
			$userQ = UserTable::query()
				->setSelect(['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'])
				->where('ID', $userId);

			$user = self::$users[$userId] = $userQ->fetch();
		}

		if (!is_array($user))
		{
			return;
		}

		$value = CUser::FormatName(
			\CSite::GetNameFormat(),
			$user,
			true,
			false
		);

		$value = SearchEnvironment::prepareToken($value);
		if ($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}

	public function addTextFragments($value): void
	{
		if (!is_string($value))
		{
			$value = (string)$value;
		}

		if ($value === '')
		{
			return;
		}

		$length = mb_strlen($value);

		//Right bound. We will stop when 3 characters are left.
		$bound = $length - 2;
		if ($bound > 0)
		{
			for ($i = 0; $i < $bound; $i++)
			{
				$fragment = mb_substr($value, $i);
				if (!isset($this->data[$fragment]))
				{
					$this->addText($fragment);
				}
			}
		}
	}

	public function addPhone($phone): void
	{
		$originalPhone = DuplicateCommunicationCriterion::sanitizePhone($phone);
		if ($originalPhone === '')
		{
			return;
		}

		//Fix for issue #111401. Store original phone for providing opportunity to search by not normalized phone.
		$this->data[$originalPhone] = true;

		$phone = DuplicateCommunicationCriterion::normalizePhone($phone);
		if ($phone === '')
		{
			return;
		}

		$length = mb_strlen($phone);
		if ($length >= 10 && mb_substr($phone, 0, 1) === '7')
		{
			$altPhone = '8'.mb_substr($phone, 1);
			if (!isset($this->data[$altPhone]))
			{
				$this->data[$altPhone] = true;
			}
		}

		//Right bound. We will stop when 3 digits are left.
		$bound = $length - 2;
		if ($bound > 0)
		{
			for ($i = 0; $i < $bound; $i++)
			{
				$fragment = mb_substr($phone, $i);
				if (!isset($this->data[$fragment]))
				{
					$this->data[$fragment] = true;
				}
			}
		}
	}

	public function addEmail($email): void
	{
		if ($email === '')
		{
			return;
		}

		$keys = preg_split('/\W+/', $email, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($keys as $key)
		{
			$key = SearchEnvironment::prepareToken($key);
			if (!isset($this->data[$key]))
			{
				$this->data[$key] = true;
			}
		}
	}

	public function addMultiFieldValue(string $typeId, $value): void
	{
		if ($typeId === CCrmFieldMulti::PHONE)
		{
			$this->addPhone($value);
		}
		elseif ($typeId === CCrmFieldMulti::EMAIL)
		{
			$this->addEmail($value);
		}
		else
		{
			throw new NotSupportedException("Multifield type: '" . $typeId . "' is not supported in current context");
		}
	}

	public function addEntityMultiFields(int $entityTypeId, int $entityId, array $typeIds): void
	{
		if ($entityId <= 0)
		{
			return;
		}

		$multiFields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
			$entityTypeId,
			$entityId
		);

		foreach ($typeIds as $typeId)
		{
			if (!(CCrmFieldMulti::IsSupportedType($typeId) && isset($multiFields[$typeId])))
			{
				continue;
			}

			foreach ($multiFields[$typeId] as $multiField)
			{
				if (isset($multiField['VALUE']))
				{
					$this->addMultiFieldValue($typeId, $multiField['VALUE']);
				}
			}
		}
	}
	
	public function addStatus($statusEntityId, $statusId): void
	{
		$list = CCrmStatus::GetStatusList($statusEntityId);
		if (isset($list[$statusId]))
		{
			$value = SearchEnvironment::prepareToken($list[$statusId]);
			if ($value !== '' && !isset($this->data[$value]))
			{
				$this->data[$value] = true;
			}
		}
	}

	public function addUserField(array $userField): void
	{
		global $USER_FIELD_MANAGER;

		$userTypeId = $userField['USER_TYPE_ID'] ?? '';
		if ($userTypeId === 'boolean')
		{
			$values = [];

			if (isset($userField['VALUE']) && (bool)$userField['VALUE'] && isset($userField['EDIT_FORM_LABEL']))
			{
				$values[] = $userField['EDIT_FORM_LABEL'];
			}
		}
		else
		{
			$values = explode(',', $USER_FIELD_MANAGER->getPublicText($userField));
		}
		//$values = explode(',', $USER_FIELD_MANAGER->getPublicText($userField));

		foreach ($values as $value)
		{
			$this->addText(trim($value), 1024);
		}
	}

	public function addCompany(int $companyId): void
	{
		$this->add(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $companyId, false));
		$this->addEntityMultiFields(
			CCrmOwnerType::Company,
			$companyId,
			[CCrmFieldMulti::PHONE, CCrmFieldMulti::EMAIL]
		);
	}

	public function addContacts(array $contactIds): void
	{
		foreach ($contactIds as $contactId)
		{
			$this->add(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $contactId, false));
			$this->addEntityMultiFields(
				CCrmOwnerType::Contact,
				$contactId,
				[CCrmFieldMulti::PHONE, CCrmFieldMulti::EMAIL]
			);
		}
	}

	public function getString(): string
	{
		return implode(' ', array_keys($this->data));
	}
}
