<?php
IncludeModuleLangFile(__FILE__);

final class CWebDavSymlinkHelper
{
	const ENTITY_TYPE_USER    = 'user';
	const ENTITY_TYPE_GROUP   = 'group';
	const ENTITY_TYPE_SECTION = 'section';
	const ENTITY_TYPE_SHARED  = 'shared';

	/** @var  array */
	protected static $_pathPattern;

	protected $_entityId;
	protected $_entityType;

	protected static $_rootSectionGarbage = array();
	private static $_cacheDataSectionIblockId = array();

	private static $_sectionOriginalNames = array();

	public function __construct()
	{

	}

	/**
	 * @param integer $sectionId
	 * @param string $name
	 */
	public static function setSectionOriginalName($sectionId, $name)
	{
		self::$_sectionOriginalNames[$sectionId] = $name;
	}

	/**
	 * @param integer $sectionId
	 * @param string $defaultName
	 * @return string
	 */
	public static function getSectionOriginalName($sectionId, $defaultName)
	{
		return isset(self::$_sectionOriginalNames[$sectionId])? self::$_sectionOriginalNames[$sectionId] : $defaultName;
	}

	/**
	 * @param int $groupId
	 * @param array $targetSectionData
	 * @return bool
	 */
	public static function generateNameForGroupLink($groupId, array $targetSectionData)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}
		$query = CSocNetGroup::getList(array(), array('ID' => $groupId), false, false, array('NAME'));
		if(!$query)
		{
			return false;
		}
		$group = $query->fetch();

		if(empty($group['NAME']))
		{
			return false;
		}

		$group['NAME'] = GetMessage('WD_SYMLINK_TEMPLATE_NAME', array('#NAME#' => $group['NAME']));

		return CWebDavTools::regenerateNameIfNonUnique($group['NAME'], $targetSectionData['IBLOCK_ID'], $targetSectionData['SECTION_ID']);
	}

	/**
	 * @param int $sectionId
	 * @param array $targetSectionData
	 * @return bool|string
	 */
	public static function generateNameForUserSectionLink($sectionId, array $targetSectionData)
	{
		$section = CIBlockSection::getList(
			array(),
			array('ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N',),
			false,
			array('NAME',)
		)->fetch();

		if(empty($section['NAME']))
		{
			return false;
		}

		$section['NAME'] = GetMessage('WD_SYMLINK_TEMPLATE_NAME', array('#NAME#' => $section['NAME']));

		return CWebDavTools::regenerateNameIfNonUnique($section['NAME'], $targetSectionData['IBLOCK_ID'], $targetSectionData['SECTION_ID']);
	}

	public function setContextToUser()
	{
		return $this->setEntityType(self::ENTITY_TYPE_USER);
	}

	public function setContextToGroup()
	{
		return $this->setEntityType(self::ENTITY_TYPE_GROUP);
	}

	public function setContextToShared()
	{
		return $this->setEntityType(self::ENTITY_TYPE_SHARED);
	}

	public function setContextToSection()
	{
		return $this->setEntityType(self::ENTITY_TYPE_SECTION);
	}

	/**
	 * @param mixed $entityId
	 * @return $this
	 */
	public function setEntityId($entityId)
	{
		$this->_entityId = $entityId;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEntityId()
	{
		return $this->_entityId;
	}

	/**
	 * @param string $entityType
	 * @return $this
	 */
	public function setEntityType($entityType)
	{
		$this->_entityType = $entityType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEntityType()
	{
		return $this->_entityType;
	}

	public static function addRootSectionData($entityType, $entityId, $data)
	{
		empty(self::$_rootSectionGarbage[$entityType]) && (self::$_rootSectionGarbage[$entityType] = array());
		self::$_rootSectionGarbage[$entityType][$entityId] = $data;
	}

	public static function getRootSectionData($entityType, $entityId)
	{
		if(isset(self::$_rootSectionGarbage[$entityType][$entityId]))
		{
			return self::$_rootSectionGarbage[$entityType][$entityId];
		}
		return array();
	}

	public static function getLinkData($entityType, $entityId, $sectionData)
	{
		$chain = self::getNavChain($sectionData['IBLOCK_ID'], $sectionData['ID']);
		$sectionIds = array();
		foreach ($chain as $item)
		{
			$sectionIds[] = $item['ID'];
		}
		unset($item);

		//hack. Now we have symlink only in user library. And then entityType ~equals to user
		if($entityType == self::ENTITY_TYPE_USER)
		{
			$userLib = CWebDavIblock::LibOptions('user_files', false, SITE_ID);
			if ($userLib && isset($userLib['id']) && ($iblockId = intval($userLib['id'])))
			{
				$rootSection = self::getRootSectionData($entityType, $entityId);
				if(empty($rootSection))
				{
					$rootSection = CWebDavIblock::getRootSectionDataForUser($entityId);
					if(empty($rootSection))
					{
						return array();
					}

					$margins = CIBlockSection::GetList(array(), array(
						'ID' => $rootSection['SECTION_ID'],
						'IBLOCK_ID' => $rootSection['IBLOCK_ID'],
						'CHECK_PERMISSIONS' => 'N',
					), false, array('LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID'));
					if(!$margins)
					{
						return array();
					}
					$rootSection = $margins->fetch();

					self::addRootSectionData($entityType, $entityId, $rootSection);
				}

				$symlinkSection = CIBlockSection::getList(
					array(),
					array(
						'IBLOCK_ID' => $iblockId,
						CWebDavIblock::UF_LINK_SECTION_ID => $sectionIds,
						'CHECK_PERMISSIONS' => 'N',
						'>LEFT_BORDER' => $rootSection['LEFT_MARGIN'],
						'<RIGHT_BORDER' => $rootSection['RIGHT_MARGIN'],
					),
					false,
					CWebDavIblock::getUFNamesForSectionLink()
				);
				if(!$symlinkSection || !($symlinkSection = $symlinkSection->fetch()))
				{
					return array();
				}
				return $symlinkSection;
			}
		}
		elseif($entityType == self::ENTITY_TYPE_GROUP)
		{
			return array();
		}
		elseif($entityType == self::ENTITY_TYPE_SECTION) //or any another context
		{
			return array();
		}
		elseif($entityType == self::ENTITY_TYPE_SHARED)
		{
			return array();
		}
	}

	public static function getLinkDataOfElement($entityType, $entityId, $elementId)
	{
		$parentData = self::getParentDataForElementId($elementId);
		$parentData['ID'] = $parentData['IBLOCK_SECTION_ID'];
		return self::getLinkData($entityType, $entityId, $parentData);
	}

	public static function isLinkElement($entityType, $entityId, $elementId)
	{
		$parentData = self::getParentDataForElementId($elementId);
		$parentData['ID'] = $parentData['IBLOCK_SECTION_ID'];
		unset($parentData['IBLOCK_SECTION_ID']);
		return self::isLink($entityType, $entityId, $parentData);
	}

	public static function isLink($entityType, $entityId, $sectionData)
	{
		if($entityType == self::ENTITY_TYPE_USER)
		{
			$userLib = CWebDavIblock::LibOptions('user_files', false, SITE_ID);
			if ($userLib && isset($userLib['id']) && ($iblockId = intval($userLib['id'])))
			{
				if($iblockId != $sectionData['IBLOCK_ID'])
				{
					return true;
				}
				$chain = self::getNavChain($sectionData['IBLOCK_ID'], $sectionData['ID']);
				$rootSection = reset($chain);
				if($rootSection['CREATED_BY'] != $entityId)
				{
					return true;
				}
			}
			return false;
		}
		elseif($entityType == self::ENTITY_TYPE_GROUP)
		{
			$groupLib = CWebDavIblock::LibOptions('group_files', false, SITE_ID);
			if ($groupLib && isset($groupLib['id']) && ($iblockId = intval($groupLib['id'])))
			{
				if($iblockId != $sectionData['IBLOCK_ID'])
				{
					return true;
				}
				$chain = self::getNavChain($sectionData['IBLOCK_ID'], $sectionData['ID']);
				$rootSection = reset($chain);
				if($rootSection['SOCNET_GROUP_ID'] != $entityId)
				{
					return true;
				}
			}
			return false;
		}
		elseif($entityType == self::ENTITY_TYPE_SECTION) //or any another context
		{
			$margins = CIBlockSection::GetList(array(), array(
				'ID' => $entityId,
				'CHECK_PERMISSIONS' => 'N',
			), false, array('LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID'));
			if(!$margins)
			{
				return null;
			}
			$margins = $margins->fetch();

			if($margins['IBLOCK_ID'] != $sectionData['IBLOCK_ID'])
			{
				return true;
			}
			if($sectionData['ID'] == $entityId)
			{
				return false;
			}

			$isSubSection = CIBlockSection::GetList(array(), array(
				'ID' => $sectionData['ID'],
				'IBLOCK_ID' => $sectionData['IBLOCK_ID'],
				'CHECK_PERMISSIONS' => 'N',
				'>LEFT_BORDER' => $margins['LEFT_MARGIN'],
				'<RIGHT_BORDER' => $margins['RIGHT_MARGIN'],
			), false, array('ID'));
			if(!$isSubSection || !($isSubSection = $isSubSection->fetch()))
			{
				//not find real subsection === symlink
				return true;
			}
			return empty($isSubSection['ID']);
		}
		elseif($entityType == self::ENTITY_TYPE_SHARED)
		{
			return false;
		}

		throw new Exception('Unknown type ' . $entityType);
	}

	/**
	 * @param array $sectionLinkData
	 * @param array $unshareUsers if empty - unshare all users, else only ids
	 * @return bool|null
	 */
	public static function unshareUserSection(array $sectionLinkData, array $unshareUsers = array())
	{
		if(
			empty($sectionLinkData['ID']) ||
			empty($sectionLinkData['IBLOCK_ID'])
		)
		{
			return false;
		}

		return \Bitrix\Webdav\FolderInviteTable::deleteByFilter(array(
			'=IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionLinkData['ID'],
		));
	}

	public static function deleteSymLinkSection(array $sectionTargetData, array $sectionLinkData, $typeLibrary = self::ENTITY_TYPE_USER)
	{
		if(
			empty($sectionLinkData['ID']) ||
			empty($sectionLinkData['IBLOCK_ID']) ||
			empty($sectionLinkData['INVITE_USER_ID'])
		)
		{
			return false;
		}
		if(empty($sectionTargetData['IBLOCK_ID']) || empty($sectionTargetData['IBLOCK_SECTION_ID']))
		{
			return false;
		}

		$typeLibrary = strtolower($typeLibrary);
		if($typeLibrary != self::ENTITY_TYPE_USER && $typeLibrary != self::ENTITY_TYPE_GROUP && $typeLibrary != self::ENTITY_TYPE_SHARED)
		{
			return false;
		}

		return \Bitrix\Webdav\FolderInviteTable::deleteByFilter(array(
			'=INVITE_USER_ID' => $sectionLinkData['INVITE_USER_ID'],
			'=IS_APPROVED' => true,
			'=IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionLinkData['ID'],
		));
	}

	public static function deleteAllSymLinkOnSection(array $sectionLinkData, $typeLibrary = self::ENTITY_TYPE_USER)
	{
		if(
			empty($sectionLinkData['ID']) ||
			empty($sectionLinkData['IBLOCK_ID'])
		)
		{
			return false;
		}

		$typeLibrary = strtolower($typeLibrary);
		if($typeLibrary != self::ENTITY_TYPE_USER && $typeLibrary != self::ENTITY_TYPE_GROUP && $typeLibrary != self::ENTITY_TYPE_SHARED)
		{
			return false;
		}

		return \Bitrix\Webdav\FolderInviteTable::deleteByFilter(array(
			'=IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionLinkData['ID'],
		));
	}

	public static function deleteSymLinkOnSectionByUserIds(array $userIds, array $sectionLinkData, $typeLibrary = self::ENTITY_TYPE_USER)
	{
		if(
			empty($sectionLinkData['ID']) ||
			empty($sectionLinkData['IBLOCK_ID'])
		)
		{
			return false;
		}

		$typeLibrary = strtolower($typeLibrary);
		if($typeLibrary != self::ENTITY_TYPE_USER && $typeLibrary != self::ENTITY_TYPE_GROUP && $typeLibrary != self::ENTITY_TYPE_SHARED)
		{
			return false;
		}

		return \Bitrix\Webdav\FolderInviteTable::deleteByFilter(array(
			'=IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionLinkData['ID'],
			'INVITE_USER_ID' => $userIds,
		));
	}

	/**
	 * User by user
	 *        array(
	 *        'IBLOCK_ID' => 16,
	 *        'IBLOCK_SECTION_ID' => 162,
	 *    );
	 *    array(
	 *        'NAME' => 'link on folder',
	 *        'IBLOCK_ID' => 15,
	 *        'ID' => 3574,
	 *        'CREATED_BY' => 1,
	 *        'CAN_FORWARD' => 1,
	 *        'INVITE_USER_ID' => 480,
	 *    );
	 *
	 * @param array  $sectionTargetData
	 * @param array  $sectionLinkData
	 * @param string $typeLibrary - user, group, shared
	 * @return bool|int
	 */
	public static function createSymLinkSection(array $sectionTargetData, array $sectionLinkData, $typeLibrary = self::ENTITY_TYPE_USER)
	{
		if(
			empty($sectionLinkData['IBLOCK_ID']) ||
			empty($sectionLinkData['ID']) ||
			empty($sectionLinkData['NAME']) ||
			empty($sectionLinkData['CREATED_BY'])
		)
		{
			return false;
		}
		if(empty($sectionTargetData['IBLOCK_ID']) || empty($sectionTargetData['IBLOCK_SECTION_ID']))
		{
			return false;
		}

		$typeLibrary = strtolower($typeLibrary);
		if($typeLibrary != self::ENTITY_TYPE_USER && $typeLibrary != self::ENTITY_TYPE_GROUP && $typeLibrary != self::ENTITY_TYPE_SHARED)
		{
			return false;
		}

		if(!CWebDavTools::isIntranetUser($sectionLinkData['INVITE_USER_ID']))
		{
			return false;
		}

		$sectionTargetData = array_intersect_key($sectionTargetData, array(
			'IBLOCK_ID' => true,
			'IBLOCK_SECTION_ID' => true,
		));
		$additionalData = array(
			CWebDavIblock::UF_LINK_IBLOCK_ID => $sectionLinkData['IBLOCK_ID'],
			CWebDavIblock::UF_LINK_SECTION_ID => $sectionLinkData['ID'],
			CWebDavIblock::UF_LINK_ROOT_SECTION_ID => self::getRootSectionId($sectionLinkData['IBLOCK_ID'], $sectionLinkData['ID'], $typeLibrary),
			CWebDavIblock::UF_LINK_CAN_FORWARD => $sectionLinkData['CAN_FORWARD'],
			'CREATED_BY' => $sectionLinkData['CREATED_BY'],
			'MODIFIED_BY' => $sectionLinkData['CREATED_BY'],
			'NAME' => $sectionLinkData['NAME'],
		);

		$exists = \Bitrix\Webdav\FolderInviteTable::getRow(array('filter' => array(
			'INVITE_USER_ID' => $sectionLinkData['INVITE_USER_ID'],
			'IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
			'SECTION_ID' => $sectionLinkData['ID'],
		), 'select' => array('ID', 'LINK_SECTION_ID', 'IS_DELETED', 'IS_APPROVED')));
		//rewrite old self-deleted by user invite
		if($exists && !$exists['IS_DELETED'] && $exists['IS_APPROVED'])
		{
			return $exists['LINK_SECTION_ID'];
		}

		$section = new CIBlockSection();
		$sectionId = $section->add(array_merge(
			$sectionTargetData,
			$additionalData
		));

		if($typeLibrary == self::ENTITY_TYPE_GROUP)
		{
			$inviteUserId = $sectionLinkData['CREATED_BY'];
			\Bitrix\Webdav\FolderInviteTable::addIfNonExists(array(
				'INVITE_USER_ID' => $sectionLinkData['CREATED_BY'],
				'USER_ID' => $sectionLinkData['CREATED_BY'],
				'IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
				'SECTION_ID' => $sectionLinkData['ID'],
				'LINK_SECTION_ID' => $sectionId,
				'IS_APPROVED' => true,
				'IS_DELETED' => false,
				'CAN_FORWARD' => false,
			));
		}
		elseif($typeLibrary == self::ENTITY_TYPE_USER)
		{
			if($sectionId)
			{
				$inviteUserId = $sectionLinkData['INVITE_USER_ID'];
				\Bitrix\Webdav\FolderInviteTable::addIfNonExists(array(
					'INVITE_USER_ID' => $sectionLinkData['INVITE_USER_ID'],
					'USER_ID' => $sectionLinkData['CREATED_BY'],
					'IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
					'SECTION_ID' => $sectionLinkData['ID'],
					'LINK_SECTION_ID' => $sectionId,
					'IS_APPROVED' => true,
					'IS_DELETED' => false,
					'CAN_FORWARD' => false,
					'CAN_EDIT' => $sectionLinkData['CAN_EDIT'],
				));

				$rightsLetter = $sectionLinkData['CAN_EDIT']? 'W' : 'R';
				CWebDavIblock::appendRightsOnSections(array($sectionLinkData), array(
					$rightsLetter => 'U' . $sectionLinkData['INVITE_USER_ID'],
				));

			}
		}

		if($sectionId && $inviteUserId)
		{
			CWebDavDiskDispatcher::sendChangeStatus($inviteUserId, 'symlink');
		}

		return $sectionId;
	}

	public static function createInviteOnSection(array $sectionTargetData, array $sectionLinkData, $typeLibrary = self::ENTITY_TYPE_USER)
	{
		if(
			empty($sectionLinkData['IBLOCK_ID']) ||
			empty($sectionLinkData['ID']) ||
			empty($sectionLinkData['NAME']) ||
			empty($sectionLinkData['CREATED_BY'])
		)
		{
			return false;
		}
		if(empty($sectionTargetData['IBLOCK_ID']) || empty($sectionTargetData['IBLOCK_SECTION_ID']))
		{
			return false;
		}

		$typeLibrary = strtolower($typeLibrary);
		if($typeLibrary != self::ENTITY_TYPE_USER && $typeLibrary != self::ENTITY_TYPE_GROUP && $typeLibrary != self::ENTITY_TYPE_SHARED)
		{
			return false;
		}

		if(!CWebDavTools::isIntranetUser($sectionLinkData['INVITE_USER_ID']))
		{
			return false;
		}

		$exists = \Bitrix\Webdav\FolderInviteTable::getRow(array('filter' => array(
			'INVITE_USER_ID' => $sectionLinkData['INVITE_USER_ID'],
			'IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
			'SECTION_ID' => $sectionLinkData['ID'],
		), 'select' => array('ID', 'LINK_SECTION_ID', 'IS_DELETED', 'IS_APPROVED')));
		//rewrite old self-deleted by user invite
		if($exists && !$exists['IS_DELETED'] && $exists['IS_APPROVED'])
		{
			return true;
		}

		if($typeLibrary == self::ENTITY_TYPE_GROUP)
		{
			\Bitrix\Webdav\FolderInviteTable::addIfNonExists(array(
				'INVITE_USER_ID' => $sectionLinkData['CREATED_BY'],
				'USER_ID' => $sectionLinkData['CREATED_BY'],
				'IBLOCK_ID' => $sectionLinkData['IBLOCK_ID'],
				'SECTION_ID' => $sectionLinkData['ID'],
				'IS_APPROVED' => CWebDavTools::allowAutoconnectShareGroupFolder(),
				'IS_DELETED' => false,
				'CAN_FORWARD' => false,
			));
		}
		elseif($typeLibrary == self::ENTITY_TYPE_USER)
		{
		}

		return true;
	}

	/**
	 * Determine root section for library
	 * @param $iblockId
	 * @param $sectionId
	 * @param $typeLibrary
	 * @return integer|false
	 */
	private static function getRootSectionId($iblockId, $sectionId, $typeLibrary)
	{
		$section = CIBlockSection::GetList(array(), array(
			'ID' => $sectionId,
			'IBLOCK_ID' => $iblockId,
		), false, array('ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'))->fetch();

		if($typeLibrary == self::ENTITY_TYPE_USER)
		{
			$sectionOwnerElement = CIBlockSection::GetList(array('LEFT_MARGIN' => 'DESC'), array(
				'IBLOCK_ID'         => $iblockId,
				'DEPTH_LEVEL'       => 1,
				'IBLOCK_SECTION_ID' => null,
				'!LEFT_MARGIN'      => $section['LEFT_MARGIN'],
				'!RIGHT_MARGIN'     => $section['RIGHT_MARGIN'],
				'CHECK_PERMISSIONS' => 'N',
			), false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'CREATED_BY', 'NAME'))->fetch();

			return $sectionOwnerElement['ID'];
		}
		elseif($typeLibrary == self::ENTITY_TYPE_GROUP)
		{
			if($section['DEPTH_LEVEL'] == 1)
			{
				return $section['ID'];
			}
			$sectionOwnerElement = CIBlockSection::GetList(array('LEFT_MARGIN' => 'DESC'), array(
				'IBLOCK_ID'         => $iblockId,
				'DEPTH_LEVEL'       => 1,
				'IBLOCK_SECTION_ID' => null,
				'!LEFT_MARGIN'      => $section['LEFT_MARGIN'],
				'!RIGHT_MARGIN'     => $section['RIGHT_MARGIN'],
				'CHECK_PERMISSIONS' => 'N',
			), false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'SOCNET_GROUP_ID', 'NAME'))->fetch();

			return empty($sectionOwnerElement['ID'])? 0 : $sectionOwnerElement['ID'];
		}
		elseif($typeLibrary == self::ENTITY_TYPE_SHARED)
		{
			return 0;
		}
	}

	public static function getNavChain($iblockId, $sectionId)
	{
		static $_cacheData = array();

		if(isset($_cacheData[$iblockId][$sectionId]))
		{
			return $_cacheData[$iblockId][$sectionId];
		}

		if(!isset($_cacheData[$iblockId]))
		{
			$_cacheData[$iblockId] = array();
		}
		$_cacheData[$iblockId][$sectionId] = array();

		$dbQuery = CIBlockSection::GetNavChain($iblockId, $sectionId, array(
			'ID', 'CREATED_BY', 'IBLOCK_SECTION_ID', 'NAME', 'LEFT_MARGIN',
			'RIGHT_MARGIN', 'DEPTH_LEVEL', 'SOCNET_GROUP_ID', 'IBLOCK_CODE',
		));
		while($chainItem = $dbQuery->fetch())
		{
			$_cacheData[$iblockId][$sectionId][] = $chainItem;
		}

		return $_cacheData[$iblockId][$sectionId];
	}

	/**
	 * Return iblockId for section
	 * @param $sectionId
	 * @return null|integer
	 */
	public static function getIblockIdForSectionId($sectionId)
	{
		if(isset(self::$_cacheDataSectionIblockId[$sectionId]))
		{
			return self::$_cacheDataSectionIblockId[$sectionId];
		}
		self::$_cacheDataSectionIblockId[$sectionId] = null;
		$getIblock = CIBlockSection::GetList(array(), array('ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'), false, array('IBLOCK_ID'));
		if($getIblock && ($getIblock = $getIblock->fetch()))
		{
			self::$_cacheDataSectionIblockId[$sectionId] = $getIblock['IBLOCK_ID'];
		}

		return self::$_cacheDataSectionIblockId[$sectionId];
	}

	public static function setIblockIdForSectionId($sectionId, $data)
	{
		self::$_cacheDataSectionIblockId[$sectionId] = $data;
	}

	/**
	 * Return iblock & section for element
	 * @param $elementId
	 * @return array
	 */
	public static function getParentDataForElementId($elementId)
	{
		static $_cacheData = array();
		if(isset($_cacheData[$elementId]))
		{
			return $_cacheData[$elementId];
		}
		$_cacheData[$elementId] = array();
		$getParentData = CIBlockElement::GetList(array(), array('ID' => $elementId, 'SHOW_HISTORY'=>'Y'), false, false, array('IBLOCK_ID', 'IBLOCK_SECTION_ID'));
		if($getParentData && ($getParentData = $getParentData->fetch()))
		{
			$_cacheData[$elementId] = $getParentData;
		}

		return $_cacheData[$elementId];
	}

	public static function sendNotifyUnshare(array $folderInvite)
	{
		if(empty($folderInvite['IS_DELETED']) && !empty($folderInvite['IS_APPROVED']) && $folderInvite['USER_ID'] != $folderInvite['INVITE_USER_ID'] && \Bitrix\Main\Loader::includeModule('im'))
		{
			$sectionToShare = CIBlockSection::getList(array(), array(
				'ID' => $folderInvite['SECTION_ID'],
				'IBLOCK_ID' => $folderInvite['IBLOCK_ID'],
				'CHECK_PERMISSIONS' => 'N',
			), false, array('ID', 'NAME'))->fetch();
			if(empty($sectionToShare['NAME']))
			{
				return;
			}
			$notifyFields = array();
			$notifyFields['NOTIFY_MODULE'] = 'webdav';
			$notifyFields['NOTIFY_EVENT'] = "invite";
			$notifyFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			$notifyFields['FROM_USER_ID'] = $folderInvite['USER_ID'];
			$notifyFields['TO_USER_ID'] = $folderInvite['INVITE_USER_ID'];
			$notifyFields['NOTIFY_TAG'] = \Bitrix\Webdav\FolderInviteTable::getNotifyTag($folderInvite);
			$notifyFields['NOTIFY_SUB_TAG'] = "WEBDAV|INVITE|{$folderInvite['ID']}";

			$sectionName = self::getSectionOriginalName($sectionToShare['ID'], $sectionToShare['NAME']);
			$notifyFields['MESSAGE'] = $notifyFields['TITLE'] = GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_TITLE',
				array(
					'#FOLDERNAME#' => $sectionName,
				)
			);
			\CIMNotify::Add($notifyFields);
		}
	}

	public static function sendNotify(array $folderInvite)
	{
		$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
		if(empty($folderInvite['IS_DELETED']) && !empty($folderInvite['IS_APPROVED']) && $folderInvite['USER_ID'] != $folderInvite['INVITE_USER_ID'] && \Bitrix\Main\Loader::includeModule('im'))
		{
			$sectionToShare = CIBlockSection::getList(array(), array(
				'ID' => $folderInvite['SECTION_ID'],
				'IBLOCK_ID' => $folderInvite['IBLOCK_ID'],
				'CHECK_PERMISSIONS' => 'N',
			), false, array('NAME'))->fetch();
			if(empty($sectionToShare['NAME']))
			{
				return;
			}
			$notifyFields = array();
			$notifyFields['NOTIFY_MODULE'] = 'webdav';
			$notifyFields['NOTIFY_EVENT'] = "invite";
			$notifyFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			$notifyFields['FROM_USER_ID'] = $folderInvite['USER_ID'];
			$notifyFields['TO_USER_ID'] = $folderInvite['INVITE_USER_ID'];
			$notifyFields['NOTIFY_TAG'] = \Bitrix\Webdav\FolderInviteTable::getNotifyTag($folderInvite);
			$notifyFields['NOTIFY_SUB_TAG'] = "WEBDAV|INVITE|{$folderInvite['ID']}";

			$uriShow = \CComponentEngine::makePathFromTemplate(
				CWebDavSymlinkHelper::getPathPattern('user', '/company/personal/user/#user_id#/'),
				array('user_id' => $folderInvite['INVITE_USER_ID']
			)) . 'files/lib/?result=sec' . $folderInvite['LINK_SECTION_ID'];
			$uriDisconnect = \CComponentEngine::makePathFromTemplate(
				CWebDavSymlinkHelper::getPathPattern('user', '/company/personal/user/#user_id#/'),
				array('user_id' => $folderInvite['INVITE_USER_ID']
			)) . 'files/lib/?result=sec' . $folderInvite['LINK_SECTION_ID'] . '#disconnect';
			$notifyFields['NOTIFY_MESSAGE'] = GetMessage('WD_SYMLINK_INVITE_TEXT_APPROVE_N1',
				array(
					'#FOLDERNAME#' => '<a href="' . $uriShow . '">' . $sectionToShare['NAME'] . '</a>',
					'#DISCONNECT_LINK#' => '<a href="' . $uriDisconnect . '">' . GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_LINK') . '</a>',
					'#INVITETEXT#' => $folderInvite['DESCRIPTION'] ?: '',
				)
			);
			$notifyFields['NOTIFY_MESSAGE_OUT'] = GetMessage('WD_SYMLINK_INVITE_TEXT_APPROVE_N1',
				array(
					'#FOLDERNAME#' => $sectionToShare['NAME'] . " ({$uriShow})",
					'#DISCONNECT_LINK#' => "\n\n". GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_LINK') . ': ' . $serverName . $uriDisconnect,
					'#INVITETEXT#' => $folderInvite['DESCRIPTION'] ?: '',
				)
			);

			\CIMNotify::Add($notifyFields);
		}
		//self invite. It's connect group disk.
		elseif(empty($folderInvite['IS_DELETED']) && $folderInvite['USER_ID'] == $folderInvite['INVITE_USER_ID'] && \Bitrix\Main\Loader::includeModule('im'))
		{
			$sectionToShare = CIBlockSection::getList(array(), array(
				'ID' => $folderInvite['SECTION_ID'],
				'IBLOCK_ID' => $folderInvite['IBLOCK_ID'],
				'CHECK_PERMISSIONS' => 'N',
			), false, array('NAME', 'SOCNET_GROUP_ID'))->fetch();
			if(empty($sectionToShare['NAME']) || empty($sectionToShare['SOCNET_GROUP_ID']))
			{
				return;
			}

			if(\Bitrix\Main\Loader::includeModule('socialnetwork'))
			{
				$group = CSocNetGroup::GetList(array(), array('ID' => $sectionToShare['SOCNET_GROUP_ID']), false, false, array('NAME'))->fetch();
			}

			$notifyFields = array();
			$notifyFields['NOTIFY_MODULE'] = 'webdav';
			$notifyFields['NOTIFY_EVENT'] = "invite";
			$notifyFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			$notifyFields['FROM_USER_ID'] = $folderInvite['USER_ID'];
			$notifyFields['TO_USER_ID'] = $folderInvite['INVITE_USER_ID'];
			$notifyFields['NOTIFY_TAG'] = \Bitrix\Webdav\FolderInviteTable::getNotifyTag($folderInvite);
			$notifyFields['NOTIFY_SUB_TAG'] = "WEBDAV|INVITE|{$folderInvite['ID']}";

			$uriShow = \CComponentEngine::makePathFromTemplate(
				CWebDavSymlinkHelper::getPathPattern('group', '/company/personal/user/#user_id#/'),
				array('user_id' => $folderInvite['INVITE_USER_ID']
			)) . 'files/lib/?result=sec' . $folderInvite['LINK_SECTION_ID'];
			$uriDisconnect = \CComponentEngine::makePathFromTemplate(
				CWebDavSymlinkHelper::getPathPattern('user', '/company/personal/user/#user_id#/'),
				array('user_id' => $folderInvite['INVITE_USER_ID']
			)) . 'files/lib/?result=sec' . $folderInvite['LINK_SECTION_ID'] . '#disconnect';

			if(\CWebDavTools::allowAutoconnectShareGroupFolder())
			{
				$notifyFields['NOTIFY_MESSAGE'] = GetMessage('WD_SYMLINK_INVITE_GROUP_TEXT_APPROVE_N1',
					array(
						'#FOLDERNAME#' => $sectionToShare['NAME'],
						'#INVITETEXT#' => $folderInvite['DESCRIPTION'] ?: '',
						'#GROUPNAME#' => '<a href="' . $uriShow . '">' . $group['NAME'] . '</a>',
						'#DISCONNECT_LINK#' => '<a href="' . $uriDisconnect . '">' . GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_LINK') . '</a>',
					)
				);
				$notifyFields['NOTIFY_MESSAGE_OUT'] = GetMessage('WD_SYMLINK_INVITE_GROUP_TEXT_APPROVE_N1',
					array(
						'#FOLDERNAME#' => $sectionToShare['NAME'],
						'#INVITETEXT#' => $folderInvite['DESCRIPTION'] ?: '',
						'#GROUPNAME#' => $group['NAME'],
						'#DISCONNECT_LINK#' => "\n\n". GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_LINK') . ': ' . $serverName . $uriDisconnect,
					)
				);
			}
			elseif(empty($folderInvite['IS_APPROVED']))
			{
				$notifyFields['NOTIFY_TYPE'] = IM_NOTIFY_CONFIRM;
				$notifyFields['NOTIFY_BUTTONS'] = Array(
					Array('TITLE' => GetMessage('WD_SYMLINK_INVITE_APPROVE_Y'), 'VALUE' => 'Y', 'TYPE' => 'accept'),
					Array('TITLE' => GetMessage('WD_SYMLINK_INVITE_APPROVE_N'), 'VALUE' => 'N', 'TYPE' => 'cancel')
				);

				$notifyFields['MESSAGE'] = GetMessage('WD_SYMLINK_INVITE_GROUP_TEXT_APPROVE_CONFIRM_N1',
					array(
						'#FOLDERNAME#' => $sectionToShare['NAME'],
						'#GROUPNAME#' => $group['NAME'],
					)
				);
			}

			\CIMNotify::Add($notifyFields);
		}
		elseif(!empty($folderInvite['IS_DELETED']) && \Bitrix\Main\Loader::includeModule('im'))
		{
			$sectionToShare = CIBlockSection::getList(array(), array(
				'ID' => $folderInvite['SECTION_ID'],
				'IBLOCK_ID' => $folderInvite['IBLOCK_ID'],
				'CHECK_PERMISSIONS' => 'N',
			), false, array('NAME'))->fetch();
			if(empty($sectionToShare['NAME']))
			{
				return;
			}
			$inviteUser = \CUser::getById($folderInvite['INVITE_USER_ID']);
			if($inviteUser)
			{
				$inviteUser = $inviteUser->fetch();
			}
			$notifyFields = array();
			$notifyFields['NOTIFY_MODULE'] = 'webdav';
			$notifyFields['NOTIFY_EVENT'] = "invite";
			$notifyFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			$notifyFields['FROM_USER_ID'] = $folderInvite['INVITE_USER_ID'];
			$notifyFields['TO_USER_ID'] = $folderInvite['USER_ID'];
			$notifyFields['NOTIFY_TAG'] = \Bitrix\Webdav\FolderInviteTable::getNotifyTag($folderInvite);
			$notifyFields['NOTIFY_SUB_TAG'] = "WEBDAV|INVITE|{$folderInvite['ID']}";
			if(CWebDavTools::getUserGender($inviteUser['PERSONAL_GENDER']) == 'F')
			{
				$notifyFields['MESSAGE'] = GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_F',
					array(
						'#FOLDERNAME#' => $sectionToShare['NAME'],
						'#USERNAME#' => CWebDavTools::getUserName($inviteUser)
					)
				);
			}
			else
			{
				$notifyFields['MESSAGE'] = GetMessage('WD_SYMLINK_INVITE_TEXT_DISCONNECT_M',
					array(
						'#FOLDERNAME#' => $sectionToShare['NAME'],
						'#USERNAME#' => CWebDavTools::getUserName($inviteUser)
					)
				);
			}

			\CIMNotify::Add($notifyFields);
		}
	}

	public static function onBeforeConfirmNotify($module, $tag, $value, $arNotify)
	{
		global $USER;
		$userId = $USER->getId();
		if ($module == 'webdav' && $userId)
		{
			$tagData = explode('|', $tag);
			$folderInviteId = intval($tagData[2]);
			if ($tagData[0] == "WEBDAV" && $tagData[1] == "INVITE" && $folderInviteId > 0 && $userId == $tagData[3])
			{
				if (\Bitrix\Main\Loader::includeModule('im'))
				{
					CIMNotify::DeleteByTag(\Bitrix\Webdav\FolderInviteTable::getNotifyTag(array('ID' => $folderInviteId, 'INVITE_USER_ID' => $userId)));
				}
				//decline
				if($value === 'N')
				{
					\Bitrix\Webdav\FolderInviteTable::delete($folderInviteId);
					return false;
				}

				$targetSectionData = CWebDavIblock::getRootSectionDataForUser($userId);
				if(!$targetSectionData)
				{
					return false;
				}
				$folderInviteData = \Bitrix\Webdav\FolderInviteTable::getRowById($folderInviteId);
				if(!$folderInviteData)
				{
					return false;
				}

				$sectionToShare = CIBlockSection::getList(array(), array(
					'ID' => $folderInviteData['SECTION_ID'],
					'IBLOCK_ID' => $folderInviteData['IBLOCK_ID'],
					'CHECK_PERMISSIONS' => 'N',
				), false, array('NAME', 'SOCNET_GROUP_ID'))->fetch();
				if(empty($sectionToShare['NAME']) || empty($sectionToShare['SOCNET_GROUP_ID']))
				{
					return false;
				}

				if(\Bitrix\Main\Loader::includeModule('socialnetwork'))
				{
					$group = CSocNetGroup::GetList(array(), array('ID' => $sectionToShare['SOCNET_GROUP_ID']), false, false, array('NAME'))->fetch();
				}
				if(empty($group))
				{
					return false;
				}
				$groupId = $sectionToShare['SOCNET_GROUP_ID'];

				$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
				$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
				$attachObjectId = (int)$groupId;

				$inviteComponentParams = array(
					'attachObject' => array(
						'id' => $attachObjectId,
						'type' => $attachObjectType,
					),
					'attachToUserId' => $folderInviteData['INVITE_USER_ID'],
					'inviteFromUserId' => $folderInviteData['USER_ID'],
					'canEdit' => $folderInviteData['CAN_EDIT'],
				);
				$response = $dispatcher->processActionConnect($inviteComponentParams);
				if($response['status'] == $dispatcher::STATUS_SUCCESS)
				{
					\Bitrix\Webdav\FolderInviteTable::update($folderInviteId, array(
						'IS_APPROVED' => true,
						'LINK_SECTION_ID' => $response['sectionId'],
					));
				}

				return $response['status'] == $dispatcher::STATUS_SUCCESS;
			}
		}
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public static function setPathPattern($name, $value)
	{
		self::$_pathPattern[$name] = $value;
	}

	/**
	 * @param      $name
	 * @param null $default
	 * @return string
	 */
	public static function getPathPattern($name, $default = null)
	{
		return isset(self::$_pathPattern[$name])? self::$_pathPattern[$name] : $default;
	}
}