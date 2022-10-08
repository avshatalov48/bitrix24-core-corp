<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton\Channel;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Script as WebFormScript;
use Bitrix\Crm\WebForm;

Loc::loadMessages(__FILE__);

/**
 * Class ChannelCallback
 * @package Bitrix\Crm\SiteButton\Channel
 */
class ChannelCallback implements iProvider
{
	/**
	 * Return true if it can be used.
	 *
	 * @return bool
	 */
	public static function canUse()
	{
		return (bool) Loader::includeModule('voximplant') && (bool) Loader::includeModule('crm');
	}

	/**
	 * Get presets.
	 *
	 * @return array
	 */
	public static function getPresets()
	{
		if (!self::canUse())
		{
			return array();
		}

		return FormTable::getDefaultTypeList(array(
			'select' => array('ID', 'NAME'),
			'filter' => array(
				'=ACTIVE' => 'Y',
				'=IS_CALLBACK_FORM' => 'Y',
				'=IS_SYSTEM' => 'Y'
			),
		))->fetchAll();
	}

	/**
	 * Get list.
	 *
	 * @return array
	 */
	public static function getList()
	{
		if (!self::canUse())
		{
			return array();
		}

		$enumList = array();
		$enumListDb = FormTable::getDefaultTypeList(array(
			'select' => array('ID', 'NAME', 'CALL_FROM'),
			'filter' => array(
				'=ACTIVE' => 'Y',
				'=IS_CALLBACK_FORM' => 'Y'
			),
		));
		while($enumItem = $enumListDb->fetch())
		{
			$workTime = null;
			$config = \CVoxImplantConfig::getConfigBySearchId($enumItem['CALL_FROM']);
			if ($config && $config['WORKTIME_ENABLE'] == 'Y')
			{
				$workTime = array(
					'ENABLED' => $config['WORKTIME_ENABLE'] == 'Y',
					'TIME_FROM' => (float) $config['WORKTIME_FROM'],
					'TIME_TO' => (float) $config['WORKTIME_TO'],
					'TIME_ZONE' => $config['WORKTIME_TIMEZONE'],
					'HOLIDAYS' => explode(',', $config['WORKTIME_HOLIDAYS']),
					'DAY_OFF' => explode(',', $config['WORKTIME_DAYOFF']),
					'ACTION_RULE' => '',
					'ACTION_TEXT' => '',
				);
			}
			$enumItem['WORK_TIME'] = $workTime;
			$enumItem['PHONE_NAME'] = $config['PHONE_NAME'];
			$enumList[] = $enumItem;
		}

		return $enumList;
	}

	/**
	 * Get widgets.
	 *
	 * @param string $id Channel id
	 * @param bool $removeCopyright Remove copyright
	 * @param string|null $lang Language ID
	 * @param array $config Config
	 * @return array
	 */
	public static function getWidgets($id, $removeCopyright = true, $lang = null, array $config = array())
	{
		Loc::loadMessages(__FILE__); // TODO: remove with dependence main: deeply lazy Load loc files
		if (!self::canUse())
		{
			return array();
		}

		$widgets = array();

		$type = self::getType();
		$widget = array(
			'id' => $type,
			'title' => Loc::getMessage('CRM_BUTTON_MANAGER_TYPE_NAME_'.mb_strtoupper($type)),
			'script' => WebFormScript::getCrmButtonWidget(
				$id,
				array(
					'IS_CALLBACK' => true,
					'REMOVE_COPYRIGHT' => $removeCopyright,
					'LANGUAGE_ID' => $lang
				)
			),
			'freeze' => WebForm\Manager::isEmbeddingEnabled($id),
			'sort' => 200,
			'useColors' => true,
			'classList' => array('b24-widget-button-' . $type),
			'show' => WebFormScript::getCrmButtonWidgetShower(
				$id, $lang,
				[
					'siteButton' => true,
				]
			),
			'hide' => WebFormScript::getCrmButtonWidgetHider($id),
		);
		$widgets[] = $widget;

		return $widgets;
	}


	/**
	 * Get resources.
	 *
	 * @return \Bitrix\Main\Web\WebPacker\Resource\Asset[]
	 */
	public static function getResources()
	{
		return [];
	}

	/**
	 * Get edit path.
	 *
	 * @return array
	 */
	public static function getPathEdit()
	{
		return array(
			'path' => Option::get('crm', 'path_to_webform_edit', ''),
			'id' => '#form_id#'
		);
	}

	/**
	 * Get add path.
	 * @return string
	 */
	public static function getPathAdd()
	{
		return str_replace('#form_id#', '0', Option::get('crm', 'path_to_webform_edit', ''));
	}

	/**
	 * Get list path.
	 *
	 * @return string
	 */
	public static function getPathList()
	{
		return Option::get('crm', 'path_to_webform_list', '');
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_BUTTON_MANAGER_TYPE_NAME_'.mb_strtoupper(self::getType()));
	}

	/**
	 * Get type.
	 *
	 * @return string
	 */
	public static function getType()
	{
		return 'callback';
	}
}
