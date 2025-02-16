<?php

namespace Bitrix\Call\Track;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Im\Call\Registry;
use Bitrix\Call;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Logger\Logger;
use Bitrix\Call\Integration\AI;
use Bitrix\Call\Integration\AI\CallAIError;
use Bitrix\Call\Integration\AI\CallAISettings;


final class TrackService
{
	protected const
		MAX_RETRY_COUNT = 3,
		RETRY_DELAY = 10
	;

	private static ?TrackService $service = null;

	private function __construct()
	{}

	public static function getInstance(): self
	{
		if (!self::$service)
		{
			self::$service = new self();
		}
		return self::$service;
	}

	public function doNeedDownloadTrack(Call\Track $track): bool
	{
		return
			!$track->getDownloaded()
			&& $track->hasDownloadUrl()
		;
	}

	public function doNeedNeedAttachToDisk(Call\Track $track): bool
	{
		if ($track->getType() != Call\Track::TYPE_RECORD)
		{
			return false;
		}

		return
			!$track->hasDiskFileId()
			&& $track->getDiskFileId() > 0
			&& $track->hasFileId()
		;
	}

	public function doNeedNeedAiProcessing(Call\Track $track): bool
	{
		if ($track->getType() != Call\Track::TYPE_TRACK_PACK)
		{
			return false;
		}

		$minDuration = \Bitrix\Call\Integration\AI\CallAISettings::getRecordMinDuration();
		if ($track->getDuration() > 0 && $track->getDuration() < $minDuration)
		{
			return false;
		}

		$taskList = Call\Model\CallAITaskTable::query()
			->setSelect(['ID'])
			->where('TRACK_ID', $track->getId())
			->setLimit(1)
			->exec()
		;

		return $taskList->getSelectedRowsCount() == 0;
	}

