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

	/** @var bool */
	protected $skipPhoneValidate = false;

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
	 * @return self
	 */
	public function setSession(Session $session): self
	{
		$this->session = $session;
		return $this;
	}

	/**
	 * @return Session
	 */
	public function getSession(): Session
	{
		return $this->session;
	}

	/**
	 * @param $field
	 * @return self
	 */
	public function setCode($field): self
	{
		$this->code = $field;

		return $this;
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
	 * @return self
	 */
	public function setUserId($field): self
	{
		$this->userId = $field;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param bool $flag
	 * @return self
	 */
	public function setSkipPhoneValidate(bool $flag): self
	{
		$this->skipPhoneValidate = $flag;

		return $this;
	}

	/**
	 * @param $field
	 * @return bool
	 */
	public function addPhone($field): bool
	{
		$result = false;

		if (
			!empty($field)
			&& $this->skipPhoneValidate !== true
			&& Tools\Phone::validate($field)
			&& !Tools\Phone::isInArray($this->phones, $field)
		)
		{
			$this->phones[] = $field;
			$result = true;
		}
		elseif (
			!empty($field)
			&& $this->skipPhoneValidate === true
			&& Tools\Phone::extractNumbers($field)
			&& !Tools\Phone::isInArray($this->phones, $field)
		)
		{
			$this->phones[] = $field;
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return self
	 */
	public function setPhones($fields): self
	{
		if (!empty($fields) && is_array($fields))
		{
			$this->phones = Tools\Phone::getArrayUniqueValidate($fields);
		}

		return $this;
	}

	/**
	 * @return self
	 */
	public function resetPhones(): self
	{
		$this->phones = [];

		return $this;
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
	 * @return self
	 */
	public function addEmail($field): self
	{
		if (!empty($field) && Tools\Email::validate($field) && !Tools\Email::isInArray($this->emails, $field))
		{
			$this->emails[] = $field;
		}

		return $this;
	}

	/**
	 * @param $fields
	 * @return self
	 */
	public function setEmails($fields): self
	{
		if (!empty($fields) && is_array($fields))
		{
			$this->emails = Tools\Email::getArrayUniqueValidate($fields);
		}

		return $this;
	}

	/**
	 * @return self
	 */
	public function resetEmails(): self
	{
		$this->emails = [];

		return $this;
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
	 * @return self
	 */
	public function setPerson(array $fields): self
	{
		if (!empty($fields) && is_array($fields))
		{
			if (isset($fields['NAME']))
			{
				$this->setPersonName($fields['NAME']);
			}
			if (isset($fields['LAST_NAME']))
			{
				$this->setPersonLastName($fields['LAST_NAME']);
			}
			if (isset($fields['SECOND_NAME']))
			{
				$this->setPersonSecondName($fields['SECOND_NAME']);
			}
			if (isset($fields['EMAIL']) && Tools\Email::validate($fields['EMAIL']))
			{
				$this->setPersonEmail($fields['EMAIL']);
			}
			if (isset($fields['PHONE']) && Tools\Phone::validate($fields['PHONE']))
			{
				$this->setPersonPhone($fields['PHONE']);
			}
			if (isset($fields['WEBSITE']))
			{
				$this->setPersonWebsite($fields['WEBSITE']);
			}
		}

		return $this;
	}

	/**
	 * @param int $userId
	 * @return self
	 */
	public function setDataFromUser($userId = 0): self
	{
		if (!empty($this->session) && !empty($this->session->getData('USER_ID')) && $this->session->getData('USER_ID') > 0)
		{
			$userId = $this->session->getData('USER_ID');
		}

		if (!empty($userId) && $userId>0)
		{
			$user = Im\User::getInstance($userId);

			if (!empty($user))
			{
				if (!$user->getLastName() && !$user->getName())
				{
					$this->setPersonName($user->getFullName(false));
				}
				else
				{
					$this->setPersonName($user->getName());
				}
				$this
					->setPersonLastName($user->getLastName())
					->setPersonSecondName('')
				;

				$email = $user->getEmail();
				if (!empty($email) && Tools\Email::validate($email))
				{
					$this->setPersonEmail($email);
				}
				elseif (empty($email))
				{
					$this->setPersonEmail('');
				}
				$phone = $user->getPhone();
				if (
					!empty($phone)
					&& $this->skipPhoneValidate !== true
					&& Tools\Phone::validate($phone)
				)
				{
					$this->setPersonPhone($phone);
				}
				elseif (
					!empty($phone)
					&& $this->skipPhoneValidate === true
					&& Tools\Phone::extractNumbers($phone)
				)
				{
					$this->setPersonPhone($phone);
				}
				elseif (empty($phone))
				{
					$this->setPersonPhone('');
				}

				$this->setPersonWebsite($user->getWebsite());
			}
		}

		return $this;
	}

	/**
	 * @param string $field
	 * @return self
	 */
	public function setPersonName($field): self
	{
		$this->person['NAME'] = $field;

		return $this;
	}

	/**
	 * @param string $field
	 * @return self
	 */
	public function setPersonLastName($field): self
	{
		$this->person['LAST_NAME'] = $field;

		return $this;
	}

	/**
	 * @param string $field
	 * @return self
	 */
	public function setPersonSecondName($field): self
	{
		$this->person['SECOND_NAME'] = $field;

		return $this;
	}

	/**
	 * @param $field
	 * @return self
	 */
	public function setPersonEmail($field): self
	{
		if (Tools\Email::validate($field))
		{
			$this->person['EMAIL'] = $field;
		}

		return $this;
	}

	/**
	 * @param $field
	 * @return self
	 */
	public function setPersonPhone($field): self
	{
		if (
			$this->skipPhoneValidate !== true
			&& Tools\Phone::validate($field)
		)
		{
			$this->person['PHONE'] = $field;
		}
		elseif (
			$this->skipPhoneValidate === true
			&& Tools\Phone::extractNumbers($field)
		)
		{
			$this->person['PHONE'] = $field;
		}

		return $this;
	}

	/**
	 * @param string $field
	 * @return self
	 */
	public function setPersonWebsite($field): self
	{
		$this->person['WEBSITE'] = $field;

		return $this;
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
	public function getPersonName(): string
	{
		return $this->person['NAME'] ?? '';
	}

	/**
	 * @return string
	 */
	public function getPersonLastName(): string
	{
		return $this->person['LAST_NAME'] ?? '';
	}

	/**
	 * @return string
	 */
	public function getPersonSecondName(): string
	{
		return $this->person['SECOND_NAME'] ?? '';
	}

	/**
	 * @return string
	 */
	public function getPersonEmail(): string
	{
		return $this->person['EMAIL'] ?? '';
	}

	/**
	 * @return string
	 */
	public function getPersonPhone(): string
	{
		return $this->person['PHONE'] ?? '';
	}

	/**
	 * @return string
	 */
	public function getPersonWebsite(): string
	{
		return $this->person['WEBSITE'] ?? '';
	}

	/**
	 * @param string $field
	 * @return self
	 */
	public function setTitle($field): self
	{
		$this->title = $field;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}
}