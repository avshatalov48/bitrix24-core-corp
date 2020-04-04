<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Rest\RestException;

/**
 * Class Rest
 * @package Bitrix\Crm\SiteButton
 */
class Rest
{
	public static function onRestServiceBuildDescription()
	{
		return array(
			'crm' => array(
				'crm.button.list' => array(__CLASS__, 'getButtonList'),
				'crm.button.guest.register' => array(__CLASS__, 'registerGuest'),
			)
		);
	}

	public static function registerGuest($params)
	{
		if(!Manager::checkWritePermission())
		{
			throw new RestException('Access denied.');
		}

		$data = array();

		$data['ENTITIES'] = array();
		if (!isset($params['ENTITIES']) || !is_array($params['ENTITIES']))
		{
			throw new RestException('Wrong format of parameter ENTITIES.');
		}

		$isWrongFormatEntity = true;
		foreach ($params['ENTITIES'] as $entity)
		{
			$isWrongFormatEntity = true;
			if (!is_array($entity))
			{
				break;
			}

			if (!isset($entity['ENTITY_TYPE_ID']) || !is_numeric($entity['ENTITY_TYPE_ID']))
			{
				break;
			}

			if (!isset($entity['ENTITY_ID']) || !is_numeric($entity['ENTITY_ID']))
			{
				break;
			}

			$data['ENTITIES'][] = array(
				'ENTITY_TYPE_ID' => $entity['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $entity['ENTITY_ID']
			);
			$isWrongFormatEntity = false;
		}

		if ($isWrongFormatEntity)
		{
			throw new RestException('Wrong format of parameter `ENTITIES`.');
		}

		return Guest::register($data);
	}

	public static function getButtonList()
	{
		if (Preset::checkVersion())
		{
			$preset = new Preset();
			$preset->install();
		}

		$result = array();
		$buttonList = Manager::getList(array(
			'filter' => array('=ACTIVE' => 'Y'),
			'order' => array('ID' => 'DESC')
		));
		foreach ($buttonList as $button)
		{
			$button['DATE_CREATE'] = \CRestUtil::ConvertDateTime($button['DATE_CREATE']);
			$button['ACTIVE_CHANGE_DATE'] = \CRestUtil::ConvertDateTime($button['ACTIVE_CHANGE_DATE']);
			$result[] = $button;
		}

		return $result;
	}

}
