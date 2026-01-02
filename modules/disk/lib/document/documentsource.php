<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Version;

class DocumentSource
{
	private ?File $file = null;
	private ?Version $version = null;
	private ?AttachedObject $attachedObject = null;

	private function __construct() {}

	public static function fromFile(File $file): self
	{
		$instance = new self();
		$instance->file = $file;

		return $instance;
	}

	public static function fromVersion(Version $version): self
	{
		$instance = new self();
		$instance->version = $version;

		return $instance;
	}

	public static function fromAttachedObject(AttachedObject $attachedObject): self
	{
		$instance = new self();
		$instance->attachedObject = $attachedObject;

		return $instance;
	}

	public function getFile(): ?File
	{
		return $this->file;
	}

	public function getVersion(): ?Version
	{
		return $this->version;
	}

	public function getAttachedObject(): ?AttachedObject
	{
		return $this->attachedObject;
	}

	public function getFileFromSource(): ?File
	{
		if ($this->file)
		{
			return $this->file;
		}

		if ($this->version)
		{
			return $this->version->getObject();
		}

		return $this->attachedObject->getFile();
	}

	public function hasValidFile(): bool
	{
		return $this->getFileFromSource() instanceof File;
	}
}