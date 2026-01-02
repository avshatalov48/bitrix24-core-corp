<?php

namespace Bitrix\Call\Controller;

use Bitrix\Call\Idempotence;
use Bitrix\Call\Signaling;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Im\Call\CallUser;
use Bitrix\Im\Call\Integration\EntityType;
use Bitrix\Im\Call\Registry;
use Bitrix\Im\Call\Util;
use Bitrix\Im\V2\Call\CallFactory;
use Bitrix\Call\Error;
use Bitrix\Call\DTO;
use Bitrix\Call\JwtCall;
use Bitrix\Call\Integration\AI\CallAISettings;
use Bitrix\Call\Controller\Filter\UniqueRequestFilter;

class Call extends JwtController
{
	protected const LOCK_TTL = 10; // in seconds

	public function getAutoWiredParameters(): array
	{
		return array_merge([
			new ExactParameter(
				DTO\CallRequest::class,
				'callRequest',
				$this->decodeJwtParameter()
			),
			new ExactParameter(
				DTO\CallUserRequest::class,
				'callUserRequest',
				$this->decodeJwtParameter()
			),
			new ExactParameter(
				DTO\CallTokenRequest::class,
				'tokenRequest',
				function ($className, $params = [])
				{
					$parameters = $this->getSourceParametersList()[0];
					$chatData = new DTO\CallTokenRequest($parameters);
					return $chatData;
				}
			),
			new ExactParameter(
				DTO\UserRequest::class,
				'userRequest',
				function ($className, $params = [])
				{
					$parameters = $this->getSourceParametersList()[0];
					$userRequest = new DTO\UserRequest($parameters);
					return $userRequest;
				}
			),
			new ExactParameter(
				DTO\CallPushRequest::class,
				'pushRequest',
				$this->decodeJwtParameter()
			),
		], parent::getAutoWiredParameters());
	}

	public function configureActions()
	{
		return [
			'startCall' => [
				'+prefilters' => [
					new UniqueRequestFilter(),
				],
			],
			'finishCall' => [
				'+prefilters' => [
					new UniqueRequestFilter(),
				],
			],
			'createChildCall' => [
				'+prefilters' => [
					new UniqueRequestFilter(),
				],
			],
			'userStatus' => [
				'+prefilters' => [
					new UniqueRequestFilter(),
				],
			],
			'startPush' => [
				'+prefilters' => [
					new UniqueRequestFilter()
				]
			],
		];
	}

	/**
	 * Return call token
	 *
	 * @restMethod call.Call.getCallToken
	 *
	 * @param DTO\CallTokenRequest $tokenRequest
	 * @return array
	 */
	public function getCallTokenAction(DTO\CallTokenRequest $tokenRequest): array
	{
		$callToken = $tokenRequest->chatId
			? JwtCall::getCallToken($tokenRequest->chatId, $tokenRequest->additionalData)
			: '';

		return [
			'callToken' => $callToken,
			'userToken' => JwtCall::getUserJwt((int)$this->getCurrentUser()->getId()),
		];
	}

