<?php

namespace Bitrix\Disk\Search;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable;
use Bitrix\Disk\Internals\Index\ObjectHeadIndexTable;
use Bitrix\Disk\Internals\ObjectSaveIndexTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Disk\Search\Reindex\HeadIndex;
use Bitrix\Disk\Storage;
use Bitrix\Disk\ProxyType;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use CSearch;

final class IndexManager
{
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var ContentManager */
	protected $contentManager;
	protected $useSearchModule = true;
	protected $allowUseExtendedFullText = true;

	/**
	 * Constructor IndexManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
		$this->contentManager = new ContentManager;

		$this->initDefaultConfiguration();
	}

	public function initDefaultConfiguration()
	{
		$this->allowUseExtendedFullText = Configuration::allowUseExtendedFullText();
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

	public function disableUsingExtendedFullText()
	{
		$this->allowUseExtendedFullText = false;

		return $this;
	}

	protected function saveExtendedFullTextByContent(BaseObject $object, $content = null)
	{
		$textBuilder = $this->getTextBuilder($object);

		if ($object instanceof Folder)
		{
			ObjectExtendedIndexTable::upsert(
				$object->getId(),
				$textBuilder->getSearchValue(),
				ObjectExtendedIndexTable::STATUS_EXTENDED
			);

			return;
		}

		$status = $content ? ObjectExtendedIndexTable::STATUS_EXTENDED : ObjectExtendedIndexTable::STATUS_SHORT;
		if ($status === ObjectExtendedIndexTable::STATUS_EXTENDED)
		{
			//try to update by short version of search index
			ObjectExtendedIndexTable::upsert(
				$object->getId(),
				$textBuilder->getSearchValue(),
				ObjectExtendedIndexTable::STATUS_EXTENDED
			);
		}

		if (is_callable($content))
		{
			$content = $content();
		}

		ObjectExtendedIndexTable::upsert(
			$object->getId(),
			$textBuilder->addText($content)->getSearchValue(),
			$status
		);
	}

	protected function getTextBuilder(BaseObject $object)
	{
		return FullTextBuilder::create()
			->addText($object->getName())
			->addUser($object->getCreatedBy())
		;
	}

	/**
	 * @param BaseObject $object
	 * @deprecated
	 */
	protected function saveOldFullText(BaseObject $object)
	{
		$textBuilder = $this->getTextBuilder($object);
		if (!ModuleManager::isModuleInstalled('bitrix24') && ($object instanceof File))
		{
			$content = $this->getFileContent($object);
			$maxIndexSize = Configuration::getMaxIndexSize();
			if ($maxIndexSize > 0)
			{
				//yes, we know that substr may kill some last characters
				$content = substr($content, 0, $maxIndexSize);
			}

			$textBuilder->addText($content);
		}

		ObjectSaveIndexTable::update($object->getId(), array(
			'SEARCH_INDEX' => $textBuilder->getSearchValue(),
		));
	}

	protected function saveFullTextByHead(BaseObject $object)
	{
		ObjectHeadIndexTable::upsert(
			$object->getId(),
			$this->getTextBuilder($object)->getSearchValue()
		);
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
		if (!$this->allowIndex($file))
		{
			return;
		}

		$this->saveFullTextByHead($file);
		$this->updateFileContent($file);
		$this->indexFileByModuleSearch($file);
	}

	/**
	 * @param File $file
	 * @deprecated
	 */
	public function indexFileByModuleSearch(File $file)
	{
		if (!$this->useSearchModule)
		{
			return;
		}

		if (!Loader::includeModule('search'))
		{
			return;
		}

		$storage = $file->getStorage();
		$searchData = array(
			'LAST_MODIFIED' => $file->getUpdateTime()?: $file->getCreateTime(),
			'TITLE' => $file->getName(),
			'PARAM1' => $file->getStorageId(),
			'PARAM2' => $file->getParentId(),
			'SITE_ID' => self::resolveSiteId($storage),
			'URL' => $this->getDetailUrl($file),
			'PERMISSIONS' => $this->getSimpleRights($file),
			//CSearch::killTags
			'BODY' => strip_tags($file->getCreateUser()->getFormattedName()) . "\r\n" . $this->getFileContent($file),
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
			$searchData['BODY'] = substr($searchData['BODY'], 0, $maxIndexSize);
		}


		CSearch::index(Driver::INTERNAL_MODULE_ID, $this->getItemId($file), $searchData, true);
	}

	private function allowIndex(BaseObject $object)
	{
		//here we place configuration by Module (Options). Example, we can deactivate index for big files in Disk.
		if (!Configuration::allowIndexFiles())
		{
			return false;
		}

		$storage = $object->getStorage();
		if (!$storage || !$storage->getProxyType()->canIndexBySearch())
		{
			return false;
		}

		return true;
	}

	public function updateFileContent(File $file)
	{
		if (!$this->allowIndex($file))
		{
			return;
		}

		$connection = Application::getConnection();
		if (!HeadIndex::isReady() && $connection->getTableField(ObjectTable::getTableName(), 'SEARCH_INDEX'))
		{
			$this->saveOldFullText($file);
		}

		if ($this->allowUseExtendedFullText)
		{
			$this->saveExtendedFullTextByContent($file);
		}
	}

