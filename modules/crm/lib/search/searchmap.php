<?php
namespace Bitrix\Crm\Search;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Main\NotSupportedException;

class SearchMap
{
	private static $users = array();
	private $data = array();

	/**
	 * Cache specified users.
	 * @param array $userIDs User IDs.
	 * @return void
	 */
	public static function cacheUsers(array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$dbResult = \CUser::GetList(
			'ID',
			'ASC',
			array('ID' => implode('|', array_filter(array_map('intval', $userIDs)))),
			array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'))
		);
		while($user = $dbResult->Fetch())
		{
			self::$users[$user['ID']] = $user;
		}
	}

	public function add($value)
	{
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		$value = SearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addField(array $fields, $name)
	{
		$value = isset($fields[$name]) ? $fields[$name] : '';
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		$value = SearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addText($value, $length = null)
	{
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		if($length > 0)
		{
			$value = mb_substr($value, 0, $length);
		}

		$value = SearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addHtml($value, $length = null)
	{
		$this->addText(strip_tags(html_entity_decode($value)), $length);
	}
	public function addUserByID($userID)
	{
		if((int)$userID <= 0)
		{
			return;
		}

		if(isset(self::$users[(int)$userID]))
		{
			$user = self::$users[(int)$userID];
		}
		else
		{
			$dbResult = \CUser::GetList(
				'ID',
				'ASC',
				array('ID'=> $userID),
				array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'))
			);
			$user = self::$users[$userID] = $dbResult->Fetch();
		}

		if(!is_array($user))
		{
			return;
		}

		$value = \CUser::FormatName(
			\CSite::GetNameFormat(),
			$user,
			true,
			false
		);

		$value = SearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}

	public function addTextFragments($value)
	{
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		if($value === '')
		{
			return;
		}

		$length = mb_strlen($value);

		//Right bound. We will stop when 3 characters are left.
		$bound = $length - 2;
		if($bound > 0)
		{
			for($i = 0; $i < $bound; $i++)
			{
				$fragment = mb_substr($value, $i);
				if(!isset($this->data[$fragment]))
				{
					$this->addText($fragment);
				}
			}
		}
	}

	public function addPhone($phone)
	{
		$originalPhone = DuplicateCommunicationCriterion::sanitizePhone($phone);
		if($originalPhone === '')
		{
			return;
		}

		//Fix for issue #111401. Store original phone for providing opportunity to search by not normalized phone.
		$this->data[$originalPhone] = true;

		$phone = DuplicateCommunicationCriterion::normalizePhone($phone);
		if($phone === '')
		{
			return;
		}

		$length = mb_strlen($phone);
		if($length >= 10 && mb_substr($phone, 0, 1) === '7')
		{
			$altPhone = '8'.mb_substr($phone, 1);
			if(!isset($this->data[$altPhone]))
			{
				$this->data[$altPhone] = true;
			}
		}

		//Right bound. We will stop when 3 digits are left.
		$bound = $length - 2;
		if($bound > 0)
		{
			for($i = 0; $i < $bound; $i++)
			{
				$fragment = mb_substr($phone, $i);
				if(!isset($this->data[$fragment]))
				{
					$this->data[$fragment] = true;
				}
			}
		}
	}
	public function addEmail($email)
	{
		if($email === '')
		{
			return;
		}

		$keys = preg_split('/\W+/', $email, -1, PREG_SPLIT_NO_EMPTY);
		foreach($keys as $key)
		{
			$key = SearchEnvironment::prepareToken($key);
			if(!isset($this->data[$key]))
			{
				$this->data[$key] = true;
			}
		}
	}
	public function addMultiFieldValue($typeID, $value)
	{
		if($typeID === \CCrmFieldMulti::PHONE)
		{
			$this->addPhone($value);
		}
		elseif($typeID === \CCrmFieldMulti::EMAIL)
		{
			$this->addEmail($value);
		}
		else
		{
			throw new NotSupportedException("Multifield type: '".$typeID."' is not supported in current context");
		}
	}
	public function addEntityMultiFields($entityTypeID, $entityID, array $typeIDs)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return;
		}

		$multiFields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues($entityTypeID, $entityID);
		foreach($typeIDs as $typeID)
		{
			if(!(\CCrmFieldMulti::IsSupportedType($typeID) && isset($multiFields[$typeID])))
			{
				continue;
			}

			foreach($multiFields[$typeID] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$this->addMultiFieldValue($typeID, $multiField['VALUE']);
				}
			}
		}

	}
	public function addStatus($statusEntityID, $statusID)
	{
		$list = \CCrmStatus::GetStatusList($statusEntityID);
		if(isset($list[$statusID]))
		{
			$value = SearchEnvironment::prepareToken($list[$statusID]);
			if($value !== '' && !isset($this->data[$value]))
			{
				$this->data[$value] = true;
			}
		}
	}
	public function addUserField(array $userField)
	{
		global $USER_FIELD_MANAGER;

		$userTypeID = isset($userField['USER_TYPE_ID']) ? $userField['USER_TYPE_ID'] : '';
		if($userTypeID === 'boolean')
		{
			$values = array();
			if(isset($userField['VALUE']) && (bool)$userField['VALUE'] && isset($userField['EDIT_FORM_LABEL']))
			{
				$values[] = $userField['EDIT_FORM_LABEL'];
			}
		}
		else
		{
			$values = explode(',', $USER_FIELD_MANAGER->getPublicText($userField));
		}
		//$values = explode(',', $USER_FIELD_MANAGER->getPublicText($userField));

		foreach($values as $value)
		{
			$this->addText(trim($value), 1024);
		}
	}
	public function getString()
	{
		return implode(' ', array_keys($this->data));
	}
}