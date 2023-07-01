<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Control\Exception\TemplateAddException;
use Bitrix\Tasks\Control\Exception\TemplateUpdateException;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\SocialServices\User;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util;
use Bitrix\Main\Localization\Loc;

final class Template extends \Bitrix\Tasks\Dispatcher\PublicAction
{
	/**
	 * Get the task template
	 */
	public function get($id, array $parameters = array())
	{
		if (!TemplateAccessController::can($this->userId, ActionDictionary::ACTION_TEMPLATE_READ, $id))
		{
			return [];
		}

		// todo: field access policy here?
		$select = array();
		if(array_key_exists('select', $parameters) && count($parameters['select']))
		{
			$select = $parameters['select'];
		}

		$template = new Item\Task\Template($id);

		return array(
			'ID'   => $id,
			// todo: in case of REST, ALL dates should be converted to the ISO string (write special exporter here, instead of Canonical)
			'DATA' => $template->export($select),// export ALL or only selected
		);
	}

	/**
	 * Get a list of task templates
	 */
	public function find(array $parameters = array())
	{
		if(!array_key_exists('limit', $parameters) || intval($parameters['limit'] > 10))
		{
			$parameters['limit'] = 10;
		}
		$selectIsEmpty = !array_key_exists('select', $parameters) || !count($parameters['select']);

		$data = array();
		$result = Item\Task\Template::find($parameters);

		if($result->isSuccess())
		{
			/** @var Item $item */
			foreach($result as $item)
			{
				// todo: in case of REST, ALL dates should be converted to the ISO string (write special exporter here, instead of Canonical)
				$data[] = $item->export($selectIsEmpty ? array() : '~'); // export ALL or only selected
			}
		}
		else
		{
			// clear DATA because we do not want error detail info sent to the client
			$this->errors->load($result->getErrors()->transform(array('DATA' => null)));
		}

		return array(
			'DATA' => $data,
		);
	}

