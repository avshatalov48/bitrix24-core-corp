<?php

namespace Bitrix\Disk\Search;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ObjectSaveIndexTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Disk\Storage;
use Bitrix\Disk\ProxyType;
use Bitrix\Main\Loader;
use Bitrix\Main\Text;
use CSearch;

final class IndexManager
{
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var ContentManager */
	protected $contentManager;
	protected $useSearchModule = true;

	/**
	 * Constructor IndexManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
		$this->contentManager = new ContentManager;
	}

	/**
	 * Disables using search module.
	 * @return $this
	 */
	public function disableUsingSearchModule()
	{
		$this->useSearchModule = false;

		return $this;
	}

	protected function saveFullText(BaseObject $object, $content)
	{
		if (Configuration::allowFullTextIndex())
		{
			$fulltextContent = FullTextBuilder::create()
				->addText($content)
				->addUser($object->getCreatedBy())
				->getSearchValue()
			;

			ObjectSaveIndexTable::update($object->getId(), array(
				'SEARCH_INDEX' => $fulltextContent,
			));
		}
	}

	/**
	 * Runs index by file.
	 *
	 * @param File $file Target file.
	 * @param array $additionalData
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Exception
	 */
	public function indexFile(File $file, array $additionalData = array())
	{
		//here we place configuration by Module (Options). Example, we can deactivate index for big files in Disk.
		if (!Configuration::allowIndexFiles())
		{
			return;
		}

		$storage = $file->getStorage();
		if (!$storage || !$storage->getProxyType()->canIndexBySearch())
		{
			return;
		}

		//save the short information about file
		$this->saveFullText($file, $this->getFileContent($file, ['withoutBody' => true]));

		if (array_key_exists('content', $additionalData))
		{
			$fileContent = $this->processIndexContent($additionalData['content']);
		}
		else
		{
			//try to extract full text from file
			$fileContent = $this->getFileContent($file);
		}

		if ($fileContent)
		{
			//if we don't have content we don't have to rewrite data which was saved before (only title).
			$this->saveFullText($file, $fileContent);
		}

		if (!$this->useSearchModule)
		{
			return;
		}

		if (!Loader::includeModule('search'))
		{
			return;
		}

		$searchData = array(
			'LAST_MODIFIED' => $file->getUpdateTime()?: $file->getCreateTime(),
			'TITLE' => $file->getName(),
			'PARAM1' => $file->getStorageId(),
			'PARAM2' => $file->getParentId(),
			'SITE_ID' => self::resolveSiteId($storage),
			'URL' => $this->getDetailUrl($file),
			'PERMISSIONS' => $this->getSimpleRights($file),
			//CSearch::killTags
			'BODY' => strip_tags($file->getCreateUser()->getFormattedName()) . "\r\n" . $fileContent,
		);
		if ($storage->getProxyType() instanceof Group)
		{
			$searchData['PARAMS'] = array(
				'socnet_group' => $storage->getEntityId(),
				'entity' => 'socnet_group',
			);
		}

		$maxIndexSize = Configuration::getMaxIndexSize();
		if ($maxIndexSize > 0)
		{
			//yes, we know that substr may kills some last characters
			$searchData['BODY'] = Text\BinaryString::getSubstring($searchData['BODY'], 0, $maxIndexSize);
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CSearch::index(Driver::INTERNAL_MODULE_ID, $this->getItemId($file), $searchData, true);
	}

	/**
	 * Runs index by folder.
	 * @param Folder $folder Target folder.
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function indexFolder(Folder $folder)
	{
		//here we place configuration by Module (Options). Example, we can deactivate index for big files in Disk.
		if (!Configuration::allowIndexFiles())
		{
			return;
		}

		$storage = $folder->getStorage();
		if (!$storage || !$storage->getProxyType()->canIndexBySearch())
		{
			return;
		}

		$folderContent = $this->getFolderContent($folder);
		$this->saveFullText($folder, $folderContent);

		if (!$this->useSearchModule)
		{
			return;
		}

		if(!Loader::includeModule('search'))
		{
			return;
		}

		$searchData = array(
			'LAST_MODIFIED' => $folder->getUpdateTime()?: $folder->getCreateTime(),
			'TITLE' => $folder->getName(),
			'PARAM1' => $folder->getStorageId(),
			'PARAM2' => $folder->getParentId(),
			'SITE_ID' => self::resolveSiteId($storage),
			'URL' => $this->getDetailUrl($folder),
			'PERMISSIONS' => $this->getSimpleRights($folder),
			//CSearch::killTags
			'BODY' => $folderContent,
		);
		if($storage->getProxyType() instanceof Group)
		{
			$searchData['PARAMS'] = array(
				'socnet_group' => $storage->getEntityId(),
				'entity' => 'socnet_group',
			);
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CSearch::index(Driver::INTERNAL_MODULE_ID, $this->getItemId($folder), $searchData, true);
	}

	/**
	 * Changes index after rename.
	 * @param BaseObject $object Target file or folder.
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function changeName(BaseObject $object)
	{
		//here we place configuration by Module (Options). Example, we can deactivate index for big files in Disk.
		if(!Configuration::allowIndexFiles())
		{
			return;
		}
		$storage = $object->getStorage();
		if(!$storage)
		{
			return;
		}
		if(!$storage->getProxyType()->canIndexBySearch())
		{
			return;
		}

		if($object instanceof Folder)
		{
			$this->indexFolder($object);
		}
		elseif($object instanceof File)
		{
			$this->indexFile($object);
		}
	}

	/**
	 * Delete information from Search by concrete file or folder.
	 * @param BaseObject $object Target object.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function dropIndex(BaseObject $object)
	{
		if(!Loader::includeModule('search'))
		{
			return;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CSearch::deleteIndex(Driver::INTERNAL_MODULE_ID, $this->getItemId($object));
	}

	/**
	 * Recalculate rights in Search if it needs.
	 * @param BaseObject $object Target object (can be folder or file).
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function recalculateRights(BaseObject $object)
	{
		if (!$this->useSearchModule)
		{
			return;
		}

		if(!Loader::includeModule('search'))
		{
			return;
		}
		if($object instanceof File)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			CSearch::changePermission(
				Driver::INTERNAL_MODULE_ID,
				$this->getSimpleRights($object),
				$this->getItemId($object)
			);
		}
		elseif($object instanceof Folder)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			CSearch::changePermission(
				Driver::INTERNAL_MODULE_ID,
				$this->getSimpleRights($object),
				false,
				$object->getStorageId(),
				$object->getId()
			);
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			CSearch::changePermission(
				Driver::INTERNAL_MODULE_ID,
				$this->getSimpleRights($object),
				$this->getItemId($object)
			);
		}
	}

	/**
	 * Event listener which return url for resource by fields.
	 * @param array $fields Fields from search module.
	 * @return string
	 */
	public static function onSearchGetUrl($fields)
	{
		if(!is_array($fields))
		{
			return '';
		}
		if($fields["MODULE_ID"] !== "disk" || substr($fields["URL"], 0, 1) !== "=")
		{
			return $fields["URL"];
		}

		parse_str(ltrim($fields["URL"], "="), $data);
		if(empty($data['ID']))
		{
			return '';
		}
		$object = BaseObject::loadById($data['ID']);
		if(!$object)
		{
			return '';
		}
		$pathFileDetail = self::getDetailUrl($object);
		\CSearch::update($fields['ID'], array('URL' => $pathFileDetail));

		return $pathFileDetail;
	}

	/**
	 * Returns stored index in module search.
	 * @param BaseObject $object File or Folder.
	 *
	 * @return null|array
	 */
	public function getStoredIndex(BaseObject $object)
	{
		if(!Loader::includeModule('search'))
		{
			return null;
		}

		$itemId = self::getItemId($object);
		$index = \CSearch::getIndex(Driver::INTERNAL_MODULE_ID, $itemId);

		return $index?: null;
	}

	/**
	 * Search re-index handler.
	 * @param array  $nextStepData Array with data about step.
	 * @param null   $searchObject Search object.
	 * @param string $method Method.
	 * @return array|bool
	 */
	public static function onSearchReindex($nextStepData = array(), $searchObject = null, $method = "")
	{
		$result = array();
		$filter = array(
			'!PARENT_ID' => null,
		);

		if(isset($nextStepData['MODULE']) && ($nextStepData['MODULE'] === 'disk') && !empty($nextStepData['ID']))
		{
			$filter['>ID'] = self::getObjectIdFromItemId($nextStepData['ID']);
		}
		else
		{
			$filter['>ID'] = 0;
		}

		static $self = null;
		if($self === null)
		{
			$self = Driver::getInstance()->getIndexManager();
		}

		$query = BaseObject::getList([
			'filter' => $filter,
			'order' => ['ID' => 'ASC'],
			'limit' => 1000,
		]);
		while($fileData = $query->fetch())
		{
			/** @var BaseObject $object */
			$object = BaseObject::buildFromArray($fileData);
			if(!$object->getStorage())
			{
				continue;
			}

			$searchData = array(
				'ID' => self::getItemId($object),
				'LAST_MODIFIED' => $object->getUpdateTime() ?: $object->getCreateTime(),
				'TITLE' => $object->getName(),
				'PARAM1' => $object->getStorageId(),
				'PARAM2' => $object->getParentId(),
				'SITE_ID' => self::resolveSiteId($object->getStorage()),
				'URL' => self::getDetailUrl($object),
				'PERMISSIONS' => $self->getSimpleRights($object),
				//CSearch::killTags
				'BODY' => $self->getObjectContent($object),
			);

			if($searchObject)
			{
				$indexResult = call_user_func(array($searchObject, $method), $searchData);
				if(!$indexResult)
				{
					return $searchData["ID"];
				}
			}
			else
			{
				$result[] = $searchData;
			}
		}

		if($searchObject)
		{
			return false;
		}

		return $result;
	}

	private static function resolveSiteId(Storage $storage)
	{
		$siteId = $storage->getSiteId();
		if($siteId)
		{
			return array($siteId => '');
		}

		if(!Loader::includeModule('socialnetwork'))
		{
			return array(
				self::getDefaultSiteId()?: SITE_ID => ''
			);
		}

		if($storage->getProxyType() instanceof ProxyType\User)
		{
			$user = \Bitrix\Main\UserTable::getList(
				array(
					'select' => array('ID', 'UF_DEPARTMENT'),
					'filter' => array('ID' => $storage->getEntityId())
				)
			)->fetch();

			//user is intranet user
			if(
				!empty($user["UF_DEPARTMENT"])
				&& is_array($user["UF_DEPARTMENT"])
				&& intval($user["UF_DEPARTMENT"][0]) > 0
			)
			{
				$site = \CSocNetLogComponent::getSiteByDepartmentId($user["UF_DEPARTMENT"]);

				return $site["LID"];
			}

			if(Loader::includeModule('extranet'))
			{
				return array(
					self::getExtranetSiteId() => '',
				);
			}

			return array(
				self::getDefaultSiteId()?: SITE_ID => ''
			);
		}
		elseif($storage->getProxyType() instanceof ProxyType\Group)
		{
			$query = \CSocNetGroup::getSite($storage->getEntityId());
			$sites = array();
			while($groupSite = $query->fetch())
			{
				$sites[$groupSite['LID']] = '';
			}

			return $sites;
		}

		return array(
			self::getDefaultSiteId()?: SITE_ID => ''
		);
	}

	private static function getExtranetSiteId()
	{
		static $extranetSiteId = null;

		if($extranetSiteId)
		{
			return $extranetSiteId;
		}

		if(!Loader::includeModule('extranet'))
		{
			return null;
		}

		return \CExtranet::getExtranetSiteID();
	}

	private static function getDefaultSiteId()
	{
		static $defaultSite = null;

		if($defaultSite)
		{
			return $defaultSite['LID'];
		}

		$sites = \CSite::GetList($b = 'SORT', $o = 'asc', array('ACTIVE' => 'Y'));
		while ($site = $sites->getNext())
		{
			if(!$defaultSite && $site['DEF'] == 'Y')
			{
				$defaultSite = $site;
			}
		}

		return $defaultSite['LID'];
	}

	private function processIndexContent($content)
	{
		$maxIndexSize = Configuration::getMaxIndexSize();
		if ($maxIndexSize > 0)
		{
			//yes, we know that substr may kills some last characters
			return Text\BinaryString::getSubstring($content, 0, $maxIndexSize);
		}

		return $content;
	}

	private function getObjectContent(BaseObject $object, array $options = null)
	{
		return $this->processIndexContent(
			$this->contentManager->getObjectContent($object, $options)
		);
	}

	private function getFolderContent(Folder $folder)
	{
		return $this->getObjectContent($folder);
	}

	private function getFileContent(File $file, array $options = null)
	{
		return $this->getObjectContent($file, $options);
	}

	private function getSimpleRights(BaseObject $object)
	{
		$query = SimpleRightTable::getList(array(
			'select' => array('ACCESS_CODE'),
			'filter' => array(
				'OBJECT_ID' => $object->getId(),
			)
		));
		$permissions = array();
		while($row = $query->fetch())
		{
			$permissions[] = $row['ACCESS_CODE'];
		}

		return $permissions;
	}

	/**
	 * Getting id for module search.
	 * @param BaseObject $object
	 * @return string
	 */
	private static function getItemId(BaseObject $object)
	{
		if($object instanceof File)
		{
			return 'FILE_' . $object->getId();
		}
		return 'FOLDER_' . $object->getId();
	}

	private static function getObjectIdFromItemId($itemId)
	{
		if(substr($itemId, 0, 5) === 'FILE_')
		{
			return substr($itemId, 5);
		}
		return substr($itemId, 7);
	}

	private static function getDetailUrl(BaseObject $object)
	{
		$detailUrl = '';
		$urlManager = Driver::getInstance()->getUrlManager();
		if($object instanceof File)
		{
			$detailUrl = $urlManager->getUrlFocusController('openFileDetail', array('fileId' => $object->getId()));
		}
		elseif($object instanceof Folder)
		{
			$detailUrl = $urlManager->getUrlFocusController('openFolderList', array('folderId' => $object->getId()));
		}

		return $detailUrl;
	}
} 