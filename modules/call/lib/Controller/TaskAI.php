<?php

namespace Bitrix\Call\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Engine;
use Bitrix\Main\Web\Json;
use Bitrix\Main\ArgumentException;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\Call\Call;
use Bitrix\Im\Call\Registry;
use Bitrix\Im\V2\Message\Send\SendingConfig;
use Bitrix\Call\Error;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Integration\AI\ChatMessage;
use Bitrix\Call\Integration\AI\CallAIService;
use Bitrix\Call\Model\CallTrackTable;
use Bitrix\Call\Model\CallAITaskTable;
use Bitrix\Call\Model\CallOutcomePropertyTable;

class TaskAI extends Engine\Controller
{
	protected function init(): void
	{
		parent::init();
		Loader::includeModule('call');
		Loader::includeModule('im');
	}

	public function configureActions(): array
	{
		return [
			'restart' => [
				'prefilters' => [
					new Engine\ActionFilter\HttpMethod([Engine\ActionFilter\HttpMethod::METHOD_GET, Engine\ActionFilter\HttpMethod::METHOD_POST])
				],
			],
		];
	}

	/**
	 * @restMethod call.TaskAI.restart
	 * @param int $callId
	 * @return array|null
	 */
	public function restartAction(int $callId): ?array
	{
		$call = $this->getCall($callId);
		if (!$call)
		{
			return null;
		}

		$currentUserId = $this->getCurrentUser()?->getId();
		if (!$currentUserId || !$this->checkCallAccess($currentUserId, $call))
		{
			$this->addError(new Error("access_denied", "You do not have access to this call"));
			return null;
		}

		$result = CallAIService::getInstance()->restartCallAiTask($callId);
		if ($result->isSuccess())
		{
			$chat = Chat::getInstance($call->getChatId());

			if (NotifyService::getInstance()->findMessage($chat->getId(), $callId, NotifyService::MESSAGE_TYPE_AI_START, 1) === null)
			{
				$message = ChatMessage::generateTaskStartMessage($callId, $chat);
				if ($message)
				{
					$sendingConfig = (new SendingConfig())
						->enableSkipCounterIncrements()
						->enableSkipUrlIndex()
					;
					NotifyService::getInstance()->sendMessageDeferred($chat, $message, $sendingConfig);
				}
			}
		}
		else
		{
			NotifyService::getInstance()->sendTaskFailedMessage($result->getError(), $call, -1);
		}

		return [];
	}

	/**
	 * @restMethod call.TaskAI.info
	 * @param int $callId
	 * @return array<array>|null
	 */
	public function infoAction(int $callId): ?array
	{
		if ($this->getScope() !== static::SCOPE_AJAX)
		{
			$this->addError(new Error('wrong_scope', 'Scope is not supported'));
			return null;
		}

		$call = $this->getCall($callId);
		if (!$call)
		{
			return null;
		}

		$currentUserId = $this->getCurrentUser()?->getId();
		if (!$currentUserId || !$this->checkCallAccess($currentUserId, $call))
		{
			$this->addError(new Error('access_denied', "You do not have access to this call"));
			return null;
		}

		$result = [];

		$result['CALL'] = [
			'ID' => $call->getId(),
			'TYPE' => $call->getType(),
			'INITIATOR_ID' => $call->getInitiatorId(),
			'PROVIDER' => $call->getProvider(),
			'STATE' => $call->getState(),
			'START_DATE' => $call->getStartDate(),
			'END_DATE' => $call->getEndDate(),
			'UUID' => $call->getUuid(),
			'RECORD_AUDIO' => $call->isAudioRecordEnabled(),
		];

		$trackList = CallTrackTable::query()
			->setSelect([
				'ID',
				'TYPE',
				'FILE_ID',
				'DISK_FILE_ID',
				'DURATION',
				'FILE_SIZE',
				'FILE_NAME',
				'FILE_MIME_TYPE',
				'CALL_ID',
			])
			->where('CALL_ID', $call->getId())
			->setOrder(['ID' => 'DESC'])
			->exec()
		;
		$result['TRACKS'] = [];
		while ($track = $trackList->fetchObject())
		{
			$result['TRACKS'][] = $track->toArray();
		}

		$taskList = CallAITaskTable::query()
			->setSelect([
				'ID',
				'TYPE',
				'CALL_ID',
				'HASH',
				'TRACK_ID',
				'STATUS',
				'ERROR_CODE',
				'ERROR_MESSAGE',
				'DATE_CREATE',
				'DATE_FINISHED',
			])
			->where('CALL_ID', $call->getId())
			->setOrder(['ID' => 'DESC'])
			->exec()
		;
		$result['AI_TASK'] = [];
		while ($task = $taskList->fetchObject())
		{
			$result['AI_TASK'][] = [
				'ID' => $task->getId(),
				'TYPE' => $task->getType(),
				'CALL_ID' => $task->getCallId(),
				'HASH' => $task->getHash(),
				'TRACK_ID' => $task->getTrackId(),
				'STATUS' => $task->getStatus(),
				'ERROR_CODE' => $task->getErrorCode(),
				'ERROR_MESSAGE' => $task->getErrorMessage(),
				'DATE_CREATE' => $task->getDateCreate(),
				'DATE_FINISHED' => $task->getDateFinished(),
			];
		}

		$outcomeList = CallOutcomePropertyTable::query()
			->setSelect([
				'OUTCOME_ID' => 'OUTCOME_ID',
				'TYPE' => 'OUTCOME.TYPE',
				'TRACK_ID' => 'OUTCOME.TRACK_ID',
				'DATE_CREATE' => 'OUTCOME.DATE_CREATE',
				'CODE' => 'CODE',
				'CONTENT' => 'CONTENT',
			])
			->where('OUTCOME.CALL_ID', $call->getId())
			->setOrder(['ID' => 'DESC'])
			->exec()
		;

		$result['AI_OUTCOME'] = [];
		while ($outcome = $outcomeList->fetch())
		{
			try
			{
				$jsonData = Json::decode($outcome['CONTENT']);
			}
			catch (ArgumentException)
			{
				$jsonData = htmlspecialcharsbx($outcome['CONTENT']);
			}
			$result['AI_OUTCOME'][] = [
				'OUTCOME_ID' => $outcome['OUTCOME_ID'],
				'TRACK_ID' => $outcome['TRACK_ID'],
				'TYPE' => $outcome['TYPE'],
				'DATE_CREATE' => $outcome['DATE_CREATE'],
				'CODE' => $outcome['CODE'],
				'CONTENT' => $jsonData,
			];
		}

		return $result;
	}

	protected function getCall(int $callId): ?\Bitrix\Im\Call\Call
	{
		$call = Registry::getCallWithId($callId);
		if (!$call)
		{
			$this->addError(new Error("call_not_found", "Call not found"));
			return null;
		}

		return $call;
	}

	protected function checkCallAccess(int $userId, Call $call): bool
	{
		if (!$call->checkAccess($userId))
		{
			$this->addError(new Error('access_denied', "You don't have access to the call " . $call->getId() . "; (current user id: " . $userId . ")"));
			return false;
		}

		return true;
	}
}