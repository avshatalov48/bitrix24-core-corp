<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager;

use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Integration\Extranet;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Integration\Timeman;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Manager\Task\Accomplice;
use Bitrix\Tasks\Manager\Task\Auditor;
use Bitrix\Tasks\Manager\Task\Checklist;
use Bitrix\Tasks\Manager\Task\Description;
use Bitrix\Tasks\Manager\Task\ElapsedTime;
use Bitrix\Tasks\Manager\Task\Log;
use Bitrix\Tasks\Manager\Task\Originator;
use Bitrix\Tasks\Manager\Task\ParentTask;
use Bitrix\Tasks\Manager\Task\Project;
use Bitrix\Tasks\Manager\Task\ProjectDependence;
use Bitrix\Tasks\Manager\Task\RelatedTask;
use Bitrix\Tasks\Manager\Task\Reminder;
use Bitrix\Tasks\Manager\Task\Responsible;
use Bitrix\Tasks\Manager\Task\Tag;
use Bitrix\Tasks\Manager\Task\TaskWebdavFiles;
use Bitrix\Tasks\Manager\Task\Template;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\UserField\Task as UserField;
use TasksException;

final class Task extends \Bitrix\Tasks\Manager
{
	// standard CRUD

	public const UF_PREFIX = 'UF_';

	/**
	 * @param int $userId
	 * @param mixed[] $data
	 * @param mixed[] $parameters
	 *        <li>PUBLIC_MODE
	 *        <li>SOURCE
	 *            <li> TYPE (TEMPLATE or TASK)
	 *            <li> ID
	 */
	public static function add($userId, array $data, array $parameters = array('PUBLIC_MODE' => false, 'RETURN_ENTITY' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		$task = null;
		$can = array();

		if ($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, static::getFieldMap(), $errors);
		}
		$parameters['ANALYTICS_DATA'] = static::getAnalyticsData($data);

		if ($errors->checkNoFatals())
		{
			$cacheAFWasDisabled = \CTasks::disableCacheAutoClear();
			$notifADWasDisabled = \CTaskNotifications::disableAutoDeliver();

			$task = static::doAdd($userId, $data, $parameters);

			if ($notifADWasDisabled)
			{
				\CTaskNotifications::enableAutoDeliver();
			}
			if ($cacheAFWasDisabled)
			{
				\CTasks::enableCacheAutoClear();
			}

			if ($errors->checkNoFatals())
			{
				$data = array('ID' => $task->getId());

				if ($parameters[ 'RETURN_ENTITY' ])
				{
					try
					{
						$data = $task->getData(false);
						$data[ static::ACT_KEY ] = $can = static::translateAllowedActionNames($task->getAllowedActions(true));
					}
					catch (TasksException $e)
					{

					}
				}
			}
		}

		return array(
			'TASK' => $task,
			'ERRORS' => $errors,
			'DATA' => $data,
			'CAN' => $can
		);
	}

	private static function getFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		$fieldMap = \CTasks::getPublicFieldMap();

		$fieldMap[ 'REPLICATE' ] = array(1, 1, 0, 0, 0); // not allowed in rest, but allowed here
		$fieldMap[ 'MULTITASK' ] = array(1, 1, 0, 0, 0); // not allowed in rest, but allowed here
		$fieldMap[ 'ADD_TO_FAVORITE' ] = array(0, 1, 0, 0, 0); // virtual, for add() only
		$fieldMap[ 'ADD_TO_TIMEMAN' ] = array(0, 1, 0, 0, 0); // virtual, for add() only

		$fieldMap[ 'RESPONSIBLES' ] = array(0, 1, 0, 0, 0); // just for compatibility

		$fieldMap[ 'IM_CHAT_ID' ] = array(0, 1, 0, 0, 0);
		$fieldMap[ 'IM_MESSAGE_ID' ] = array(0, 1, 0, 0, 0);

		$fieldMap['TASKS_ANALYTICS_SECTION'] = array(0, 1, 0, 0, 0);
		$fieldMap['TASKS_ANALYTICS_SUB_SECTION'] = array(0, 1, 0, 0, 0);
		$fieldMap['TASKS_ANALYTICS_ELEMENT'] = array(0, 1, 0, 0, 0);
		$fieldMap['TASKS_ANALYTICS_PARAMS'] = array(0, 1, 0, 0, 0);
		$fieldMap['TASKS_ANALYTICS_CATEGORY'] = array(0, 1, 0, 0, 0);