	/**
	 * @restMethod call.Call.startCall
	 *
	 * @param DTO\CallRequest $callRequest
	 * @return array|null
	 */
	public function startCallAction(DTO\CallRequest $callRequest): array|null
	{
		Loader::includeModule('im');

		// Validate required parameters
		if (!$callRequest->chatId)
		{
			$this->addError(new Error('Chat ID is required', 'missing_chat_id'));
			return [
				'result' => false,
				'errorCode' => 'missing_chat_id',
				'errorMessage' => 'Chat ID is required',
			];
		}

		if (!$callRequest->initiatorUserId)
		{
			$this->addError(new Error('Initiator user ID is required', 'missing_initiator_user_id'));
			return [
				'result' => false,
				'errorCode' => 'missing_initiator_user_id',
				'errorMessage' => 'Initiator user ID is required',
			];
		}

		if (!$callRequest->provider)
		{
			$this->addError(new Error('Provider is required', 'missing_provider'));
			return [
				'result' => false,
				'errorCode' => 'missing_provider',
				'errorMessage' => 'Provider is required',
			];
		}

		if (!$callRequest->callUuid && !$callRequest->roomId)
		{
			$this->addError(new Error('Call UUID or Room ID is required', 'missing_call_identifier'));
			return [
				'result' => false,
				'errorCode' => 'missing_call_identifier',
				'errorMessage' => 'Call UUID or Room ID is required',
			];
		}

		try
		{
			$tokenVersion = JwtCall::getTokenVersion($callRequest->chatId);
			if ($tokenVersion > $callRequest->tokenVersion)
			{
				$this->addError(new Error('Call token version deprecated',  'call_token_version_deprecated'));

				return [
					'result' => false,
					'errorCode' => 'call_token_version_deprecated',
					'errorMessage' => 'Call token version deprecated',
				];
			}

			$userId = $callRequest->initiatorUserId;
			$roomId = $callRequest->roomId ?: $callRequest->callUuid;
			$entityId = \Bitrix\Im\Dialog::getDialogId($callRequest->chatId, $userId);

			// Terminate ALL active calls in this chat before starting new one
			\Bitrix\Call\Call::terminateAllCallsInChat($callRequest->chatId, null);

			$prevCall = CallFactory::searchActiveCall(
				type: $callRequest->callType,
				provider: $callRequest->provider,
				entityType: EntityType::CHAT,
				entityId: $entityId,
			);
			if ($prevCall instanceof \Bitrix\Im\Call\Call)
			{
				if ($prevCall->isAiAnalyzeEnabled())
				{
					$prevCall
						->disableAudioRecord()
						->disableAiAnalyze()
						->save()
					;
				}
				$prevCall->finish();
			}

			$lockName = static::getLockNameWithCallId('call_state', $roomId);
			if (!Application::getConnection()->lock($lockName, static::LOCK_TTL))
			{
				$this->addError(new \Bitrix\Main\Error('Could not get exclusive lock', 'could_not_lock'));
				return null;
			}

			$call = CallFactory::createWithEntity(
				type: $callRequest->callType,
				provider: $callRequest->provider,
				entityType: EntityType::CHAT,
				entityId: $entityId,
				initiatorId: $userId,
				callUuid: $roomId,
				scheme: \Bitrix\Im\Call\Call::SCHEME_JWT,
			);

			if ($call->hasErrors())
			{
				$this->addErrors($call->getErrors());
				return null;
			}

			\Bitrix\Call\Call::updateUserActiveCallsCache($userId);

			$this->setUserStateReady($call, $userId, $callRequest->legacyMobile);

			$users = array_diff($call->getUsers(), [$userId]);
			$this->inviteUsers(
				$call,
				$users,
				$callRequest->video,
				'N',
				'Y',
				'N',
				Signaling::MODE_WEB
			);

			Application::getConnection()->unlock($lockName);

			$callAIError = CallAISettings::isAIAvailableInCall();

			if ($callRequest->requestId)
			{
				Idempotence::addKey($callRequest->requestId);
			}

			return [
				'callId' => $call->getId(),
				'tokenVersion' => $tokenVersion,
				'autoStartAIRecording' => $call->autoStartRecording(),
				'AIAvailableInCall' => !$callAIError,
				'AIErrorCode' => $callAIError?->getCode(),
				'AIErrorMessage' => $callAIError?->getMessage(),
			];
		}
		catch (\Throwable $e)
		{
			$this->addError(new \Bitrix\Main\Error($e->getMessage(), $e->getCode()));
			return null;
		}
	}

