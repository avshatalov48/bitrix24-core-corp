<?php

namespace Bitrix\Call;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Call\Model\EO_CallTrack;
use Bitrix\Call\Track\TrackError;
use Bitrix\Call\Model\CallTrackTable;


class Track extends EO_CallTrack
{
	public const
		TYPE_RECORD = 'record',
		TYPE_TRACK_PACK = 'track_pack'
	;

	public function attachTempFile(): Result
	{
		$result = new Result;

		$mimeFileType = $this->getFileMimeType();
		$attachFile = \CFile::makeFileArray($this->getTempPath(), $mimeFileType);
		if (empty($attachFile))
		{
			return $result->addError(new TrackError(TrackError::SAVE_FILE_ERROR, 'Temporaraly file not found'));
		}

		$attachFile['MODULE_ID'] = 'call';
		if ($this->getFileName())
		{
			$attachFile['name'] = $this->getFileName();
			$attachFile['ORIGINAL_NAME'] = $this->getFileName();
		}

		$fileId = \CFile::saveFile($attachFile, 'call');

		if (!$fileId)
		{
			return $result->addError(new TrackError(TrackError::SAVE_FILE_ERROR, 'Could not save file'));
		}

		$this
			->setFileId($fileId)
			->setFileSize((int)$attachFile['size'])
			->unsetTempPath()
			->unsetDownloadUrl()
			->setDownloaded(true)
			->save()
		;

		return $result->setData(['fileId' => $fileId]);
	}

