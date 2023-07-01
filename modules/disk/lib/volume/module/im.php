<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Main;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Im extends Volume\Module\Module
	implements Volume\IDeleteConstraint, Volume\IClearConstraint
{
	/** @var string */
	protected static $moduleId = 'im';

	/** @var Disk\Storage[] */
	private $storageList = [];

	/** @var Disk\Folder[] */
	private $folderList = [];

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function measure(array $collectData = []): self
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new \Bitrix\Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$indicatorType = $connection->getSqlHelper()->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		// collect disk statistics
		$this
			->addFilter(0, [
				'LOGIC' => 'OR',
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => \Bitrix\Im\Disk\ProxyType\Im::className(),
			])
			->addFilter('DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);

		parent::measure();

		// collect none disk statistics
		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
				SUM(FILE_SIZE) as FILE_SIZE,
				COUNT(*) as FILE_COUNT,
				0 as DISK_SIZE,
				0 as DISK_COUNT
			FROM
				b_file
			WHERE
				MODULE_ID IN('imopenlines', 'imconnector', 'imbot')
		";

		$columnList = Volume\QueryHelper::prepareInsert(
			[
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
			],
			$this->getSelect()
		);

		$tableName = VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");


		// collect folders statistics
		$storageListId = [];
		$folderListId = [];
		$storageList = $this->getStorageList();
		if (count($storageList) > 0)
		{
			foreach ($storageList as $storage)
			{
				$storageListId[] = $storage->getId();
				$folders = $this->getFolderList($storage);
				if (count($folders) > 0)
				{
					foreach ($folders as $folder)
					{
						$folderListId[] = $folder->getId();
					}
				}
			}
		}
		if (count($storageListId) > 0 && count($folderListId) > 0)
		{
			$agr = new Volume\FolderTree;
			$agr
				->setOwner($this->getOwner())
				->addFilter('@STORAGE_ID', $storageListId)
				->addFilter('@FOLDER_ID', $folderListId)
				->purify()
				->measure([self::DISK_FILE]);
		}

		return $this;
	}

	/**
	 * Returns module storage.
	 * @return Disk\Storage[]|array
	 */
	public function getStorageList(): array
	{
		if (count($this->storageList) == 0 || !$this->storageList[0] instanceof Disk\Storage)
		{
			$entityTypes = self::getEntityType();
			$storage = Disk\Storage::load([
				'MODULE_ID' => self::getModuleId(),
				//'ENTITY_TYPE' => \Bitrix\Im\Disk\ProxyType\Im::className()
				'ENTITY_TYPE' => $entityTypes[0]
			]);

			if ($storage instanceof Disk\Storage)
			{
				$this->storageList[] = $storage;
			}
		}

		return $this->storageList;
	}

	/**
	 * Returns folder list corresponding to module.
	 * @param Disk\Storage $storage Module's storage.
	 * @return Disk\Folder[]|array
	 */
	public function getFolderList($storage): array
	{
		if (
			$storage instanceof Disk\Storage
			&& $storage->getId() > 0
		)
		{
			if (
				!isset($this->folderList[$storage->getId()])
				|| empty($this->folderList[$storage->getId()])
			)
			{
				$this->folderList[$storage->getId()] = [];
				if ($this->isMeasureAvailable())
				{
					$this->folderList[$storage->getId()][] = $storage->getRootObject();
				}
			}

			return $this->folderList[$storage->getId()];
		}

		return [];
	}

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode(): array
	{
		return ['IM_SAVED'];
	}

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		return [
			\Bitrix\Im\Disk\ProxyType\Im::class
		];
	}

	/**
	 * Check ability to clear storage.
	 * @param Disk\Storage $storage Storage to clear.
	 * @return boolean
	 */
	public function isAllowClearStorage(Disk\Storage $storage): bool
	{
		static $imStorageId;
		if (empty($imStorageId))
		{
			$storageList = $this->getStorageList();
			if ($storageList[0] instanceof Disk\Storage)
			{
				$imStorageId = $storageList[0]->getId();
			}
		}

		// disallow clearance if im is unavailable
		if ($storage instanceof Disk\Storage)
		{
			if ($imStorageId === $storage->getId())
			{
				return $this->isMeasureAvailable();// returns false to prevent fatal error
			}
		}

		return true;
	}

	/**
	 * Check ability to drop folder.
	 * @param Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(Disk\Folder $folder): bool
	{
		if ($folder->isDeleted())
		{
			return true;
		}

		static $imStorageId;
		if (empty($imStorageId))
		{
			$storageList = $this->getStorageList();
			if ($storageList[0] instanceof Disk\Storage)
			{
				$imStorageId = $storageList[0]->getId();
			}
		}

		// disallow drop any folders within IM storage
		return (bool)($imStorageId != $folder->getStorageId());
	}

	/**
	 * Returns calculation result set per folder.
	 * @param array $collectedData List types of collected data to return.
	 * @return array
	 */
	public function getMeasurementFolderResult($collectedData = [])
	{
		\Bitrix\Main\Loader::includeModule(self::getModuleId());

		$chatList = [];

		$totalSize = 0;
		$storageList = $this->getStorageList();
		if (count($storageList) > 0)
		{
			foreach ($storageList as $storage)
			{
				$folders = $this->getFolderList($storage);
				$folderIds = [];
				if (count($folders) > 0)
				{
					foreach ($folders as $folder)
					{
						$folderIds[] = $folder->getId();
					}
				}

				if (count($folderIds) > 0)
				{
					$folder = new Volume\FolderTree;
					$folder
						->setOwner($this->getOwner())
						->addFilter('=STORAGE_ID', $storage->getId())
						->addFilter('@FOLDER_ID', $folderIds)
						->loadTotals();

					if ($folder->getTotalCount() > 0)
					{
						$result = $folder->getMeasurementResult();

						foreach ($result as $row)
						{
							$chatList[] = $row;
							$totalSize += $row['FILE_SIZE'];
						}
					}
				}
			}
		}
		if ($totalSize > 0)
		{
			foreach ($chatList as $id => $row)
			{
				$percent = $row['FILE_SIZE'] * 100 / $totalSize;
				$chatList[$id]['PERCENT'] = round($percent, 1);
			}
		}

		return $chatList;
	}

	/**
	 * @param string[] $filter Filter with module id.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter): Volume\Fragment
	{
		if ($filter['INDICATOR_TYPE'] == Volume\Folder::className() || $filter['INDICATOR_TYPE'] == Volume\FolderTree::className())
		{
			if (\Bitrix\Main\Loader::includeModule(self::getModuleId()))
			{
				$chatList = \Bitrix\Im\Model\ChatTable::getList([
					'select' => ['ID'],
					'filter' => ['=DISK_FOLDER_ID' => $filter['FOLDER_ID']]
				]);
				if ($chat = $chatList->fetch())
				{
					$chatId = $chat['ID'];

					// Chat specific
					$chatData = \CIMChat::getChatData(['ID' => $chatId, 'PHOTO_SIZE' => 50]);
					if ($chatData && isset($chatData['chat'], $chatData['chat'][$chatId]))
					{
						if ($chatData['chat'][$chatId]['avatar'] === '/bitrix/js/im/images/blank.gif')
						{
							$chatData['chat'][$chatId]['avatar'] = '';
						}
						if ($chatData['chat'][$chatId]['owner'] > 0)
						{
							$chatOwner = \Bitrix\Im\User::getInstance($chatData['chat'][$chatId]['owner']);
							if ($chatOwner instanceof \Bitrix\Im\User)
							{
								$chatData['chat'][$chatId]['owner_name'] = $chatOwner->getFullName();

								if ($chatOwner->isActive() !== true)
								{
									// user fired
									Loc::loadMessages(__DIR__.'/socialnetwork.php');

									if ($chatOwner->getGender() === 'F')
									{
										$chatData['chat'][$chatId]['owner_name'] = Loc::getMessage(
											'DISK_VOLUME_MODULE_SONET_FIRED_F',
											['#USER_NAME#' => $chatData['chat'][$chatId]['owner_name']]
										);
									}
									else
									{
										$chatData['chat'][$chatId]['owner_name'] = Loc::getMessage(
											'DISK_VOLUME_MODULE_SONET_FIRED_M',
											['#USER_NAME#' => $chatData['chat'][$chatId]['owner_name']]
										);
									}
								}
							}
						}
						$filter['SPECIFIC'] = [
							'chat' => $chatData['chat'][$chatId],
						];
						if (is_array($chatData['userInChat'][$chatId]))
						{
							$filter['SPECIFIC']['userInChat'] = $chatData['userInChat'][$chatId];
							$filter['SPECIFIC']['userCount'] = count($chatData['userInChat'][$chatId]);
						}
					}
				}
			}

			return new Volume\Fragment($filter);
		}
		return parent::getFragment($filter);
	}

	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		if (
			$fragment->getIndicatorType() == Volume\Folder::className()
			|| $fragment->getIndicatorType() == Volume\FolderTree::className()
		)
		{
			$specific = $fragment->getSpecific();
			if ($specific['chat']['TITLE'] != '')
			{
				$title = $specific['chat']['TITLE'];
			}
			elseif($specific['userCount'] > 0)
			{
				$chatUserNameList = [];
				foreach ($specific['userInChat'] as $chatUserId)
				{
					$chatUserNameList[] = $userName = \Bitrix\Im\User::getInstance($chatUserId)->getFullName();
					if (count($chatUserNameList) >= 3)
					{
						$chatUserNameList[] = '...';
						break;
					}
				}
				$title = implode(', ', $chatUserNameList);
			}
			else
			{
				$folder = $fragment->getFolder();
				if (!$folder instanceof Disk\Folder)
				{
					throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
				}
				$title = $folder->getOriginalName();
			}

			return $title;
		}

		Loc::loadMessages(__FILE__);
		return Loc::getMessage('DISK_VOLUME_MODULE_IM');
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 * @throws ArgumentTypeException
	 */
	public static function getUpdateTime(Volume\Fragment $fragment): ?\Bitrix\Main\Type\DateTime
	{
		$timestampUpdate = null;
		if ($fragment->getIndicatorType() == Volume\Folder::className() || $fragment->getIndicatorType() == Volume\FolderTree::className())
		{
			$specific = $fragment->getSpecific();
			if ($specific['chat']['LAST_MESSAGE_ID'] > 0)
			{
				$message = \Bitrix\Im\Model\MessageTable::getById($specific['chat']['LAST_MESSAGE_ID'])->fetch();
				/** @var \Bitrix\Main\Type\DateTime $timestampUpdate */
				$timestampUpdate = $message['DATE_CREATE'];
			}
			else
			{
				$folder = $fragment->getFolder();
				if (!$folder instanceof Disk\Folder)
				{
					throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
				}
				$timestampUpdate = $folder->getUpdateTime()->toUserTime();
			}
		}

		return $timestampUpdate;
	}
}
