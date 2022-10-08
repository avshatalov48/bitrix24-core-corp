<?php

namespace Bitrix\DocumentGenerator\Service\ActualizeQueue;

use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Type\DateTime;

class Task
{
	public const ACTUALIZATION_POSITION_QUEUE = 'actualizationQueue';
	public const ACTUALIZATION_POSITION_BACKGROUND = 'actualizationBackground';
	public const ACTUALIZATION_POSITION_IMMEDIATELY = 'actualizationImmediately';

	protected int $documentId;
	protected ?int $userId;
	protected string $position;
	protected ?Document $document;
	protected ?DateTime $addedTime;

	public function __construct(int $documentId)
	{
		$this->documentId = $documentId;
		$this->userId = null;
		$this->position = self::ACTUALIZATION_POSITION_QUEUE;
		$this->document = null;
		$this->addedTime = null;
	}

	public static function createByDocument(Document $document)
	{
		$task = new self($document->ID);
		$task->setDocument($document);

		return $task;
	}

	public function setUserId(?int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}

	public function getUserId(): ?int
	{
		return $this->userId;
	}

	public function getDocumentId(): int
	{
		return $this->documentId;
	}

	public function setPosition(string $position): self
	{
		if (
			$position !== static::ACTUALIZATION_POSITION_QUEUE
			&& $position !== static::ACTUALIZATION_POSITION_BACKGROUND
			&& $position !== static::ACTUALIZATION_POSITION_IMMEDIATELY
		)
		{
			throw new ArgumentOutOfRangeException('position');
		}

		$this->position = $position;

		return $this;
	}

	public function getPosition(): string
	{
		return $this->position;
	}

	public function setDocument(Document $document): self
	{
		$this->document = $document;

		return $this;
	}

	public function getDocument(): ?Document
	{
		return $this->document;
	}

	public function isPositionImmediately(): bool
	{
		return $this->position === static::ACTUALIZATION_POSITION_IMMEDIATELY;
	}

	public function isPositionBackground(): bool
	{
		return $this->position === static::ACTUALIZATION_POSITION_BACKGROUND;
	}

	public function isPositionQueue(): bool
	{
		return $this->position === static::ACTUALIZATION_POSITION_QUEUE;
	}

	public function setAddedTime(DateTime $addedTime): self
	{
		$this->addedTime = $addedTime;

		return $this;
	}

	public function getAddedTime(): ?DateTime
	{
		return $this->addedTime;
	}
}