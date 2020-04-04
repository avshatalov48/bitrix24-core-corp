<?
/**
 * Class implements all further interactions with "disk" module considering "task" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Disk;

class UserField extends \Bitrix\Tasks\Integration\Disk
{
	/**
	 * Returns a code of the main system field for this entity
	 * @return string
	 */
	public static function getMainSysUFCode()
	{
		return 'UF_TASK_WEBDAV_FILES';
	}

	public static function getSysUFScheme()
	{
		if(!static::isInstalled())
		{
			return array();
		}

		return array(
			static::getMainSysUFCode() => array(
				'FIELD_NAME'    => static::getMainSysUFCode(),
				'USER_TYPE_ID'  => 'disk_file',
				'XML_ID'        => 'TASK_WEBDAV_FILES',
				'MULTIPLE'      => 'Y',
				'MANDATORY'     =>  null,
				'SHOW_FILTER'   => 'N',
				'SHOW_IN_LIST'  =>  null,
				'EDIT_IN_LIST'  =>  null,
				'IS_SEARCHABLE' =>  null,
				'SETTINGS'      =>  array(
					'IBLOCK_TYPE_ID'        => '0',
					'IBLOCK_ID'             => '',
					'UF_TO_SAVE_ALLOW_EDIT' => '',
				),
				'EDIT_FORM_LABEL' => array(
					'en' => 'Load files',
					'ru' => 'Load files',
					'de' => 'Load files'
				),
			)
		);
	}
}