		return $fieldMap;
	}

	private static function doAdd($userId, array $data, array $parameters)
	{
		$userId = (int)$userId;

		$errors = static::ensureHaveErrorCollection($parameters);
		$data = static::normalizeData($data);

		static::inviteMembers($data, $errors);
		static::adaptSet($data);
		static::ensureDatePlanChangeAllowed($userId, $data);
		static::setDefaultUFValues($userId, $data);

		$task = \CTaskItem::add(static::stripSubEntityData($data), $userId, $parameters);
		$taskId = $task->getId();

		if ($taskId)
		{
			$commentPoster = Comments\Task\CommentPoster::getInstance($taskId, $userId);
			$commentPoster->enableDeferredPostMode();
			$commentPoster->clearComments();

			if (
				($data['ADD_TO_TIMEMAN'] ?? null) === 'Y'
				&& $userId === (int)$data['RESPONSIBLE_ID']
				&& $userId === User::getId()
				&& !Extranet\User::isExtranet($userId)
			)
			{
				// add the task to planner only if the user this method executed under is current and responsible for the task
				\CTaskPlannerMaintance::plannerActions(['add' => [$taskId]]);
			}
			if (($data['ADD_TO_FAVORITE'] ?? null) === "Y")
			{
				$task->addToFavorite();
			}

			// add sub-entities (SE_*)
			$subEntityParams = array_merge($parameters, ['MODE' => static::MODE_ADD]);

			if (array_key_exists(Reminder::getCode(true), $data))
			{
				Reminder::manageSet($userId, $taskId, $data[ Reminder::getCode(true) ], $subEntityParams);
			}

			if (array_key_exists(ProjectDependence::getCode(true), $data))
			{
				ProjectDependence::manageSet($userId, $taskId, $data[ ProjectDependence::getCode(true) ], $subEntityParams);
			}

			if (array_key_exists(Checklist::getCode(true), $data))
			{
				TaskCheckListFacade::merge(
					$taskId,
					$userId,
					$data[CheckList::getCode(true)],
					[
						'analyticsData' => $parameters['ANALYTICS_DATA'],
						'context' => CheckListFacade::TASK_ADD_CONTEXT,
					]
				);
			}

			Template::manageTaskReplication($userId, $taskId, $data, $subEntityParams);
		}

		return $task;
	}

	public static function normalizeData($data)
	{
		if (!is_array($data) || empty($data))
		{
			return array();
		}

		foreach ($data as $k => $v)
		{
			if ($seName = static::checkIsSubEntityKey($k))
			{
				$fName = __NAMESPACE__ . '\\Task\\' . $seName . '::normalizeData';
				if (is_callable($fName))
				{
					$data[ $k ] = call_user_func_array($fName, array($v));
				}
			}
		}

		return $data;
	}

	private static function inviteMembers(&$data, Collection $errors)
	{
		//Originator::inviteMembers($data, $errors); // we may not invite originator
		Auditor::inviteMembers($data, $errors);
		Accomplice::inviteMembers($data, $errors);
		Responsible::inviteMembers($data, $errors);
	}

	private static function adaptSet(&$data)
	{
		Originator::adaptSet($data);
		Auditor::adaptSet($data);
		Accomplice::adaptSet($data);
		Tag::adaptSet($data);
		CheckList::adaptSet($data);
		RelatedTask::adaptSet($data);
		ParentTask::adaptSet($data);
		Project::adaptSet($data);

		// special case: responsibles
		Responsible::adaptSet($data);
		if (is_array($data[Responsible::getLegacyFieldName()] ?? null))
		{
			$data[ Responsible::getLegacyFieldName() ] = array_shift($data[ Responsible::getLegacyFieldName() ]);
		}
	}

	// specific functionality

	private static function ensureDatePlanChangeAllowed($userId, array &$data)
	{
		$projdepKey = ProjectDependence::getCode(true);

		// smth is meant to be added in project dependency, thus we must enable ALLOW_CHANGE_DEADLINE for the task
		// todo: this is required for making dependencies in case of task update with rights loose. remove this when AUTHOR_ID field introduced
		if (array_key_exists($projdepKey, $data) && !empty($data[ $projdepKey ]) && $userId == $data[ 'RESPONSIBLE_ID' ])
		{
			$data[ 'ALLOW_CHANGE_DEADLINE' ] = 'Y';
		}
	}

	private static function setDefaultUFValues($userId, array &$data)
	{
		$scheme = UserField::getScheme(0, $userId);

		foreach ($scheme as $field => $desc)
		{
			if (!array_key_exists($field, $data))
			{
				$default = UserField::getDefaultValue($field, $userId);
				if ($default !== null)
				{
					$data[ $field ] = $default;
				}
			}
		}
	}

	private static function translateAllowedActionNames($can)
	{
		$newCan = array();
		if (is_array($can))
		{
			foreach ($can as $act => $flag)
			{
				$newCan[ str_replace('ACTION_', '', $act) ] = $flag;
			}

			static::replaceKey($newCan, 'CHANGE_DIRECTOR', 'EDIT.ORIGINATOR');
			static::replaceKey($newCan, 'CHECKLIST_REORDER_ITEMS', 'CHECKLIST.REORDER');
			static::replaceKey($newCan, 'ELAPSED_TIME_ADD', 'ELAPSEDTIME.ADD');
			static::replaceKey($newCan, 'START_TIME_TRACKING', 'DAYPLAN.TIMER.TOGGLE');

			// todo: when mobile stops using this fields, remove the third argument here
			static::replaceKey($newCan, 'CHANGE_DEADLINE', 'EDIT.PLAN', false); // used in mobile already
			static::replaceKey($newCan, 'CHECKLIST_ADD_ITEMS', 'CHECKLIST.ADD', false); // used in mobile already
			static::replaceKey($newCan, 'ADD_FAVORITE', 'FAVORITE.ADD', false); // used in mobile already
			static::replaceKey($newCan, 'DELETE_FAVORITE', 'FAVORITE.DELETE', false); // used in mobile already
		}

		return $newCan;
	}

	private static function replaceKey(array &$data, $from, $to, $dropFrom = true)
	{
		if (array_key_exists($from, $data))
		{
			$data[ $to ] = $data[ $from ];
			if ($dropFrom)
			{
				unset($data[ $from ]);
			}
		}
	}

	// private methods

	public static function update($userId, $taskId, array $data, array $parameters = array('PUBLIC_MODE' => false, 'RETURN_ENTITY' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		$task = null;
		$can = array();

		if ($parameters[ 'PUBLIC_MODE' ])
		{
			$data = static::filterData($data, static::getFieldMap(), $errors);
		}

		if ($errors->checkNoFatals())
		{
			$cacheAFWasDisabled = \CTasks::disableCacheAutoClear();
			$notifADWasDisabled = \CTaskNotifications::disableAutoDeliver();

			$updateParams = [
				'TASK_ACTION_UPDATE_PARAMETERS' => [
					'THROTTLE_MESSAGES' => ($parameters['THROTTLE_MESSAGES'] ?? null),
				],
				'PUBLIC_MODE' => $parameters['PUBLIC_MODE'],
				'ERRORS' => $errors,
				'ANALYTICS_DATA' => static::getAnalyticsData($data),
			];

			$task = static::doUpdate($userId, $taskId, $data, $updateParams);

			if ($notifADWasDisabled)
			{
				\CTaskNotifications::enableAutoDeliver();
			}
			if ($cacheAFWasDisabled)
			{
				\CTasks::enableCacheAutoClear();
			}

			if ($errors->checkNoFatals())
			{
				$data = ['ID' => $task->getId()];

				if ($parameters['RETURN_ENTITY'] ?? null)
				{
					try
					{
						$data = $task->getData(false);
						$data[ static::ACT_KEY ] = $can = static::translateAllowedActionNames($task->getAllowedActions(true));
					}
					catch (TasksException $e)
					{

					}
				}
			}
		}

		return array(
			'TASK' => $task,
			'ERRORS' => $errors,
			'DATA' => $data,
			'CAN' => $can
		);
	}

	private static function doUpdate($userId, $taskId, array $data, array $parameters)
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$task = static::getTask($userId, $taskId);

		$data = static::normalizeData($data);

		static::inviteMembers($data, $errors);
		static::adaptSet($data);

		if (!is_array($parameters[ 'TASK_ACTION_UPDATE_PARAMETERS' ]))
		{
			$parameters[ 'TASK_ACTION_UPDATE_PARAMETERS' ] = array();
		}

		static::ensureDatePlanChangeAllowed($userId, $data);
		$cleanData = static::stripSubEntityData($data);

		$commentPoster = Comments\Task\CommentPoster::getInstance($taskId, $userId);
		$commentPoster->clearComments();

		$action = ActionDictionary::ACTION_TASK_EDIT;

		// under some conditions we may loose rights (for edit or read, or both) during update, so a little trick is needed
		$canEditBefore = $task->checkAccess($action); // get our rights before doing anything
		if (!empty($cleanData))
		{
			$commentPoster->enableDeferredPostMode();
			$task->update($cleanData, $parameters['TASK_ACTION_UPDATE_PARAMETERS']); // do not check return result, because method will throw an exception on error
		}

		$canReadAfter = $task->checkCanRead();
		$canEditAfter = $canReadAfter && $task->checkAccess($action);
		$rightsLost = $canEditBefore && !$canEditAfter;
		$adminUserId = \Bitrix\Tasks\Util\User::getAdminId();

		// if we have had rights before, but have lost them now, do the rest of update under superuser`s rights, or else continue normally
		// todo: instead of replacing userId make option "skipRights under current user"
		$continueAs = $rightsLost ? $adminUserId : $userId;

		if (!$canReadAfter) // at least become an auditor for that task
		{
			$sameTask = \CTaskItem::getInstance($taskId, $adminUserId);
			$sameTask->startWatch($userId);
		}

		// update sub-entities (SE_*)
		$subEntityParams = array_merge(
			$parameters, array('MODE' => static::MODE_UPDATE, 'ERROR' => $errors)
		);

		if (array_key_exists(Reminder::getCode(true), $data))
		{
			Reminder::manageSet($userId, $taskId, $data[ Reminder::getCode(true) ], $subEntityParams);
		}

		if (array_key_exists(ProjectDependence::getCode(true), $data))
		{
			ProjectDependence::manageSet($continueAs, $taskId, $data[ ProjectDependence::getCode(true) ], $subEntityParams);
		}

		if (array_key_exists(Checklist::getCode(true), $data))
		{
			TaskCheckListFacade::merge(
				$taskId,
				$continueAs,
				$data[Checklist::getCode(true)],
				['analyticsData' => $parameters['ANALYTICS_DATA']]
			);
		}

		Template::manageTaskReplication($userId, $taskId, $data, $subEntityParams);

		$commentPoster->postComments();
		$commentPoster->clearComments();

		return $task;
	}

	public static function convertFromItem($item)
	{
		if (!\Bitrix\Tasks\Item::isA($item))
		{
			return array();
		}

		$data = $item->getArray();

		// do some transformations...
		Originator::formatSet($data);
		Auditor::formatSet($data);
		Accomplice::formatSet($data);
		ParentTask::formatSet($data);
		Project::formatSet($data);
		Tag::formatSet($data);
		RelatedTask::formatSet($data);

		// special case for checklist... i hate special cases...
		CheckList::parseSet($data);

		return $data;
	}

	public static function makeItem($data, $userId = 0)
	{
		Originator::adaptSet($data);
		Auditor::adaptSet($data);
		Accomplice::adaptSet($data);
		ParentTask::adaptSet($data);
		Project::adaptSet($data);

		Tag::adaptSet($data);
		RelatedTask::adaptSet($data);

		return new \Bitrix\Tasks\Item\Task($data, $userId);
	}

	public static function get($userId, $taskId, array $parameters = array())
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		// todo: filterArguments() and filterResult() here on public mode?

		$data = static::getBasicData($userId, $taskId, $parameters);
		$can = array();

		if ($errors->checkNoFatals())
		{
			$can = array(static::ACT_KEY => &$data[ static::ACT_KEY ]); // for compatibility

			// select sub-entity related data
			$parameters['ENTITY_SELECT'] = $parameters['ENTITY_SELECT'] ?? null;
			if (!is_array($parameters['ENTITY_SELECT']))
			{
				// by default none is selected
				$parameters[ 'ENTITY_SELECT' ] = array();
				// could be of static::getLegalSubEntities()
			}
			$entitySelect = array_flip($parameters[ 'ENTITY_SELECT' ]);

			Originator::formatSet($data);
			Auditor::formatSet($data);
			Accomplice::formatSet($data);
			ParentTask::formatSet($data);
			Project::formatSet($data);

			// special case: responsibles
			$data[ Responsible::getCode(true) ] = array(array('ID' => $data[ 'RESPONSIBLE_ID' ]));

			$code = Tag::getCode(true);
			if (isset($entitySelect[ Tag::getCode() ]))
			{
				if (array_key_exists('TAGS', $data) && is_array($data['TAGS']))
				{
					$can[$code] = [];
					foreach ($data['TAGS'] as $tag)
					{
						$data[$code][] = ['NAME' => $tag];
					}
				}
				else
				{
					$mgrResult = Tag::getList($userId, $taskId);
					$data[ $code ] = $mgrResult[ 'DATA' ];
					if (!empty($mgrResult[ 'CAN' ]))
					{
						$can[ $code ] = $mgrResult[ 'CAN' ];
					}

					Tag::adaptSet($data); // for compatibility
				}
			}

			$code = Checklist::getCode(true);
			if (isset($entitySelect[ 'CHECKLIST' ]))
			{
				if (array_key_exists('CHECKLIST', $data) && is_array($data['CHECKLIST']))
				{
					$data['CHECKLIST'] = TaskCheckListFacade::fillActionsForItems($taskId, $userId, $data['CHECKLIST']);
					$data[$code] = $data['CHECKLIST'];
				}
				else
				{
					$mgrResult = TaskCheckListFacade::getItemsForEntity($taskId, $userId);
					$data[$code] = $mgrResult;
				}

				foreach ($data[$code] as $id => $item)
				{
					$can[$code][$id]['ACTION'] = $item['ACTION'];
				}
			}

			if (isset($entitySelect[ 'REMINDER' ]))
			{
				$mgrResult = Reminder::getListByParentEntity($userId, $taskId, $parameters);
				$data[ static::SE_PREFIX . 'REMINDER' ] = $mgrResult[ 'DATA' ];
				if (!empty($mgrResult[ 'CAN' ]))
				{
					$can[ static::SE_PREFIX . 'REMINDER' ] = $mgrResult[ 'CAN' ];
				}
			}

			if (isset($entitySelect[ 'LOG' ]))
			{
				$mgrResult = Log::getListByParentEntity($userId, $taskId, $parameters);
				$data[ static::SE_PREFIX . 'LOG' ] = $mgrResult[ 'DATA' ];
				if (!empty($mgrResult[ 'CAN' ]))
				{
					$can[ static::SE_PREFIX . 'LOG' ] = $mgrResult[ 'CAN' ];
				}
			}

			if (isset($entitySelect[ 'ELAPSEDTIME' ]))
			{
				$mgrResult = ElapsedTime::getListByParentEntity($userId, $taskId, $parameters);
				$data[ static::SE_PREFIX . 'ELAPSEDTIME' ] = $mgrResult[ 'DATA' ];
				if (!empty($mgrResult[ 'CAN' ]))
				{
					$can[ static::SE_PREFIX . 'ELAPSEDTIME' ] = $mgrResult[ 'CAN' ];
				}
			}

			if (isset($entitySelect[ 'PROJECTDEPENDENCE' ]))
			{
				$mgrResult = ProjectDependence::getListByParentEntity($userId, $taskId, array_merge($parameters, array(
					'TYPE' => ProjectDependence::INGOING,
					'DIRECT' => true,
					'DEPENDS_ON_DATA' => true
				)));
				$data[ static::SE_PREFIX . 'PROJECTDEPENDENCE' ] = $mgrResult[ 'DATA' ];
				if (!empty($mgrResult[ 'CAN' ]))
				{
					$can[ static::SE_PREFIX . 'PROJECTDEPENDENCE' ] = $mgrResult[ 'CAN' ];
				}
			}

			if (isset($entitySelect[ 'TEMPLATE' ]))
			{
				if ($data[ 'REPLICATE' ] == 'Y')
				{
					$template = Template::getByParentTask($userId, $taskId);
					$data[ static::SE_PREFIX . 'TEMPLATE' ] = $template[ 'DATA' ];
				}
			}

			if (isset($entitySelect[ 'TEMPLATE.SOURCE' ]))
			{
				if (intval($data[ 'FORKED_BY_TEMPLATE_ID' ]))
				{
					$template = Template::get($userId, intval($data[ 'FORKED_BY_TEMPLATE_ID' ]));

					// todo: remove this
					$tData = $template[ 'DATA' ];
					if (!empty($tData))
					{
						$tData = array(
							'ID' => $tData[ 'ID' ],
							'TITLE' => $tData[ 'TITLE' ],
							'TASK_ID' => $tData[ 'TASK_ID' ],
							'TPARAM_TYPE' => $tData[ 'TPARAM_TYPE' ],
							'REPLICATE_PARAMS' => $tData[ 'REPLICATE_PARAMS' ]
						);
					}
					$data[ static::SE_PREFIX . 'TEMPLATE.SOURCE' ] = $tData;
				}
			}

			if (isset($entitySelect[ 'RELATEDTASK' ]))
			{
				$mgrResult = RelatedTask::getListByParentEntity($userId, $taskId, $parameters);
				$data[ static::SE_PREFIX . 'RELATEDTASK' ] = $mgrResult[ 'DATA' ];
				if (!empty($mgrResult[ 'CAN' ]))
				{
					$can[ static::SE_PREFIX . 'RELATEDTASK' ] = $mgrResult[ 'CAN' ];
				}
			}

			if (isset($entitySelect[ 'TIMEMANAGER' ]) || isset($entitySelect[ 'DAYPLAN' ])) // 'TIMEMANAGER' condition left for compatibility
			{
				$subData = array($data[ 'ID' ] => &$data);
				$subCan = array($data[ 'ID' ] => &$can);
				static::injectDayPlanFields($userId, $parameters, $subData, $subCan);
			}

			$data['FLOW_ID'] = (int)$data['FLOW_ID'];
		}

		return array(
			'DATA' => $data,
			'CAN' => $can, // for compatibility
			'ERRORS' => $errors
		);
	}

	private static function getBasicData($userId, $taskId, array $parameters)
	{
		$data = array();
		$denied = false;

		try
		{
			$task = static::getTask($userId, $taskId);
			$taskParameters = array();

			if ($task !== null)
			{
				$data = $task->getData((bool)($parameters['ESCAPE_DATA'] ?? null));
				$data[ static::ACT_KEY ] = static::translateAllowedActionNames($task->getAllowedActions(true));

				if (!isset($data['FORUM_ID']) || !is_numeric($data['FORUM_ID']))
				{
					$data['FORUM_ID'] = \CTasksTools::getForumIdForIntranet();
				}
				$data['COMMENTS_COUNT'] = (int)($data['COMMENTS_COUNT'] ?? 0);

				// get task parameters
				$res = ParameterTable::getList(array('filter' => array('=TASK_ID' => $taskId)));
				while ($item = $res->fetch())
				{
					$taskParameters[] = $item;
				}
			}

			if ($parameters['DROP_PRIMARY'] ?? null)
			{
				unset($data[ 'ID' ]);
				$data[ static::ACT_KEY ] = static::getFullRights($userId);
			}

			$data[ 'SE_PARAMETER' ] = $taskParameters;
		}
		catch (TasksException $e) // todo: get rid of this annoying catch by making \Bitrix\Tasks\*Exception classes inherited from TasksException (dont forget about code)
		{
			if ($e->checkOfType(TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
			{
				$denied = true;
			}
			else
			{
				throw $e; // let it log
			}
		}
		catch (\Bitrix\Tasks\AccessDeniedException $e) // task not found or not accessible
		{
			$denied = true;
		}

		if ($denied)
		{
			$parameters[ 'ERRORS' ]->add('ACCESS_DENIED.NO_TASK', 'Task not found or not accessible');
		}

		return $data;
	}

	public static function getFullRights($userId)
	{
		// get rights as creator, just EDIT for now
		return array(
			'EDIT' => true,
			'EDIT.PLAN' => true,
			'CHECKLIST.ADD' => true,
			'CHECKLIST.REORDER' => true,
			'EDIT.ORIGINATOR' => true,
			'FAVORITE.ADD' => true,
			'FAVORITE.DELETE' => false,
			'DAYPLAN.ADD' => (!Extranet\User::isExtranet($userId) && Timeman::canUse())
		);
	}

	private static function injectDayPlanFields($userId, array $parameters, array &$data, array &$can)
	{
		if (empty($data))
		{
			return;
		}

		$extranetSite = Extranet::isExtranetSite();
		$extranetUser = Extranet\User::isExtranet($userId);

		// no dayplan for extranet site, even if intranet user goes to extranet site
		$plan = array();
		if (!$extranetSite && !$extranetUser)
		{
			$plan = \CTaskPlannerMaintance::getCurrentTasksList();

			if (is_array($plan) && !empty($plan))
			{
				$plan = array_flip($plan);
			}
		}

		foreach ($data as &$task)
		{
			$inDayPlan = false;
			$canAddToPlan = false;

			if ($task[ "RESPONSIBLE_ID" ] == $userId || (is_array($task[ 'ACCOMPLICES' ]) && in_array($userId, $task[ 'ACCOMPLICES' ])))
			{
				$canAddToPlan = true;

				// if in day plan already
				if (isset($plan[ $task[ 'ID' ] ]))
				{
					$inDayPlan = true;
					$canAddToPlan = false;
				}
			}

			$task[ 'IN_DAY_PLAN' ] = $inDayPlan;
			$task[ 'TIME_ELAPSED' ] = intval($task[ 'TIME_SPENT_IN_LOGS' ]);
			$task[ 'TIMER_IS_RUNNING_FOR_CURRENT_USER' ] = false;

			$can[ $task[ 'ID' ] ][ 'ACTION' ][ 'ADD_TO_DAY_PLAN' ] = $can[ $task[ 'ID' ] ][ 'ACTION' ][ 'DAYPLAN.ADD' ] =
				!$extranetUser && $canAddToPlan && Timeman::canUse();
		}
		unset($task);

		// current timer
		$runningTaskData = \CTaskTimerManager::getInstance($userId)->getRunningTask(false);
		foreach ($data as $k => &$task)
		{
			if (
				$runningTaskData
				&& (int)$task['ID'] === (int)$runningTaskData['TASK_ID']
				&& $task['ALLOW_TIME_TRACKING'] === 'Y'
			)
			{
				$task[ 'TIME_ELAPSED' ] += (time() - $runningTaskData[ 'TIMER_STARTED_AT' ]); // elapsed time is a sum of times in task log plus time of the current timer
				$task[ 'TIME_ELAPSED' ] = (string)$task[ 'TIME_ELAPSED' ]; // for consistency
				$task[ 'TIMER_IS_RUNNING_FOR_CURRENT_USER' ] = true;
			}
		}
		unset($task);
	}

	/**
	 * @throws TasksException
	 */
	public static function getList($userId, array $listParameters = [], array $parameters = [])
	{
		$data = [];
		$can = [];
		$params = [];

		$userId = (int) $userId;

		$errors = static::ensureHaveErrorCollection($parameters);

		// todo: get rid of LIST_PARAMETERS, if can. Move limit, filter, sort, etc.. to the first level

		if (array_key_exists('NAV_PARAMS', $listParameters) && !empty($listParameters['NAV_PARAMS']))
		{
			$params = ['NAV_PARAMS' => $listParameters['NAV_PARAMS']];
		}
		else
		{
			$navParams = static::prepareNav(
				($listParameters['limit'] ?? null),
				($listParameters['offset'] ?? null),
				($listParameters['page'] ?? null),
				($parameters['PUBLIC_MODE'] ?? null)
			);
			if (!empty($navParams))
			{
				$params = ['NAV_PARAMS' => $navParams];
			}
		}

		if (array_key_exists('TARGET_USER_ID', $parameters))
		{
			$params['TARGET_USER_ID'] = $parameters['TARGET_USER_ID'];
		}

		$params['DISTINCT'] = $parameters['DISTINCT'] ?? false;
		if (!array_key_exists('select', $listParameters) || !is_array($listParameters['select']))
		{
			$listParameters['select'] = [];
		}
		$getNewCommentsCount = in_array('NEW_COMMENTS_COUNT', $listParameters['select'], true);

		if (
			array_key_exists('SORTING', (array)($listParameters['order'] ?? []))
			&& array_key_exists('GROUP_ID', $listParameters['legacyFilter'])
		)
		{
			$params['SORTING_GROUP_ID'] = $listParameters['legacyFilter']['GROUP_ID'];
		}

		if (array_key_exists('USE_MINIMAL_SELECT_LEGACY', $parameters))
		{
			$params['USE_MINIMAL_SELECT_LEGACY'] = $parameters['USE_MINIMAL_SELECT_LEGACY'];
		}
		if (array_key_exists('MAKE_ACCESS_FILTER', $parameters))
		{
			$params['MAKE_ACCESS_FILTER'] = $parameters['MAKE_ACCESS_FILTER'];
		}

		if ($getNewCommentsCount)
		{
			$listParameters['select'][] = 'CREATED_DATE';
			$listParameters['select'][] = 'VIEWED_DATE';
		}

		if (
			!array_key_exists('RETURN_ACCESS', $parameters)
			|| (array_key_exists('RETURN_ACCESS', $parameters) && $parameters['RETURN_ACCESS'] != 'N')
		)
		{
			$listParameters['select'][] = 'ALLOW_CHANGE_DEADLINE';
			$listParameters['select'][] = 'TASK_CONTROL';
			$listParameters['select'][] = 'ALLOW_TIME_TRACKING';
		}

		// an exception about sql error may fall here
		[$items, $res] = \CTaskItem::fetchListArray(
			$userId,
			($listParameters['order'] ?? null),
			$listParameters['legacyFilter'] ?? null,
			$params,
			$listParameters['select']
		);

		if (is_array($items) && !empty($items))
		{
			(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload($userId, array_column($items, 'ID'));

			foreach ($items as $taskData)
			{
				$taskId = $taskData['ID'];

				if (!array_key_exists('RETURN_ACCESS', $parameters) || $parameters['RETURN_ACCESS'] != 'N')
				{
					$taskData['ACTION'] = $can[$taskId]['ACTION'] = static::translateAllowedActionNames(
						\CTaskItem::getAllowedActionsArray($userId, $taskData, true)
					);
				}

				$data[$taskId] = $taskData;
			}
		}

		return [
			'DATA' => $data,
			'CAN' => $can,
			'ERRORS' => $errors,
			'AUX' => [
				'OBJ_RES' => $res,
			],
		];
	}

	public static function getCount(array $filter = array(), array $params = array())
	{
		return \CTasks::GetCountInt($filter, $params);
	}

	private static function prepareNav($limit = false, $offset = false, $page=1, $public = false)
	{
		$nav = array();

		if ($limit !== false && $limit !== null)
		{
			$limit = intval($limit);

			if ($public)
			{
				$limit = min($limit, static::LIMIT_PAGE_SIZE);
			}

			if ($offset !== false)
			{
				$nav[ 'nPageSize' ] = $limit;
			}
			else
			{
				$nav[ 'nTopCount' ] = $limit;
			}
		}
		else
		{
			if ($public)
			{
				$nav[ 'nTopCount' ] = static::LIMIT_PAGE_SIZE;
			}
		}

		if ($offset !== false && $offset !== null)
		{
			$nav[ 'iNumPageSize' ] = intval($offset);
			$nav[ 'iNumPage' ] = intval($page);
		}

		return $nav;
	}

	public static function extendData(&$data, array $references = array(), int $userId = 0)
	{
		if (!$userId)
		{
			$userId = (int) \Bitrix\Tasks\Util\User::getId();
		}

		$user = UserModel::createFromId($userId);
		$data = self::cleanData($user, $data);

		if (isset($references['USER']) && is_array($references['USER']))
		{
			Originator::extendData($data, $references[ 'USER' ]);
			Responsible::extendData($data, $references[ 'USER' ]);
			Auditor::extendData($data, $references[ 'USER' ]);
			Accomplice::extendData($data, $references[ 'USER' ]);
		}
		if (isset($references['RELATED_TASK']) && is_array($references['RELATED_TASK']))
		{
			RelatedTask::extendData($data, $references[ 'RELATED_TASK' ]);
			ParentTask::extendData($data, $references[ 'RELATED_TASK' ]);
			ProjectDependence::extendData($data, $references[ 'RELATED_TASK' ]);
		}
		if (isset($references['GROUP']) && is_array($references['GROUP']))
		{
			Project::extendData($data, $references[ 'GROUP' ]);
		}
	}

	private static function cleanData(UserModel $user, array $data)
	{
		if ($user->isAdmin())
		{
			return $data;
		}

		$userId = $user->getUserId();

		if (!$user->isExtranet())
		{
			return $data;
		}

		$isCollaber = Extranet\User::isCollaber($userId);

		if (
			array_key_exists('SE_ORIGINATOR', $data)
			&& isset($data['SE_ORIGINATOR']['ID'])
			&& !Group::usersHasCommonGroup($userId, (int)$data['SE_ORIGINATOR']['ID'])
		)
		{
			if (!$isCollaber)
			{
				$data['SE_ORIGINATOR']['ID'] = $userId;
			}
			else
			{
				$data['SE_ORIGINATOR']['COLLABER_NO_HAS_COMMON_GROUP'] = true;
			}
		};

		if (
			array_key_exists('SE_RESPONSIBLE', $data)
			&& is_array($data['SE_RESPONSIBLE'])
		)
		{
			foreach ($data['SE_RESPONSIBLE'] as $code => $member)
			{
				if (!Group::usersHasCommonGroup($userId, (int)$member['ID']))
				{
					if (!$isCollaber)
					{
						unset($data['SE_RESPONSIBLE'][$code]);
					}
					else
					{
						$data['SE_RESPONSIBLE'][$code]['COLLABER_NO_HAS_COMMON_GROUP'] = true;
					}
				}
			}
		}
		if (empty($data['SE_RESPONSIBLE']))
		{
			$data['SE_RESPONSIBLE'] = [
				'U'.$userId => [
					'ID' => $userId,
					'NAME' => '',
					'LAST_NAME' => '',
					'EMAIL' => '',
				],
			];
		}

		if (
			array_key_exists('SE_ACCOMPLICE', $data)
			&& is_array($data['SE_ACCOMPLICE'])
		)
		{
			foreach ($data['SE_ACCOMPLICE'] as $code => $member)
			{
				if (!Group::usersHasCommonGroup($userId, (int)$member['ID']))
				{
					if (!$isCollaber)
					{
						unset($data['SE_ACCOMPLICE'][$code]);
					}
					else
					{
						$data['SE_ACCOMPLICE'][$code]['COLLABER_NO_HAS_COMMON_GROUP'] = true;
					}
				}
			}
		}

		if (
			array_key_exists('SE_AUDITOR', $data)
			&& is_array($data['SE_AUDITOR'])
		)
		{
			foreach ($data['SE_AUDITOR'] as $code => $member)
			{
				if (!Group::usersHasCommonGroup($userId, (int)$member['ID']))
				{
					if (!$isCollaber)
					{
						unset($data['SE_AUDITOR'][$code]);
					}
					else
					{
						$data['SE_AUDITOR'][$code]['COLLABER_NO_HAS_COMMON_GROUP'] = true;
					}
				}
			}
		}

		return $data;
	}

	public static function mergeData($primary = [], $secondary = [], bool $withAddition = true)
	{
		if (!is_array($primary) || !is_array($secondary))
		{
			return $primary;
		}

		foreach ($secondary as $secondaryKey => $secondaryValue)
		{
			$isSecondaryExistsInPrimary = array_key_exists($secondaryKey, $primary);
			$isAction = ($secondaryKey === self::ACT_KEY);

			if (!$isSecondaryExistsInPrimary || $isAction) // force rights merging
			{
				$primary[$secondaryKey] = $secondaryValue;

				continue;
			}

			if ($callable = self::getMergeHandler($secondaryKey))
			{
				$primary[$secondaryKey] = $callable($primary[$secondaryKey], $secondaryValue, $withAddition);
			}
		}

		return $primary;
	}

	private static function getMergeHandler(string $propertyName): ?string
	{
		if (!self::isAllowUseHandler($propertyName))
		{
			return null;
		}

		$handlerClass = self::getMergeHandlerClassName($propertyName);
		$callable = __NAMESPACE__ . '\\Task\\' . $handlerClass . '::mergeData';

		return is_callable($callable) ? $callable : null;
	}

	private static function getMergeHandlerClassName(string $propertyName): string
	{
		$className = $propertyName;
		if (str_starts_with($propertyName, self::SE_PREFIX))
		{
			$className = mb_substr($propertyName, mb_strlen(self::SE_PREFIX));
		}

		if (str_starts_with($propertyName, self::UF_PREFIX))
		{
			$className = mb_substr($propertyName, mb_strlen(self::UF_PREFIX));
		}

		return str_replace('_', '', $className);
	}

	/**
	 * @param array $task
	 * @param array $fields
	 * @return array|string
	 */
	public static function prepareSearchIndex(array $task, array $fields = [])
	{
		if (empty($task))
		{
			return '';
		}

		if (empty($fields))
		{
			$fields = [
				'ID',
				'TITLE',
				'DESCRIPTION',
				'CHECKLIST',
				'RESPONSIBLE',
				'ORIGINATOR',
				'AUDITORS',
				'ACCOMPLICES',
				'CRM',
				'TAGS',
				'GROUP'
			];
		}

		$index = [];

		foreach ($fields as $field)
		{
			switch ($field)
			{
				default:
					if (array_key_exists($field, $task) && !empty($task[$field]))
					{
						$index[] = $task[$field];
					}
					break;

				// custom fields
				case 'CHECKLIST':
					/** Bitrix\Tasks\Item\Task\Collection\CheckList */
					$checkList = (
						is_object($task['SE_CHECKLIST'] ?? null)
							? $task['SE_CHECKLIST']->export()
							: (array)($task['CHECKLIST'] ?? [])
					);
					foreach ($checkList as $item)
					{
						$index[] = $item['TITLE'];
					}
					break;

				case 'RESPONSIBLE':
					$index[] = join(' ', User::getUserName([$task['RESPONSIBLE_ID']]));
					break;

				case 'ORIGINATOR':
					$index[] = join(' ', User::getUserName([$task['CREATED_BY']]));
					break;

				case 'AUDITORS':
					if (array_key_exists('AUDITORS', $task))
					{
						$auditors = (is_object($task['AUDITORS'])? $task['AUDITORS']->toArray() : $task['AUDITORS']);
						if ($auditors)
						{
							$index[] = join(' ', User::getUserName(array_unique($auditors)));
						}
					}
					break;

				case 'ACCOMPLICES':
					if (array_key_exists('ACCOMPLICES', $task))
					{
						$accomplices = (is_object($task['ACCOMPLICES'])? $task['ACCOMPLICES']->toArray() : $task['ACCOMPLICES']);
						if ($accomplices)
						{
							$index[] = join(' ', User::getUserName(array_unique($accomplices)));
						}
					}
					break;

				case 'CRM':
					if (\Bitrix\Main\ModuleManager::isModuleInstalled('crm'))
					{
						$uf = (
							is_object($task['UF_CRM_TASK'] ?? null)
								? $task['UF_CRM_TASK']->toArray()
								: (array)($task['UF_CRM_TASK'] ?? [])
						);
						foreach ($uf as $item)
						{
							$crmElement = explode('_', $item);
							$type = $crmElement[0];
							$typeId = \CCrmOwnerType::ResolveID(\CCrmOwnerTypeAbbr::ResolveName($type));
							$title = \CCrmOwnerType::GetCaption($typeId, $crmElement[1]);

							$index[] = $title;
						}
					}
					break;

				case 'TAGS':
					$tags = (
						is_object($task['TAGS'] ?? null)
							? $task['TAGS']->export()
							: (array)($task['TAGS'] ?? [])
					);
					$index[] = join(' ', $tags);
					break;

				case 'GROUP':
					$groupId = $task['GROUP_ID'];
					$groups = Group::getData([$groupId]);
					$groupName = ($groups[$groupId]['NAME'] ?? null);

					$index[] = $groupName;
					break;
			}
		}

		$strIndex = join(' ', $index);
		$strIndex = array_unique(explode(' ', $strIndex));
		$strIndex = join(' ', $strIndex);
		$strIndex = mb_strtoupper($strIndex);

		return $strIndex;
	}

	private static function isAllowUseHandler(string $propertyName): bool
	{
		$propertyNameWithoutSubEntityPrefix = $propertyName;
		if (str_starts_with($propertyName, self::SE_PREFIX))
		{
			$propertyNameWithoutSubEntityPrefix = mb_substr($propertyName, mb_strlen(self::SE_PREFIX));
		}

		return in_array($propertyNameWithoutSubEntityPrefix, self::getAllowedPropertiesToUseHandler(), true);
	}

	private static function getAllowedPropertiesToUseHandler(): array
	{
		static $allowedPropertyList = [];

		if (empty($allowedPropertyList))
		{
			$allowedPropertyList = [
				...self::getLegalSubEntities(),
				Description::getCode(),
				TaskWebdavFiles::getCode(),
			];
		}

		return $allowedPropertyList;
	}

	protected static function getLegalSubEntities()
	{
		static $legal;

		if ($legal === null)
		{
			$legal = array(
				Originator::getCode(),
				Responsible::getCode(),
				Auditor::getCode(),
				Accomplice::getCode(),
				Checklist::getCode(),
				Reminder::getCode(),
				ElapsedTime::getCode(),
				Log::getCode(),
				ProjectDependence::getCode(),
				Template::getCode(),
				Tag::getCode(),
				RelatedTask::getCode(),
				'DAYPLAN',
				'TIMEMANAGER', // alias for DAYPLAN
			);
		}

		return $legal;
	}

	/**
	 * @param $data
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function getAnalyticsData(&$data)
	{
		$code = Checklist::getCode(true);
		$checklistData = ($data[$code] ?? null);

		if (!$checklistData)
		{
			return [];
		}

		$checklistParents = array_filter(
			$checklistData,
			static function($item)
			{
				return is_array($item) && $item['PARENT_NODE_ID'] === '0';
			}
		);

		$analyticsData = [
			'checklistCount' => count($checklistParents),
		];

		if ($checklistData['analyticsData'] ?? null)
		{
			foreach (explode(',', $checklistData['analyticsData']) as $key => $value)
			{
				$analyticsData[$value] = 1;
			}
		}

		if ($checklistData['fromDescription'] ?? null)
		{
			$analyticsData['fromDescription'] = 1;
		}

		unset($data[$code]['analyticsData'], $data[$code]['fromDescription']);

		return $analyticsData;
	}
}
