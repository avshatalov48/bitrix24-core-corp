<?php

namespace Bitrix\Call\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Im\Call\Call;
use Bitrix\Im\Call\Registry;
use Bitrix\Call\Error;
use Bitrix\Call\Settings;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Track\TrackCollection;
use Bitrix\Call\Model\CallTrackTable;
use Bitrix\Call\ControllerClient;
use Bitrix\Call\Integration\AI\CallAIError;
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
	 * @param int $callId
	 * @return array<array>|null
	 */
	public function listAction(int $callId): ?array
	{
		$call = $this->getCall($callId);
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
	 * @restMethod call.Track.start
	 * @param int $callId
	 * @return array|null
	 */
	public function startAction(int $callId): ?array
	{
		$call = $this->getCall($callId);
		if (!$call)
		{
			return null;
		}

		$error = null;
		if (
			!Settings::isAIServiceEnabled()
			|| !CallAISettings::isTariffAvailable()
		)
		{
			$error = new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR);// AI service unavailable by tariff
		}
		elseif (!CallAISettings::isEnableBySettings())
		{
			$error = new CallAIError(CallAIError::AI_SETTINGS_ERROR);// Module AI is disabled by settings
		}
		elseif (!CallAISettings::isAgreementAccepted())
		{
			$error = new CallAIError(CallAIError::AI_AGREEMENT_ERROR);// AI service agreement must be accepted
		}
		elseif (!CallAISettings::isAutoStartRecordingEnable())
		{
			if (!CallAISettings::isBaasServiceHasPackage())
			{
				$error = new CallAIError(CallAIError::AI_NOT_ENOUGH_BAAS_ERROR);// It's not enough baas packages
			}
		}
		if ($error)
		{
			$this->addError($error);
			NotifyService::getInstance()->sendCallError($error, $call);

			return null;
		}

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

		return ['started' => true];
	}

	/**
	 * @restMethod call.Track.stop
	 * @param int $callId
	 * @return array|null
	 */
	public function stopAction(int $callId): ?array
	{
		$call = $this->getCall($callId);
		if (!$call)
		{
			return null;
		}

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
		}

		return BFile::createByFileId($track->getFileId(), $track->getFileName())->showInline(!$forceDownload);
	}

	/**
	 * Setup session flag to enable record all user's call.
	 * @restMethod call.Track.debugOn
	 * @return array
	 */
	public function debugOnAction(int $chatId): array
	{
		\Bitrix\Call\Integration\AI\ChatEventLog::chatDebugEnable($chatId);

		return ['record.audio' => 'on'];
	}

	/**
	 * Removes session flag that is enabled record all user's call.
	 * @restMethod call.Track.debugOff
	 * @return array
	 */
	public function debugOffAction(int $chatId): array
	{
		\Bitrix\Call\Integration\AI\ChatEventLog::chatDebugDisable($chatId);

		return ['record.audio' => 'off'];
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

	protected function getCall(int $callId): ?\Bitrix\Im\Call\Call
	{
		$call = Registry::getCallWithId($callId);
		if (!$call)
		{
			$this->addError(new Error("call_not_found", "Call not found"));
			return null;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		if (!$this->checkCallAccess($call, $currentUserId))
		{
			$this->addError(new Error("access_denied", "You do not have access to the parent call"));
			return null;
		}

		return $call;
	}

	protected function checkCallAccess(Call $call, int $userId): bool
	{
		if (!$call->checkAccess($userId))
		{
			$this->addError(new Error("You don't have access to the call " . $call->getId() . "; (current user id: " . $userId . ")", 'access_denied'));
			return false;
		}

		return true;
	}

	protected function sendSwitchTrackRecordStatus(Call $call, bool $isTrackRecordOn)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		if (!$this->checkCallAccess($call, $currentUserId))
		{
			$this->addError(new Error("access_denied", "You do not have access to the parent call"));
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