	public function attachToDisk(): Result
	{
		$result = new Result();

		$chatId = $this->fillCall()?->getChatId();
		if ($chatId)
		{
			if ($this->hasDiskFileId())
			{
				$diskFileId = $this->getDiskFileId();
			}
			else
			{
				if (!Loader::includeModule('im') || !Loader::includeModule('disk'))
				{
					return $result->addError(
						new TrackError(TrackError::DISK_ATTACH_ERROR, 'Can not put file on chat disk')
					);
				}
				$diskFileId = \CIMDisk::UploadFileFromMain($chatId, [$this->getFileId()])[0];
			}

			if ($diskFileId)
			{
				$this->setDiskFileId($diskFileId)->save();

				$type = match (true)
				{
					$this->getType() == self::TYPE_RECORD => \Bitrix\Im\V2\Link\File\FileItem::AUDIO_SUBTYPE,
					str_contains($this->getFileMimeType(), 'audio/') => \Bitrix\Im\V2\Link\File\FileItem::AUDIO_SUBTYPE,
					str_contains($this->getFileMimeType(), 'video/') => \Bitrix\Im\V2\Link\File\FileItem::MEDIA_SUBTYPE,
					default => \Bitrix\Im\V2\Link\File\FileItem::OTHER_SUBTYPE
				};

				$file = \Bitrix\Im\V2\Entity\File\FileItem::initByDiskFileId($diskFileId);
				$link = (new \Bitrix\Im\V2\Link\File\FileItem)
					->setSubtype($type)
					->setAuthorId($this->getCall()->getInitiatorId())
					->setChatId($this->getCall()->getChatId())
					->setEntity($file)
				;
				if ($link->save()->isSuccess())
				{
					\Bitrix\Im\V2\Link\Push::getInstance()
						->sendFull($link, 'fileAdd', ['CHAT_ID' => $link->getChatId()]);
				}

				$result->setData(['diskFileId' => $diskFileId]);
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function drop(): Result
	{
		$deleted = false;
		if ($this->hasDiskFileId() && $this->getDiskFileId())
		{
			if (Loader::includeModule('im'))
			{
				\Bitrix\Im\V2\Link\File\FileItem::getByDiskFileId($this->getDiskFileId())?->delete();
			}
			if (Loader::includeModule('disk'))
			{
				$diskFile = \Bitrix\Disk\File::getById($this->getDiskFileId());
				if ($diskFile instanceof \Bitrix\Disk\File)
				{
					$deletedBy = $this->fillCall()?->getInitiatorId() ?? CurrentUser::get()->getId();
					$deleted = $diskFile->delete($deletedBy);
				}
			}
		}

		if (!$deleted && $this->hasFileId() && $this->getFileId())
		{
			\CFile::Delete($this->getFileId());
		}

		if ($this->hasExternalTrackId())
		{
			(new ControllerClient())->dropTrack($this);
		}

		return $this->delete();
	}

	/**
	 * @see \Bitrix\Call\Controller\Track::downloadAction
	 * @param bool $absolute
	 * @param bool $forceDownload
	 * @return string
	 */
	public function getUrl(bool $absolute = true, bool $forceDownload = false): string
	{
		$params = [
			'callId' => $this->getCallId(),
			'trackId' => $this->getId(),
		];
		if ($forceDownload)
		{
			$params['forceDownload'] = 1;
		}

		$url = UrlManager::getInstance()->create(
			'call.Track.download',
			[
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
					'call.Track.download',
					$params
				),
			],
			$absolute
		);

		if (Loader::includeModule('ai'))
		{
			$publicUrl = \Bitrix\AI\Config::getValue('public_url') ?? '';
			if (!empty($publicUrl))
			{
				$parsed = parse_url($publicUrl);
				$url = $url->withScheme($parsed['scheme'])->setHost($parsed['host']);
			}
		}

		return $url->getLocator();
	}

	public function toRestFormat(): array
	{
		return [
			'trackId' => $this->getId(),
			'type' => $this->getType(),
			'fileId' => $this->getFileId(),
			'diskFileId' => $this->getDiskFileId(),
			'duration' => $this->getDuration(),
			'fileSize' => $this->getFileSize(),
			'fileName' => $this->getFileName(),
			'mimeType' => $this->getFileMimeType(),
			'callId' => $this->getCallId(),
			'relUrl' => $this->getUrl(false),
			'url' => $this->getUrl(true, true),
			'dateCreate' => $this->getDateCreate(),
		];
	}

	public function toArray(): array
	{
		return [
			'TRACK_ID' => $this->getId(),
			'TYPE' => $this->getType(),
			'FILE_ID' => $this->getFileId(),
			'DISK_FILE_ID' => $this->getDiskFileId(),
			'DURATION' => $this->getDuration(),
			'FILE_SIZE' => $this->getFileSize(),
			'FILE_NAME' => $this->getFileName(),
			'MIME_TYPE' => $this->getFileMimeType(),
			'CALL_ID' => $this->getCallId(),
			'REL_URL' => $this->getUrl(false),
			'URL' => $this->getUrl(true, true),
			'DATE_CREATE' => $this->getDateCreate(),
		];
	}

	public function generateFilename(): self
	{
		$callId = $this->getCallId();
		$externalId = $this->getExternalTrackId();

		if ($this->getType() == self::TYPE_TRACK_PACK)
		{
			$this->setFileName("track-pack-{$callId}-{$externalId}.zip");
		}
		elseif ($this->getType() == self::TYPE_RECORD)
		{
			$fileName =
				Loc::getMessage('CALL_TRACK_RECORD_FILE_NAME', [
					'#CALL_ID#' => $callId,
					'#CALL_START#' => (new DateTime())->format('Y-m-d')
				])
				. ".ogg";

			$this->setFileName($fileName ?: "composed-{$callId}.ogg");
		}
		elseif (!$this->getFileName())
		{
			$this->setFileName("record-{$externalId}");
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function generateTemporaryPath(): self
	{
		$tempFilePath = \Bitrix\Main\Security\Random::getString(20);
		$this->setTempPath(\CFile::GetTempName('', $tempFilePath));

		return $this;
	}

	public static function getTrackForCall(int $callId, string $type): ?self
	{
		return CallTrackTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=CALL_ID' => $callId,
				'=TYPE' => $type,
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])?->fetchObject();
	}
}