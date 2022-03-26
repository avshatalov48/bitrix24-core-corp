<?php
namespace Bitrix\ImOpenLines\Crm;

use Bitrix\ImOpenLines\Tools;
use Bitrix\ImOpenLines\Session;

use Bitrix\Im;

class Fields
{
	/** @var Session */
	protected $session;

	/** @var string */
	protected $code = '';
	/** @var int */
	protected $userId;
	/** @var array */
	protected $emails = [];
	/** @var array */
	protected $phones = [];
	/** @var array */
	protected $person = [
		'NAME' => '',
		'LAST_NAME' => '',
		'SECOND_NAME' => '',
		'EMAIL' => '',
		'PHONE' => '',
		'WEBSITE' => ''
	];
	/** @var string */
	protected $title = '';

	/**
	 * @param Session $session
	 * @return bool
	 */
	public function setSession($session): bool
	{
		$result = false;

		if(!empty($session) && $session instanceof Session)
		{
			$this->session = $session;

			$result = true;
		}

		return $result;
	}

	/**
	 * @return Session
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * @param $field
	 * @return bool
	 */
	public function setCode($field): bool
	{
		$this->code = $field;

		return true;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}


	/**
	 * @param $field
	 * @return bool
	 */
	public function setUserId($field): bool
	{
		$this->userId = $field;

		return true;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param $field
	 * @return bool
	 */
	public function addPhone($field): bool
	{
		$result = false;

		if(!empty($field) && Tools\Phone::validate($field) && !Tools\Phone::isInArray($this->phones, $field))
		{
			$this->phones[] = $field;

			$result = true;
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return bool
	 */
	public function setPhones($fields)
	{
		$result = false;

		if(!empty($fields) && is_array($fields))
		{
			$this->phones = Tools\Phone::getArrayUniqueValidate($fields);

			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function resetPhones(): bool
	{
		$this->phones = [];

		return true;
	}

	/**
	 * @return array
	 */
	public function getPhones()
	{
		return $this->phones;
	}

	/**
	 * @param $field
	 * @return bool
	 */
	public function addEmail($field): bool
	{
		$result = false;

		if(!empty($field) && Tools\Email::validate($field) && !Tools\Email::isInArray($this->emails, $field))
		{
			$this->emails[] = $field;

			$result = true;
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return bool
	 */
	public function setEmails($fields): bool
	{
		$result = false;

		if(!empty($fields) && is_array($fields))
		{
			$this->emails = Tools\Email::getArrayUniqueValidate($fields);

			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function resetEmails(): bool
	{
		$this->emails = [];

		return true;
	}

	/**
	 * @return array
	 */
	public function getEmails()
	{
		return $this->emails;
	}

	/**
	 * @param array $fields
	 * @return bool
	 */
	public function setPerson(array $fields): bool
	{
		$result = false;

		if(!empty($fields) && is_array($fields))
		{
			if(isset($fields['NAME']))
			{
				$result = $this->setPersonName($fields['NAME']);
			}
			if(isset($fields['LAST_NAME']))
			{
				$result = $this->setPersonLastName($fields['LAST_NAME']);
			}
			if(isset($fields['SECOND_NAME']))
			{
				$result = $this->setPersonSecondName($fields['SECOND_NAME']);
			}
			if(isset($fields['EMAIL']) && Tools\Email::validate($fields['EMAIL']))
			{
				$result = $this->setPersonEmail($fields['EMAIL']);
			}
			if(isset($fields['PHONE']) && Tools\Phone::validate($fields['PHONE']))
			{
				$result = $this->setPersonPhone($fields['PHONE']);
			}
			if(isset($fields['WEBSITE']))
			{
				$result = $this->setPersonWebsite($fields['WEBSITE']);
			}
		}

		return $result;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function setDataFromUser($userId = 0): bool
	{
		$result = false;

		if(!empty($this->session) && !empty($this->session->getData('USER_ID')) && $this->session->getData('USER_ID') > 0)
		{
			$userId = $this->session->getData('USER_ID');
		}

		if(!empty($userId) && $userId>0)
		{
			$user = Im\User::getInstance($userId);

			if(!empty($user))
			{
				if (!$user->getLastName() && !$user->getName())
				{
					$this->setPersonName($user->getFullName(false));
				}
				else
				{
					$this->setPersonName($user->getName());
				}
				$this->setPersonLastName($user->getLastName());
				$this->setPersonSecondName('');

				$email = $user->getEmail();
				if(!empty($email) && Tools\Email::validate($email))
				{
					$this->setPersonEmail($email);
				}
				elseif(empty($email))
				{
					$this->setPersonEmail('');
				}
				$phone = $user->getPhone();
				if(!empty($phone) && Tools\Phone::validate($phone))
				{
					$this->setPersonPhone($phone);
				}
				elseif(empty($phone))
				{
					$this->setPersonPhone('');
				}

				$this->setPersonWebsite($user->getWebsite());

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function setPersonName($field): bool
	{
		$this->person['NAME'] = $field;

		return true;
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function setPersonLastName($field): bool
	{
		$this->person['LAST_NAME'] = $field;

		return true;
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function setPersonSecondName($field): bool
	{
		$this->person['SECOND_NAME'] = $field;

		return true;
	}

	/**
	 * @param $field
	 * @return bool
	 */
	public function setPersonEmail($field): bool
	{
		$return = false;

		if(Tools\Email::validate($field))
		{
			$this->person['EMAIL'] = $field;

			$return = true;
		}

		return $return;
	}

	/**
	 * @param $field
	 * @return bool
	 */
	public function setPersonPhone($field): bool
	{
		$return = false;

		if(Tools\Phone::validate($field))
		{
			$this->person['PHONE'] = $field;

			$return = true;
		}

		return $return;
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function setPersonWebsite($field): bool
	{
		$this->person['WEBSITE'] = $field;

		return true;
	}

	/**
	 * @return array
	 */
	public function getPerson()
	{
		return $this->person;
	}

	/**
	 * @return string
	 */
	public function getPersonName()
	{
		return $this->person['NAME'];
	}

	/**
	 * @return string
	 */
	public function getPersonLastName()
	{
		return $this->person['LAST_NAME'];
	}

	/**
	 * @return string
	 */
	public function getPersonSecondName()
	{
		return $this->person['SECOND_NAME'];
	}

	/**
	 * @return string
	 */
	public function getPersonEmail()
	{
		return $this->person['EMAIL'];
	}

	/**
	 * @return string
	 */
	public function getPersonPhone()
	{
		return $this->person['PHONE'];
	}

	/**
	 * @return string
	 */
	public function getPersonWebsite()
	{
		return $this->person['WEBSITE'];
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function setTitle($field): bool
	{
		$this->title = $field;

		return true;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
}