<?php
namespace Bitrix\Tasks\CheckList\Template;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\AnalyticLogger;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable;
use Bitrix\Tasks\Internals\Task\Template\CheckListTable;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Util;
use CTaskAssert;

/**
 * Class TemplateCheckListFacade
 *
 * @package Bitrix\Tasks\CheckList\Template
 */
class TemplateCheckListFacade extends CheckListFacade
{
	protected static $selectFields = [
		'ID',
		'TEMPLATE_ID',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'MEMBERS',
		'ATTACHMENTS',
	];
	protected static $filterFields = [
		'ID',
		'TEMPLATE_ID',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
	];
	protected static $orderFields = [
		'ID',
		'TEMPLATE_ID',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
	];

	public static $entityIdName = 'TEMPLATE_ID';
	public static $userFieldsEntityIdName = 'TASKS_TASK_TEMPLATE_CHECKLIST';
	public static $commonAccessActions;
	public static $itemAccessActions;

	/**
	 * Returns class that extends abstract class CheckListTree.
	 * @see CheckListTree
	 *
	 * @return string
	 */
	public static function getCheckListTree()
	{
		return TemplateCheckListTree::class;
	}

	/**
	 * Returns table class for checklist table.
	 *
	 * @return string
	 */
	public static function getCheckListDataController()
	{
		return CheckListTable::getClass();
	}

	/**
	 * Returns table class for checklist tree table.
	 *
	 * @return string
	 */
	public static function getCheckListTreeDataController()
	{
		return TemplateCheckListTree::getDataController();
	}

	/**
	 * Returns table class for checklist member table.
	 *
	 * @return string
	 */
	public static function getCheckListMemberDataController()
	{
		return MemberTable::getClass();
	}

	/**
	 * Returns checklists with actions for entity if entity is accessible for reading.
	 *
	 * @param int $templateId
	 * @param int $userId
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function getItemsForEntity($templateId, $userId)
	{
		$items = false;
		$template = new Template($templateId, $userId);

		$canRead = \Bitrix\Tasks\Access\TemplateAccessController::can((int) $userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_READ, (int) $templateId);

		if ($template !== null && $canRead)
		{
			$items = static::getByEntityId($templateId);
			$items = static::fillActionsForItems($templateId, $userId, $items);
		}

		return $items;
	}

	/**
	 * Does some actions after merging checklists.
	 *
	 * @param int $templateId
	 * @param int $userId
	 * @param array $data
	 */
	public static function doMergePostActions($templateId, $userId, $data = [])
	{
		static::logToAnalyticsFile($data['PARAMETERS']);
	}

	/**
	 * Returns array of fields suitable for table data adding or updating.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function getFieldsForTable($fields)
	{
		return [
			'TEMPLATE_ID' => $fields['ENTITY_ID'],
			'TITLE' => $fields['TITLE'],
			'SORT_INDEX' => $fields['SORT_INDEX'],
			'IS_COMPLETE' => $fields['IS_COMPLETE'],
			'IS_IMPORTANT' => $fields['IS_IMPORTANT'],
		];
	}

	/**
	 * @param int $templateId
	 * @param int $userId
	 * @return void
	 */
	protected static function fillCommonAccessActions($templateId, $userId)
	{
		$actions = array_keys(self::ACTIONS['COMMON']);
		$canUpdate = \Bitrix\Tasks\Access\TemplateAccessController::can((int) $userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_EDIT, (int) $templateId);

		static::$commonAccessActions[$templateId][$userId] = array_fill_keys($actions, $canUpdate);
	}

	/**
	 * @param int $templateId
	 * @param CheckList $checkList
	 * @param int $userId
	 * @return void
	 */
	protected static function fillItemAccessActions($templateId, $checkList, $userId)
	{
		$actions = array_keys(self::ACTIONS['ITEM']);
		$checkListId = $checkList->getFields()['ID'];
		$canUpdate = \Bitrix\Tasks\Access\TemplateAccessController::can((int) $userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_EDIT, (int) $templateId);
		static::$itemAccessActions[$templateId][$userId][$checkListId] = array_fill_keys($actions, $canUpdate);
	}

	/**
	 * Logs error message.
	 *
	 * @param string $message
	 */
	public static function logError($message)
	{
		CTaskAssert::logError($message);
		Util::log($message);
	}

	/**
	 * Logs checklist changes to analytics file.
	 *
	 * @param array $parameters
	 */
	private static function logToAnalyticsFile($parameters)
	{
		$analyticsData = $parameters['analyticsData'];

		if (is_array($analyticsData) && !empty($analyticsData))
		{
			foreach ($analyticsData as $action => $value)
			{
				$tag = '';
				$label = '';

				if ($action === 'checklistCount')
				{
					$action = 'saveChecklist';
					$label = $value;
				}

				AnalyticLogger::logToFile($action, $tag, $label);
			}
		}
	}

	protected static function getAccessControllerClass(): string
	{
		return TemplateAccessController::class;
	}
}