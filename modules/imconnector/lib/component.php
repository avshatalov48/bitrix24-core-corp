<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Component
 * Helper class for working with components.
 * @package Bitrix\ImConnector
 */
class Component
{
	/**
	 * Returns the javascript that installs the language constants.
	 *
	 * @return string
	 */
	static public function getJsLangMessageSetting()
	{
		return '<script type="text/javascript">
			BX.message({
				IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE_TITLE: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE_TITLE') . '\',
				IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE') . '\',
				IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE_BUTTON_OK: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE_BUTTON_OK') . '\',
				IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE_BUTTON_CANCEL: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_CONFIRM_DISABLE_BUTTON_CANCEL') . '\',
				IMCONNECTOR_COMPONENT_SETTINGS_COPIED_TO_CLIPBOARD: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_COPIED_TO_CLIPBOARD') . '\',
				IMCONNECTOR_COMPONENT_SETTINGS_FAILED_TO_COPY: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_FAILED_TO_COPY') . '\'
			});
		</script>';
	}
}
