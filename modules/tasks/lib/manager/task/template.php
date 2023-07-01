<?
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

namespace Bitrix\Tasks\Manager\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Assert;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;
use Bitrix\Tasks\Integration\SocialNetwork\User;

// todo: inherit this class from \Bitrix\Tasks\Manager\Task ?
final class Template extends \Bitrix\Tasks\Manager
{
	public static function get($userId, $itemId, array $parameters = array())
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		$result = array(
			'DATA' => array(),
			'CAN' => array(),
			'ERRORS' => $errors
		);

		$userId = 		Assert::expectIntegerPositive($userId, '$userId');
		$itemId = 		Assert::expectIntegerPositive($itemId, '$itemId');

		$data = \CTaskTemplates::getList(array(), array('ID' => $itemId), array(), array('USER_ID' => $userId), array('*', 'UF_*'))->fetch();
		$can = array();

		if(empty($data))
		{
			$errors->add('ACCESS_DENIED.NO_TEMPLATE', 'Template not found');
			return $result;
		}

		$data['ACCOMPLICES'] = 			Type::unserializeArray($data['ACCOMPLICES']);
		$data['AUDITORS'] = 			Type::unserializeArray($data['AUDITORS']);
		$data['REPLICATE_PARAMS'] = 	Type::unserializeArray($data['REPLICATE_PARAMS']);

		// adapt responsibles

		$responsibles = array();
		if($data['MULTITASK'] == 'Y')
		{
			$responsibles = Type::unserializeArray($data['RESPONSIBLES']);
		}
		unset($data['RESPONSIBLES']);

		if(empty($responsibles))
		{
			$responsibles = array(
				$data['RESPONSIBLE_ID']
			);
		}

		$data[Responsible::getCode(true)] = static::formatSetResponsible($responsibles);

		// todo: temporal formatters, implement model object with array access and lazy data fetch/format
		Originator::formatSet($data);
		Auditor::formatSet($data);
		Accomplice::formatSet($data);
		ParentTask::formatSet($data);
		Project::formatSet($data);

		$deadline = static::getDeadLine($data['DEADLINE_AFTER']);
		if($deadline != '')
		{
			$data['DEADLINE'] = $deadline;
		}

		// select sub-entity related data

		if (!is_array($parameters['ENTITY_SELECT'] ?? null))
		{
			$parameters['ENTITY_SELECT'] = []; // none by default
		}
		$entitySelect = array_flip($parameters['ENTITY_SELECT']);

		// select CHECKLIST sub entity
		if(isset($entitySelect['CHECKLIST']))
		{
			$mgrResult = Template\CheckList::getListByParentEntity($userId, $itemId, $parameters);
			$data[static::SE_PREFIX.'CHECKLIST'] = $mgrResult['DATA'];
			if(!empty($mgrResult['CAN']))
			{
				$can[static::SE_PREFIX.'CHECKLIST'] = $mgrResult['CAN'];
			}
		}

		// select RELATEDTASK sub entity
		// todo: should be a separate class here, but for now this is okay
		$related = Type::unserializeArray($data['DEPENDS_ON']);
		unset($data['DEPENDS_ON']);
		if(isset($entitySelect['RELATEDTASK']))
		{
			// in task entity tags come from a separate table, in template - from the same table
			$seRelated = array();
			foreach($related as $task)
			{
				$seRelated[] = array('ID' => $task);
			}
			$data[static::SE_PREFIX.'RELATEDTASK'] = $seRelated;
		}

		// select TAG sub entity
		// todo: should be a separate class here, but for now this is okay
		$tags = Type::unserializeArray($data['TAGS']);
		unset($data['TAGS']);
		if(isset($entitySelect['TAG']))
		{
			// in task entity tags come from a separate table, in template - from the same table
			$seTag = array();
			foreach($tags as $tag)
			{
				$seTag[] = array('NAME' => $tag);
			}
			$data[static::SE_PREFIX.'TAG'] = $seTag;
		}

		if ($parameters['DROP_PRIMARY'] ?? null)
		{
			unset($data['ID']);
			$can = [];
		}

