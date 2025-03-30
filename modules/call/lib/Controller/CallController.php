<?php

namespace Bitrix\Call\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Service\MicroService\BaseReceiver;
use Bitrix\Im\Call\Call;
use Bitrix\Im\V2\Call\CallFactory;
use Bitrix\Call\Error;
use Bitrix\Call\Logger\Logger;
use Bitrix\Call\Track\TrackError;
use Bitrix\Call\Track\TrackService;
use Bitrix\Call\DTO\TrackFileRequest;
use Bitrix\Call\DTO\TrackErrorRequest;
use Bitrix\Call\DTO\ControllerRequest;
use Bitrix\Call\Model\CallTrackTable;
use Bitrix\Call\Integration\AI\CallAIError;
use Bitrix\Call\Integration\AI\CallAISettings;


class CallController extends BaseReceiver
{
	public function getAutoWiredParameters(): array
	{
		return array_merge([
			new ExactParameter(
				TrackFileRequest::class,
				'trackFile',
				function ($className, $params = [])
				{
					return new $className($this->getSourceParametersList()[0]);
				}
			),
			new ExactParameter(
				TrackErrorRequest::class,
				'trackError',
				function ($className, $params = [])
				{
					return new $className($this->getSourceParametersList()[0]);
				}
			),
			new ExactParameter(
				ControllerRequest::class,
				'callRequest',
				function ($className, $params = [])
				{
					return new $className($this->getSourceParametersList()[0]);
				}
			),
		], parent::getAutoWiredParameters());
	}

	/**
	 * @restMethod call.CallController.finishCall
	 */
	public function finishCallAction(ControllerRequest $callRequest): ?array
	{
		Loader::includeModule('im');

		$call = CallFactory::searchActiveByUuid(Call::PROVIDER_BITRIX, $callRequest->callUuid);

		if (!isset($call))
 		{
			$this->addError(new Error(Error::CALL_NOT_FOUND));

			return null;
		}

		$isSuccess = $call->getSignaling()->sendFinish();

		if (!$isSuccess)
		{
			$this->addError(new Error(Error::SEND_PULL_ERROR));

			return null;
		}

		return ['result' => true];
	}

	/**
	 * @restMethod call.CallController.disconnectUser
	 */
	public function disconnectUserAction(ControllerRequest $callRequest): ?array
	{
		Loader::includeModule('im');

		$call = CallFactory::searchActiveByUuid(Call::PROVIDER_BITRIX, $callRequest->callUuid);

		if (!isset($call))
		{
			$this->addError(new Error(Error::CALL_NOT_FOUND));

			return null;
		}

		$isSuccess = $call->getSignaling()->sendHangup($callRequest->userId, $call->getUsers(), null);

		if (!$isSuccess)
		{
			$this->addError(new Error(Error::SEND_PULL_ERROR));

			return null;
		}

		return ['result' => true];
	}

	/**
	 * @restMethod call.CallController.trackReady
	 */
	public function trackReadyAction(TrackFileRequest $trackFile): ?array
	{
		Loader::includeModule('im');

		if ($log = CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
		}

		$call = CallFactory::searchActiveByUuid(Call::PROVIDER_BITRIX, $trackFile->callUuid);
		if (!isset($call))
		{
			$log && $logger->error("Call uuid:{$trackFile->callUuid} not found");
			$this->addError(new Error(Error::CALL_NOT_FOUND));
			return null;
		}

		$minDuration = CallAISettings::getRecordMinDuration();
		if ($call->getDuration() < $minDuration)
		{
			$log && $logger->error("Ignoring track:{$trackFile->url}, track #{$trackFile->trackId}. Call #{$call->getId()} was too short.");
			$this->addError(new CallAIError(CallAIError::AI_RECORD_TOO_SHORT));

			$call
				->disableAudioRecord()
				->disableAiAnalyze()
				->save();

			return null;
		}

		$trackList = CallTrackTable::query()
			->setSelect(['ID'])
			->where('CALL_ID', $call->getId())
			->where('EXTERNAL_TRACK_ID', $trackFile->trackId)
			->setLimit(1)
			->exec()
		;
		if ($trackList->getSelectedRowsCount() > 0)
		{
			$log && $logger->error("Ignoring track:{$trackFile->url}, track #{$trackFile->trackId}. Got duplicate request for call #{$call->getId()}");
			$this->addError(new Error(TrackError::TRACK_DUPLICATE_ERROR));
			return null;
		}

		$track = (new \Bitrix\Call\Track)
			->setCallId($call->getId())
			->setExternalTrackId($trackFile->trackId)
			->setDownloadUrl($trackFile->url)
		;

		if (in_array($trackFile->type, [\Bitrix\Call\Track::TYPE_TRACK_PACK, \Bitrix\Call\Track::TYPE_RECORD], true))
		{
			$track->setType($trackFile->type);
		}

		$mime = \Bitrix\Main\Web\MimeType::normalize($trackFile->mime);
		if ($mime)
		{
			$track->setFileMimeType($mime);
		}

		if ($trackFile->name)
		{
			$track->setFileName($trackFile->name);
		}
		$track->generateFilename();

		if ($trackFile->duration)
		{
			$track->setDuration($trackFile->duration);
		}

		if ($trackFile->size)
		{
			$track->setFileSize($trackFile->size);
		}

		$saveResult = $track->save();
		if (!$saveResult->isSuccess())
		{
			$log && $logger->error("Save track error: ".implode('; ', $saveResult->getErrorMessages()));
			$this->addErrors($saveResult->getErrors());
			return null;
		}

		$trackService = TrackService::getInstance();

		$processResult = $trackService->processTrack($track);
		if (!$processResult->isSuccess())
		{
			$this->addErrors($processResult->getErrors());
		}

		if ($trackService->doNeedDownloadTrack($track))
		{
			$downloadResult = $trackService->downloadTrackFile($track, true);
			if (!$downloadResult->isSuccess())
			{
				$this->addErrors($downloadResult->getErrors());
				return null;
			}
		}

		return ['result' => true];
	}

	/**
	 * @restMethod call.CallController.trackError
	 */
	public function trackErrorAction(TrackErrorRequest $trackError): ?array
	{
		Loader::includeModule('im');

		if ($log = CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
		}

		$call = CallFactory::searchActiveByUuid(Call::PROVIDER_BITRIX, $trackError->callUuid);
		if (!isset($call))
		{
			$log && $logger->error("Call uuid:{$trackError->callUuid} not found");
			$this->addError(new Error(Error::CALL_NOT_FOUND));
			return null;
		}

		$log && $logger->error("Got track error: ".($trackError->errorCode ?? '-'));

		$call
			->disableAudioRecord()
			->save();

		$call->getSignaling()
			->sendSwitchTrackRecordStatus(0, false, $trackError->errorCode);

		return ['result' => true];
	}
}