	/**
	 * @restMethod call.Call.startPush
	 *
	 * @param DTO\CallPushRequest $pushRequest
	 * @return array|null
	 */
	public function startPushAction(DTO\CallPushRequest $pushRequest): ?array
	{
		try {
			Loader::includeModule('im');

			$callUuid = $pushRequest->roomId ?: $pushRequest->callUuid;
			$call = Registry::getCallWithUuid($callUuid);

			if (!$call)
			{
				$this->addError(new Error(Loc::getMessage('IM_REST_CALL_ERROR_CALL_NOT_FOUND'), 'call_not_found'));
				return null;
			}

			if ($call->getState() === \Bitrix\Im\Call\Call::STATE_FINISHED) {
				$this->addError(new Error('Call already finished', 'call_finished'));
				return null;
			}

			if (!$call->hasActiveUsers(false)) {
				$this->addError(new Error('Call has no active users', 'call_inactive'));
				return null;
			}

			if ($pushRequest->requestId)
			{
				Idempotence::addKey($pushRequest->requestId);
			}

			$excluded = array_merge(
				array_map('intval', $pushRequest->usersIds),
				[$pushRequest->initiatorUserId]
			);

			$allUsers = array_map('intval', $call->getUsers());
			$userIds = array_diff($allUsers, $excluded);

			$userIds = array_filter($userIds, function ($userId) use ($call) {
				return $call->checkAccess($userId);
			});

			if (!empty($userIds))
			{
				$call->sendInviteUsers(
					$pushRequest->initiatorUserId,
					$userIds,
					$pushRequest->legacyMobile,
					$pushRequest->video,
					true,
					Signaling::MODE_ALL
				);
			}

			return ['result' => true];
		}
		catch (\Throwable $e)
		{
			$this->addError(new \Bitrix\Main\Error($e->getMessage(), $e->getCode()));
			return null;
		}
	}

	/**
	 * @restMethod call.Call.finishCall
	 *
	 * @param DTO\CallRequest $callRequest
	 * @return array|null
	 */
	public function finishCallAction(DTO\CallRequest $callRequest): array|null
	{
		Loader::includeModule('im');

		// Validate required parameters
		if (!$callRequest->callUuid && !$callRequest->roomId)
		{
			$this->addError(new \Bitrix\Main\Error('Call UUID or Room ID is required', 'missing_call_identifier'));
			return null;
		}

		$callUuid = $callRequest->roomId ?: $callRequest->callUuid;

		// Lock to prevent race conditions with startCall
		$lockName = static::getLockNameWithCallId('call_state', $callUuid);
		if (!Application::getConnection()->lock($lockName, static::LOCK_TTL))
		{
			$this->addError(new \Bitrix\Main\Error('Could not get exclusive lock', 'could_not_lock'));
			return null;
		}

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			Application::getConnection()->unlock($lockName);
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}
		$userId = $callRequest->userId ?: $call->getInitiatorId();
		$call->setActionUserId($userId);

		if ($call->isAudioRecordEnabled())
		{
			$call->disableAudioRecord();
		}

		$call->save();
		$call->finish();

		// Terminate all other active calls in the same chat after this call finishes
		\Bitrix\Call\Call::terminateAllCallsInChat($call->getChatId(), $call->getId());

		\Bitrix\Call\Call::updateCallCache($call->getId());

		Application::getConnection()->unlock($lockName);

		if ($callRequest->requestId)
		{
			Idempotence::addKey($callRequest->requestId);
		}

