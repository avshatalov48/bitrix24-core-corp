<?php

declare(strict_types=1);

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Model;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Viewer\FilePreviewTable;

class Preview extends Model
{
	protected ?int $fileId;
	protected ?int $previewId;
	protected ?int $previewImageId;
	protected ?DateTime $createdAt;
	protected ?DateTime $touchedAt;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @return string
	 */
	public static function getTableClassName(): string
	{
		return FilePreviewTable::class;
	}

	/**
	 * Returns the list of pair for mapping.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes(): array
	{
		return [
			'ID' => 'id',
			'FILE_ID' => 'fileId',
			'PREVIEW_ID' => 'previewId',
			'PREVIEW_IMAGE_ID' => 'previewImageId',
			'CREATED_AT' => 'createdAt',
			'TOUCHED_AT' => 'touchedAt',
		];
	}

	/**
	 * Returns id of file (table {b_file}).
	 * @return int|null
	 */
	public function getFileId(): ?int
	{
		return $this->fileId;
	}

	/**
	 * Returns id of file (table {b_file}) that is a preview.
	 * @return int|null
	 */
	public function getPreviewId(): ?int
	{
		return $this->previewId;
	}

	/**
	 * Returns id of file (table {b_file}) that is a image preview.
	 * @return int|null
	 */
	public function getPreviewImageId(): ?int
	{
		return $this->previewImageId;
	}

	/**
	 * Returns time of create object.
	 * @return DateTime|null
	 */
	public function getCreatedAt(): ?DateTime
	{
		return $this->createdAt;
	}

	/**
	 * Returns time of touch object.
	 * @return DateTime|null
	 */
	public function getTouchedAt(): ?DateTime
	{
		return $this->touchedAt;
	}
}
