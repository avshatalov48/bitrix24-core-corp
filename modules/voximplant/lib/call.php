<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\CallCrmEntityTable;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\Model\CallUserTable;
use Bitrix\Voximplant\Routing\Node;

class Call
{
	protected $id;
	protected $configId;
	protected $userId;
	protected $portalUserId;
	protected $callId;
	protected $externalCallId;
	protected $incoming;
	protected $callerId;
	protected $status;
	protected $crm;
	protected $crmActivityId;
	protected $crmCallList;
	protected $crmBindings;
	protected $crmEntities;
	protected $accessUrl;
	protected $dateCreate;
	protected $restAppId;
	protected $externalLineId;
	protected $portalNumber;
	protected $stage;
	protected $ivrActionId;
	protected $queueId;
	protected $queueHistory = [];
	protected $sessionId;
	protected $callbackParameters;
	protected $comment;
	protected $worktimeSkipped = false;
	protected $sipHeaders = [];
	protected $gatheredDigits;
	protected $parentCallId;
	protected $lastPingDate;
	protected $executionGraph;

	protected $users = [];

	protected $signaling;
	protected $scenario;
	protected static $instances = [];
	protected static $externalInstances = [];

	protected $config;

	/**
	 * Call constructor. Do not use directly, use static constructor create or load instead.
	 */
	protected function __construct()
	{
		$this->signaling = new Signaling($this);
		$this->scenario = new Scenario($this);
	}

	/**
	 * Loads call from database.
	 *
	 * @param string $callId Id of the call.
	 * @return Call | false
	 */
	public static function load($callId)
	{
		if(static::$instances[$callId])
		{
			return static::$instances[$callId];
		}

		$fields = CallTable::getByCallId($callId);
		if(!$fields)
		{
			return false;
		}

		$users = [];
		$cursor = CallUserTable::getList([
			'select' => ['USER_ID', 'ROLE', 'STATUS'],
			'filter' => ['=CALL_ID' => $callId]
		]);

		while ($row = $cursor->fetch())
		{
			$users[$row['USER_ID']] = $row;
		}

		$instance = new static();
		static::$instances[$callId] = $instance;

		$instance->fromArray($fields);
		$instance->users = $users;
		return $instance;
	}

	public static function loadExternal($externalCallId)
	{
		if(static::$externalInstances[$externalCallId])
		{
			return static::$externalInstances[$externalCallId];
		}

		$fields = CallTable::getRow([
			'filter' => [
				'=EXTERNAL_CALL_ID' => $externalCallId
			]
		]);
		if(!$fields)
		{
			return false;
		}

		$users = [];
		$cursor = CallUserTable::getList([
			'select' => ['USER_ID', 'ROLE', 'STATUS'],
			'filter' => ['=CALL_ID' => $fields['CALL_ID']]
		]);

		while ($row = $cursor->fetch())
		{
			$users[$row['USER_ID']] = $row;
		}

		$instance = new static();
		static::$externalInstances[$externalCallId] = $instance;

		$instance->fromArray($fields);
		$instance->users = $users;
		return $instance;
	}

