<?php
namespace Bitrix\Tasks\TourGuide\Internals;

/**
 * Class Step
 *
 * @package Bitrix\Tasks\TourGuide\Internals
 */
class Step
{
	private $maxTriesCount = 1;
	private $currentTry = 0;
	private $isFinished = false;
	private $additionalData = [];
	private $popupData = [];

	/**
	 * Step constructor.
	 *
	 * @param array $data
	 */
	public function __construct($data = [])
	{
		$this->setData($data);
	}

	public function getData(): array
	{
		return [
			'maxTriesCount' => $this->maxTriesCount,
			'currentTry' => $this->currentTry,
			'isFinished' => $this->isFinished,
			'additionalData' => $this->additionalData,
		];
	}

	public function setData(array $data): void
	{
		if (array_key_exists('maxTriesCount', $data))
		{
			$this->maxTriesCount = $data['maxTriesCount'];
		}
		if (array_key_exists('currentTry', $data))
		{
			$this->currentTry = $data['currentTry'];
		}
		if (array_key_exists('isFinished', $data))
		{
			$this->isFinished = $data['isFinished'];
		}
		if (array_key_exists('additionalData', $data))
		{
			$this->additionalData = $data['additionalData'];
		}
	}

	public function makeTry(): void
	{
		$this->currentTry++;

		if ($this->currentTry >= $this->maxTriesCount)
		{
			$this->finish();
		}
	}

	public function finish(): void
	{
		$this->isFinished = true;
	}

	public function isFinished(): bool
	{
		return $this->isFinished;
	}

	public function getAdditionalData(): array
	{
		return $this->additionalData;
	}

	public function setAdditionalData(array $additionalData): void
	{
		$this->additionalData = $additionalData;
	}

	public function getPopupData(): array
	{
		return $this->popupData;
	}

	public function setPopupData(array $popupData): void
	{
		$this->popupData = $popupData;
	}
}