		$data[static::ACT_KEY] = $can;
		$result['DATA'] = $data;
		$result['CAN'] = $can;

		return $result;
	}

	private static function prepareNav($limit = false, $offset = false, $public = false)
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
				$nav['nPageSize'] = $limit;
			}
			else
			{
				$nav['nTopCount'] = $limit;
			}
		}
		else
		{
			if ($public)
			{
				$nav['nTopCount'] = static::LIMIT_PAGE_SIZE;
			}
		}

		if ($offset !== false && $offset !== null)
		{
			$nav['iNumPageSize'] = intval($offset);
		}

		return $nav;
	}

	/**
	 * @param $userId
	 * @param array $listParameters
	 * @param array $parameters
	 *
	 * @return array
	 */
	public static function getList($userId, array $listParameters = [], array $parameters = [])
	{
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
				($parameters['PUBLIC_MODE'] ?? null)
			);

			$params = false;
			if (!empty($navParams))
			{
				$params = array('NAV_PARAMS' => $navParams);
			}
		}

		if (
			array_key_exists('SORTING', (array)($listParameters['order'] ?? null))
			&& array_key_exists('GROUP_ID', ($listParameters['legacyFilter'] ?? null))
		)
		{ // need for sorting in group
			$params['SORTING_GROUP_ID'] = $listParameters['legacyFilter']['GROUP_ID'];
		}

		// an exception about sql error may fall here
		$res = \CTaskTemplates::GetList(
			($listParameters['order'] ?? null),
			($listParameters['filter'] ?? null),
			$params,
			[
				'USER_ID' => $userId, // check permissions for current user
				'USER_IS_ADMIN' => SocialNetwork\User::isAdmin(),
			],
			($listParameters['select'] ?? null)
		);

		$accessController = new TemplateAccessController((int) $userId);

		$items = [];
		while ($row = $res->Fetch())
		{
			$templateId = (int) $row['ID'];
			$templateModel = TemplateModel::createFromId($templateId);
			$accessRequest = [
				ActionDictionary::ACTION_TEMPLATE_READ => null,
				ActionDictionary::ACTION_TEMPLATE_CREATE => null,
				ActionDictionary::ACTION_TEMPLATE_REMOVE => null,
				ActionDictionary::ACTION_TEMPLATE_EDIT => null,
			];

			$row['ACCOMPLICES'] = unserialize($row['ACCOMPLICES'], ['allowed_classes' => false]);
			$row['AUDITORS'] = unserialize($row['AUDITORS'], ['allowed_classes' => false]);
			$row['RESPONSIBLES'] = unserialize($row['RESPONSIBLES'], ['allowed_classes' => false]);

			$row['TAGS'] = unserialize(($row['TAGS'] ?? ''), ['allowed_classes' => false]);
			$row['DEPENDS_ON'] = unserialize(($row['DEPENDS_ON'] ?? ''), ['allowed_classes' => false]);

			$row['REPLICATE_PARAMS'] = unserialize(($row['REPLICATE_PARAMS'] ?? ''), ['allowed_classes' => false]);
			$row['ALLOWED_ACTIONS'] = $accessController->batchCheck($accessRequest, $templateModel);

			$items[$templateId] = $row;
		}

		$childCounts = \Bitrix\Tasks\Internals\Helper\Task\Template\Dependence::getDirectChildCount(
			array_keys($items),
			array(
				'USER_ID' => $userId
			)
		);

		foreach ($childCounts as $itemId => $count)
		{
			$items[$itemId]['CHILDS_COUNT'] = (int) $count;
		}

		return [
			'DATA' => $items,
			'ERRORS' => $errors,
			'OBJ_RES' => $res
		];
	}

	private static function adaptSet(&$data)
	{
		Originator::adaptSet($data);
		Auditor::adaptSet($data);
		Accomplice::adaptSet($data);
		Tag::adaptSet($data);
		RelatedTask::adaptSet($data);
		ParentTask::adaptSet($data);
		Project::adaptSet($data);

		// special case: responsibles
		Responsible::adaptSet($data);
		if(is_array($data[Responsible::getLegacyFieldName()]))
		{
			$data[Responsible::getLegacyFieldName()] = array_shift($data[Responsible::getLegacyFieldName()]);
		}
	}

	// todo: normal add() here later, with public mode check
	// todo: normalize data here!
	public static function add($userId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$userId = Assert::expectIntegerPositive($userId, '$userId');

		$errors = static::ensureHaveErrorCollection($parameters);

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, array(), $errors);
		}

		if($errors->checkNoFatals())
		{
			$responsibles = Responsible::extractPrimaryIndexes($data[Responsible::getCode(true)]);

			// todo: temporal adapters, implement model object with array access and lazy adaptation
			static::adaptSet($data);

			// serialize some...
			$data["ACCOMPLICES"] = 		Type::serializeArray($data["ACCOMPLICES"], true);
			$data["AUDITORS"] = 		Type::serializeArray($data["AUDITORS"], true);
			$data["RESPONSIBLES"] = 	Type::serializeArray($responsibles, true);
			$data["TAGS"] = 			Type::serializeArray($data["TAGS"], true);
			$data["REPLICATE_PARAMS"] = Type::serializeArray($data["REPLICATE_PARAMS"], true);

			if(count($responsibles) > 1) // template represents multitask in case of multiple responsibles
			{
				$data['MULTITASK'] = 'Y';
			}

			// todo: CTaskTemplates::add() hates unknown fields in $data. Improve CTaskTemplates::add() to make it ignore garbage in input
			$dataToAdd = $data;
			unset($dataToAdd['DEPENDS_ON']);
			foreach($dataToAdd as $key => $v)
			{
				if(mb_strpos($key, static::SE_PREFIX) === 0)
				{
					unset($dataToAdd[$key]);
				}
			}

			$templateInstance = new \CTaskTemplates();
			$itemId = $templateInstance->add($dataToAdd);

			if($itemId)
			{
				$subEntityParams = array_merge(
					$parameters, array('MODE' => static::MODE_ADD)
				);

				if(is_array($data[static::SE_PREFIX.'CHECKLIST']))
				{
					Template\Checklist::manageSet($userId, $itemId, $data[static::SE_PREFIX.'CHECKLIST'], $subEntityParams);
				}
			}

			$resultErrors = $templateInstance->getErrors();
			if(!empty($resultErrors))
			{
				$errors->load($resultErrors);
			}
		}

		return array(
			'DATA' => array('ID' => $itemId),
			'ERRORS' => $errors,
		);
	}

	// todo: make normal update() here, without spiking REPLICATE and with accepting members as arrays
	// todo: normalize data here
	public static function update($userId, $itemId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$userId = 		Assert::expectIntegerPositive($userId, '$userId');
		$itemId = 		Assert::expectIntegerPositive($itemId, '$itemId');

		$errors = static::ensureHaveErrorCollection($parameters);

		if(isset($parameters['PUBLIC_MODE']))
		{
			$data = static::filterData($data, array(), $errors);
		}

		$template = static::getTemplate($itemId);
		if (isset($template['ID']) && (int)$template['ID'] > 0)
		{
			$templateData = $template;
		}
		else
		{
			$errors->add('ACCESS_DENIED.NO_TEMPLATE', 'Template not found');
			return;
		}
		$prevReplicateData = Type::unserializeArray($templateData['REPLICATE_PARAMS']);

		if(array_key_exists('REPLICATE_PARAMS', $data))
		{
			$times = intval($data['REPLICATE_PARAMS']['TIMES']);
			$data['REPLICATE_PARAMS'] = Type::serializeArray($data['REPLICATE_PARAMS']);
		}
		else
		{
			$times = false; // times unchanged
		}

		// reset replication counter when TIMES was changed
		if($times !== false && intval($prevReplicateData['TIMES']) != $times)
		{
			$data['TPARAM_REPLICATION_COUNT'] = 1;
		}

		$templateInstance = new \CTaskTemplates();
		$templateInstance->update($itemId, $data);

		$resultErrors = $templateInstance->getErrors();
		if(!empty($resultErrors))
		{
			$errors->load($resultErrors);
		}
	}

	// task-specific
	public static function manageTaskReplication($userId, $taskId, array $taskData = array(), array $parameters = array('PUBLIC_MODE' => false, 'MODE' => self::MODE_ADD))
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$result = array(
			'ERRORS' => $errors
		);

		$templateKey = static::getCode(true);
		$replicateKey = 'REPLICATE';

		if(!array_key_exists($templateKey, $taskData) && !array_key_exists($replicateKey, $taskData))
		{
			return $result; // nothing to do
		}

		$data = array();

		$replicate = $taskData[$replicateKey] == 'Y';
		$templateResult = array(
			'ID' => 0,
			'ERRORS' => array()
		);

		if($replicate) // replication was changed to true
		{
			if(array_key_exists($templateKey, $taskData)) // replication data defined
			{
				$templateData = array(
					'REPLICATE' => $taskData[$replicateKey],
					'CREATED_BY' => $taskData['CREATED_BY'],
					'REPLICATE_PARAMS' => $taskData[$templateKey]['REPLICATE_PARAMS']
				);
				$replicateParams = (new ReplicateParamsCorrector((int)$userId))->correctReplicateParamsByTemplateData($templateData);
				$taskData[$templateKey]['REPLICATE_PARAMS'] = $replicateParams;

				if($parameters['MODE'] == static::MODE_ADD) // task add, replicate = y
				{
					// then add template
					$templateResult = static::addTemplateByTask($userId, $taskId, $taskData);
				}
				elseif($parameters['MODE'] == static::MODE_UPDATE) // task update, replicate = y
				{
					$template = static::getByParentTask(false, $taskId);
					if(intval($template['DATA']['ID'])) // then update template
					{
						static::update(
							$userId,
							intval($template['DATA']['ID']),
							array(
								'REPLICATE_PARAMS' => $replicateParams,
								'REPLICATE' => 'Y' // required for agent re-creation by update()
							),
							$parameters
						);

						$templateResult['ID'] = intval($template['DATA']['ID']);
					}
					else // no template? add!
					{
						$templateResult = static::addTemplateByTask($userId, $taskId, $taskData);
					}
				}
			}
		}
		else if ($parameters['MODE'] == static::MODE_UPDATE)
		{
			$template = static::getByParentTask(false, $taskId);
			if ((int)($template['DATA']['ID'] ?? null)) // then update template
			{
				$templateInstance = new \CTaskTemplates();
				$templateInstance->delete((int)$template['DATA']['ID']);
			}
		}

		if ($templateResult['ID'])
		{
			$data['ID'] = $templateResult['ID'];
		}
		elseif (!empty($templateResult['ERRORS']))
		{
			$result['ERRORS']->addWarning('SAVE_AS_TEMPLATE_ERROR', $templateResult['ERRORS'][0]);
		}

		$result['DATA'] = $data;

		return $result;
	}

	/**
	 * @param $userId
	 * @param $taskId
	 * @param $data
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function addTemplateByTask($userId, $taskId, $data)
	{
		$id = 0;
		$errors = [];

		$task = new Item\Task($taskId, $userId);
		$conversionResult = $task->transformToTemplate();

		if ($conversionResult->isSuccess())
		{
			$template = $conversionResult->getInstance();

			$responsibles = [];
			if (is_array($data['SE_RESPONSIBLE']))
			{
				foreach($data['SE_RESPONSIBLE'] as $user)
				{
					if (intval($user['ID']))
					{
						$responsibles[] = intval($user['ID']);
					}
				}
			}

			if (count($responsibles))
			{
				$template['RESPONSIBLES'] = $responsibles;
			}
			$template['SE_CHECKLIST'] = new Item\Task\CheckList();
			$template['REPLICATE_PARAMS'] = $data[static::getCode(true)]['REPLICATE_PARAMS'];

			$saveResult = $template->save();

			if ($saveResult->isSuccess())
			{
				$id = $template->getId();

				$checkListItems = TaskCheckListFacade::getByEntityId($taskId);
				$checkListItems = array_map(
					function($item)
					{
						$item['COPIED_ID'] = $item['ID'];
						unset($item['ID']);
						return $item;
					},
					$checkListItems
				);

				$checkListRoots = TemplateCheckListFacade::getObjectStructuredRoots($checkListItems, $id, $userId);
				foreach ($checkListRoots as $root)
				{
					$checkListSaveResult = $root->save();
					if (!$checkListSaveResult->isSuccess())
					{
						$conversionResult->abortConversion();
						$errors = $checkListSaveResult->getErrors()->getMessages();
					}
				}
			}
			else
			{
				$conversionResult->abortConversion();
				$errors = $saveResult->getErrors()->getMessages();
			}
		}
		else
		{
			$errors[] = Loc::getMessage('TASKS_MANAGER_TASK_TEMPLATE_CONVERSION_ERROR');
		}

		if (!$id && empty($errors))
		{
			$errors[] = Loc::getMessage('TASKS_MANAGER_TASK_TEMPLATE_UNKNOWN_ERROR');
		}

		return [
			'ID' => $id,
			'ERRORS' => $errors,
		];
	}

	public static function getByParentTask($userId, $taskId)
	{
		$access = array();
		if($userId !== false)
		{
			$access = array(
				'USER_ID' => $userId,
				'USER_IS_ADMIN' => User::isAdmin($userId)
			);
		}

		$item = \CTaskTemplates::getList(array(), array('TASK_ID' => $taskId), array(), $access, array(
			'ID', 'TASK_ID', 'TITLE', 'REPLICATE_PARAMS', 'TPARAM_TYPE', 'CREATED_BY'
		))->fetch();

		$data = array();
		if(is_array($item))
		{
			// there are lots of garbage come in $item even if 'select' array defined
			$data = array(
				'ID' => 				$item['ID'],
				'TITLE' => 				$item['TITLE'],
				'TASK_ID' => 			$item['TASK_ID'],
				'TPARAM_TYPE' => 		$item['TPARAM_TYPE'],
				'CREATED_BY' =>         $item['CREATED_BY'],
				'REPLICATE_PARAMS' => 	Type::unserializeArray($item['REPLICATE_PARAMS'])
			);
		}

		return array(
			'DATA' => $data
		);
	}

	// todo: make more clever function which will return all errors and also check other part of data
	protected static function filterData(array $data, array $fieldMap, Collection $errors)
	{
		return $data;
	}

	private static function formatSetResponsible(array $responsibles)
	{
		$data = array();
		$responsibles = \Bitrix\Tasks\Util\Type::normalizeArray($responsibles);
		foreach($responsibles as $item)
		{
			$data[] = array('ID' => intval($item));
		}

		return $data;
	}

	private static function getDeadLine($deadlineAfter)
	{
		$deadlineAfter = intval($deadlineAfter);

		if ($deadlineAfter)
		{
			$deadlineAfter = $deadlineAfter / 86400; // to days
			return \Bitrix\Tasks\UI::formatDateTime(strtotime(date("Y-m-d 00:00")." +".$deadlineAfter." days"));
		}

		return '';
	}

	private static function getTemplate($id)
	{
		$item = \CTaskTemplates::getList(array(), array('ID' => $id), array(), array(), array(
			'ID', 'TASK_ID', 'TITLE', 'REPLICATE_PARAMS', 'TPARAM_TYPE'
		))->fetch();

		return $item;
	}

	/**
	 * @param $data
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getAnalyticsData(&$data)
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

		if ($checklistData['analyticsData'])
		{
			foreach (explode(',', $checklistData['analyticsData']) as $key => $value)
			{
				$analyticsData[$value] = 1;
			}
		}

		if ($checklistData['fromDescription'])
		{
			$analyticsData['fromDescription'] = 1;
		}

		unset($data[$code]['analyticsData'], $data[$code]['fromDescription']);

		return $analyticsData;
	}
}