	public function processTrack(Call\Track $track): Result
	{
		$result = new Result();

		if ($log = \Bitrix\Call\Integration\AI\CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
			$logger->info(
				"Call track file: {$track->getFileName()}"
				. ", size:{$track->getFileSize()}b"
				. ", type:{$track->getType()}"
				. ", duration:{$track->getDuration()}"
				. ", source:{$track->getDownloadUrl()}"
				. ", url:{$track->getUrl()}"
			);
		}

		$event = $this->fireTrackReadyEvent($track);
		if (
			($eventResult = $event->getResults()[0] ?? null)
			&& $eventResult instanceof EventResult
			&& $eventResult->getType() == EventResult::ERROR
		)
		{
			$log && $logger->info('Processing track was canceled by event. TrackId:'.$track->getId());

			return $result;// cancel processing by event
		}

		$minDuration = \Bitrix\Call\Integration\AI\CallAISettings::getRecordMinDuration();
		if ($track->getDuration() > 0 && $track->getDuration() < $minDuration)
		{
			$log && $logger->error("Ignoring track:{$track->getUrl()}, track #{$track->getExternalTrackId()}. Call #{$track->getCallId()} was too short.");

			$error = new CallAIError(CallAIError::AI_RECORD_TOO_SHORT);

			if ($track->getType() == Call\Track::TYPE_TRACK_PACK)
			{
				$call = Registry::getCallWithId($track->getCallId());
				NotifyService::getInstance()->sendTaskFailedMessage($error, $call);
			}

			return $result->addError($error);
		}

		if ($this->doNeedNeedAiProcessing($track))
		{
			if (!CallAISettings::isCallAIEnable())
			{
				$log && $logger->error('Unable process track. Module AI is unavailable. TrackId:'.$track->getId());

				$error = new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR);

				$call = Registry::getCallWithId($track->getCallId());
				NotifyService::getInstance()->sendTaskFailedMessage($error, $call);

				return $result->addError($error);
			}
			/*
			if (!CallAISettings::isAutoStartRecordingEnable())
			{
				if (!CallAISettings::isBaasServiceHasPackage())
				{
					$log && $logger->error('Unable process track. It is not enough baas packages. TrackId:'.$track->getId());

					return $result->addError(new CallAIError(CallAIError::AI_NOT_ENOUGH_BAAS_ERROR, 'It is not enough baas packages'));
				}
			}
			*/

			$log && $logger->info('Start AI processing. TrackId:'.$track->getId());

			$aiService = AI\CallAIService::getInstance();
			$aiResult = $aiService->processTrack($track);
			if (!$aiResult->isSuccess())
			{
				$error = $aiResult->getError();

				$call = Registry::getCallWithId($track->getCallId());
				NotifyService::getInstance()->sendTaskFailedMessage($error, $call);

				$result->addErrors($aiResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param Call\Track $track
	 * @param bool $retryOnFailure
	 * @return Result
	 */
	public function downloadTrackFile(Call\Track $track, bool $retryOnFailure = true): Result
	{
		$result = new Result;

		if ($track->getFileId() > 0)
		{
			return $result->setData(['fileId' => $track->getFileId()]);
		}

		if ($log = \Bitrix\Call\Integration\AI\CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
		}

		if (empty($track->getDownloadUrl()))
		{
			$log && $logger->error('Download URL undefined');
			$result->addError(new TrackError(TrackError::EMPTY_DOWNLOAD_URL, 'Download URL undefined'));
		}
		else
		{
			$log && $logger->info('Downloading track. TrackId:'.$track->getId(). ' Url: ' . $track->getDownloadUrl());

			try
			{
				$tempPath = $track->generateTemporaryPath()->getTempPath();

				$httpClient = $this->instanceHttpClient();
				$isDownloadSuccess = $httpClient->download($track->getDownloadUrl(), $tempPath);

				if (!$isDownloadSuccess || $httpClient->getStatus() !== 200)
				{
					$result->addError(new TrackError(TrackError::DOWNLOAD_ERROR, 'Call track download failure'));

					$errors = [];
					foreach ($httpClient->getError() as $code => $message)
					{
						$result->addError(new TrackError(TrackError::DOWNLOAD_ERROR, $code . ": " . $message));
						$errors[] = $code . ": " . $message;
					}

					$error = !empty($errors) ? implode("; " , $errors) : $httpClient->getStatus();
					$log && $logger->error("Call track download failure; Error: " . $error);

					if ($retryOnFailure)
					{
						$log && $logger->info("Set delay download agent. TrackId:".$track->getId());
						$this->delayDownload($track->getId());
					}
				}
				else
				{
					$log && $logger->info("Track downloaded successfully into ".$track->getTempPath());
				}
			}
			catch (\Psr\Http\Client\ClientExceptionInterface $ex)
			{
				$log && $logger->error('Error caught during downloading record: ' . PHP_EOL . print_r($ex, true));
				$result->addError(new TrackError(Call\Track\TrackError::DOWNLOAD_ERROR, '('.$ex->getMessage().') '.$ex->getMessage()));
			}
			catch (SystemException $ex)
			{
				$log && $logger->error('Error caught during downloading record: ' . PHP_EOL . print_r($ex, true));
				$result->addError(new TrackError(Call\Track\TrackError::DOWNLOAD_ERROR, '('.$ex->getMessage().') '.$ex->getMessage()));
			}
		}

		if ($result->isSuccess())
		{
			$attachResult = $track->attachTempFile();
			if (!$attachResult->isSuccess())
			{
				$log && $logger->error('Attach track failure. Error: ' . implode('; ', $attachResult->getErrorMessages()));
				$result->addErrors($attachResult->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			if ($this->doNeedNeedAttachToDisk($track))
			{
				$diskResult = $track->attachToDisk();
				if (!$diskResult->isSuccess())
				{
					$log && $logger->error('Attach file to Disk failed. TrackId:'.$track->getId().' Error: '. implode('; ', $diskResult->getErrorMessages()));
					$result->addErrors($diskResult->getErrors());
				}
			}
		}

		if ($result->isSuccess())
		{
			$log && $logger->info("File successfully saved. TrackId:".$track->getId()." FileId:".$track->getFileId()." DiskFileId:".$track->getDiskFileId());
		}
		else
		{
			$this->fireTrackErrorEvent($track, $result->getError());
		}

		return $result;
	}

	/**
	 * @param int $trackId
	 * @param int $retryCount
	 * @return string
	 */
	public static function downloadAgent(int $trackId, int $retryCount = 1): string
	{
		if (!Loader::includeModule('call'))
		{
			return '';
		}

		$track = Call\Model\CallTrackTable::getById($trackId)->fetchObject();
		if (!$track || $track->getDownloaded() === true)
		{
			return '';
		}

		$trackService = self::getInstance();
		if ($trackService->downloadTrackFile($track, false)->isSuccess())
		{
			$trackService->processTrack($track);

			return '';
		}

		if ($retryCount >= self::MAX_RETRY_COUNT)
		{
			return '';
		}

		$retryCount ++;

		return __METHOD__ . "({$trackId}, {$retryCount});";
	}

	/**
	 * Setup agent to delayed download.
	 * @param int $trackId
	 * @param int $delay
	 * @return void
	 */
	protected function delayDownload(int $trackId, int $delay = self::RETRY_DELAY): void
	{
		$agents = \CAgent::getList([], [
			'MODULE_ID' => 'call',
			'NAME' => __CLASS__ . "::downloadAgent({$trackId}%);"
		]);
		if (!($row = $agents->fetch()))
		{
			/** @see self::downloadAgent() */
			\CAgent::AddAgent(
				__CLASS__ . "::downloadAgent({$trackId});",
				'call',
				'N',
				60,
				'',
				'Y',
				\ConvertTimeStamp(\time() + \CTimeZone::GetOffset() + $delay, 'FULL')
			);
		}
	}

	/**
	 * @return HttpClient
	 */
	protected function instanceHttpClient(): HttpClient
	{
		$httpClient = new HttpClient();
		$httpClient
			->waitResponse(true)
			->setTimeout(20)
			->setStreamTimeout(60)
			->disableSslVerification()
			->setHeader('User-Agent', 'Bitrix Call Client '.\Bitrix\Main\Service\MicroService\Client::getPortalType())
			->setHeader('Referer', \Bitrix\Main\Service\MicroService\Client::getServerName())
		;

		return $httpClient;
	}

	/**
	 * @event call:onCallTrackReady
	 * @param Call\Track $track
	 * @return Event
	 */
	protected function fireTrackReadyEvent(Call\Track $track): Event
	{
		$event = new Event('call', 'onCallTrackReady', ['track' => $track]);
		$event->send();

		return $event;
	}

	/**
	 * @event call:onCallTrackError
	 * @param Call\Track $track
	 * @return Event
	 */
	protected function fireTrackErrorEvent(Call\Track $track, \Bitrix\Main\Error $error): Event
	{
		$event = new Event('call', 'onCallTrackError', ['track' => $track, 'error' => $error]);
		$event->send();

		return $event;
	}
}