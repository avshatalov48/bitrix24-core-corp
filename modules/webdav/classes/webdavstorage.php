<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavStorageCore extends CWebDavAbstractStorage
{
	/** @var CWebDavIblock|null */
	protected $webDav = null;

	public function __construct()
	{
	}

	public function getStorageClassName()
	{
		return 'CWebDavIblock';
	}
	/**
	 * @param \CWebDavIblock|null $webDav
	 * @return $this
	 */
	public function setWebDav($webDav)
	{
		$this->webDav = $webDav;

		return $this;
	}

	/**
	 * @return \CWebDavIblock|null
	 */
	public function getWebDav()
	{
		return $this->webDav;
	}

	/**
	 * @return bool
	 */
	protected function isSetWebDav()
	{
		return isset($this->webDav);
	}

	public function isCorrectName($name, &$msg)
	{
		if(substr($name, 0, 1) == '.')
		{
			$msg = 'File/Directory name should not start with "."';
			return false;
		}
		if(strpbrk($name, '/\:*?"\'|{}%&~'))
		{
			$msg = 'File/Directory name should not have /\:*?"\'|{}%&~ ';
			return false;
		}

		return true;
	}

	/**
	 * @param array $source
	 * @return array
	 */
	public function parseStorageExtra(array $source)
	{
		static::setStorageExtra(array(
			'iblockId' => empty($source['iblockId'])? null : (int)$source['iblockId'],
			'sectionId' => empty($source['sectionId'])? null : (int)$source['sectionId'],
		));

		return $this->getStorageExtra();
	}

	/**
	 * @param array $source
	 * @return array
	 */
	public function parseElementExtra(array $source)
	{
		return array(
			'id' => empty($source['id'])? null : (int)$source['id'],
			'iblockId' => empty($source['iblockId'])? null : (int)$source['iblockId'],
			'sectionId' => empty($source['sectionId'])? null : (int)$source['sectionId'],
			'rootSectionId' => empty($source['rootSectionId'])? null : (int)$source['rootSectionId'],
			'inSymlink' => empty($source['inSymlink'])? null : (int)$source['inSymlink'],
		);
	}

	/**
	 * @param bool  $reinit
	 * @param array $params
	 * @throws Exception
	 * @return $this
	 */
	protected function init($reinit = false, $params = array())
	{
		if($this->isSetWebDav() && !$reinit)
		{
			return $this;
		}

		CModule::IncludeModule('webdav');

		$key = $this->getStorageId();
		//$params = array();
		if(empty($key['IBLOCK_SECTION_ID']))
		{
			throw new Exception('Wrong storage key. Not set section id');
		}
		$params['ROOT_SECTION_ID'] = $key['IBLOCK_SECTION_ID'];
		$params['PLEASE_DO_NOT_MAKE_REDIRECT'] = true;
		//todo what is it? socnet magic to published docs
		global $USER;
		$params['DOCUMENT_TYPE'] = array('webdav', 'CIBlockDocumentWebdavSocnet', 'iblock_'.$key['IBLOCK_SECTION_ID'].'_user_'.intval($USER->getId()));

		$className = $this->getStorageClassName();
		$webdav = new $className($key['IBLOCK_ID'], '', $params);
		$webdav->attributes['user_id'] = $USER->getId();

		return $this->setWebDav($webdav);
	}

	public function isUnique($name, $targetDirectoryId, &$opponentId = null)
	{
		/** @noinspection PhpUndefinedVariableInspection */
		$isUniqueName = $this->init()->getWebDav()->checkUniqueName($name, $targetDirectoryId, $res);
		if(!$isUniqueName)
		{
			$opponentId = $res['id'];
		}
		return $isUniqueName;
	}

	/**
	 * Add (1), (2), etc. if name non unique in traget dir
	 * @param string $name
	 * @param int $targetDirectoryId
	 * @return string
	 */
	public function regenerateNameIfNonUnique($name, $targetDirectoryId)
	{
		$key = $this->getStorageId();

		$mainPartName = $name;
		$newName = $mainPartName;
		$count = 0;
		/** @var CWebDavIblock $className */
		$className = $this->getStorageClassName();
		while(!$className::sCheckUniqueName($key['IBLOCK_ID'], $targetDirectoryId, '', $newName, $res))
		{
			$count++;
			if(strstr($mainPartName, '.', true))
			{
				$newName = strstr($mainPartName, '.', true) . " ({$count})" . strstr($mainPartName, '.');
			}
			else
			{
				$newName = $mainPartName . " ({$count})";
			}
		}

		return $newName;
	}

	/**
	 * @param                $name
	 * @param                $targetDirectoryId
	 * @param CWebDavTmpFile $tmpFile
	 * @throws CWebDavAccessDeniedException
	 * @throws WebDavStorageBreakDownException
	 * @return bool|array
	 */
	public function addFile($name, $targetDirectoryId, CWebDavTmpFile $tmpFile)
	{
		$key = $this->getStorageId();
		if(!$targetDirectoryId)
		{
			//in root
			$targetDirectoryId = $key['IBLOCK_SECTION_ID'];
		}
		$name = $this->init()->getWebDav()->correctName($name);

		if(!$this->checkRights('create', array('targetDirectoryId' => $targetDirectoryId)))
		{
			throw new CWebDavAccessDeniedException;
		}

		$options = array(
			'new' => true,
			'dropped' => true,
			'arDocumentStates' => array(),
			'arUserGroups' => $this->getWebDav()->USER['GROUPS'],
			'TMP_FILE' => $tmpFile->getAbsolutePath(),
			'WIDTH' => $tmpFile->width,
			'HEIGHT' => $tmpFile->height,
			'FILE_NAME' => $name,
			'IBLOCK_ID' => $key['IBLOCK_ID'],
			'IBLOCK_SECTION_ID' => $targetDirectoryId,
			'WF_STATUS_ID' => 1,
		);
		$options['arUserGroups'][] = 'Author';

		$this->getDb()->startTransaction();
		if (!$this->getWebDav()->put_commit($options))
		{
			$this->getDb()->rollback();
			$tmpFile->delete();
			return false;
		}
		$this->getDb()->commit();
		$tmpFile->delete();
		if(!empty($options['ELEMENT_ID']))
		{
			$this->clearCache();
			$file = $this->getFile(null, array('id' => $options['ELEMENT_ID']), true);
			if($file)
			{
				return $file;
			}
		}

		throw new WebDavStorageBreakDownException('bd addFile');
	}

	/**
	 * @param                $name
	 * @param                $targetElementId
	 * @param CWebDavTmpFile $tmpFile
	 * @throws CWebDavAccessDeniedException
	 * @throws WebDavStorageBreakDownException
	 * @return bool|array
	 */
	public function updateFile($name, $targetElementId, CWebDavTmpFile $tmpFile)
	{
		$this->init();
		$name = $this->getWebDav()->correctName($name);
		if(!$this->checkRights('update', array(
			'name' => $name,
			'targetElementId' => $targetElementId)))
		{
			throw new CWebDavAccessDeniedException;
		}

		$options = array(
			'new' => false,
			'FILE_NAME' => $name,
			'ELEMENT_ID' => $targetElementId,
			'arUserGroups' => $this->getWebDav()->USER['GROUPS'],
			'TMP_FILE' => $tmpFile->getAbsolutePath(),
			'WIDTH' => $tmpFile->width,
			'HEIGHT' => $tmpFile->height,
		);

		$this->getDb()->startTransaction();
		if (!$this->getWebDav()->put_commit($options))
		{
			$this->getDb()->rollback();
			$tmpFile->delete();
			return false;
		}
		$this->getDb()->commit();
		$tmpFile->delete();

		if(!empty($options['ELEMENT_ID']))
		{
			$file = $this->getFile(null, array('id' => $options['ELEMENT_ID']), true);
			if($file)
			{
				return $file;
			}
		}

		throw new WebDavStorageBreakDownException('bd updateFile');
	}

	/**
	 * @param $name
	 * @param $targetElementId
	 * @param $newParentDirectoryId
	 * @throws CWebDavAccessDeniedException
	 * @throws WebDavStorageBreakDownException
	 * @return bool|array
	 */
	public function moveFile($name, $targetElementId, $newParentDirectoryId)
	{
		$this->init();
		$key = $this->getStorageId();
		if(!$newParentDirectoryId)
		{
			//in root
			$newParentDirectoryId = $key['IBLOCK_SECTION_ID'];
		}
		$name = $this->getWebDav()->correctName($name);
		$pathArray = $this->getPathArrayForSection($newParentDirectoryId);
		$pathArray[] = $name;

		$newPath = '/' . implode('/', $pathArray);
		$options = array(
			'element_id' => $targetElementId,
			'dest_url' => $newPath,
			'overwrite' => false,
		);

		$response = $this->getWebDav()->move($options);
		$oError = $this->getLastException();
		if(intval($response) == 412) //FILE_OR_FOLDER_ALREADY_EXISTS
		{
			return false;
		}
		elseif(intval($response) == 400) //FOLDER_IS_EXISTS (destination equals source)
		{
			return $this->getFile(null, array('id' => $targetElementId), true);
		}
		elseif(intval($response) == 403)
		{
			throw new CWebDavAccessDeniedException;
		}
		elseif(!$oError && intval($response) >= 300)
		{
			return false;
		}
		elseif($oError)
		{
			return false;
		}

		$file = $this->getFile(null, array('id' => $targetElementId), true);
		if($file)
		{
			return $file;
		}

		throw new WebDavStorageBreakDownException('bd moveFile');
	}

	public function renameDirectory($name, $targetDirectoryId, $parentDirectoryId)
	{
		return $this->moveDirectory($name, $targetDirectoryId, $parentDirectoryId);
	}

	public function renameFile($name, $targetElementId, $parentDirectoryId)
	{
		return $this->moveFile($name, $targetElementId, $parentDirectoryId);
	}

	public function getVersionDelete($element)
	{
		if(empty($element) || !is_array($element))
		{
			return false;
		}

		return CWebDavLogDeletedElement::isAlreadyRemoved(array(
			'SECTION_ID' => $element['extra']['rootSectionId'],
			'IS_DIR' => (bool)$element['isDirectory'],
			'ELEMENT_ID' => $element['id'],
		));
	}

	public function deleteFile($file)
	{
		if(empty($file) || !is_array($file))
		{
			return false;
		}
		$this->init();
		$result = $this->getWebDav()->delete(array('element_id' => $file['extra']['id']));
		if (intval($result) == 403)
		{
			throw new CWebDavAccessDeniedException;
		}
		elseif (intval($result) != 204)
		{
			//$this->getWebDav()->LAST_ERROR;
			return false;
		}
		$lastVersion = $this->getVersionDelete($file);
		$this->clearCache();

		return $lastVersion;
	}

	public function deleteDirectory($directory)
	{
		if(empty($directory) || !is_array($directory))
		{
			return false;
		}
		$this->init();
		$result = $this->getWebDav()->delete(array('section_id' => $directory['extra']['id']));
		if (intval($result) == 403)
		{
			throw new CWebDavAccessDeniedException;
		}
		elseif (intval($result) != 204)
		{
			//$this->getWebDav()->LAST_ERROR;
			return false;
		}
		$lastVersion = $this->getVersionDelete($directory);
		$this->clearCache();

		return $lastVersion;
	}

	public function getDirectory($id, array $extra, $skipCheckId = false)
	{
		if(!$skipCheckId && $this->generateId(array('ID' => $extra['id'], 'FILE' => false)) != $id)
		{
			return false;
		}
		//todo usage propfind with section_id options
		$storageId = $this->getStorageId();
		CTimeZone::Disable();
		$dir = CIBlockSection::GetList(array(), array(
			'IBLOCK_ID' => (int)CWebDavSymlinkHelper::getIblockIdForSectionId($extra['id']), //todo symlink logic. Usage propfind!
			'ID' => (int)$extra['id'],
		), false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'CREATED_BY', 'MODIFIED_BY', 'PATH', 'NAME', 'TIMESTAMP_X', 'XML_ID', 'IBLOCK_CODE', CWebDavIblock::UF_LINK_SECTION_ID));
		CTimeZone::Enable();

		$dir = $dir->fetch();
		if(!$dir || !is_array($dir))
		{
			return array();
		}

		$dir['PATH'] = implode('/', ($this->getPathArrayForSection($extra['id'])));

		return $this->formatSectionToResponse($dir);
	}

	/**
	 * @param $name
	 * @param $targetDirectoryId
	 * @param $newParentDirectoryId
	 * @throws CWebDavAccessDeniedException
	 * @throws CWebDavSymlinkMoveFakeErrorException
	 * @return bool|string
	 */
	public function moveDirectory($name, $targetDirectoryId, $newParentDirectoryId)
	{
		$this->init();

		$key = $this->getStorageId();
		if(!$newParentDirectoryId)
		{
			//in root
			$newParentDirectoryId = $key['IBLOCK_SECTION_ID'];
		}

		$pathArray = $this->getPathArrayForSection($newParentDirectoryId);
		$pathArray[] = $name;

		$newPath = '/' . implode('/', $pathArray);
		$options = array(
			'section_id' => $targetDirectoryId,
			'dest_url' => $newPath,
			'overwrite' => false,
		);

		$response = $this->getWebDav()->move($options);

		//todo bad hack. Operation move with symlink folders equals drop and create. This is not atomic operation.
		//and we send response error, but this is fake error. Really we added new folders and move to trash.
		$webdav = $this->getWebDav();
		if($webdav::$lastActionMoveWithSymlink)
		{
			throw new CWebDavSymlinkMoveFakeErrorException;
		}

		$oError = $this->getLastException();
		if(!$oError && intval($response) == 403)
		{
			throw new CWebDavAccessDeniedException;
		}
		elseif(intval($response) == 400) //FOLDER_IS_EXISTS (destination equals source)
		{
			return $this->getDirectory(null, array('id' => $targetDirectoryId), true);
		}
		elseif(!$oError && intval($response) >= 300)
		{
			return false;
		}
		elseif($oError)
		{
			return false;
		}
		$this->clearCache();

		$directory = $this->getDirectory(null, array('id' => $targetDirectoryId), true);
		//todo getPathArrayForSection() static cached. And we set path manual.
		$directory['path'] = $newPath;

		return $directory;
	}

	/**
	 * @param $id
	 * @return array
	 */
	protected function getPathArrayForSection($id)
	{
		return $this->init()->getWebDav()->getNavChain(array('section_id' => (int)$id));
	}

	public function getFile($id, array $extra, $skipCheckId = false)
	{
		if(!$skipCheckId && $this->generateId(array('ID' => $extra['id'], 'FILE' => true)) != $id)
		{
			return false;
		}
		CTimeZone::Disable();
		$storageId = $this->getStorageId();
		$options = array(
			'path' => '/',
			'depth' => '10000',
			'element_id' => (int)$extra['id'],
		);
		/** @noinspection PhpUndefinedVariableInspection */
		$element = $this
			->init()
			->getWebDav()
			->propFind($options, $files, array(
				'COLUMNS' => array(),
				'return'  => 'array',
				//todo fix to $arParentPerms = $this->GetPermissions in CWebDavIblock::_get_mixed_list()
				'PARENT_ID' => $storageId['IBLOCK_SECTION_ID'],
				'FILTER'  => array(
					'ID' => (int)$extra['id'],
					//'IBLOCK_ID' => (int)$storageId['IBLOCK_ID'],
					//'SECTION_ID' => (int)$storageId['IBLOCK_SECTION_ID'],
				),
				'NON_DROPPED_SECTION' => true,
			))
		;
		CTimeZone::Enable();
		if(!is_array($element))
		{
			return array();
		}
		//fetch first from result
		return $this->formatFileToResponse((array_shift($element['RESULT']))?: array());
	}

	/**
	 * @param $name
	 * @param $parentDirectoryId
	 * @return bool|array
	 */
	public function addDirectory($name, $parentDirectoryId)
	{
		$key = $this->getStorageId();
		if(!$parentDirectoryId)
		{
			//in root
			$parentDirectoryId = $key['IBLOCK_SECTION_ID'];
		}
		/** @var CWebDavIblock $webDav  */
		$webDav = $this
			->init()
			->getWebDav()
		;

		$alreadyExists = false;
		$sectionId = null;
		$name = $webDav->correctName($name);
		$pathArray = $this->getPathArrayForSection($parentDirectoryId);
		$pathArray[] = $name;
		$path = '/' . implode('/', $pathArray);
		$response = $webDav->MKCOL(array('path' => $path));

		if(intval($response) == 403)
		{
			throw new CWebDavAccessDeniedException;
		}
		elseif($exception = $this->getLastException())
		{
			if($exception['code'] == 'FOLDER_IS_EXISTS')
			{
				$alreadyExists = true;
				$sectionId = $webDav->arParams['item_id'];
			}
			else
			{
				return array();
			}
		}
		elseif($response == '201 Created')
		{
			$sectionId = $webDav->arParams['changed_element_id'];
		}

		if(!$sectionId)
		{
			return array();
		}
		if(!$alreadyExists)
		{
			$this->clearCache();
		}

		return $this->getDirectory(null, array('id' => $sectionId), true);
	}

	protected function getLastException()
	{
		/** @var CAllMain */
		global $APPLICATION;

		$exception = $APPLICATION->GetException();
		if($exception instanceof CApplicationException)
		{
			return array(
				'code' => $exception->getId(),
			);
		}

		return false;
	}

	public function sendFile($file)
	{
		if(empty($file['extra']['id']))
		{
			throw new Exception('Wrong file id');
		}
		if(empty($file['extra']['iblockId']))
		{
			throw new Exception('Wrong file iblockId');
		}

		//todo session_commit() ?
		return $this
			->getWebDav()
			->fileViewByUser($file['extra']['id'], array('IBLOCK_ID' => $file['extra']['iblockId']))
		;
	}

	/**
	 * @param        $version
	 * @param string $path
	 * @param array  $miscOptions
	 * @return array
	 */
	protected function searchFilesByPropFind($version, $path = '/', array $miscOptions = array())
	{
		$storageId = $this->getStorageId();
		$version = convertTimeStamp((int)$version, 'FULL');
		$options = array_merge(array('path' => $path, 'depth' => '10000'), $miscOptions);
		CTimeZone::Disable();
		/** @noinspection PhpUndefinedVariableInspection */
		$result  = $this->getWebDav()->propFind($options, $files, array(
			'COLUMNS' => array(),
			'return'  => 'array',
			//todo fix to $arParentPerms = $this->GetPermissions in CWebDavIblock::_get_mixed_list()
			'PARENT_ID' => $storageId['IBLOCK_SECTION_ID'],
			'FILTER'  => array(
				'timestamp_1' => $version,
			),
			'NON_TRASH_SECTION' => true,
			'NON_OLD_DROPPED_SECTION' => true,
			'NON_DROPPED_SECTION' => true,
		));
		CTimeZone::Enable();

		if(!is_array($result))
		{
			return array();
		}

		return $this->formatFilesToResponse($result['RESULT']?: array(), !empty($miscOptions['underSymlink']));
	}

	/**
	 * Hide folder or file if path contains trash, dropped
	 * @param array $element
	 * @return bool
	 */
	protected function isHiddenElement(array $element)
	{
		$droppedData = $this->getWebDav()->getOldDroppedMetaData();
		$trashData = $this->getWebDav()->getTrashMetaData();

		$dataDeterminesElement = array();
		if(isset($element['~NAME']))
		{
			$dataDeterminesElement[] = $element['~NAME'];
		}
		if(isset($element['PATH']))
		{
			$dataDeterminesElement[] = rtrim($element['PATH'], '/') . '/';
		}

		foreach ($dataDeterminesElement as $data)
		{
			if($data === $droppedData['name'] || $data === $trashData['name'])
			{
				return true;
			}
			if(preg_match('%/(' . preg_quote($trashData['alias']) . '|' . preg_quote($trashData['name']) . ')/%i' . BX_UTF_PCRE_MODIFIER, $data))
			{
				return true;
			}
			if(preg_match('%/(' . preg_quote($droppedData['alias']) . '|' . preg_quote($droppedData['name']) . ')/%i' . BX_UTF_PCRE_MODIFIER, $data))
			{
				return true;
			}
		}
		unset($data);

		return false;
	}

	protected function getDeletedElements($version)
	{
		$deletedItems = array();
		$version = CWebDavDiskDispatcher::convertFromExternalVersion($version);
		if(!$version)
		{
			return array();
		}
		$storageId = $this->getStorageId();

		$query = CWebDavLogDeletedElement::getList(array(), array(
			'VERSION' => $version,
			'IBLOCK_ID' => $storageId['IBLOCK_ID'],
			'SECTION_ID' => $storageId['IBLOCK_SECTION_ID'],
		));
		if(!$query)
		{
			throw new Exception('Error in DB');
		}

		while($row = $query->fetch())
		{
			if(!$row)
			{
				continue;
			}
			$deletedItems[] = array(
				'id' => $row['ELEMENT_ID'],
				'isDirectory' => (bool)$row['IS_DIR'],
				'deletedBy' => (string) (isset($row['USER_ID'])? $row['USER_ID'] : 0),
				'isDeleted' => true,
				'storageId' => $this->getStringStorageId(),
				'version' => CWebDavDiskDispatcher::convertToExternalVersion($row['VERSION']),
			);
		}

		return $deletedItems;
	}

	private function getDataSymlinkSections()
	{
		global $USER;
		$invitesQuery = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'filter' => array(
				'INVITE_USER_ID' => $USER->getId(),
				'IS_APPROVED' => true,
				'IS_DELETED' => false,
			),
		));
		$symlinkSections = array();
		while($invite = $invitesQuery->fetch())
		{
			$symlinkSections[$invite['LINK_SECTION_ID']] = $invite;
		}

		return $symlinkSections;
	}

	/**
	 * @param int   $version
	 * @param array &$realSections - set symlink flag if needed. This is full tree.
	 * @return array
	 */
	private function getSymlinkSnapshot($version = 0, array &$realSections = array())
	{
		/** @var \CWebDavIblock $webdav */
		$webdav = $this
			->getWebDav()
		;

		$files = $sections = array();
		foreach ($this->getDataSymlinkSections() as $linkSectionId => $invite)
		{
			$webdav->enableUFSymlinkMode();

			$pathOnSymlinkSection = '';
			$symlinkSectionVersion = 0;
			foreach ($realSections as &$realSection)
			{
				if($realSection['isDirectory'] && $realSection['extra']['id'] == $linkSectionId)
				{
					$realSection['isSymlinkDirectory'] = true;
					$pathOnSymlinkSection = rtrim($realSection['path'], '/') . '/';
					$symlinkSectionVersion = $realSection['version'];
					break;
				}
			}
			unset($realSection);

			if(!$pathOnSymlinkSection)
			{
				//we are not found symlink section in full real tree sections
				continue;
			}

			$portionSections = $this->formatSectionsToResponse($webdav->getSectionsTree(array(
				'NON_DROPPED_SECTION' => true,
				'section_id' => $linkSectionId,
				'prependPath' => $pathOnSymlinkSection,
				'setERights' => true,
			)), true);

			$versionForFileFilter = 0;
			//todo not good. Hack. We need read tree if create new symlink.
			if(self::compareVersion($symlinkSectionVersion, $version) <= 0)
			{
				$versionForFileFilter = CWebDavDiskDispatcher::convertFromExternalVersion($version);
				$portionSections = $this->filterSectionByVersion($portionSections, $version);
			}

			$files = array_merge(
				$files,
				$this->searchFilesByPropFind($versionForFileFilter, null, array('section_id' => $linkSectionId, 'underSymlink' => true))
			);
			$sections = array_merge(
				$sections,
				$portionSections
			);
		}
		unset($invite);

		return array_merge($files, $sections);
	}

	public function getSnapshot($version = 0)
	{
		CTimeZone::Disable();

		/** @var \CWebDavIblock $webdav */
		$webdav = $this
			->init()
			->getWebDav()
		;
		$webdav->disableUFSymlinkMode();
		$sections = $webdav->getSectionsTree(array(
			'path' => '/',
			'NON_DROPPED_SECTION' => true,
			'SET_IS_SHARED' => true,
		));
		$webdav->enableUFSymlinkMode();

		$realSections = $this->formatSectionsToResponse($sections, false);
		$elements = $this->getSymlinkSnapshot($version, $realSections);

		$elements = array_merge($elements, $this->filterSectionByVersion($realSections, $version));

		$webdav->disableUFSymlinkMode();
		$files    = $this->searchFilesByPropFind(CWebDavDiskDispatcher::convertFromExternalVersion($version), '/', array('underSymlink' => false));
		$webdav->enableUFSymlinkMode();

		CTimeZone::Enable();

		return array_merge(
			$elements,
			$files,
			$this->getDeletedElements($version)
		);
	}

	/**
	 * @param $version
	 * @return array
	 */
	protected function getSnapshotFromTrash($version)
	{
		CTimeZone::Disable();
		$version = convertTimeStamp((int)$version, 'FULL');
		$options = array('path' => '/' . $this->getWebDav()->meta_names['TRASH']['name'], 'depth' => '1');
		$this->getWebDav()->meta_state = 'TRASH';
		/** @noinspection PhpUndefinedVariableInspection */
		$result  = $this->getWebDav()->propFind($options, $files, array(
			'COLUMNS' => array(),
			'return'  => 'array',
			'FILTER'  => array(
				'timestamp_1' => $version,
				'TIMESTAMP_X_1' => $version,
			),
		));
		CTimeZone::Enable();

		if(!is_array($result))
		{
			return array();
		}

		//file, folders format. This is not good. But it is not used.
		return $this->formatFilesToResponse($result['RESULT']?: array());
	}

	/**
	 * @param $file
	 * @return bool|string
	 */
	public function getPublicLink(array $file)
	{
		/** @var \CWebDavIblock $webdav */
		$webdav = $this
			->init()
			->getWebDav()
		;

		$hash = CWebDavExtLinks::getHashLink(array(
			'IBLOCK_TYPE' => $webdav->IBLOCK_TYPE,
			'IBLOCK_ID' => $file['extra']['iblockId'],
			'ROOT_SECTION_ID' => $file['extra']['rootSectionId']
		), array(
			'PASSWORD' => '',
			'URL' => $file['path'],
			'BASE_URL' => $webdav->base_url,
			'SINGLE_SESSION' => false,
			'LINK_TYPE' => CWebDavExtLinks::LINK_TYPE_MANUAL,
			'VERSION_ID' => null,
			'FILE_ID' => null,
			'ELEMENT_ID' => $file['extra']['id'],
		), null);

		return $hash;
	}

	/**
	 * @param array $element
	 * @return string
	 */
	public function generateId(array $element)
	{
		//this is unique id in this storage (pair iblock + id)
		return implode('|', array(
			'st' . $this->getStringStorageId(), (empty($element['FILE'])? 's' : 'f') . $element['ID']
		));
	}

	protected function skipSection(array $section)
	{
		return false;
		if(!empty($section['XML_ID']) && $section['XML_ID'] == 'SHARED_FOLDER')
		{
			return true;
		}
		return false;
	}

	protected function formatSectionToResponse(array $section, $markAsSymlink = null)
	{
		if(empty($section))
		{
			return array();
		}
		$storageId = $this->getStorageId();
		$rootSection = isset($storageId['IBLOCK_SECTION_ID'])? $storageId['IBLOCK_SECTION_ID'] : null;
		$result = array(
			'id' => $this->generateId($section),
			'isDirectory' => true,
			'isShared' => !empty($section['IS_SHARED']),
			'isSymlinkDirectory' => !empty($section[CWebDavIblock::UF_LINK_SECTION_ID]) || !empty($section['symlink']),
			'isDeleted' => false,
			'storageId' => $this->getStringStorageId(),
			'path' => isset($section['PATH']) ? '/' . trim($section['PATH'], '/') : null,
			'name' => $section['NAME'],
			'version' => (string)$this->generateTimestamp($section['TIMESTAMP_X']),
			'extra' => array(
				'id' => (string)$section['ID'],
				'iblockId' => (string)$section['IBLOCK_ID'],
				'sectionId' => (string)$section['IBLOCK_SECTION_ID'],
				'rootSectionId' => (string)$rootSection,
				'name' => $section['NAME'],
			),
			'permission' => $this->getRightSection($section),
			'createdBy' => (string)$section['CREATED_BY'],
			'modifiedBy' => (string)$section['MODIFIED_BY'],
		);
		if($rootSection != $section['IBLOCK_SECTION_ID'])
		{
			$result['parentId'] = $this->generateId(array('FILE' => false, 'ID' => $section['IBLOCK_SECTION_ID']));
		}
		if($markAsSymlink === null)
		{
			global $USER;
			if(CWebDavSymlinkHelper::isLink(CWebDavSymlinkHelper::ENTITY_TYPE_USER, $USER->getId(), array(
				'ID' => $section['ID'],
				'IBLOCK_ID' => $section['IBLOCK_ID'],
			)))
			{
				$markAsSymlink = true;

				$result['path'] =
					'/' .
					trim(implode($this->getPathArrayForSection($section['ID']), '/'), '/') .
					'/'
				;
			}
		}
		if($markAsSymlink)
		{
			$result['extra']['inSymlink'] = '1';
		}
		return $result;
	}

	/**
	 * @param array $sections
	 * @param bool  $markAsSymlink
	 * @return array
	 */
	protected function formatSectionsToResponse(array $sections, $markAsSymlink = null)
	{
		$result = array();
		foreach ($sections as $section)
		{
			if(empty($section) || $this->isHiddenElement($section))
			{
				continue;
			}
			$result[] = $this->formatSectionToResponse($section, $markAsSymlink);
		}
		unset($section);

		return $result;
	}

	protected function formatFileToResponse(array $file, $markAsSymlink = null)
	{
		if(empty($file))
		{
			return array();
		}
		$storageId = $this->getStorageId();
		$rootSection = isset($storageId['IBLOCK_SECTION_ID'])? $storageId['IBLOCK_SECTION_ID'] : null;
		$result = array(
			'id' => $this->generateId($file),
			'isDirectory' => false,
			'isDeleted' => false,
			'storageId' => $this->getStringStorageId(),
			'path' => isset($file['PATH']) ? '/' . trim($file['PATH'], '/') : null,
			'name' => $file['NAME'],
			'revision' => (string)$file['PROPERTY_FILE_VALUE'],
			'version' => (string)$this->generateTimestamp($file['TIMESTAMP_X']),
			'extra' => array(
				'id' => (string)$file['ID'],
				'iblockId' => (string)$file['IBLOCK_ID'],
				'sectionId' => (string)$file['IBLOCK_SECTION_ID'],
				'rootSectionId' => (string)$rootSection,
				'name' => $file['NAME'],
			),
			'size' => isset($file['FILE']['FILE_SIZE'])? (string)$file['FILE']['FILE_SIZE'] : '0',
			'permission' => $this->getRightFile($file),
			'createdBy' => (string)$file['CREATED_BY'],
			'modifiedBy' => (string)$file['MODIFIED_BY'],
		);
		if($rootSection != $file['IBLOCK_SECTION_ID'])
		{
			$result['parentId'] = $this->generateId(array('FILE' => false, 'ID' => $file['IBLOCK_SECTION_ID']));
		}

		if($markAsSymlink === null)
		{
			global $USER;
			if(CWebDavSymlinkHelper::isLink(CWebDavSymlinkHelper::ENTITY_TYPE_USER, $USER->getId(), array(
				'ID' => $file['IBLOCK_SECTION_ID'],
				'IBLOCK_ID' => $file['IBLOCK_ID'],
			)))
			{
				$markAsSymlink = true;

				$result['path'] =
					'/' .
					trim(implode($this->getPathArrayForSection($file['IBLOCK_SECTION_ID']), '/'), '/') .
					'/' .
					$file['NAME']
				;
			}
		}
		if($markAsSymlink)
		{
			$result['extra']['inSymlink'] = '1';
		}

		return $result;
	}

	/**
	 * @param array $files
	 * @param bool  $markAsSymlink
	 * @return array
	 */
	protected function formatFilesToResponse(array $files, $markAsSymlink = null)
	{
		$result = array();
		foreach ($files as $file)
		{
			if(empty($file) || $this->isHiddenElement($file))
			{
				continue;
			}
			$result[] = $this->formatFileToResponse($file, $markAsSymlink);
		}
		unset($file);

		return $result;
	}

	/**
	 * @param array $sections
	 * @param int   $version
	 * @return array
	 */
	protected function filterSectionByVersion(array $sections, $version = 0)
	{
		if($version == 0)
		{
			return $sections;
		}

		foreach ($sections as $i => $section)
		{
			if(self::compareVersion($section['version'], $version) < 0)
			{
				unset($sections[$i]);
			}
		}

		return $sections;
	}

	protected function generateTimestamp($date)
	{
		return CWebDavDiskDispatcher::convertToExternalVersion(makeTimeStamp($date));
	}

	protected function clearCache()
	{
		WDClearComponentCache(array(
			'webdav.element.edit',
			'webdav.element.hist',
			'webdav.element.upload',
			'webdav.element.view',
			'webdav.menu',
			'webdav.section.edit',
			'webdav.section.list'));
	}

	protected function checkRights($action, array $element = array())
	{
		//return true;
		//maybe throw expection?
		$action = strtolower($action);
		if($action == 'create')
		{
			return $this->init()
						->getWebDav()
							->checkWebRights('PUT', array(
								'arElement' => array(
									'is_dir' => false,
									'parent_id' => $element['targetDirectoryId'],
								),
								'action' => 'create',
								'create_element_in_section' => true,
							), false);
		}
		elseif($action == 'update' && !empty($element['targetElementId']))
		{
				return $this
					->init()
					->getWebDav()
						->checkWebRights('PUT', array('arElement' => array(
							'not_found' => false,
							'item_id' => $element['targetElementId'],
							'is_dir' => false,
					), 'action' => 'edit'), false);
		}

		return false;
	}

	/**
	 * @param array $section
	 * @return string
	 */
	private function getRightSection(array $section)
	{
		$perm = 'W';
		if (!empty($section['E_RIGHTS']))
		{
			$perm = isset($section['E_RIGHTS']['section_edit']) ? 'W' : 'R';
		}

		return $perm;
	}

	/**
	 * @param array $file
	 * @return string
	 */
	private function getRightFile(array $file)
	{
		$perm = 'W';
		if (!empty($file['E_RIGHTS']))
		{
			$perm = isset($file['E_RIGHTS']['element_edit']) ? 'W' : 'R';
		}

		return $perm;
	}
}