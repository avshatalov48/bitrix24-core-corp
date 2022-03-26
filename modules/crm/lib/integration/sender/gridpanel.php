<?php

namespace Bitrix\Crm\Integration\Sender;

use Bitrix\Main\Loader;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Sender;

/**
 * Class GridPanel
 *
 * @package Bitrix\Crm\Integration\Sender
 */
class GridPanel
{
	/**
	 * Return true if can use.
	 * @param array $actionList Action list.
	 * @param array $applyButton Apply button.
	 * @param string $gridManagerID Grid manager ID.
	 * @return void
	 */
	public static function appendActions(array &$actionList, array $applyButton, $gridManagerID)
	{
		if (!self::canCurrentUserUse())
		{
			return;
		}

		if (self::canCurrentUserAddLetters())
		{
			$actionList[] = self::getActionAddLetter($applyButton, $gridManagerID);
		}
		if (self::canCurrentUserModifySegments())
		{
			$actionList[] = self::getActionAddToSegment($applyButton, $gridManagerID);
		}
	}

	/**
	 * Return true if can use.
	 * @return bool
	 */
	public static function canUse()
	{
		return Loader::includeModule('sender');
	}

	/**
	 * Return true if current user can use.
	 * @return bool
	 */
	public static function canCurrentUserUse()
	{
		if (!self::canUse())
		{
			return false;
		}

		return (
			Sender\Security\Access::current()->canModifySegments()
			||
			Sender\Security\Access::current()->canModifyLetters()
		);
	}

	/**
	 * Return true if current user can modify segments.
	 * @return bool
	 */
	public static function canCurrentUserModifySegments()
	{
		if (!self::canUse())
		{
			return false;
		}

		return Sender\Security\Access::current()->canModifySegments();
	}

	/**
	 * Return true if current user can modify letters.
	 * @return bool
	 */
	public static function canCurrentUserAddLetters()
	{
		if (!self::canUse())
		{
			return false;
		}

		return (
			Sender\Security\Access::current()->canModifySegments()
			&&
			Sender\Security\Access::current()->canModifyLetters()
		);
	}

	/**
	 * Get action `add letter`.
	 *
	 * @param array $applyButton Apply button.
	 * @param string $gridManagerID Grid manager ID.
	 * @return array
	 */
	public static function getActionAddLetter(array $applyButton, $gridManagerID)
	{
		self::includeJsLibs();
		if (!Sender\Integration\Bitrix24\Service::isMailingsAvailable())
		{
			Sender\Integration\Bitrix24\Service::initLicensePopup();
		}

		$id = 'sender_letter_add';

		$letterTypes = array_map(
			function ($message)
			{
				/** @var Sender\Message\iBase $message */
				return ['NAME' => $message->getName(), 'VALUE' => $message->getCode()];
			},
			array_filter(
				Sender\Message\Factory::getMailingMessages(),
				function ($message)
				{
					/** @var Sender\Message\iBase $message */
					return $message->getCode() !== Sender\Message\iBase::CODE_IM;
				}
			)
		);
		sort($letterTypes);

		return [
			'NAME' => Loc::getMessage('CRM_INTEGRATION_SENDER_GRID_PANEL_ACTION_LETTER_ADD'),
			'VALUE' => $id,
			'ONCHANGE' => [
				[
					'ACTION' => Grid\Panel\Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Grid\Panel\Types::DROPDOWN,
							'ID' => 'sender_letter_code',
							'NAME' => 'SENDER_LETTER_CODE',
							'ITEMS' => $letterTypes
						],
						[
							'TYPE' => Grid\Panel\Types::HIDDEN,
							'ID' => 'sender_letter_available_codes',
							'NAME' => 'SENDER_LETTER_AVAILABLE_CODES',
							'VALUE' => implode(',', Sender\Integration\Bitrix24\Service::getAvailableMailingCodes()),
						],
						[
							'TYPE' => Grid\Panel\Types::HIDDEN,
							'ID' => 'sender_path_to_letter_add',
							'NAME' => 'SENDER_PATH_TO_LETTER_ADD',
							'VALUE' => self::getPathToAddLetter(),
						],
						$applyButton
					]
				],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', '{$id}')"]]
				]
			]
		];
	}

	/**
	 * Get action `Add to segment`.
	 *
	 * @param array $applyButton Apply button.
	 * @param string $gridManagerID Grid manager ID.
	 * @return array
	 */
	public static function getActionAddToSegment(array $applyButton, $gridManagerID)
	{
		self::includeJsLibs();

		$segments = array_map(
			function ($segment)
			{
				return ['NAME' => $segment['NAME'], 'VALUE' => $segment['ID']];
			},
			Sender\Entity\Segment::getList([
				'select' => ['ID', 'NAME'],
				'filter' => ['=HIDDEN' => 'N'],
				'order' => ['ID' => 'DESC']
			])->fetchAll()
		);
		$segments = array_merge(
			[[
				'NAME' => Loc::getMessage('CRM_INTEGRATION_SENDER_GRID_PANEL_ADD_NEW_SEGMENT'),
				'VALUE' => '',
			]],
			$segments
		);

		$id = 'sender_segment_add';
		return [
			'NAME' => Loc::getMessage('CRM_INTEGRATION_SENDER_GRID_PANEL_ACTION_SEGMENT_ADD'),
			'VALUE' => $id,
			'ONCHANGE' => [
				[
					'ACTION' => Grid\Panel\Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Grid\Panel\Types::DROPDOWN,
							'ID' => 'sender_segment_list',
							'NAME' => 'SENDER_SEGMENT_ID',
							'ITEMS' => $segments
						],
						[
							'TYPE' => Grid\Panel\Types::HIDDEN,
							'ID' => 'sender_path_to_segment_edit',
							'NAME' => 'SENDER_PATH_TO_SEGMENT_EDIT',
							'VALUE' => self::getPathToEditSegment(),
						],
						$applyButton
					]
				],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', '{$id}')"]]
				]
			]
		];
	}

	protected static function includeJsLibs()
	{
		Extension::load('ui.notification');
		\CJSCore::init('sidepanel');
	}

	/**
	 * Get path to `letter add` page.
	 * @return bool
	 */
	public static function getPathToAddLetter()
	{
		return "/marketing/letter/edit/0/?code=#code#&SEGMENTS_INCLUDE[]=#segment_id#&isOutside=Y";
	}

	/**
	 * Get path to `letter add` page.
	 * @return bool
	 */
	public static function getPathToEditSegment()
	{
		return "/marketing/segment/edit/#id#/";
	}
}
