<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Rest\RestException;
use Bitrix\Crm\UI\Webpack;

/**
 * Class Rest
 * @package Bitrix\Crm\SiteButton
 */
class Rest
{
	public static function onRestServiceBuildDescription(): array
	{
		return [
			'crm' => [
				'crm.button.list' => [__CLASS__, 'getButtonList'],
				'crm.button.widgets.get' => [__CLASS__, 'getButtonWidgets'],
				'crm.button.guest.register' => [__CLASS__, 'registerGuest'],
			],
		];
	}

	/** @noinspection PhpUnused */
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
				'ENTITY_ID' => $entity['ENTITY_ID'],
			);
			$isWrongFormatEntity = false;
		}

		if ($isWrongFormatEntity)
		{
			throw new RestException('Wrong format of parameter `ENTITIES`.');
		}

		return Guest::register($data);
	}

	/** @noinspection PhpUnused */
	public static function getButtonList(): array
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

	/** @noinspection PhpUnused */
	public static function getButtonWidgets($params): ?array
	{
		if (!isset($params['ID']))
		{
			throw new RestException('Wrong format of parameter ID.');
		}

		$button = Webpack\Button::instance($params['ID']);
		$button->configure();
		$widgets = $button->getWidgets();

		$resources = [];
		foreach ($button->getWidgetResources() as $resource)
		{
			$resources[] = [
				'path' => $resource->getPath(),
				'type' => $resource->getType(),
			];
		}

		return (!empty($widgets) && !empty($resources)) ?
			[
				'widgets' => $widgets,
				'resources' => $resources,
			]
			: null;
	}
}
