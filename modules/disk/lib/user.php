<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Document\OnlyOffice\Models\GuestUser;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class User extends Internals\Model
{
	/** @var int */
	protected $id;
	/** @var string */
	protected $login;
	/** @var string */
	protected $formattedName;
	/** @var string */
	protected $password;
	/** @var string */
	protected $email;
	/** @var string */
	protected $active;
	/** @var string */
	protected $name;
	/** @var string */
	protected $secondName;
	/** @var string */
	protected $lastName;
	/** @var string */
	protected $workPosition;
	protected $lid;
	/** @var string */
	protected $personalGender;
	/** @var int */
	protected $personalPhoto;
	/** @var string */
	protected $isOnline;
	/** @var bool */
	private $isIntranetUser;
	/** @var bool */
	private $isExternalAuthEmail;
	/** @var bool */
	private $isCrmEmail;

	/** @var User[] */
	protected static $loadedUsers;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return 'Bitrix\Main\UserTable';
	}

	/**
	 * @inheritdoc
	 */
	public static function loadById($id, array $with = array())
	{
		if(isset(self::$loadedUsers[$id]))
		{
			return self::$loadedUsers[$id];
		}

		if(SystemUser::isSystemUserId($id))
		{
			self::$loadedUsers[$id] = SystemUser::create();
		}
		elseif(GuestUser::isGuestUserId($id))
		{
			self::$loadedUsers[$id] = GuestUser::create();
		}
		else
		{
			self::$loadedUsers[$id] = parent::loadById($id, $with);
		}

		return self::$loadedUsers[$id];
	}

	/**
	 * Builds model from array.
	 * @param array $attributes Model attributes.
	 * @param array &$aliases Aliases.
	 * @internal
	 * @return static
	 */
	public static function buildFromArray(array $attributes, array &$aliases = null)
	{
		$id = $attributes['ID'];
		if(isset(self::$loadedUsers[$id]))
		{
			return self::$loadedUsers[$id];
		}

		if(SystemUser::isSystemUserId($id))
		{
			self::$loadedUsers[$id] = SystemUser::create();
		}
		elseif(GuestUser::isGuestUserId($id))
		{
			self::$loadedUsers[$id] = GuestUser::create();
		}
		else
		{
			self::$loadedUsers[$id] = parent::buildFromArray($attributes, $aliases);
		}

		return self::$loadedUsers[$id];
	}

	/**
	 * Builds model from \Bitrix\Main\Entity\Result.
	 * @param Result $result Query result.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function buildFromResult(Result $result)
	{
		/** @var Storage $model */
		$model = parent::buildFromResult($result);
		self::$loadedUsers[$model->getId()] = $model;

		return $model;
	}

	/**
	 * @return string
	 */
	public function getActive()
	{
		return $this->active;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * @return mixed
	 */
	public function getLid()
	{
		return $this->lid;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getPersonalGender()
	{
		return $this->personalGender == 'F'? 'F' : 'M';
	}

	public function getPersonalGenderExact()
	{
		return $this->personalGender;
	}

	/**
	 * @return int
	 */
	public function getPersonalPhoto()
	{
		return $this->personalPhoto;
	}

	/**
	 * @return string
	 */
	public function getSecondName()
	{
		return $this->secondName;
	}

	/**
	 * @return string
	 */
	public function getWorkPosition()
	{
		return $this->workPosition;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'LOGIN' => 'login',
			'EMAIL' => 'email',
			'ACTIVE' => 'active',
			'NAME' => 'name',
			'SECOND_NAME' => 'secondName',
			'LAST_NAME' => 'lastName',
			'WORK_POSITION' => 'workPosition',
			'LID' => 'lid',
			'PERSONAL_GENDER' => 'personalGender',
			'PERSONAL_PHOTO' => 'personalPhoto',
			'IS_ONLINE' => 'isOnline',
		);
	}

	/**
	 * Getting fields for sql select. Use in reference map for optimization
	 * @return array
	 * @internal
	 */
	public static function getFieldsForSelect()
	{
		return array(
			'ID',
			'LOGIN',
			'EMAIL',
			'NAME',
			'SECOND_NAME',
			'LAST_NAME',
			'PERSONAL_GENDER',
			'PERSONAL_PHOTO',
			'ACTIVE',
			'WORK_POSITION'
		);
	}

	public function isEmptyName()
	{
		return empty($this->name) && empty($this->lastName) && empty($this->secondName);
	}

	public function getFormattedName()
	{
		if($this->formattedName === null)
		{
			$this->formattedName = \CUser::formatName(\CSite::GetNameFormat(), array(
				'NAME' => $this->name,
				'LAST_NAME' => $this->lastName,
				'SECOND_NAME' => $this->secondName,
				'EMAIL' => $this->email,
				'ID' => $this->id,
				'LOGIN' => $this->login,
			), true, false);
		}
		return $this->formattedName;
	}

	public function isIntranetUser()
	{
		if($this->isIntranetUser !== null)
		{
			return $this->isIntranetUser;
		}

		$this->isIntranetUser = false;
		$this->isExternalAuthEmail = false;
		$this->isCrmEmail = false;
		if(!Loader::includeModule('intranet'))
		{
			return false;
		}
		$queryUser = \CUser::getList(
			'ID',
			'ASC',
			array(
				'ID_EQUAL_EXACT' => $this->id,
			),
			array(
				'FIELDS' => array('ID', 'EXTERNAL_AUTH_ID'),
				'SELECT' => array('UF_DEPARTMENT', 'UF_USER_CRM_ENTITY'),
			)
		);
		if ($user = $queryUser->fetch())
		{
			$this->isIntranetUser = !empty($user['UF_DEPARTMENT'][0]);

			if (!empty($user["UF_USER_CRM_ENTITY"]))
			{
				$this->isCrmEmail = true;
			}
			elseif (
				isset($user["EXTERNAL_AUTH_ID"])
				&& $user["EXTERNAL_AUTH_ID"] === 'email'
			)
			{
				$this->isExternalAuthEmail = true;
			}
		}

		return $this->isIntranetUser;
	}

	/**
	 * Checks is user from extranet.
	 * @return bool
	 */
	public function isExtranetUser()
	{
		return !$this->isIntranetUser() && Loader::includeModule('extranet');
	}

	/**
	 * Checks is simple email user.
	 * @return bool
	 */
	public function isExternalAuthEmail()
	{
		return !$this->isIntranetUser() && $this->isExternalAuthEmail;
	}

	/**
	 * Checks is user has CRM junction.
	 * @return bool
	 */
	public function isCrmEmail()
	{
		return !$this->isIntranetUser() && $this->isCrmEmail;
	}

	public function getAvatarSrc($width = 58, $height = 58)
	{
		return Ui\Avatar::getPerson($this->personalPhoto, $width, $height);
	}

	/**
	 * Returns url of detail user page.
	 * @return string
	 */
	public function getDetailUrl()
	{
		if ($this->isExtranetUser())
		{

			$userPage = \COption::getOptionString("socialnetwork", "user_page", false, \CExtranet::getExtranetSiteID());
			if(!$userPage)
			{
				$userPage = '/extranet/contacts/personal/';
			}
		}
		else
		{
			$userPage = \COption::getOptionString("socialnetwork", "user_page", false, SITE_ID);
			if(!$userPage)
			{
				$userPage = SITE_DIR . 'company/personal/';
			}
		}

		return $userPage . 'user/' .  $this->id . '/';
	}

	public function isAdmin()
	{
		try
		{
			if(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24'))
			{
				return \CBitrix24::isPortalAdmin($this->getId());
			}

			global $USER;

			if($USER instanceof \CUser && $this->getId() === $USER->getId())
			{
				return $USER->isAdmin();
			}

			$user = new \CUser();

			return in_array(1, $user->getUserGroup($this->getId()));
		}
		catch(\Exception $e)
		{}

		return false;
	}

	/**
	 * Determines if current user is admin.
	 *
	 * @deprecated
	 * @return bool
	 */
	public static function isCurrentUserAdmin()
	{
		global $USER;
		if(!$USER instanceof \CUser)
		{
			return false;
		}
		if($USER->isAdmin())
		{
			return true;
		}
		try
		{
			if(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24'))
			{
				return \CBitrix24::isPortalAdmin($USER->getId());
			}
		}
		catch(\Exception $e)
		{
		}

		return false;
	}

	/**
	 * Resolves userId from parameter $user.
	 *
	 * @param \CUser|User|CurrentUser|int $user Different types: User model, CUser, id of user.
	 * @return int|null
	 */
	public static function resolveUserId($user)
	{
		if ($user instanceof CurrentUser)
		{
			return $user->getId();
		}
		if($user instanceof User)
		{
			return (int)$user->getId();
		}
		if ($user instanceof \CUser)
		{
			return (int)$user->getId();
		}
		elseif(is_numeric($user) && (int)$user > 0)
		{
			return (int)$user;
		}

		return null;
	}

	/**
	 * @internal
	 * @param      $userId
	 * @param User $currentFieldValue
	 * @return User|EmptyUser|SystemUser
	 */
	public static function getModelForReferenceField($userId, User $currentFieldValue = null)
	{
		if($userId === null)
		{
			return EmptyUser::create();
		}
		if(SystemUser::isSystemUserId($userId))
		{
			return SystemUser::create();
		}
		if(isset($currentFieldValue) && $userId == $currentFieldValue->getId())
		{
			return $currentFieldValue;
		}

		$user = User::loadById($userId);
		if(!$user)
		{
			return EmptyUser::create();
		}

		return $user;
	}
}