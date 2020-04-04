<?php

namespace Bitrix\Webdav;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;

class InviteDispatcher
{
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR   = 'error';

	const DESKTOP_DISK_STATUS_ONLINE        = 'online';
	const DESKTOP_DISK_STATUS_NOT_INSTALLED = 'not_installed';
	const DESKTOP_DISK_STATUS_NOT_ENABLED   = 'not_enabled';

	const USERS_ON_PAGE = 4;

	/**
	 * Current params
	 * @var array
	 */
	private $params = array();

	/**
	 * @return \CAllMain
	 */
	private static function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	private function isAjax()
	{
		return !empty($params['ajax']);
	}

	public function processActionConnect(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$targetSectionData = $this->getSectionDataByUserId($params['attachToUserId']);
		$targetSectionData['IBLOCK_SECTION_ID'] = $targetSectionData['SECTION_ID'];

		$linkData = array(
			'ID' => $attachSectionData['SECTION_ID'],
			'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
			'NAME' => $this->generateNameForSymLinkSection($targetSectionData, $params['attachObject']),
			'CREATED_BY' => $params['inviteFromUserId'],
			'INVITE_USER_ID' => $params['attachToUserId'],
			'CAN_EDIT' => $params['canEdit'],
			'CAN_FORWARD' => 0,
		);

		$symlinkSectionId = \CWebDavSymlinkHelper::createSymLinkSection($targetSectionData, $linkData, $params['attachObject']['type']);
		if($symlinkSectionId)
		{
			$symlinkSection = \CIBlockSection::getList(array(), array('ID' => $symlinkSectionId), false, array('NAME'))->fetch();
			return $this->sendJsonResponse(array(
				'status' => self::STATUS_SUCCESS,
				'sectionId' => $symlinkSectionId,
				'sectionName' => $symlinkSection['NAME'],
				'statusDisk' => $this->getDesktopDiskStatus(),
			));
		}

		return $this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
		));
	}

	public function processActionInvite(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$targetSectionData = $this->getSectionDataByUserId($params['attachToUserId']);
		$targetSectionData['IBLOCK_SECTION_ID'] = $targetSectionData['SECTION_ID'];

		$linkData = array(
			'ID' => $attachSectionData['SECTION_ID'],
			'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
			'NAME' => $this->generateNameForSymLinkSection($targetSectionData, $params['attachObject']),
			'CREATED_BY' => $params['inviteFromUserId'],
			'INVITE_USER_ID' => $params['attachToUserId'],
			'CAN_EDIT' => $params['canEdit'],
			'CAN_FORWARD' => 0,
		);

		$statusInvite = \CWebDavSymlinkHelper::createInviteOnSection($targetSectionData, $linkData, $params['attachObject']['type']);
		if($statusInvite)
		{
			return $this->sendJsonResponse(array(
				'status' => self::STATUS_SUCCESS,
			));
		}

		return $this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
		));
	}

	public function processActionDisconnect(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$targetSectionData = $this->getSectionDataByUserId($params['attachToUserId']);
		$targetSectionData['IBLOCK_SECTION_ID'] = $targetSectionData['SECTION_ID'];

		$linkData = array(
			'ID' => $attachSectionData['SECTION_ID'],
			'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
			'INVITE_USER_ID' => $params['attachToUserId'],
		);

		$successDelete = \CWebDavSymlinkHelper::deleteSymLinkSection($targetSectionData, $linkData, $params['attachObject']['type']);
		if($successDelete)
		{
			return $this->sendJsonResponse(array(
				'status' => self::STATUS_SUCCESS,
			));
		}

		return $this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
		));
	}

	public function processActionDetailGroupConnect(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$result = array();
		$result['GROUP_DISK'] = array();
		$result['GROUP_DISK']['CONNECT_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'connect',
			'group' => $params['attachObject']['id'],
		)));
		$result['GROUP_DISK']['DISCONNECT_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'disconnect',
			'group' => $params['attachObject']['id'],
		)));
		$result['GROUP_DISK']['IS_CONNECTED'] = $this->isConnected($params['attachToUserId'], $attachSectionData);

		$result['CONNECTED_USERS_CAN_EDITED_COUNT'] = $this->getCountConnectedUsersCanEdited($attachSectionData);
		$result['CONNECTED_USERS_CANNOT_EDITED_COUNT'] = $this->getCountConnectedUsersCannotEdited($attachSectionData);
		$result['DISCONNECTED_USERS_COUNT'] = $this->getCountDisconnected($attachSectionData);

		$result['OWNER'] = $this->reformatGroup($this->getGroupBySection($attachSectionData));
		$result['OWNER']['IS_GROUP'] = true;

		$result['USER_DISK']['LIST_USERS_CAN_EDIT_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'load_users_for_detail_user_share',
		)));

		return $result;
	}

	public function processActionDetailUserShare(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$result = array();
		$result['CONNECTED_USERS_CAN_EDITED_COUNT'] = $this->getCountConnectedUsersCanEdited($attachSectionData);
		$result['CONNECTED_USERS_CANNOT_EDITED_COUNT'] = $this->getCountConnectedUsersCannotEdited($attachSectionData);
		$result['DISCONNECTED_USERS_COUNT'] = $this->getCountDisconnected($attachSectionData);

		$result['OWNER'] = $this->getOwnerBySection($attachSectionData);
		$result['OWNER'] = $result['OWNER']['USER'];

		$result['TARGET_NAME'] = empty($attachSectionData['NAME'])? '' : $attachSectionData['NAME'];

		$result['USER_DISK'] = array();
		$result['USER_DISK']['SHARE_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'share',
		)));

		$result['USER_DISK']['UNSHARE_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'unshare',
		)));

		$result['USER_DISK']['LIST_USERS_CAN_EDIT_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'load_users_for_detail_user_share',
		)));

		return $result;
	}

	public function processActionInfoUserShare(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$result = array();
		if(empty($attachSectionData['SOCNET_GROUP_ID']))
		{
			$result['OWNER'] = $this->getOwnerBySection($attachSectionData);
			$result['OWNER'] = $result['OWNER']['USER'];
			$result['OWNER']['IS_GROUP'] = false;
			if(!empty($attachSectionData['IBLOCK_TYPE']) && $attachSectionData['IBLOCK_TYPE'] == 'shared_files')
			{
				$result['OWNER']['IS_SHARED'] = true;
			}
		}
		else
		{
			$result['OWNER'] = $this->reformatGroup($this->getGroupBySection($attachSectionData));
			$result['OWNER']['IS_GROUP'] = true;
			$result['OWNER']['IS_SHARED'] = false;
		}
		$result['TARGET_NAME'] = empty($attachSectionData['NAME'])? '' : $attachSectionData['NAME'];

		$result['CONNECTED_USERS_CAN_EDITED_COUNT'] = $this->getCountConnectedUsersCanEdited($attachSectionData);
		$result['CONNECTED_USERS_CANNOT_EDITED_COUNT'] = $this->getCountConnectedUsersCannotEdited($attachSectionData);
		$result['DISCONNECTED_USERS_COUNT'] = $this->getCountDisconnected($attachSectionData);

		$result['USER_DISK'] = array();
		$result['USER_DISK']['SHARE_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'share',
		)));

		$result['USER_DISK']['UNSHARE_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'unshare',
		)));

		$result['USER_DISK']['LIST_USERS_CAN_EDIT_URL'] = $this->getApplication()->getCurUri(http_build_query(array(
			'toWDController' => 1,
			'wdaction' => 'load_users_for_detail_user_share',
		)));

		return $result;
	}

	public function processActionLoadUsersForDetailUserShare(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$isLinkSection = false;
		if($attachSectionData)
		{
			$exists = FolderInviteTable::getRow(array('filter' => array(
				'INVITE_USER_ID' => $params['attachToUserId'],
				'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
				'SECTION_ID' => $attachSectionData['SECTION_ID'],
			), 'select' => array('ID')));

			if($exists)
			{
				$isLinkSection = true;
			}
		}

		$userListType = $this->params['userListType'];
		if($userListType == 'can_edit')
		{
			return array_merge(array('IS_LINK_SECTION' => $isLinkSection), $this->getListConnectedUsersCanEdited($attachSectionData));
		}
		elseif($userListType == 'cannot_edit')
		{
			return array_merge(array('IS_LINK_SECTION' => $isLinkSection), $this->getListConnectedUsersCannotEdited($attachSectionData));
		}
		elseif($userListType == 'disconnect')
		{
			return array_merge(array('IS_LINK_SECTION' => $isLinkSection), $this->getListDisconnected($attachSectionData));
		}
	}

	public function processActionShare(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		\CWebDavSymlinkHelper::setPathPattern('user', $params['pathToUser']);
		\CWebDavSymlinkHelper::setPathPattern('group', $params['pathToGroup']);

		$result = array();
		foreach ($params['attachToUserIds'] as $userIdToShare)
		{
			if($userIdToShare == $params['inviteFromUserId'])
			{
				//to owner we don't create really symlink. Already there section.
				$result[] = array(
					'userId' => $attachSectionData['SECTION_ID'],
				);
				continue;
			}
			$targetSectionData = $this->getSectionDataByUserId($userIdToShare);
			$targetSectionData['IBLOCK_SECTION_ID'] = $targetSectionData['SECTION_ID'];

			$linkData = array(
				'ID' => $attachSectionData['SECTION_ID'],
				'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
				'NAME' => $this->generateNameForSymLinkSection($targetSectionData, $params['attachObject']),
				'CREATED_BY' => $params['inviteFromUserId'],
				'INVITE_USER_ID' => $userIdToShare,
				'CAN_EDIT' => $params['canEdit'],
				'CAN_FORWARD' => 0,
			);

			$symlinkSectionId = \CWebDavSymlinkHelper::createSymLinkSection($targetSectionData, $linkData, $params['attachObject']['type']);
			if($symlinkSectionId)
			{
				$result[] = array(
					'userId' => $symlinkSectionId,
				);
			}
		}

		if($result)
		{
			\CWebDavDiskDispatcher::sendChangeStatus($params['inviteFromUserId'], 'share_section');
			global $DB;
			//update timestamp_x for incremental snapshot from disk.
			$section = new \CIBlockSection;
			$section->update($attachSectionData['SECTION_ID'], array('~TIMESTAMP_X' => $DB->getNowFunction()), false, false);

			return $this->sendJsonResponse(array(
				'status' => self::STATUS_SUCCESS,
				'sections' => $result,
			));
		}

		return $this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
		));
	}


	public function processActionUnshare(array $params)
	{
		$this->params = $params;
		$attachSectionData = $this->getSectionDataByAttachObject($params['attachObject']);

		$successDelete = false;
		if(empty($params['unshareUserIds']))
		{
			$successDelete = \CWebDavSymlinkHelper::deleteAllSymLinkOnSection(array(
				'ID' => $attachSectionData['SECTION_ID'],
				'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
			), $params['attachObject']['type']);
		}
		else
		{
			$successDelete = \CWebDavSymlinkHelper::deleteSymLinkOnSectionByUserIds($params['unshareUserIds'], array(
				'ID' => $attachSectionData['SECTION_ID'],
				'IBLOCK_ID' => $attachSectionData['IBLOCK_ID'],
			), $params['attachObject']['type']);
		}

		if($successDelete)
		{
			return $this->sendJsonResponse(array(
				'status' => self::STATUS_SUCCESS,
			));
		}

		return $this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
		));
	}

	private function generateNameForSymLinkSection(array $targetSectionData, array $attachObject)
	{
		if($attachObject['type'] == \CWebDavSymlinkHelper::ENTITY_TYPE_GROUP)
		{
			return \CWebDavIblock::correctName(\CWebDavSymlinkHelper::generateNameForGroupLink($attachObject['id'], $targetSectionData));
		}
		if($attachObject['type'] == \CWebDavSymlinkHelper::ENTITY_TYPE_USER)
		{
			return \CWebDavIblock::correctName(\CWebDavSymlinkHelper::generateNameForUserSectionLink($attachObject['id'], $targetSectionData));
		}

		return '';
	}

	private function getSectionDataByAttachObject(array $attachObject)
	{
		if(empty($attachObject['type']))
		{
			throw new \Bitrix\Main\ArgumentException('type', 'attachObject');
		}
		if(!isset($attachObject['id']))
		{
			throw new \Bitrix\Main\ArgumentException('id', 'attachObject');
		}
		if($attachObject['type'] == \CWebDavSymlinkHelper::ENTITY_TYPE_GROUP)
		{
			$data = \CWebDavIblock::getRootSectionDataForGroup((int)$attachObject['id']);
			$data['SOCNET_GROUP_ID'] = $attachObject['id'];
			return $data;
		}
		if($attachObject['type'] == \CWebDavSymlinkHelper::ENTITY_TYPE_USER)
		{
			$sectionId = (int)$attachObject['id'];
			$sectionData = \CIBlockSection::getList(
				array(),
				array('ID' => $sectionId, 'CHECK_PERMISSIONS' => 'Y'),
				false,
				array('SOCNET_GROUP_ID', 'IBLOCK_ID', 'CREATED_BY', 'NAME')
			);

			if(!$sectionData || !($sectionData = $sectionData->fetch()))
			{
				return array();
			}

			$allowableIblock = false;
			$iblockType = false;
			foreach(array('user_files', 'group_files', 'shared_files',) as $type)
			{
				$wdIblockOptions = \CWebDavIblock::libOptions($type, false, SITE_ID);
				if (is_set($wdIblockOptions, 'id') && (intval($wdIblockOptions['id']) > 0))
				{
					if($sectionData['IBLOCK_ID'] == $wdIblockOptions['id'])
					{
						$allowableIblock = true;
						$iblockType = $type;
					}
				}
			}
			if(!$allowableIblock)
			{
				return array();
			}


			\CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $sectionData['IBLOCK_ID']);

			return array(
				'NAME' => $sectionData['NAME'],
				'IBLOCK_ID' => $sectionData['IBLOCK_ID'],
				'IBLOCK_TYPE' => $iblockType,
				'SECTION_ID' => $sectionId,
				'CREATED_BY' => $sectionData['CREATED_BY'],
				'SOCNET_GROUP_ID' => isset($sectionData['SOCNET_GROUP_ID'])? $sectionData['SOCNET_GROUP_ID'] : null,
			);
		}
		throw new \Bitrix\Main\ArgumentException('Wrong type', 'attachObject');
	}

	private function isConnected($userId, array $sectionData)
	{
		return (bool)\Bitrix\Webdav\FolderInviteTable::getRow(array('filter' => array(
			'=INVITE_USER_ID' => $userId,
			'=IS_APPROVED' => true,
			'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionData['SECTION_ID'],
		)));
	}

	private function getConnectedUsers(array $sectionData)
	{
		$users = \Bitrix\Webdav\FolderInviteTable::getList(array('filter' => array(
			'=IS_APPROVED' => true,
			'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionData['SECTION_ID'],
		)));

		return $users->fetchAll();
	}

	private function getInvitesBySection(array $sectionData)
	{
		return \Bitrix\Webdav\FolderInviteTable::getList(array('filter' => array(
			'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionData['SECTION_ID'],
		)))->fetchAll();
	}

	private function getListConnectedUsersCanEdited(array $sectionData)
	{
		return $this->getListUsers(array(
			'=IS_APPROVED' => true,
			'=CAN_EDIT' => true,
			'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionData['SECTION_ID'],
		));
	}

	private function getListDisconnected(array $sectionData)
	{
		return $this->getListUsers(array(
			'=IS_DELETED' => true,
			'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionData['SECTION_ID'],
		));
	}

	private function getListConnectedUsersCannotEdited(array $sectionData)
	{
		return $this->getListUsers(array(
			'=IS_APPROVED' => true,
			'=CAN_EDIT' => false,
			'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
			'=SECTION_ID' => $sectionData['SECTION_ID'],
		));
	}

	private function getListUsers(array $filter)
	{
		$limit  = !isset($this->params['limit'])? self::USERS_ON_PAGE : (int)$this->params['limit'];
		$page   = empty($this->params['page']) ?  1 : (int)$this->params['page'];
		$offset = ($page-1)*$limit;

		$query = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'select' => array('*', 'INVITE_USER'),
			'filter' => $filter,
			'limit' => $limit,
			'offset' => $offset,
			'order' => array('ID' => 'ASC'),
		));

		$users = array();
		while($row = $query->fetch())
		{
			$row = $this->reformatInviteRow($row);
			$users[] = $row;
		}

		$countQuery = new Query(\Bitrix\Webdav\FolderInviteTable::getEntity());
		$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter($filter);
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		$totalCount = $totalCount['CNT'];

		return array(
			'USERS' => $users,
			'COUNT' => count($users),
			'PAGE' => $page,
			'ON_PAGE' => self::USERS_ON_PAGE,
			'TOTAL_COUNT' => intval($totalCount),
			'TOTAL_PAGE' => ceil($totalCount/$limit),
		);
	}

	private function getCountConnectedUsersCanEdited(array $sectionData)
	{
		$count = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'select' => array('COUNT'),
			'filter' => array(
				'=IS_APPROVED' => true,
				'=CAN_EDIT' => true,
				'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
				'=SECTION_ID' => $sectionData['SECTION_ID'],
			),
		))->fetch();

		return $count['COUNT'];
	}

	private function getCountConnectedUsersCannotEdited(array $sectionData)
	{
		$count = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'select' => array('COUNT'),
			'filter' => array(
				'=IS_APPROVED' => true,
				'=CAN_EDIT' => false,
				'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
				'=SECTION_ID' => $sectionData['SECTION_ID'],
			),
		))->fetch();

		return $count['COUNT'];
	}

	private function getCountDisconnected(array $sectionData)
	{
		$count = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'select' => array('COUNT'),
			'filter' => array(
				'=IS_DELETED' => true,
				'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
				'=SECTION_ID' => $sectionData['SECTION_ID'],
			),
		))->fetch();

		return $count['COUNT'];
	}

	private function getSectionDataByUserId($userId)
	{
		return \CWebDavIblock::getRootSectionDataForUser((int)$userId);
	}

	private function getGroupBySection(array $sectionData)
	{
		if(empty($sectionData['SOCNET_GROUP_ID']))
		{
			//todo implement search
			return array();
		}
		$group = \CSocNetGroup::getByID($sectionData['SOCNET_GROUP_ID']);

		return empty($group)? array() : $group;
	}

	/**
	 * Simple logic.
	 * @param array $sectionData
	 * @return array
	 */
	private function getOwnerBySection(array $sectionData)
	{
		//shared docs
		if(
			$sectionData['IBLOCK_TYPE'] == 'shared_files' &&
			!empty($sectionData['SECTION_ID']) &&
			!empty($sectionData['CREATED_BY'])
		)
		{
			$user = \Bitrix\Main\UserTable::getById($sectionData['CREATED_BY'])->fetch();
			return empty($user)? array() : $this->reformatUserRow($user);
		}

		$row = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'select' => array('*', 'USER'),
			'limit' => 1,
			'filter' => array(
				'=IBLOCK_ID' => $sectionData['IBLOCK_ID'],
				'=SECTION_ID' => $sectionData['SECTION_ID'],
			),
		))->fetch();

		return empty($row)? array() : $this->reformatInviteRow($row);
	}

	/**
	 * @return \CAllUser
	 */
	private function getUser()
	{
		global $USER;

		return $USER;
	}

	public function sendJsonResponse($response)
	{
		return $response;
	}

	private function getDesktopDiskStatus()
	{
		if(!\CWebDavTools::isDesktopInstall())
		{
			return self::DESKTOP_DISK_STATUS_NOT_INSTALLED;
		}
		elseif(!\CWebDavTools::isDesktopDiskInstall())
		{
			return self::DESKTOP_DISK_STATUS_NOT_ENABLED;
		}
		elseif(\CWebDavTools::isDesktopDiskOnline())
		{
			return self::DESKTOP_DISK_STATUS_ONLINE;
		}
		else
		{
			return self::DESKTOP_DISK_STATUS_NOT_INSTALLED;
		}
	}

	private function getUserPictureSrc($photoId, $width = 21, $height = 21)
	{
		static $cache = array();

		$photoId = (int) $photoId;
		$key = $photoId . " $width $height";

		if (isset($cache[$key]))
		{
			$src = $cache[$key];
		}
		else
		{
			$src = false;
			if ($photoId > 0)
			{
				$imageFile = \CFile::getFileArray($photoId);
				if ($imageFile !== false)
				{
					$fileTmp = \CFile::resizeImageGet(
						$imageFile,
						array("width" => $width, "height" => $height),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$src = $fileTmp["src"];
				}

				$cache[$key] = $src;
			}
		}

		return $src;
	}

	private function reformatGroup(array $group)
	{
		if (!empty($group['ID']))
		{
			$group['PHOTO_SRC'] = $this->getUserPictureSrc($group['IMAGE_ID']);
			$group['FORMATTED_NAME'] = $group['NAME'];
			$group['HREF'] = \CComponentEngine::makePathFromTemplate(
				$this->params['pathToGroup'],
				array('group_id' => $group['ID']
			));
		}

		return $group;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function reformatInviteRow(array $row)
	{
		$row['PHOTO_SRC'] = '';
		if (!empty($row['WEBDAV_FOLDER_INVITE_USER_ID']))
		{
			$row['USER'] = array();
			$row['USER']['ID'] = $row['WEBDAV_FOLDER_INVITE_USER_ID'];
			$row['USER']['PHOTO_SRC'] = $this->getUserPictureSrc($row['WEBDAV_FOLDER_INVITE_USER_PERSONAL_PHOTO']);
			$row['USER']['FORMATTED_NAME'] = \CWebDavTools::getUserName(array(
				'ID' => $row['WEBDAV_FOLDER_INVITE_USER_ID'],
				'NAME' => $row['WEBDAV_FOLDER_INVITE_USER_NAME'],
				'LAST_NAME' => $row['WEBDAV_FOLDER_INVITE_USER_LAST_NAME'],
				'SECOND_NAME' => $row['WEBDAV_FOLDER_INVITE_USER_SECOND_NAME'],
				'EMAIL' => $row['WEBDAV_FOLDER_INVITE_USER_EMAIL'],
			));
			$row['USER']['HREF'] = \CComponentEngine::makePathFromTemplate(
				$this->params['pathToUser'],
				array('user_id' => $row['WEBDAV_FOLDER_INVITE_USER_ID']
			));
		}

		if (!empty($row['WEBDAV_FOLDER_INVITE_INVITE_USER_ID']))
		{
			$row['INVITE_USER'] = array();
			$row['INVITE_USER']['ID'] = $row['WEBDAV_FOLDER_INVITE_INVITE_USER_ID'];
			$row['INVITE_USER']['PHOTO_SRC'] = $this->getUserPictureSrc($row['WEBDAV_FOLDER_INVITE_INVITE_USER_PERSONAL_PHOTO']);
			$row['INVITE_USER']['FORMATTED_NAME'] = \CWebDavTools::getUserName(array(
				'ID' => $row['WEBDAV_FOLDER_INVITE_INVITE_USER_ID'],
				'NAME' => $row['WEBDAV_FOLDER_INVITE_INVITE_USER_NAME'],
				'LAST_NAME' => $row['WEBDAV_FOLDER_INVITE_INVITE_USER_LAST_NAME'],
				'SECOND_NAME' => $row['WEBDAV_FOLDER_INVITE_INVITE_USER_SECOND_NAME'],
				'EMAIL' => $row['WEBDAV_FOLDER_INVITE_INVITE_USER_EMAIL'],
			));
			$row['INVITE_USER']['HREF'] = \CComponentEngine::makePathFromTemplate(
				$this->params['pathToUser'],
				array('user_id' => $row['WEBDAV_FOLDER_INVITE_INVITE_USER_ID']
			));

			return $row;
		}

		return $row;
	}
	
	private function reformatUserRow(array $row)
	{
		$row['USER'] = array();
		$row['USER']['ID'] = $row['ID'];
		$row['USER']['PHOTO_SRC'] = $this->getUserPictureSrc($row['PERSONAL_PHOTO']);
		$row['USER']['FORMATTED_NAME'] = \CWebDavTools::getUserName(array(
			'ID' => $row['ID'],
			'NAME' => $row['NAME'],
			'LAST_NAME' => $row['LAST_NAME'],
			'SECOND_NAME' => $row['SECOND_NAME'],
			'EMAIL' => $row['EMAIL'],
		));
		$row['USER']['HREF'] = \CComponentEngine::makePathFromTemplate(
			$this->params['pathToUser'],
			array('user_id' => $row['ID']
		));

		return $row;
	}
}