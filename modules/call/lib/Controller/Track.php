<?php

namespace Bitrix\Call\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\Call\Call;
use Bitrix\Im\Call\Registry;
use Bitrix\Im\V2\Message\Send\SendingConfig;
use Bitrix\Call\Error;
use Bitrix\Call\Settings;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Track\TrackCollection;
use Bitrix\Call\Model\CallTrackTable;
use Bitrix\Call\ControllerClient;
use Bitrix\Call\Integration\AI\ChatMessage;
use Bitrix\Call\Integration\AI\CallAISettings;


class Track extends Engine\Controller
{
	public function configureActions(): array
	{
		return [
			'download' => [
				'prefilters' => [
					new Engine\ActionFilter\CloseSession(),
					new Engine\ActionFilter\HttpMethod([Engine\ActionFilter\HttpMethod::METHOD_GET])
				],
			],
		];
	}

	protected function init(): void
	{
		parent::init();
		Loader::includeModule('call');
		Loader::includeModule('im');
	}

	/**
	 * @restMethod call.Track.list
	 * @return array<array>|null
	 */
	public function listAction(): ?array
	{
		$call = $this->getCall();
		if (!$call)
		{
			return null;
		}

		/** @var TrackCollection $trackList */
		$trackList = CallTrackTable::query()
			->where('CALL_ID', $call->getId())
			->where('TYPE', \Bitrix\Call\Track::TYPE_RECORD)
			->setOrder(['ID' => 'DESC'])
			->exec()
		;

		return $trackList->toRestFormat();
	}

	/**
	 * @restMethod call.Track.get
	 * @param int $callId
	 * @param int $trackId
	 * @return array|null
	 */
	public function getAction(int $callId, int $trackId): ?array
	{
		$track = $this->getTrack($callId, $trackId);
		if (!$track)
		{
			return null;
		}

		return $track->toRestFormat();
	}

	/**
	 * @restMethod call.Track.drop
	 * @param int $callId
	 * @param int $trackId
	 * @return array|null
	 */
	public function dropAction(int $callId, int $trackId): ?array
	{
		$track = $this->getTrack($callId, $trackId);
		if (!$track)
		{
			return null;
		}

		$result = $track->drop();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return $track->toRestFormat();
	}

	/**
	 * @restMethod call.Track.destroy
	 * @return array|null
	 */
	public function destroyAction(): ?array
	{
		$call = $this->getCall();
		if (!$call)
		{
			return null;
		}

		if (Settings::isNewCallsEnabled())
		{
			$call
				->setActionUserId($this->getCurrentUser()->getId())
				->disableAudioRecord()
				->disableAiAnalyze()
				->save()
			;
		}
		else
		{
			$call
				->setActionUserId($this->getCurrentUser()->getId())
				->disableAudioRecord()
				->disableAiAnalyze()
				->save()
			;

			$this->sendSwitchTrackRecordStatus($call, false);

			(new ControllerClient)->destroyTrack($call);
		}

		Loader::includeModule('im');

		$chat = Chat::getInstance($call->getChatId());
		$message = ChatMessage::generateTrackDestroyMessage($call->getId(), $this->getCurrentUser()->getId(), $chat->getId());
		if ($message)
		{
			$message->setAuthorId($call->getInitiatorId());
			$sendingConfig = (new SendingConfig())
				->enableSkipCounterIncrements()
				->enableSkipUrlIndex()
			;
			NotifyService::getInstance()->sendMessageDeferred($chat, $message, $sendingConfig);
		}

		return ['destroyed' => true];
	}



	/**
	 * @restMethod call.Track.start
	 * @return array|null
	 */
	public function startAction(): ?array
	{
		$call = $this->getCall();
		if (!$call)
		{
			return null;
		}

		$error = CallAISettings::isAIAvailableInCall();
		if ($error)
		{
			$this->addError($error);
			NotifyService::getInstance()->sendCallError($error, $call);

			return null;
		}

		if (Settings::isNewCallsEnabled())
		{
			$call
				->setActionUserId($this->getCurrentUser()->getId())
				->enableAudioRecord()
				->enableAiAnalyze()
				->save()
			;
		}
		else
		{
			$result = (new ControllerClient)->startTrack($call);
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
				return null;
			}

			$call
				->setActionUserId($this->getCurrentUser()->getId())
				->enableAudioRecord()
				->enableAiAnalyze()
				->save()
			;

			$this->sendSwitchTrackRecordStatus($call, true);
		}

