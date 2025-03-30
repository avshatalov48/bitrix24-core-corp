<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics;

use Bitrix\Disk\Analytics\Context\SectionStrategyInterface;
use Bitrix\Disk\Analytics\Context\ElementStrategyInterface;
use Bitrix\Disk\Analytics\Enum\Event;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Analytics\AnalyticsEvent;

class FileEvent
{
	private DiskObject $diskObject;

	public function __construct(
		private readonly File $file,
		private readonly SectionStrategyInterface|ElementStrategyInterface $contextStrategy,
	)
	{
		$this->diskObject = new DiskObject($this->file);
	}

	public function buildAnalyticsEvent(Event $event): AnalyticsEvent
	{
		$analyticsEvent = new AnalyticsEvent($event->value, DiskAnalytics::TOOL, DiskAnalytics::CATEGORY);
		$analyticsEvent
			->setType($this->getFileType())
			->setP1('size_' . $this->getSize())
			->setP2('user_' . $this->getUserType())
			->setP5('ext_' . $this->getExtension())
		;

		if ($this->contextStrategy instanceof SectionStrategyInterface)
		{
			$analyticsEvent->setSection($this->contextStrategy->getSection());
			$analyticsEvent->setSubSection($this->contextStrategy->getSubSection());
		}

		if ($this->contextStrategy instanceof ElementStrategyInterface)
		{
			$analyticsEvent->setElement($this->contextStrategy->getElement());
		}

		if ($this->diskObject->isInCollab())
		{
			$analyticsEvent->setP4('collabId_' . $this->diskObject->getCollabId());
		}

		return $analyticsEvent;
	}

	private function getSize(): int
	{
		return (int)$this->file->getSize();
	}

	private function getExtension(): string
	{
		return mb_strtolower($this->file->getExtension());
	}

	private function getUserType(): string
	{
		$userWhoCreated = $this->file->getCreateUser();

		return match (true) {
			$userWhoCreated->isCollaber() => 'user-collaber',
			$userWhoCreated->isExtranetUser() => 'user-extranet',
			$userWhoCreated->isIntranetUser() => 'user-intranet',
			default => 'user-unknown',
		};
	}

	private function getFileType(): string
	{
		return match ((int)$this->file->getTypeFile()) {
			TypeFile::DOCUMENT => 'document',
			TypeFile::IMAGE => 'image',
			TypeFile::VIDEO => 'video',
			TypeFile::AUDIO => 'audio',
			TypeFile::ARCHIVE => 'archive',
			TypeFile::SCRIPT => 'script',
			TypeFile::KNOWN => 'known',
			TypeFile::VECTOR_IMAGE => 'vector_image',
			TypeFile::PDF => 'pdf',
			default => 'unknown',
		};
	}
}