	/**
	 * Add a new task template
	 *
	 * @param array $data
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function add(array $data)
	{
		$result = array();

		if (!TemplateAccessController::can($this->userId, ActionDictionary::ACTION_TEMPLATE_CREATE))
		{
			return $result;
		}

		// todo: check $data here, check for publicly-readable\writable keys\values

		$this->prepareMembers($data);
		$this->prepareReplicateParams($data);

		if (
			array_key_exists('REPLICATE', $data)
			&& $data['REPLICATE'] === 'Y'
		)
		{
			$templateModel = TemplateModel::createFromArray($data);
			if (!TemplateAccessController::can($this->userId, ActionDictionary::ACTION_TEMPLATE_SAVE, null, $templateModel))
			{
				$this->errors->add('TEMPLATE_CREATE_TASK_NOT_ACCESSIBLE', Loc::getMessage('TASKS_TEMPLATE_CREATE_TASK_NOT_ACCESSIBLE'));
				return $result;
			}
		}

		if (!$this->errors->checkNoFatals())
		{
			return $result;
		}

		$analyticsData = Manager\Task\Template::getAnalyticsData($data);

		$checkListItems = ($data['SE_CHECKLIST']?: []);
		unset($data['SE_CHECKLIST']);

		$templatePermissions = null;
		if (array_key_exists('SE_TEMPLATE_ACCESS', $data))
		{
			$templatePermissions = $data['SE_TEMPLATE_ACCESS'];
			unset($data['SE_TEMPLATE_ACCESS']);
		}

		$manager = new \Bitrix\Tasks\Control\Template($this->userId);
		$manager->withCheckFileRights();

		$saveResult = new Item\Result();

		try
		{
			$template = $manager->add($data);
		}
		catch (TemplateAddException $e)
		{
			$saveResult->addError('TASKS_ADD_TEMPLATE', htmlspecialcharsback($e->getMessage()));
			$this->errors->load($saveResult->getErrors());
			return $result;
		}
		catch (\Exception $e)
		{
			$saveResult->addError('TASKS_ADD_TEMPLATE', $e->getMessage());
			$this->errors->load($saveResult->getErrors());
			return $result;
		}

		// $template = new Item\Task\Template($data);
		// $saveResult = $template->save();

		// $this->errors->load($saveResult->getErrors());

		$templateId = $template->getId();
		if (!$templateId)
		{
			return $result;
		}

		$result['ID'] = $templateId;

		$mergeResult = TemplateCheckListFacade::merge(
			$templateId,
			$this->userId,
			$checkListItems,
			['analyticsData' => $analyticsData]
		);
		if (!$mergeResult->isSuccess())
		{
			$saveResult->loadErrors($mergeResult->getErrors());
		}
		// todo: also DATA and CAN keys here...

		if ($templatePermissions !== null)
		{
			$res = $this->saveTemplatePermissions($template, $templatePermissions);
			if (!$res->isSuccess())
			{
				$saveResult->loadErrors($res->getErrors());
			}
		}

		$this->errors->load($saveResult->getErrors());

		return $result;
	}

	/**
	 * Update the task template with some new data
	 *
	 * @param $id
	 * @param array $data
	 * @return array
	 * @throws SystemException
	 */
	public function update($id, array $data = null)
	{
		$result = [];

		if (!($id = $this->checkId($id)))
		{
			return $result;
		}

		if (($data['CREATED_BY'] ?? null) <= 0)
		{
			$data['CREATED_BY'] = $this->userId;
		}

		$result['ID'] = $id;

		// todo: check $data here, check for publicly-readable\writable keys\values

		$this->prepareMembers($data);
		$this->prepareReplicateParams($data);

		$oldTemplate = \Bitrix\Tasks\Access\Model\TemplateModel::createFromId($id);
		$newTemplate = TemplateModel::createFromArray($data);
		$isAccess = (new TemplateAccessController($this->userId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $oldTemplate, $newTemplate);
		if (!$isAccess)
		{
			$this->errors->add('TEMPLATE_CREATE_TASK_NOT_ACCESSIBLE', Loc::getMessage('TASKS_TEMPLATE_CREATE_TASK_NOT_ACCESSIBLE'));
			return $result;
		}

		if ($this->errors->checkNoFatals())
		{
			$analyticsData = Manager\Task\Template::getAnalyticsData($data);

			$checkListItems = ($data['SE_CHECKLIST'] ?? null);
			unset($data['SE_CHECKLIST']);

			$templatePermissions = null;
			if (array_key_exists('SE_TEMPLATE_ACCESS', $data))
			{
				$templatePermissions = $data['SE_TEMPLATE_ACCESS'];
				unset($data['SE_TEMPLATE_ACCESS']);
			}

			$manager = new \Bitrix\Tasks\Control\Template($this->userId);
			$saveResult = new Item\Result();

			try
			{
				$template = $manager->update($id, $data);
			}
			catch (TemplateUpdateException $e)
			{
				$saveResult->addError('TASKS_UPDATE_TEMPLATE', $e->getMessage());
				$this->errors->load($saveResult->getErrors());
				return $result;
			}
			catch (\Exception $e)
			{
				$saveResult->addError('TASKS_UPDATE_TEMPLATE', $e->getMessage());
				$this->errors->load($saveResult->getErrors());
				return $result;
			}

			// $template = new Item\Task\Template($id);
			// $template->setData($data);
			// $saveResult = $template->save();

			if ($saveResult->isSuccess() && $checkListItems)
			{
				$mergeResult = TemplateCheckListFacade::merge(
					$id,
					$this->userId,
					$checkListItems,
					['analyticsData' => $analyticsData]
				);
				if (!$mergeResult->isSuccess())
				{
					$saveResult->loadErrors($mergeResult->getErrors());
				}
			}

			if (
				$saveResult->isSuccess()
				&& $templatePermissions !== null
			)
			{
				$res = $this->saveTemplatePermissions($template, $templatePermissions);
				if (!$res->isSuccess())
				{
					$saveResult->loadErrors($res->getErrors());
				}
			}

			$this->errors->load($saveResult->getErrors());
		}

		return $result;
	}

	/**
	 * Delete the task template
	 */
	public function delete($id)
	{
		$result = array();

		if(!($id = $this->checkId($id)))
		{
			return $result;
		}

		if (!TemplateAccessController::can($this->userId, ActionDictionary::ACTION_TEMPLATE_EDIT, $id))
		{
			return $result;
		}

		$result['ID'] = $id;

		if (!\CTaskTemplates::Delete($id))
		{
			return null;
		}

		return $result;
	}

	/**
	 * Enable replication of the template
	 *
	 * @param $id
	 * @return array
	 */
	public function startReplication($id)
	{
		return $this->toggleReplication($id, true);
	}

	/**
	 * Disable replication of the template
	 *
	 * @param $id
	 * @return array
	 */
	public function stopReplication($id)
	{
		return $this->toggleReplication($id, false);
	}

	private function saveTemplatePermissions($template, array $permissions)
	{
		$res = new Util\Result();

		if (!Integration\Bitrix24::checkFeatureEnabled(Integration\Bitrix24\FeatureDictionary::TASKS_TEMPLATES_ACCESS))
		{
			return $res;
		}

		$permissions = array_values($permissions);

		/**
		 * Delete all permissions if anyone gets new access
		 */
		if (!empty($permissions) && is_array($permissions[0]))
		{
			TasksTemplatePermissionTable::deleteList([
				'=TEMPLATE_ID' => $template->getId()
			]);
		}
		else
		{
			TasksTemplatePermissionTable::deleteList([
				'=TEMPLATE_ID' => $template->getId(),
				'!=ACCESS_CODE' => 'U'.$this->userId
			]);
		}

		foreach ($permissions as $permission)
		{
			if (empty($permission))
			{
				continue;
			}

			TasksTemplatePermissionTable::add([
				'TEMPLATE_ID' 		=> $template->getId(),
				'ACCESS_CODE' 		=> $permission['GROUP_CODE'],
				'PERMISSION_ID' 	=> $permission['PERMISSION_ID'],
				'VALUE' 			=> PermissionDictionary::VALUE_YES
			]);
		}

		return $res;
	}

	private function toggleReplication($id, $way)
	{
		$result = array();

		if(!($id = $this->checkId($id)))
		{
			return $result;
		}
		$result['ID'] = $id;

		// access check inside
		$template = new Item\Task\Template($id);
		$template['REPLICATE'] = $way ? 'Y' : 'N';

		if($way)
		{
			$template['TPARAM_REPLICATION_COUNT'] = 0;
		}

		$taskId = intval($template['TASK_ID']);

		$saveResult = $template->save();
		$this->errors->load($saveResult->getErrors());

		if($saveResult->isSuccess())
		{
			// update related task
			if($taskId)
			{
				$task = new Item\Task($taskId);
				if($task->canUpdate())
				{
					$task['REPLICATE'] = $way ? 'Y' : 'N';
					$saveResult = $task->save(); // todo: DO NOT remove template in case of REPLICATE falls to N
					$this->errors->load($saveResult->getErrors()->transform(array(
																				'CODE' => 'TASK.#CODE#',
																				'TYPE' => Util\Error::TYPE_WARNING
																			)));
				}
			}
		}

		return $result;
	}

	private function prepareReplicateParams(array &$data)
	{
		if ($data['REPLICATE_PARAMS'] ?? null)
		{
			$data['REPLICATE_PARAMS'] = (new ReplicateParamsCorrector($this->userId))->correctReplicateParamsByTemplateData($data);
		}
	}

	private function prepareMembers(array &$data)
	{
		$toInvite = array(
			'MAIL'    => array(),
			'NETWORK' => array(),
		);
		static::getInvitationsFrom($data, 'RESPONSIBLES', $toInvite);
		static::getInvitationsFrom($data, 'AUDITORS', $toInvite);
		static::getInvitationsFrom($data, 'ACCOMPLICES', $toInvite);

		if(count($toInvite['MAIL']))
		{
			foreach($toInvite['MAIL'] as $email => $user)
			{
				$toInvite['MAIL'][$email] = Integration\Mail\User::create($user, $this->errors);
			}
		}

		if(count($toInvite['NETWORK']))
		{
			foreach($toInvite['NETWORK'] as $id => $user)
			{
				$toInvite['MAIL'][$id] = Integration\SocialServices\User::create($user, $this->errors);
			}
		}

		static::placeMemberIds($data, 'RESPONSIBLES', $toInvite);
		static::placeMemberIds($data, 'AUDITORS', $toInvite);
		static::placeMemberIds($data, 'ACCOMPLICES', $toInvite);
	}

	private static function placeMemberIds(array &$data, $fieldName, array $toInvite)
	{
		if(array_key_exists($fieldName, $data) && is_array($data[$fieldName]))
		{
			foreach($data[$fieldName] as $k => $user)
			{
				$id = 0;
				if($user == (int) $user) // already a numeric ID
				{
					$id = intval($user);
				}
				elseif(is_array($user)) // user structure, ready for invitation
				{
					if(intval($user['ID']))
					{
						$id = $user['ID'];
					}
					elseif(array_key_exists($user['EMAIL'], $toInvite['MAIL']) && intval($toInvite['MAIL'][$user['EMAIL']]))
					{
						$id = intval($toInvite['MAIL'][$user['EMAIL']]);
					}
					elseif(array_key_exists($user['ID'], $toInvite['NETWORK']) && intval($toInvite['NETWORK'][$user['ID']]))
					{
						$id = intval($toInvite['NETWORK'][$user['ID']]);
					}
				}

				if($id)
				{
					$data[$fieldName][$k] = $id;
				}
				else
				{
					if($fieldName == 'RESPONSIBLES' && count($data[$fieldName]) == 1)
					{
						$data[$fieldName][$k] = 0; // template for new user it is?
					}
					else
					{
						// smth strange passed
						// todo: add error here?
						unset($data[$fieldName][$k]);
					}
				}
			}
		}
	}

	private static function getInvitationsFrom(array $data, $fieldName, &$toInvite)
	{
		if(array_key_exists($fieldName, $data) && is_array($data[$fieldName]))
		{
			foreach($data[$fieldName] as $user)
			{
				if(!is_array($user) && !intval($user)) // not a numeric ID or user structure
				{
					continue;
				}

				if(!intval($user['ID']) && \check_email($user['EMAIL']))
				{
					$toInvite['MAIL'][$user['EMAIL']] = $user;
				}
				elseif(User::isNetworkId($user['ID']))
				{
					$toInvite['NETWORK'][$user['ID']] = $user;
				}
			}
		}
	}
}