		return ['started' => true];
	}

	/**
	 * @restMethod call.Track.stop
	 * @return array|null
	 */
	public function stopAction(): ?array
	{
		$call = $this->getCall();
		if (!$call)
		{
			return null;
		}

		if (Settings::isNewCallsEnabled())
		{
			$call
				->setActionUserId($this->getCurrentUser()->getId())
				->disableAudioRecord()
				->save()
			;
		}
		else
		{
			$result = (new ControllerClient)->stopTrack($call);
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
				return null;
			}

			$call
				->setActionUserId($this->getCurrentUser()->getId())
				->disableAudioRecord()
				->save()
			;

			$this->sendSwitchTrackRecordStatus($call, false);
		}

		return ['stopped' => true];
	}

	/**
	 * @restMethod call.Track.download
	 * @param string $signedParameters
	 * @return BFile|null
	 */
	public function downloadAction(string $signedParameters): ?BFile
	{
		$params = $this->decodeSignedParameters($signedParameters);
		$callId = (int)$params['callId'];
		$trackId = (int)$params['trackId'];
		$forceDownload = (bool)($params['forceDownload'] ?? false);

		$call = Registry::getCallWithId($callId);
		if (!$call)
		{
			$this->addError(new Error("call_not_found", "Call not found"));
			return null;
		}

		$track = TrackCollection::getTrackById($callId, $trackId);
		if (!$track)
		{
			$this->addError(new Error("track_not_found", "Track not found"));
			return null;
		}

		if (!$track->getFileId())
		{
			$this->addError(new Error("track_file_not_found", "Track file not found"));
			return null;
		}

		return BFile::createByFileId($track->getFileId(), $track->getFileName())->showInline(!$forceDownload);
	}


	protected function getTrack(int $callId, int $trackId): ?\Bitrix\Call\Track
	{
		$call = $this->getCall($callId);
		if (!$call)
		{
			return null;
		}

		$track = TrackCollection::getTrackById($callId, $trackId);
		if (!$track)
		{
			$this->addError(new Error("track_not_found", "Track not found"));
			return null;
		}

		return $track;
	}


	protected function getCall(): ?\Bitrix\Im\Call\Call
	{
		if ($this->getRequest()->isPost())
		{
			$sourceParametersList = $this->getSourceParametersList()[0];
		}
		else
		{
			$sourceParametersList = $this->getSourceParametersList()[1];
		}

		$call = null;
		if (!empty($sourceParametersList['callUuid']))
		{
			$call = Registry::getCallWithUuid((string)$sourceParametersList['callUuid']);
		}
		elseif (!empty($sourceParametersList['callId']) && is_numeric($sourceParametersList['callId']))
		{
			$call = Registry::getCallWithId((int)$sourceParametersList['callId']);
		}
		if (!$call)
		{
			$this->addError(new Error("call_not_found", "Call not found"));
			return null;
		}

		$currentUserId = $this->getCurrentUser()?->getId();
		if (!$currentUserId || !$this->checkCallAccess($call, $currentUserId))
		{
			$this->addError(new Error("access_denied", "You do not have access to this call"));
			return null;
		}

		return $call;
	}


	protected function checkCallAccess(Call $call, int $userId): bool
	{
		if (!$call->checkAccess($userId))
		{
			$this->addError(new Error('access_denied', "You don't have access to the call " . $call->getId() . "; (current user id: " . $userId . ")"));
			return false;
		}

		return true;
	}

	protected function sendSwitchTrackRecordStatus(Call $call, bool $isTrackRecordOn)
	{
		$currentUserId = $this->getCurrentUser()?->getId();
		if (!$currentUserId || !$this->checkCallAccess($call, $currentUserId))
		{
			$this->addError(new Error("access_denied", "You do not have access to this call"));
			return null;
		}
		$call->getSignaling()->sendSwitchTrackRecordStatus($currentUserId, $isTrackRecordOn);
	}

	/**
	 * Sings and stores parameters.
	 * @param string $signedParameters Signed parameters of component as string.
	 * @return array
	 */
	protected function decodeSignedParameters(string $signedParameters): array
	{
		return \Bitrix\Main\Component\ParameterSigner::unsignParameters('call.Track.download', $signedParameters);
	}
}