<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\DateTime;
use CBXShortUri;

final class ExternalLink extends Internals\Model
{
	public const TYPE_AUTO = ExternalLinkTable::TYPE_AUTO;
	public const TYPE_MANUAL = ExternalLinkTable::TYPE_MANUAL;

	public const ACCESS_RIGHT_VIEW = ExternalLinkTable::ACCESS_RIGHT_VIEW;
	public const ACCESS_RIGHT_EDIT = ExternalLinkTable::ACCESS_RIGHT_EDIT;

	/** @var int */
	protected $objectId;
	/** @var Object */
	protected $object;
	/** @var int */
	protected $versionId;
	/** @var Version */
	protected $version;
	/** @var string */
	protected $hash;
	/** @var string */
	protected $password;
	/** @var string */
	protected $salt;
	/** @var  DateTime */
	protected $deathTime;
	/** @var string */
	protected $description;
	/** @var int */
	protected $downloadCount;
	/** @var int */
	protected $accessRight;
	/** @var int */
	protected $type;
	/** @var  DateTime */
	protected $createTime;
	/** @var int */
	protected $createdBy;
	/** @var User */
	protected $createUser;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return ExternalLinkTable::className();
	}

	/**
	 * Returns time of create object.
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * Returns user model, who created object.
	 * @return User
	 */
	public function getCreateUser()
	{
		if(isset($this->createUser) && $this->createdBy == $this->createUser->getId())
		{
			return $this->createUser;
		}
		$this->createUser = User::getModelForReferenceField($this->createdBy, $this->createUser);

		return $this->createUser;
	}

	/**
	 * Returns id of user, who created object.
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * Returns time to death object.
	 * @return DateTime
	 */
	public function getDeathTime()
	{
		return $this->deathTime;
	}

	/**
	 * Returns description.
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Returns download count.
	 * @return int
	 */
	public function getDownloadCount()
	{
		return $this->downloadCount;
	}

	/**
	 * Returns hash.
	 * @return string
	 */
	public function getHash()
	{
		return $this->hash;
	}

	/**
	 * Returns id of object which is published.
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * Returns id of version which is published.
	 *
	 * Not filled if not published version.
	 * @see self::isSpecificVersion()
	 * @return int
	 */
	public function getVersionId()
	{
		return $this->versionId;
	}

	/**
	 * Returns version model.
	 *
	 * Not filled if not published version.
	 * @see self::isSpecificVersion()
	 * @return Version|null
	 */
	public function getVersion()
	{
		if(!$this->versionId)
		{
			return null;
		}

		if(isset($this->version) && $this->versionId == $this->version->getId())
		{
			return $this->version;
		}
		$this->version = Version::loadById($this->versionId);

		return $this->version;
	}

	/**
	 * Returns file.
	 *
	 * @return null|File
	 */
	public function getFile()
	{
		$object = $this->getObject();
		if(!$object instanceof File)
		{
			return null;
		}

		return $object;
	}

	/**
	 * Returns folder.
	 *
	 * @return null|Folder
	 */
	public function getFolder()
	{
		$object = $this->getObject();
		if(!$object instanceof Folder)
		{
			return null;
		}

		return $object;
	}

	/**
	 * Returns object.
	 *
	 * @return null|BaseObject
	 */
	public function getObject()
	{
		if(!$this->objectId)
		{
			return null;
		}

		if(isset($this->object) && $this->objectId == $this->object->getId())
		{
			return $this->object;
		}

		$this->object = BaseObject::loadById($this->objectId);

		return $this->object;
	}

	/**
	 * Returns password hash.
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Tells if external link has password.
	 * @return bool
	 */
	public function hasPassword()
	{
		return (bool)$this->password;
	}

	/**
	 * Tells if external link has time to death.
	 * @return bool
	 */
	public function hasDeathTime()
	{
		return (bool)$this->deathTime;
	}

	/**
	 * Returns salt.
	 * @return string
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	/**
	 * Returns type of external link.
	 * @see ExternalLinkTable::getListOfTypeValues().
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * Returns access right (view, edit).
	 * @return int
	 */
	public function getAccessRight(): int
	{
		return (int)$this->accessRight;
	}

	public function allowEdit(): bool
	{
		return $this->getAccessRight() === self::ACCESS_RIGHT_EDIT;
	}

	public function allowView(): bool
	{
		return true;
	}

	public function availableEdit(): bool
	{
		$object = $this->getObject();
		if (!($object instanceof File))
		{
			return false;
		}

		if (!DocumentHandler::isEditable($object->getExtension()))
		{
			return false;
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getDefaultHandlerForView();

		return ($documentHandler instanceof OnlyOfficeHandler);
	}

	/**
	 * Tells if the external link has type AUTO.
	 * @return bool
	 */
	public function isAutomatic()
	{
		return $this->type == self::TYPE_AUTO;
	}

	/**
	 * Tells if external link has specific version.
	 * @return bool
	 */
	public function isSpecificVersion()
	{
		return (bool)$this->versionId;
	}

	/**
	 * Tells if external link belongs to image.
	 * @return bool
	 */
	public function isImage()
	{
		$file = $this->getFile();
		if(!$file)
		{
			return false;
		}

		$fileData = $file->getFile();
		if(!$fileData || empty($fileData['CONTENT_TYPE']))
		{
			return false;
		}
		return mb_strpos($fileData['CONTENT_TYPE'], 'image/') === 0;
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'VERSION_ID' => 'versionId',
			'VERSION' => 'version',
			'HASH' => 'hash',
			'PASSWORD' => 'password',
			'SALT' => 'salt',
			'DEATH_TIME' => 'deathTime',
			'DESCRIPTION' => 'description',
			'DOWNLOAD_COUNT' => 'downloadCount',
			'ACCESS_RIGHT' => 'accessRight',
			'TYPE' => 'type',
			'CREATE_TIME' => 'createTime',
			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'CREATE_USER' => array(
				'class' => User::className(),
				'select' => User::getFieldsForSelect(),
			),
			'OBJECT' => array(
				'orm_alias' => 'OBJECT',
				'class' => BaseObject::className(),
			),
			'FILE' => array(
				'orm_alias' => 'OBJECT',
				'class' => BaseObject::className(),
			),
			'VERSION' => Version::className(),
		);
	}

	/**
	 * Adds row to entity table, fills error collection and build model.
	 * @param array           $data Data.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return \Bitrix\Disk\Internals\Model|static|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @internal
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		static::checkRequiredInputParams($data, array(
			'OBJECT_ID',
		));

		if(!empty($data['PASSWORD']))
		{
			list($data['PASSWORD'], $data['SALT']) = ExternalLink::generatePasswordAndSalt($data['PASSWORD']);
		}

		$data['HASH'] = md5(uniqid($data['OBJECT_ID'], true) . \CMain::getServerUniqID());

		return parent::add($data, $errorCollection);
	}

	/**
	 * Generates password hash and salt hash by user-input.
	 * @param string $password Password (plain).
	 * @return array
	 */
	protected static function generatePasswordAndSalt($password)
	{
		$salt = md5(uniqid());
		return array(
			static::hashPassword($password, $salt),
			$salt
		);
	}

	/**
	 * Hashes password by salt.
	 * @param string $password Password (plain).
	 * @param string $salt Salt.
	 * @return string
	 */
	protected static function hashPassword($password, $salt)
	{
		return md5($password . $salt);
	}

	/**
	 * Tells if input password is correct for external link.
	 * @param string $password Password (plain).
	 * @return bool
	 */
	public function checkPassword($password)
	{
		return $this->password === $this->hashPassword($password, $this->salt);
	}

	/**
	 * Tells if external link is expired.
	 * @return bool
	 */
	public function isExpired()
	{
		$now = new DateTime;
		return $this->deathTime && $now->getTimestamp() > $this->deathTime->getTimestamp();
	}

	/**
	 * Increments download count.
	 * @return bool
	 */
	public function incrementDownloadCount()
	{
		$this->errorCollection->clear();
		$success = $this->update(array(
			'DOWNLOAD_COUNT' => new SqlExpression('?# + 1', 'DOWNLOAD_COUNT'),
		));

		if($success)
		{
			$this->downloadCount++;
		}

		return $success;
	}

	public function revokeDeathTime()
	{
		return $this->update(array(
			'DEATH_TIME' => null,
		));
	}

	public function revokePassword()
	{
		return $this->update(array(
			'PASSWORD' => null,
			'SALT' => null,
		));
	}

	/**
	 * Changes time to death.
	 * @param DateTime $dateTime Time to death.
	 * @return bool
	 */
	public function changeDeathTime(DateTime $dateTime)
	{
		return $this->update(array(
			'DEATH_TIME' => $dateTime,
		));
	}

	/**
	 * Changes password.
	 * @param string $newPassword Password (plain).
	 * @return bool
	 */
	public function changePassword($newPassword)
	{
		$data = array();
		list($data['PASSWORD'], $data['SALT']) = ExternalLink::generatePasswordAndSalt($newPassword);

		return $this->update($data);
	}

	public function changeAccessRight(int $right): bool
	{
		return $this->update([
			'ACCESS_RIGHT' => $right,
		]);
	}

	/**
	 * Removes expired links with type AUTO.
	 * @internal Uses in CAgent.
	 * @return string
	 */
	public static function removeExpiredWithTypeAuto()
	{
		$models = static::getModelList([
			'filter' => [
				'TYPE' => self::TYPE_AUTO,
				'IS_EXPIRED' => true,
			],
			'limit' => 100,
		]);

		foreach ($models as $model)
		{
			/** @var ExternalLink $model */
			$model->delete();
		}

		return static::className() . '::removeExpiredWithTypeAuto();';
	}

	/**
	 * Deletes external link and short uri.
	 * @return bool
	 */
	public function delete()
	{
		if($this->getType() === self::TYPE_MANUAL)
		{
			$urlManager = Driver::getInstance()->getUrlManager();

			$this->deleteShortUri($urlManager->getUrlExternalLink([
				'hash' => $this->getHash(),
				'action' => 'default',
			]));
			$this->deleteShortUri($urlManager->getUrlExternalLink([
				'hash' => $this->getHash(),
				'action' => 'default',
			], true));
		}

		return $this->deleteInternal();
	}

	private function deleteShortUri($uri)
	{
		$uriCrc32 = CBXShortUri::crc32($uri);
		$query = CBXShortUri::getList(array(), array("URI_CRC" => $uriCrc32));
		if($result = $query->fetch())
		{
			CBXShortUri::delete($result['ID']);
		}
	}
}