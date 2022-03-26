<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class WebFormSettings
 * @package Bitrix\Crm\Settings
 */
class WebFormSettings
{
	const EditorConfirm = 0;
	const EditorCrm = 1;
	const EditorLanding = 2;

	/** @var static  */
	private static $current = null;

	/** @var IntegerSetting  */
	private $editor;

	private static $editorItems = null;

	function __construct()
	{
		$this->editor = new IntegerSetting('webform_editor', 0);
	}

	/**
	 * @return static
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new static();
		}
		return self::$current;
	}

	/**
	 * Get editor ID.
	 *
	 * @return bool
	 */
	public function getEditorId()
	{
		return self::EditorLanding;
	}

	/**
	 * Return true if new editor enabled.
	 *
	 * @return bool
	 */
	public function isNewEditorEnabled()
	{
		return true;
	}

	/**
	 * Return true if new editor enabled.
	 *
	 * @return bool
	 */
	public function isEditorConfirmEnabled()
	{
		return !$this->getEditorId();
	}

	/**
	 * Set editor ID.
	 *
	 * @return void
	 */
	public function setEditorId($id)
	{
		$id = (int) $id;
		$id = ($id > 2 || $id < 0) ? 0 : $id;
		$this->editor->set($id);
	}

	/**
	 * Get editor items.
	 *
	 * @return array
	 */
	public static function getEditorItems()
	{
		if(self::$editorItems === null)
		{
			self::$editorItems = array(
				self::EditorConfirm => Loc::getMessage('CRM_SETTINGS_WEBFORM_EDITOR_CONFIRM'),
				self::EditorLanding => Loc::getMessage('CRM_SETTINGS_WEBFORM_EDITOR_NEW'),
				self::EditorCrm => Loc::getMessage('CRM_SETTINGS_WEBFORM_EDITOR_OLD'),
			);
		}

		return self::$editorItems;
	}
}