		return [
			'call' => $call->toArray($userId),
			'connectionData' => $call->getConnectionData($userId),
			'logToken' => $call->getLogToken($userId)
		];
	}

	protected function inviteUsers(
		\Bitrix\Im\Call\Call $call,
		$userIds,
		$isVideo = 'N',
		$isLegacyMobile = 'N',
		$isShow = 'Y',
		$isRepeated = 'N',
		string $sendMode = Signaling::MODE_ALL
	): void
	{
		$usersToInvite = [];
		$existingUsers = [];
		foreach ($userIds as $userId)
		{
			$userId = (int)$userId;
			if (!$userId)
			{
				continue;
			}

			if (!$call->hasUser($userId))
			{
				if (!$call->addUser($userId))
				{
					continue;
				}
			}
			else if ($isRepeated === false && $call->getAssociatedEntity())
			{
				$existingUsers[] = $userId;
			}
			$usersToInvite[] = $userId;
			$callUser = $call->getUser($userId);
			if($callUser->getState() != CallUser::STATE_READY)
			{
				$callUser->updateState(CallUser::STATE_CALLING);
			}

			\Bitrix\Call\Call::updateUserActiveCallsCache($userId);
		}

		if (!empty($existingUsers))
		{
			$call->getAssociatedEntity()->onExistingUsersInvite($existingUsers);
		}

		if (count($usersToInvite) === 0)
		{
			$this->addError(new \Bitrix\Main\Error("No users to invite", "empty_users"));
			return;
		}

		$sendPush = $isRepeated !== true;
		$this->sendPushNotifications($call, $usersToInvite, $isLegacyMobile, $isVideo, $sendPush, $sendMode);

		$allUsers = $call->getUsers();
		$otherUsers = array_diff($allUsers, $userIds);
		$call->getSignaling()->sendUsersInvited(
			$this->getCurrentUser()->getId(),
			$otherUsers,
			$usersToInvite,
			$isShow
		);

		if ($call->getState() === \Bitrix\Im\Call\Call::STATE_NEW)
		{
			$call->updateState(\Bitrix\Im\Call\Call::STATE_INVITING);
		}
	}

	protected function sendPushNotifications(
		\Bitrix\Im\Call\Call $call,
		array $usersToInvite,
		$isLegacyMobile,
		$isVideo,
		bool $sendPush,
		string $sendMode = Signaling::MODE_ALL
	): void
	{
		$call->sendInviteUsers(
			$this->getCurrentUser()->getId(),
			$usersToInvite,
			$isLegacyMobile,
			$isVideo,
			$sendPush,
			$sendMode
		);
	}

	/**
	 * @restMethod call.Call.answer
	 *
	 * @param DTO\UserRequest $userRequest
	 * @return void|null
	 */
	public function answerAction(DTO\UserRequest $userRequest)
	{
		Loader::includeModule('im');

		$isLegacyMobile = $userRequest->legacyMobile === 'Y';
		$callUuid = $userRequest->roomId ?: $userRequest->callUuid;
		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage('IM_REST_CALL_ERROR_CALL_NOT_FOUND'), 'call_not_found'));
			return null;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		if (!$call->checkAccess($currentUserId))
		{
			return null;
		}

		$lockName = static::getLockNameWithCallId('user'.$currentUserId, $callUuid);
		if (!Application::getConnection()->lock($lockName, static::LOCK_TTL))
		{
			$this->addError(new \Bitrix\Main\Error('Could not get exclusive lock', 'could_not_lock'));
			return null;
		}

		$this->setUserStateReady($call, $currentUserId, $isLegacyMobile);
		Application::getConnection()->unlock($lockName);

		$call->getSignaling()->sendAnswer($currentUserId, $userRequest->callInstanceId, $isLegacyMobile);

		\Bitrix\Call\Call::updateCallCache($call->getId());
	}

	/**
	 * @restMethod call.Call.decline
	 *
	 * @param DTO\UserRequest $userRequest
	 * @return void|null
	 */
	public function declineAction(DTO\UserRequest $userRequest)
	{
		Loader::includeModule('im');

		$currentUserId = $this->getCurrentUser()->getId();
		$callUuid = $userRequest->roomId ?: $userRequest->callUuid;

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		if (!$call->checkAccess($currentUserId))
		{
			return null;
		}

		$callUser = $call->getUser($currentUserId);
		if (!$callUser)
		{
			$this->addError(new Error("User is not part of the call", "unknown_call_user"));
			return null;
		}

		if ($callUser->getState() === CallUser::STATE_READY)
		{
			$this->addError(new Error("Can not decline in {$callUser->getState()} user state", "wrong_user_state"));
			return null;
		}

		$lockName = static::getLockNameWithCallId('user'.$currentUserId, $callUuid);
		if (!Application::getConnection()->lock($lockName, static::LOCK_TTL))
		{
			$this->addError(new \Bitrix\Main\Error("Could not get exclusive lock", "could_not_lock"));
			return null;
		}

		if ($userRequest->code === 486)
		{
			$callUser->updateState(CallUser::STATE_BUSY);
		}
		else
		{
			$callUser->updateState(CallUser::STATE_DECLINED);
		}
		$callUser->updateLastSeen(new DateTime());
		Application::getConnection()->unlock($lockName);

		$userIds = $call->getUsers();
		$call->getSignaling()->sendHangup($currentUserId, $userIds, $userRequest->callInstanceId, $userRequest->code);

		if (!$call->hasActiveUsers())
		{
			$call->setActionUserId($currentUserId)->finish();
		}

		\Bitrix\Call\Call::updateCallCache($call->getId());
		\Bitrix\Call\Call::updateUserActiveCallsCache($currentUserId);
	}

	/**
	 * @restMethod call.Call.userStatus
	 *
	 * @param DTO\CallUserRequest $callUserRequest
	 * @return void|null
	 */
	public function userStatusAction(DTO\CallUserRequest $callUserRequest)
	{
		$isLegacyMobile = $callUserRequest->legacyMobile === "Y";
		$callUuid = $callUserRequest->roomId ?: $callUserRequest->callUuid;
		if (!$callUuid)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		Loader::includeModule('im');

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		if (!empty($callUserRequest->connectedUsers))
		{
			foreach ($callUserRequest->connectedUsers as $user)
			{
				if (!$call->checkAccess($user->userId))
				{
					continue;
				}

				$this->setUserStateReady($call, $user->userId, $isLegacyMobile);
			}

			$call->getSignaling()->sendConnectedUsers($callUserRequest->connectedUsers, $isLegacyMobile);
		}

		if (!empty($callUserRequest->disconnectedUsers))
		{
			foreach ($callUserRequest->disconnectedUsers as $user)
			{
				$callUser = $call->getUser($user->userId);

				if (!$callUser)
				{
					continue;
				}

				$callUser->updateState(CallUser::STATE_IDLE);
				$callUser->updateLastSeen(new DateTime());
			}

			$call->getSignaling()->sendDisconnectedUsers($callUserRequest->disconnectedUsers);
		}

		\Bitrix\Call\Call::updateCallCache($call->getId());
	}

	/**
	 * @restMethod call.Call.createChildCall
	 *
	 * @param DTO\CallRequest $callRequest
	 * @return array|null
	 */
	public function createChildCallAction(DTO\CallRequest $callRequest): ?array
	{
		Loader::includeModule('im');
		$parentCall = Registry::getCallWithUuid($callRequest->parentCallUuid);
		if (!$parentCall)
		{
			$this->addError(new Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		$currentUserId = $callRequest->userId;
		if (!$parentCall->checkAccess($currentUserId))
		{
			$this->addError(new Error("You do not have access to the parent call", "access_denied"));
			return null;
		}

		$childCall = $parentCall->createChildCall(
			$callRequest->roomId ?: $callRequest->callUuid,
			\Bitrix\Im\Dialog::getDialogId($callRequest->chatId, $currentUserId),
			$callRequest->provider,
			\Bitrix\Im\Call\Call::SCHEME_JWT,
			$currentUserId
		);
		if ($childCall->hasErrors())
		{
			$this->addErrors($childCall->getErrors());
			return null;
		}

		$this->setUserStateReady($childCall, $currentUserId, $callRequest->legacyMobile);

		\Bitrix\Call\Call::updateUserActiveCallsCache($currentUserId);

		$users = array_diff($childCall->getAssociatedEntity()->getUsers(), [$currentUserId]);

		$this->inviteUsers(
			$childCall,
			$users,
			$callRequest->video,
		);

		$callAIError = CallAISettings::isAIAvailableInCall();

		if ($callRequest->requestId)
		{
			Idempotence::addKey($callRequest->requestId);
		}

		return [
			'callId' => $childCall->getId(),
			'autoStartAIRecording' => $childCall->autoStartRecording(),
			'AIAvailableInCall' => !$callAIError,
			'AIErrorCode' => $callAIError?->getCode(),
			'AIErrorMessage' => $callAIError?->getMessage(),
		];
	}

	/**
	 * @restMethod call.Call.invite
	 *
	 * @param DTO\UserRequest $userRequest
	 * @return true|null
	 */
	public function inviteAction(DTO\UserRequest $userRequest): ?bool
	{
		$isVideo = ($userRequest->video === "Y");
		$isShow = ($userRequest->show === "Y");
		$isLegacyMobile = ($userRequest->legacyMobile === "Y");
		$isRepeated = ($userRequest->repeated === "Y");
		$userIds = array_map('intVal', $userRequest->users);
		$callUuid = $userRequest->roomId ?: $userRequest->callUuid;

		Loader::includeModule('im');

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		if (!$call->checkAccess($currentUserId))
		{
			return null;
		}

		if ($call->hasErrors())
		{
			$this->addErrors($call->getErrors());
			return null;
		}

		$call->getUser($currentUserId)?->update([
			'LAST_SEEN' => new DateTime(),
			'IS_MOBILE' => ($isLegacyMobile ? 'Y' : 'N')
		]);

		$lockName = static::getLockNameWithCallId('invite', $callUuid);
		if (!Application::getConnection()->lock($lockName, static::LOCK_TTL))
		{
			$this->addError(new \Bitrix\Main\Error("Could not get exclusive lock", "could_not_lock"));
			return null;
		}

		$this->inviteUsers($call, $userIds, $isVideo, $isLegacyMobile, $isShow, $isRepeated);

		Application::getConnection()->unlock($lockName);
		return true;
	}

	/**
	 * @restMethod call.Call.createChatForChildCall
	 *
	 * @param DTO\UserRequest $userRequest
	 * @return array|null
	 */
	public function createChatForChildCallAction(DTO\UserRequest $userRequest): array|null
	{
		Loader::includeModule('im');
		$currentUserId = $this->getCurrentUser()->getId();
		$callUuid = $userRequest->roomId ?: $userRequest->callUuid;

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		$lockName = static::getLockNameWithCallId('user'.$currentUserId, $callUuid);
		if (!Application::getConnection()->lock($lockName, static::LOCK_TTL))
		{
			$this->addError(new \Bitrix\Main\Error('Could not get exclusive lock', 'could_not_lock'));
			return null;
		}

		$users = array_merge($call->getUsers(), $userRequest->users);
		$result = \Bitrix\Im\V2\Chat\ChatFactory::getInstance()->addChat([
			'TYPE' => \Bitrix\Im\V2\Chat::IM_TYPE_CHAT,
			'AUTHOR_ID' =>$currentUserId,
			'USERS' => $users,
		]);

		if (!$result->isSuccess() || !$result->hasResult())
		{
			return ['result' => false];
		}

		$chat = $result->getResult()['CHAT'];
		$chatId = $chat->getChatId();
		if (!$chatId)
		{
			return ['result' => false];
		}
		$callToken = JwtCall::getCallToken($chatId, ['parentUuid' => $callUuid]);

		Application::getConnection()->unlock($lockName);

		return [
			'result' => true,
			'token' => $callToken,
			'chatId' => $chatId,
		];
	}

	/**
	 * @param \Bitrix\Im\Call\Call $call
	 * @param int $userId
	 * @param bool $isLegacyMobile
	 */
	protected function setUserStateReady(\Bitrix\Im\Call\Call $call, int $userId, bool $isLegacyMobile): void
	{
		$callUser = $call->getUser($userId);
		if ($callUser)
		{
			$callUser->update([
				'STATE' => CallUser::STATE_READY,
				'LAST_SEEN' => new DateTime(),
				'FIRST_JOINED' => $callUser->getFirstJoined() ?: new DateTime(),
				'IS_MOBILE' => $isLegacyMobile ? 'Y' : 'N',
			]);
		}
	}

	/**
	 * @restMethod call.Call.onShareScreen
	 *
	 * @param DTO\UserRequest $userRequest
	 * @return void|null
	 */
	public function onShareScreenAction(DTO\UserRequest $userRequest)
	{
		Loader::includeModule('im');
		$callUuid = $userRequest->roomId ?: $userRequest->callUuid;

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		if (!$call->checkAccess($currentUserId))
		{
			return null;
		}

		$callUser = $call->getUser($currentUserId);
		if ($callUser)
		{
			$callUser->update([
				'SHARED_SCREEN' => 'Y'
			]);
		}
	}

	/**
	 * @restMethod call.Call.onStartRecord
	 *
	 * @param DTO\UserRequest $userRequest
	 * @return void|null
	 */
	public function onStartRecordAction(DTO\UserRequest $userRequest)
	{
		Loader::includeModule('im');
		$callUuid = $userRequest->roomId ?: $userRequest->callUuid;

		$call = Registry::getCallWithUuid($callUuid);
		if (!$call)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("IM_REST_CALL_ERROR_CALL_NOT_FOUND"), "call_not_found"));
			return null;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		if (!$call->checkAccess($currentUserId))
		{
			return null;
		}

		$callUser = $call->getUser($currentUserId);
		if ($callUser)
		{
			$callUser->update([
				'RECORDED' => 'Y'
			]);
		}
	}

	/**
	 * @restMethod call.Call.tryJoinCall
	 * @param DTO\UserRequest $userRequest
	 * @return array|null
	 */
	public function tryJoinCallAction(DTO\UserRequest $userRequest): ?array
	{
		Loader::includeModule('im');
		$currentUserId = $this->getCurrentUser()->getId();
		$call = CallFactory::searchActiveCall(
			$userRequest->callType,
			$userRequest->provider,
			$userRequest->entityType,
			$userRequest->entityId,
			$currentUserId
		);
		if (!$call)
		{
			return ['success' => false];
		}

		if ($call->hasErrors())
		{
			$this->addErrors($call->getErrors());
			return null;
		}

		if (!$call->getAssociatedEntity()->checkAccess($currentUserId))
		{
			$this->addError(new \Bitrix\Main\Error("You can not access this call", 'access_denied'));
			return null;
		}

		if (!$call->hasUser($currentUserId))
		{
			$addedUser = $call->addUser($currentUserId);
			if (!$addedUser)
			{
				$this->addError(new Error("User limit reached",  "user_limit_reached"));
				return null;
			}
			$call->getSignaling()->sendUsersJoined($currentUserId, [$currentUserId]);
		}

		\Bitrix\Call\Call::updateCallCache($call->getId());

		return array_merge(
			['success' => true],
			$this->formatCallResponse($call)
		);
	}

	protected static function getLockNameWithCallId(string $prefix, string $callUuid): string
	{
		if (!empty($prefix) && !empty($callUuid))
		{
			return "{$prefix}_call_{$callUuid}";
		}

		return '';
	}

	/**
	 * @param \Bitrix\Im\Call\Call $call
	 * @param bool $isNew
	 * @return array{call: array, connectionData: array, users: array, userData: array, publicChannels: array, logToken: string, isNew: bool}
	 */
	protected function formatCallResponse(\Bitrix\Im\Call\Call $call, int $initiatorId = 0, bool $isNew = false): array
	{
		$currentUserId = $this->getCurrentUser()->getId();

		$users = $call->getUsers();
		$publicChannels = Loader::includeModule('pull')
			? \Bitrix\Pull\Channel::getPublicIds([
				'TYPE' => \CPullChannel::TYPE_PRIVATE,
				'USERS' => $users,
				'JSON' => true
			])
			: []
		;

		$response = [
			'call' => $call->toArray($initiatorId),
			'connectionData' => $call->getConnectionData($currentUserId),
			'users' => $users,
			'userData' => Util::getUsers($users),
			'publicChannels' => $publicChannels,
			'logToken' => $call->getLogToken($currentUserId),
			'callToken' => JwtCall::getCallToken($call->getChatId())
		];
		if ($isNew)
		{
			$response['isNew'] = $isNew;
		}

		return $response;
	}
}