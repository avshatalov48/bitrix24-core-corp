<?
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

use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\SocialServices\User;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util;

final class Template extends \Bitrix\Tasks\Dispatcher\PublicAction
{
	/**
	 * Get the task template
	 */
	public function get($id, array $parameters = array())
	{
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
	 */
	public function add(array $data)
	{
		$result = array();

		// todo: check $data here, check for publicly-readable\writable keys\values

		$this->prepareMembers($data);
		$this->prepareReplicateParams($data);

		if($this->errors->checkNoFatals())
		{
			$template = new Item\Task\Template($data);
			$saveResult = $template->save();

			$this->errors->load($saveResult->getErrors());

			if($template->getId())
			{
				$result['ID'] = $template->getId();
				// todo: also DATA and CAN keys here...
			}
		}

		return $result;
	}

	/**
	 * Update the task template with some new data
	 */
	public function update($id, array $data)
	{
		$result = array();

		if(!($id = $this->checkId($id)))
		{
			return $result;
		}
		$result['ID'] = $id;

		// todo: check $data here, check for publicly-readable\writable keys\values

		$this->prepareMembers($data);
		$this->prepareReplicateParams($data);

		if($this->errors->checkNoFatals())
		{
			$template = new Item\Task\Template($id);
			$template->setData($data);
			$saveResult = $template->save();

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
		if ($data['REPLICATE_PARAMS'])
		{
			$data['REPLICATE_PARAMS'] = ReplicateParamsCorrector::correctReplicateParamsByTemplateData($data);
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