	/**
	 * Create new call with specified fields.
	 *
	 * @param array $fields
	 * @return Call
	 */
	public static function create(array $fields): Call
	{
		static::checkFields($fields);

		if($fields['CONFIG_ID'])
		{
			$config = \CVoxImplantConfig::GetConfig($fields['CONFIG_ID']);
		}

		if(!$fields['DATE_CREATE'])
		{
			$fields['DATE_CREATE'] = new DateTime();
		}
		if(!$fields['LAST_PING'])
		{
			$fields['LAST_PING'] = new DateTime();
		}

		$fields['CRM'] = 'Y';

		if(!$fields['QUEUE_ID'] && (int)$fields['INCOMING'] !== \CVoxImplantMain::CALL_OUTGOING)
		{
			if(isset($config['QUEUE_ID']))
			{
				$fields['QUEUE_ID'] = $config['QUEUE_ID'];
			}
			else
			{
				$fields['QUEUE_ID'] = \CVoxImplantMain::getDefaultGroupId();
			}
		}

		$instance = new static();
		$instance->config = $config;
		$instance->fromArray($fields);
		$instance->save();
		static::$instances[$fields['CALL_ID']] = $instance;
		return $instance;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getCallId()
	{
		return $this->callId;
	}

	/**
	 * @return string|null
	 */
	public function getExternalCallId()
	{
		return $this->externalCallId;
	}

	/**
	 * @return mixed
	 */
	public function getParentCallId()
	{
		return $this->parentCallId;
	}

	/**
	 * @return mixed
	 */
	public function getIncoming()
	{
		return $this->incoming;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 */
	public function updateUserId($userId)
	{
		$this->update(['USER_ID' => $userId]);
	}

	/**
	 * @return int
	 */
	public function getPortalUserId()
	{
		return (int)$this->portalUserId;
	}

	/**
	 * @param int $portalUserId
	 */
	public function updatePortalUserId($portalUserId)
	{
		$this->update(['PORTAL_USER_ID' => $portalUserId]);
	}

	public function isInternalCall(): bool
	{
		return (int)$this->incoming === \CVoxImplantMain::CALL_OUTGOING && ($this->portalUserId > 0 || $this->queueId > 0) ;
	}

	/**
	 * @return string
	 */
	public function getStage()
	{
		return $this->stage;
	}

	/**
	 * @param string $stage
	 */
	public function updateStage($stage)
	{
		$this->update(['STAGE' => $stage]);
	}

	/**
	 * @return int
	 */
	public function getQueueId()
	{
		return $this->queueId;
	}

	/**
	 * @return int
	 */
	public function getIvrActionId()
	{
		return $this->ivrActionId;
	}

	/**
	 * @param int $ivrActionId
	 */
	public function updateIvrActionId($ivrActionId)
	{
		$this->update(['IVR_ACTION_ID' => $ivrActionId]);
	}

	/**
	 * @return Node|null
	 */
	public function getExecutionGraph()
	{
		return $this->executionGraph;
	}

	/**
	 * @param Node $executionGraph
	 */
	public function updateExecutionGraph(Node $executionGraph)
	{
		$this->update(['EXECUTION_GRAPH' => $executionGraph]);
	}

	/**
	 * @param int $queueId
	 */
	public function moveToQueue($queueId)
	{
		$this->update([
			'QUEUE_ID' => $queueId,
			'QUEUE_HISTORY' => []
		]);
	}

	/**
	 * @param $userId
	 */
	public function moveToUser($userId)
	{
		$invitedUsers = array_filter($this->users, function ($user)
		{
			return ($user['STATUS'] == CallUserTable::STATUS_INVITING);
		});

		if(!empty($invitedUsers))
		{
			$this->removeUsers(array_keys($invitedUsers));
		}

		$fields = [
			'USER_ID' => $userId,
		];
		$queueHistory = $this->queueHistory;
		if(!in_array($userId, $queueHistory))
		{
			$queueHistory[] = $userId;
			$fields['QUEUE_HISTORY'] = $queueHistory;
		}
		$this->update($fields);
		$this->addUsers([$userId], CallUserTable::ROLE_CALLEE);
	}


	/**
	 * @return string
	 */
	public function getSessionId()
	{
		return $this->sessionId;
	}

	/**
	 * @param string $sessionId
	 */
	public function updateSessionId($sessionId)
	{
		$this->update(['SESSION_ID' => $sessionId]);
	}

	/**
	 * @return mixed
	 */
	public function getComment()
	{
		return $this->comment;
	}

	/**
	 * @return bool
	 */
	public function isWorktimeSkipped()
	{
		return $this->worktimeSkipped;
	}

	/**
	 * @return mixed
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * @return string
	 */
	public function getCallerId()
	{
		return (string)$this->callerId;
	}

	/**
	 * @return mixed
	 */
	public function getPortalNumber()
	{
		return $this->portalNumber;
	}

	/**
	 * @return string
	 */
	public function getAccessUrl()
	{
		return $this->accessUrl;
	}

	/**
	 * @return DateTime
	 */
	public function getDateCreate()
	{
		return $this->dateCreate;
	}

	/**
	 * @return mixed
	 */
	public function getRestAppId()
	{
		return $this->restAppId;
	}

	/**
	 * @return mixed
	 */
	public function getExternalLineId()
	{
		return $this->externalLineId;
	}

	/**
	 * Sets comment for the call.
	 *
	 * @param string $comment
	 * @return void
	 */
	public function setComment($comment)
	{
		$this->update(['COMMENT' => $comment]);
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Updates call status.
	 * @param string $status
	 */
	public function updateStatus($status)
	{
		$fields = ['STATUS' => $status];
		if($status == CallTable::STATUS_ENQUEUED)
		{
			$fields['QUEUE_HISTORY'] = [];
		}
		$this->update($fields);
	}

	/**
	 * Returns true if this call should be associated with crm.
	 * @return bool
	 */
	public function isCrmEnabled()
	{
		return true;
	}

	/**
	 * @return int
	 */
	public function getCrmActivityId()
	{
		return $this->crmActivityId;
	}

	/**
	 * @return mixed
	 */
	public function getCrmCallList()
	{
		return $this->crmCallList;
	}

	/**
	 * @param int $crmActivityId
	 */
	public function setCrmActivityId($crmActivityId)
	{
		$this->update(['CRM_ACTIVITY_ID' => $crmActivityId]);
	}

	public function getCrmEntities()
	{
		$this->loadCrmEntities();

		return $this->crmEntities;
	}

	/**
	 * @return array
	 */
	public function getCreatedCrmEntities()
	{
		$this->loadCrmEntities();

		$result = [];

		foreach ($this->crmEntities as $entity)
		{
			if($entity['IS_CREATED'] == 'Y')
			{
				$result[] = $entity;
			}
		}
		return $result;
	}

	public function getCreatedCrmLead()
	{
		$this->loadCrmEntities();

		$result = [];

		foreach ($this->crmEntities as $entity)
		{
			if($entity['IS_CREATED'] == 'Y' && $entity['ENTITY_TYPE'] === 'LEAD')
			{
				return (int)$entity['ENTITY_ID'];
			}
		}
		return $result;
	}

	public function updateCrmEntities(array $newEntities)
	{
		$this->crmEntities = $newEntities;
		foreach ($this->crmEntities as $entity)
		{
			CallCrmEntityTable::merge([
				'CALL_ID' => $this->callId,
				'ENTITY_TYPE' => $entity['ENTITY_TYPE'],
				'ENTITY_ID' => $entity['ENTITY_ID'],
				'IS_PRIMARY' => $entity['IS_PRIMARY'],
				'IS_CREATED' => $entity['IS_CREATED'],
			]);
		}
	}

	public function addCrmEntities(array $newEntities)
	{
		$this->loadCrmEntities();

		$this->updateCrmEntities(array_merge($this->crmEntities, $newEntities));
	}

	/**
	 * @return string|false
	 */
	public function getPrimaryEntityType()
	{
		$this->loadCrmEntities();

		foreach ($this->crmEntities as $entity)
		{
			if($entity['IS_PRIMARY'] == 'Y')
			{
				return $entity['ENTITY_TYPE'];
			}
		}
		return false;
	}

	/**
	 * @return int|false
	 */
	public function getPrimaryEntityId()
	{
		$this->loadCrmEntities();

		foreach ($this->crmEntities as $entity)
		{
			if($entity['IS_PRIMARY'] == 'Y')
			{
				return (int)$entity['ENTITY_ID'];
			}
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function getCrmBindings()
	{
		return $this->crmBindings;
	}

	/**
	 * @param array $crmBindings
	 */
	public function updateCrmBindings(array $crmBindings)
	{
		$this->update(['CRM_BINDINGS' => $crmBindings]);
	}

	/**
	 * @return array
	 */
	public function getUsers()
	{
		return $this->users;
	}

	public function getUserIds()
	{
		return array_keys($this->users);
	}

	/**
	 * Adds users to the call. Does not automatically sends invite to prevent synchronisation issues.
	 * Invite will be sent late, with the scenario request.
	 *
	 * @param int[] $users Array of user ids.
	 * @param string $role Role of users in the call.
	 * @params string $status User connection status.
	 * @return void
	 */
	public function addUsers(array $users, $role, $status = CallUserTable::STATUS_INVITING)
	{
		foreach ($users as $userId)
		{
			$userRecord = [
				'USER_ID' => $userId,
				'ROLE' => $role,
				'STATUS' => $status,
				'INSERTED' => new DateTime()
			];
			$this->users[$userId] = $userRecord;

			$dbRecord = $userRecord;
			$dbRecord['CALL_ID'] = $this->callId;
			CallUserTable::merge($dbRecord);
		}
	}

	/**
	 * @param array $users
	 * @param bool $sendTimeout
	 * @throws \Exception
	 */
	public function removeUsers(array $users, $sendTimeout = true)
	{
		foreach ($users as $userId)
		{
			CallUserTable::delete([
				'CALL_ID' => $this->callId,
				'USER_ID' => $userId
			]);

			unset($this->users[$userId]);
		}
		if(!empty($users))
		{
			if($sendTimeout)
			{
				$this->signaling->sendTimeout($users);
			}
			foreach ($users as $userId)
			{
				$userInfo = \CVoxImplantIncoming::getUserInfo($userId);
				if($userInfo['AVAILABLE'] == 'Y')
				{
					CallQueue::dequeueFirstUserCall($userId);
				}
			}
		}
	}

	public function removeAllInvitedUsers()
	{
		$usersToRemove = [];
		foreach ($this->users as $userId => $user)
		{
			if($user['STATUS'] == CallUserTable::STATUS_INVITING || $user['STATUS'] == CallUserTable::STATUS_CONNECTING)
			{
				$usersToRemove[] = $userId;
			}
		}

		if(!empty($usersToRemove))
		{
			$this->removeUsers($usersToRemove);
		}
	}

	public function updateUserStatus($userId, $status, $device = '')
	{
		if(!isset($this->users[$userId]))
		{
			throw new SystemException("User is not participant of the call");
		}

		$this->users[$userId]['STATUS'] = $status;
		if($device)
		{
			$this->users[$userId]['DEVICE'] = $device;
		}

		CallUserTable::update(['CALL_ID' => $this->callId, 'USER_ID' => $userId], [
			'STATUS' => $this->users[$userId]['STATUS'],
			'DEVICE' => $this->users[$userId]['DEVICE']
		]);
	}

	/**
	 * @return array
	 */
	public function getQueueHistory()
	{
		return $this->queueHistory;
	}

	public function updateQueueHistory(array $queueHistory)
	{
		$this->update(['QUEUE_HISTORY' => $queueHistory]);
	}

	public function addToQueueHistory(array $users)
	{
		foreach ($users as $userId)
		{
			if(!in_array($userId, $this->queueHistory))
			{
				$this->queueHistory[] = $userId;
			}
		}
		CallTable::update($this->id, ['QUEUE_HISTORY' => $this->queueHistory]);
	}

	public function clearQueueHistory()
	{
		$this->update(['QUEUE_HISTORY' => []]);
	}

	/**
	 * @param string $headerName
	 * @return mixed
	 */
	public function getSipHeader($headerName)
	{
		return isset($this->sipHeaders[$headerName]) ? $this->sipHeaders[$headerName] : null;
	}

	/**
	 * @param array $sipHeaders
	 */
	public function updateSipHeaders(array $sipHeaders)
	{
		$this->update(['SIP_HEADERS' => $sipHeaders]);
	}

	/**
	 * @return mixed
	 */
	public function getGatheredDigits()
	{
		return $this->gatheredDigits;
	}

	/**
	 * @param mixed $gatheredDigits
	 */
	public function updateGatheredDigits($gatheredDigits)
	{
		$this->update(['GATHERED_DIGITS' => $gatheredDigits]);
	}

	/**
	 * @return Signaling
	 */
	public function getSignaling()
	{
		return $this->signaling;
	}

	/**
	 * @return Scenario
	 */
	public function getScenario()
	{
		return $this->scenario;
	}

	/**
	 * @return mixed
	 */
	public function getLastPingDate()
	{
		return $this->lastPingDate;
	}

	/**
	 * @param DateTime $lastPingDate
	 */
	public function updateLastPingDate(DateTime $lastPingDate)
	{
		$this->update(['LAST_PING' => $lastPingDate]);
	}

	public function dequeue($userId)
	{
		$this->updateStatus(CallTable::STATUS_WAITING);
		$this->addUsers([$userId], CallUserTable::ROLE_CALLEE);

		$commandResult = $this->scenario->sendDequeue($userId, true);

		if(!$commandResult->isSuccess())
		{
			$this->finish();
		}

		return $commandResult;
	}

	public function handleUserAnswer($userId)
	{
		$this->updateUserStatus($userId, CallUserTable::STATUS_CONNECTING);
		$this->updateStatus(Model\CallTable::STATUS_CONNECTING);

		$this->signaling->sendAnswerSelf($userId);
		$this->scenario->sendAnswer($userId);
	}

	public function handleUserConnected($userId, $device)
	{
		$updatedFields = [
			'STATUS' => Model\CallTable::STATUS_CONNECTED
		];

		if(isset($this->users[$userId]))
		{
			$this->updateUserStatus($userId, CallUserTable::STATUS_CONNECTED, $device);

			if(in_array($this->incoming, [\CVoxImplantMain::CALL_INCOMING, \CVoxImplantMain::CALL_INCOMING_REDIRECT, \CVoxImplantMain::CALL_CALLBACK]))
			{
				$updatedFields['USER_ID'] = $userId;

				\CVoxImplantCrmHelper::updateCrmEntities($this->getCreatedCrmEntities(), ['ASSIGNED_BY_ID' => $userId]);
			}
		}
		$this->update($updatedFields);

		$userToRemove = [];
		foreach ($this->users as $userId => $user)
		{
			if ($user['STATUS'] == CallUserTable::STATUS_INVITING || $user['STATUS'] == CallUserTable::STATUS_CONNECTING)
			{
				$userToRemove[] = $userId;
			}
			else if ($user['STATUS'] == CallUserTable::STATUS_CONNECTED)
			{
				if ($this->isInternalCall() && !$this->portalUserId && $userId != $this->getUserId())
				{
					$this->updatePortalUserId($userId);
					$this->signaling->sendUpdatePortalUser($this->getUserId());
				}
				$this->signaling->sendStart($userId, $user['DEVICE']);
			}
		}

		$this->removeUsers($userToRemove);
	}

	/**
	 * Finishes current call.
	 *
	 * @param array $additionalParams Additional params to pass to timeout event.
	 * @return void
	 */
	public function finish(array $additionalParams = [])
	{
		$childCalls = static::getChildCalls($this->getCallId());
		foreach ($childCalls as $childCallId)
		{
			$childCall = Call::load($childCallId);
			if($childCall)
			{
				$childCall->finish();
			}
		}

		if($this->status == CallTable::STATUS_FINISHED)
		{
			return;
		}

		if($additionalParams['externalHangup'])
		{
			static::delete($this->callId);
		}
		else
		{
			$this->update(['STATUS' => CallTable::STATUS_FINISHED]);
		}

		$users = array_keys($this->users);
		if(!empty($users))
		{
			$this->signaling->sendTimeout($users, $additionalParams);
		}

		foreach ($users as $userId)
		{
			$userInfo = \CVoxImplantIncoming::getUserInfo($userId);
			if($userInfo['AVAILABLE'] == 'Y')
			{
				CallQueue::dequeueFirstUserCall($userId);
			}
		}
	}

	/**
	 * @return void
	 */
	protected function loadCrmEntities()
	{
		if(is_null($this->crmEntities))
		{
			$result = CallCrmEntityTable::getList([
				'filter' => [
					'=CALL_ID' => $this->callId
				]
			])->fetchAll();

			$this->crmEntities = $result ?: [];
		}
	}

	/**
	 * Stores call in the database
	 *
	 * @return void
	 */
	protected function save()
	{
		if($this->id)
		{
			CallTable::update($this->id, $this->toArray());
		}
		else
		{
			$insertResult = CallTable::add($this->toArray());
			$this->id = $insertResult->getId();
		}
	}

	/**
	 * Performs update of the call fields.
	 *
	 * @param array $fields Call fields.
	 * @internal
	 * @throws \Exception
	 */
	public function update(array $fields)
	{
		$updateResult = CallTable::update($this->id, $fields);

		if($updateResult->isSuccess())
		{
			$updateData = $updateResult->getData();
			$this->fromArray($updateData);
		}
	}

	public static function delete($callId)
	{
		$childCalls = static::getChildCalls($callId);
		$callsToDelete = array_merge($childCalls, [$callId]);

		CallUserTable::deleteBatch([
			'=CALL_ID' => $callsToDelete
		]);

		foreach ($callsToDelete as $deletedCallId)
		{
			$callInstance = static::load($deletedCallId);
			if($callInstance)
			{
				CallTable::delete($callInstance->getId());
			}
			unset(static::$instances[$deletedCallId]);
		}
	}

	/**
	 * Returns array of the child calls ids.
	 * @param string $callId Id of the current call.
	 * @return string[]
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public static function getChildCalls($callId)
	{
		$cursor = CallTable::getList([
			'select' => ['CALL_ID'],
			'filter' => [
				'=PARENT_CALL_ID' => $callId
			]
		]);

		$result = [];
		while ($row = $cursor->fetch())
		{
			$result[] = $row['CALL_ID'];
		}
		return $result;
	}

	/**
	 * Validates new call fields.
	 *
	 * @param array $fields Call fields.
	 * @throws ArgumentException
	 */
	protected static function checkFields(array $fields)
	{
		if(!$fields['CALL_ID'])
		{
			throw new ArgumentException('CALL_ID is not specified');
		}

		if(!$fields['INCOMING'])
		{
			throw new ArgumentException('INCOMING is not specified');
		}
	}

	/**
	 * Converts call to array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'ID' => $this->id,
			'CONFIG_ID' => $this->configId,
			'USER_ID' => $this->userId,
			'PORTAL_USER_ID' => $this->portalUserId,
			'CALL_ID' => $this->callId,
			'EXTERNAL_CALL_ID' => $this->externalCallId,
			'INCOMING' => $this->incoming,
			'CALLER_ID' => $this->callerId,
			'STATUS' => $this->status,
			'CRM' => $this->crm,
			'CRM_ACTIVITY_ID' => $this->crmActivityId,
			'CRM_CALL_LIST' => $this->crmCallList,
			'CRM_BINDINGS' => $this->crmBindings,
			'ACCESS_URL' => $this->accessUrl,
			'DATE_CREATE' => $this->dateCreate,
			'REST_APP_ID' => $this->restAppId,
			'EXTERNAL_LINE_ID' => $this->externalLineId,
			'PORTAL_NUMBER' => $this->portalNumber,
			'STAGE' => $this->stage,
			'IVR_ACTION_ID' => $this->ivrActionId,
			'QUEUE_ID' => $this->queueId,
			'QUEUE_HISTORY' => $this->queueHistory,
			'SESSION_ID' => $this->sessionId,
			'CALLBACK_PARAMETERS' => $this->callbackParameters,
			'COMMENT' => $this->comment,
			'WORKTIME_SKIPPED' => $this->worktimeSkipped ? 'Y' : 'N',
			'SIP_HEADERS' => $this->sipHeaders,
			'GATHERED_DIGITS' => $this->gatheredDigits,
			'PARENT_CALL_ID' => $this->parentCallId,
			'LAST_PING' => $this->lastPingDate,
			'EXECUTION_GRAPH' => $this->executionGraph,
		];
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	protected function fromArray(array $fields)
	{
		$this->id = array_key_exists('ID', $fields) ? $fields['ID'] : $this->id;
		$this->configId = array_key_exists('CONFIG_ID', $fields) ? $fields['CONFIG_ID'] : $this->configId;
		$this->userId = array_key_exists('USER_ID', $fields) ? $fields['USER_ID'] : $this->userId;
		$this->portalUserId = array_key_exists('PORTAL_USER_ID', $fields) ? (int)$fields['PORTAL_USER_ID'] : $this->portalUserId;
		$this->callId = array_key_exists('CALL_ID', $fields) ? $fields['CALL_ID'] : $this->callId;
		$this->externalCallId = array_key_exists('EXTERNAL_CALL_ID', $fields) ? $fields['EXTERNAL_CALL_ID'] : $this->externalCallId;
		$this->incoming = array_key_exists('INCOMING', $fields) ? $fields['INCOMING'] : $this->incoming;
		$this->callerId = array_key_exists('CALLER_ID', $fields) ? $fields['CALLER_ID'] : $this->callerId;
		$this->status = array_key_exists('STATUS', $fields) ? $fields['STATUS'] : $this->status;
		$this->crm = array_key_exists('CRM', $fields) ? $fields['CRM'] : $this->crm;
		$this->crmActivityId = array_key_exists('CRM_ACTIVITY_ID', $fields) ? $fields['CRM_ACTIVITY_ID'] : $this->crmActivityId;
		$this->crmCallList = array_key_exists('CRM_CALL_LIST', $fields) ? $fields['CRM_CALL_LIST'] : $this->crmCallList;
		$this->crmBindings = array_key_exists('CRM_BINDINGS', $fields) ? $fields['CRM_BINDINGS'] : $this->crmBindings;
		$this->accessUrl = array_key_exists('ACCESS_URL', $fields) ? $fields['ACCESS_URL'] : $this->accessUrl;
		$this->dateCreate = array_key_exists('DATE_CREATE', $fields) ? $fields['DATE_CREATE'] : $this->dateCreate;
		$this->restAppId = array_key_exists('REST_APP_ID', $fields) ? $fields['REST_APP_ID'] : $this->restAppId;
		$this->externalLineId = array_key_exists('EXTERNAL_LINE_ID', $fields) ? $fields['EXTERNAL_LINE_ID'] : $this->externalLineId;
		$this->portalNumber = array_key_exists('PORTAL_NUMBER', $fields) ? $fields['PORTAL_NUMBER'] : $this->portalNumber;
		$this->stage = array_key_exists('STAGE', $fields) ? $fields['STAGE'] : $this->stage;
		$this->ivrActionId = array_key_exists('IVR_ACTION_ID', $fields) ? $fields['IVR_ACTION_ID'] : $this->ivrActionId;
		$this->queueId = array_key_exists('QUEUE_ID', $fields) ? (int)$fields['QUEUE_ID'] : $this->queueId;
		$this->queueHistory = array_key_exists('QUEUE_HISTORY', $fields) ? $fields['QUEUE_HISTORY'] : $this->queueHistory;
		$this->sessionId = array_key_exists('SESSION_ID', $fields) ? $fields['SESSION_ID'] : $this->sessionId;
		$this->callbackParameters = array_key_exists('CALLBACK_PARAMETERS', $fields) ? $fields['CALLBACK_PARAMETERS'] : $this->callbackParameters;
		$this->comment = array_key_exists('COMMENT', $fields) ? $fields['COMMENT'] : $this->comment;
		$this->worktimeSkipped = array_key_exists('WORKTIME_SKIPPED', $fields) ? $fields['WORKTIME_SKIPPED'] == 'Y' : $this->worktimeSkipped;
		$this->sipHeaders = array_key_exists('SIP_HEADERS', $fields) ? $fields['SIP_HEADERS'] : $this->sipHeaders;
		$this->gatheredDigits = array_key_exists('GATHERED_DIGITS', $fields) ? $fields['GATHERED_DIGITS'] : $this->gatheredDigits;
		$this->parentCallId = array_key_exists('PARENT_CALL_ID', $fields) ? $fields['PARENT_CALL_ID'] : $this->parentCallId;
		$this->lastPingDate = array_key_exists('LAST_PING', $fields) ? $fields['LAST_PING'] : $this->lastPingDate;
		$this->executionGraph = array_key_exists('EXECUTION_GRAPH', $fields) && ($fields['EXECUTION_GRAPH'] instanceof Node) ? $fields['EXECUTION_GRAPH'] : $this->executionGraph;

		if($fields['CONFIG_ID'] && !$this->config)
		{
			$this->config = \CVoxImplantConfig::GetConfig($fields['CONFIG_ID']);
		}
	}
}