	public function indexFolderWithExtendedIndex(Folder $folder)
	{
		if (!$this->allowIndex($folder) || !$this->allowUseExtendedFullText)
		{
			return;
		}

		$this->saveExtendedFullTextByContent($folder);
	}

	public function indexFileWithExtendedIndex(File $file)
	{
		if (!$this->allowIndex($file) || !$this->allowUseExtendedFullText)
		{
			return;
		}

		$this->saveExtendedFullTextByContent($file, function() use ($file) {
			return $this->getFileContent($file);
		});
	}

	/**
	 * Runs index by folder.
	 * @param Folder $folder Target folder.
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function indexFolder(Folder $folder)
	{
		if (!$this->allowIndex($folder))
		{
			return;
		}

		$connection = Application::getConnection();
		if (!HeadIndex::isReady() && $connection->getTableField(ObjectTable::getTableName(), 'SEARCH_INDEX'))
		{
			$this->saveOldFullText($folder);
		}

		$this->saveFullTextByHead($folder);
		if ($this->allowUseExtendedFullText)
		{
			$this->saveExtendedFullTextByContent($folder);
		}
		$this->indexFolderByModuleSearch($folder);
	}

	/**
	 * @param Folder $folder
	 * @deprecated
	 */
	public function indexFolderByModuleSearch(Folder $folder)
	{
		if (!$this->useSearchModule)
		{
			return;
		}

		if (!Loader::includeModule('search'))
		{
			return;
		}

		$storage = $folder->getStorage();
		$searchData = array(
			'LAST_MODIFIED' => $folder->getUpdateTime()?: $folder->getCreateTime(),
			'TITLE' => $folder->getName(),
			'PARAM1' => $folder->getStorageId(),
			'PARAM2' => $folder->getParentId(),
			'SITE_ID' => self::resolveSiteId($storage),
			'URL' => $this->getDetailUrl($folder),
			'PERMISSIONS' => $this->getSimpleRights($folder),
			//CSearch::killTags
			'BODY' => $this->getTextBuilder($folder)->getSearchValue(),
		);
		if ($storage->getProxyType() instanceof Group)
		{
			$searchData['PARAMS'] = array(
				'socnet_group' => $storage->getEntityId(),
				'entity' => 'socnet_group',
			);
		}


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
		if ($object instanceof Folder)
		{
			$this->indexFolder($object);
		}
		elseif ($object instanceof File)
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
		ObjectHeadIndexTable::delete($object->getId());
		ObjectExtendedIndexTable::delete($object->getId());

		$this->dropIndexByModuleSearch($object);
	}

	/**
	 * @param BaseObject $object
	 * @deprecated
	 */
	public function dropIndexByModuleSearch(BaseObject $object)
	{
		if (!Loader::includeModule('search'))
		{
			return;
		}

		CSearch::deleteIndex(Driver::INTERNAL_MODULE_ID, $this->getItemId($object));
	}

	/**
	 * Recalculate rights in Search if it needs.
	 * @param BaseObject $object Target object (can be folder or file).
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 * @deprecated
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

			CSearch::changePermission(
				Driver::INTERNAL_MODULE_ID,
				$this->getSimpleRights($object),
				self::getItemId($object)
			);
		}
		elseif($object instanceof Folder)
		{
			$simpleRights = $this->getSimpleRights($object);

			CSearch::changePermission(
				Driver::INTERNAL_MODULE_ID,
				$simpleRights,
				false,
				$object->getStorageId(),
				$object->getId()
			);

			CSearch::changePermission(
				Driver::INTERNAL_MODULE_ID,
				$simpleRights,
				self::getItemId($object)
			);
		}
	}

	/**
	 * Event listener which return url for resource by fields.
	 * @param array $fields Fields from search module.
	 * @return string
	 * @deprecated
	 */
	public static function onSearchGetUrl($fields)
	{
		if(!is_array($fields))
		{
			return '';
		}
		if($fields["MODULE_ID"] !== "disk" || mb_substr($fields["URL"], 0, 1) !== "=")
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
	 * @deprecated
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
	 * @deprecated
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

		$sites = \CSite::GetList('SORT', 'asc', array('ACTIVE' => 'Y'));
		while ($site = $sites->getNext())
		{
			if(!$defaultSite && $site['DEF'] == 'Y')
			{
				$defaultSite = $site;
			}
		}

		return $defaultSite['LID'];
	}

	private function getObjectContent(BaseObject $object, array $options = null)
	{
		return $this->contentManager->getObjectContent($object, $options);
	}

	private function getFileContent(File $file, array $options = null)
	{
		return $this->contentManager->getObjectContent($file, $options);
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
		if(mb_substr($itemId, 0, 5) === 'FILE_')
		{
			return mb_substr($itemId, 5);
		}
		return mb_substr($itemId, 7);
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