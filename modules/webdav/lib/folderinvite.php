<?php
namespace Bitrix\Webdav;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

/**
 * Class FolderInviteTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> INVITE_USER_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> IBLOCK_ID int mandatory
 * <li> SECTION_ID int mandatory
 * <li> DESCRIPTION string optional
 * <li> IS_APPROVED unknown optional
 * <li> IS_DELETED unknown optional
 * <li> CAN_FORWARD unknown optional
 * <li> CAN_EDIT unknown optional
 * <li> CREATED_TIMESTAMP datetime mandatory default 'CURRENT_TIMESTAMP'
 * </ul>
 * @package Bitrix\Webdav
 */
class FolderInviteTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_webdav_folder_invite';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'INVITE_USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'INVITE_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.INVITE_USER_ID' => 'ref.ID')
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SECTION_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'LINK_SECTION_ID' => array(
				'data_type' => 'integer',
				'required' => false,
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
			),
			'IS_APPROVED' => array(
				'data_type' => 'boolean',
			),
			'IS_DELETED' => array(
				'data_type' => 'boolean',
			),
			'CAN_FORWARD' => array(
				'data_type' => 'boolean',
			),
			'CAN_EDIT' => array(
				'data_type' => 'boolean',
			),
			'CREATED_TIMESTAMP' => array(
				'data_type' => 'datetime',
			),
			'COUNT' => array(
				'data_type' => 'integer',
				'expression' => array('COUNT(*)'),
			),
		);
	}

	/**
	 * @param $filter
	 * @return bool
	 */
	public static function deleteByFilter($filter)
	{
		$result = static::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
		));
		if(!$result)
		{
			return false;
		}
		while($row = $result->fetch())
		{
			if($row['ID'])
			{
				static::delete($row['ID']);
			}
		}

		return true;
	}

	/**
	 * @param array $data
	 * @return Entity\AddResult
	 */
	public static function addIfNonExists(array $data)
	{
		$filter = array_intersect_key($data, array(
			'INVITE_USER_ID' => true,
			'IBLOCK_ID' => true,
			'SECTION_ID' => true,
		));
		$row = static::getRow(array('filter' => $filter, 'select' => array('ID', 'IS_DELETED')));
		//if we add new invite by old deleted invite, then we delete old and add new.
		if($row && empty($data['IS_DELETED']) && $row['IS_DELETED'])
		{
			static::delete($row['ID']);
		}
		elseif($row)
		{
			$result = new \Bitrix\Main\Entity\AddResult();
			$result->setId($row['ID']);

			return $result;
		}

		return static::add($data);
	}

	public static function onAfterAdd(Event $event)
	{
		$fields = $event->getParameter('fields');
		$fields['ID'] = $event->getParameter('id');
		\CWebDavSymlinkHelper::sendNotify($fields);
	}

	public static function onDelete(Event $event)
	{
		$row = static::getRowById($event->getParameter('id'));
		if(!$row)
		{
			return;
		}
		global $USER;
		//todo unshare. Fork invite. Hack
		//not fork if owner by invite unshare user.
		if(!$row['IS_DELETED'] && $row['INVITE_USER_ID'] != $row['USER_ID'] && $row['USER_ID'] != $USER->getId())
		{
			$scalarFields = array();
			foreach (static::getEntity()->getFields() as $fieldName => $field)
			{
				if($field instanceof Entity\ScalarField)
				{
					$scalarFields[$fieldName] = true;
				}
			}
			unset($field);

			$forkRow = array_intersect_key($row, $scalarFields);
			unset($forkRow['ID']);
			$forkRow['CAN_FORWARD'] = (bool)$forkRow['CAN_FORWARD'];
			$forkRow['CAN_EDIT'] = (bool)$forkRow['CAN_EDIT'];
			$forkRow['IS_DELETED'] = true;
			$forkRow['IS_APPROVED'] = false;
			\Bitrix\Webdav\FolderInviteTable::add($forkRow);
		}
		\CWebDavSymlinkHelper::sendNotifyUnshare($row);
		self::deleteSymlinkSections($row);
	}

	private static function deleteSymlinkSections(array $folderInvite)
	{
		if(!\Bitrix\Main\Loader::includeModule('iblock'))
		{
			return;
		}
		self::removeNotifyToUser($folderInvite);

		\CWebDavDiskDispatcher::addElementForDeletingMark(
			array('ID' => $folderInvite['LINK_SECTION_ID'], 'IBLOCK_ID' => \CWebDavSymlinkHelper::getIblockIdForSectionId($folderInvite['LINK_SECTION_ID']))
		);
		\CWebDavDiskDispatcher::markDeleteBatch(false);

		\CIBlockSection::delete($folderInvite['LINK_SECTION_ID'], false);
		self::removeRightsOnSharedSections($folderInvite);

		return;
	}

	private static function removeNotifyToUser(array $folderInvite)
	{
		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			\CIMNotify::deleteByTag(self::getNotifyTag($folderInvite));
		}
		\CWebDavDiskDispatcher::sendChangeStatus($folderInvite['INVITE_USER_ID'], 'delete_symlink');
	}

	private static function removeRightsOnSharedSections(array $folderInvite)
	{
		if($folderInvite['INVITE_USER_ID'] == $folderInvite['USER_ID'])
		{
			return;
		}

		$rightsLetter = $folderInvite['CAN_EDIT']? 'W' : 'R';
		\CWebDavIblock::removeRightsOnSections(array(
			array(
				'ID' => $folderInvite['SECTION_ID'],
				'IBLOCK_ID' => $folderInvite['IBLOCK_ID'],
			),
		), array(
				$rightsLetter => array(
					'U' . $folderInvite['INVITE_USER_ID'],
				)
		));
	}

	public static function getNotifyTag(array $folderInvite)
	{
		return "WEBDAV|INVITE|{$folderInvite['ID']}|{$folderInvite['INVITE_USER_ID']}";
	}
}