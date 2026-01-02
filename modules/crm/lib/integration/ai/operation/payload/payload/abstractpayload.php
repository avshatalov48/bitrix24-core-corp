<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Payload;

use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\Payload\Prompt;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadInterface;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use CCrmOwnerType;
use RuntimeException;

abstract class AbstractPayload implements PayloadInterface
{
	protected array $markers = [];
	protected array $encodedMarkers = [];

	private Date $currentDate;

	public function __construct(
		protected readonly int $userId,
		protected readonly ItemIdentifier $identifier
	)
	{
		$this->currentDate = new Date();
	}
	
	public function setMarkers(array $markers): PayloadInterface
	{
		$this->markers = $markers;

		return $this;
	}

	final public function setEncodedMarkers(array $encodedMarkers): PayloadInterface
	{
		$this->encodedMarkers = $encodedMarkers;

		return $this;
	}
	
	final public function getResult(): Result
	{
		return (new Result())->setData(['payload' => $this->getPayload()]);
	}
	
	final public function getUserName(?int $userId): string
	{
		if ($userId <= 0)
		{
			return '';
		}
		
		return Container::getInstance()->getUserBroker()->getName($userId) ?? '';
	}
	
	final public function getCompanyName(?int $companyId): string
	{
		if ($companyId <= 0)
		{
			return '';
		}

		return Container::getInstance()->getCompanyBroker()->getTitle($companyId) ?? '';
	}
	
	final public function getActivity(): ?array
	{
		if ($this->identifier->getEntityTypeId() === CCrmOwnerType::Activity)
		{
			return Container::getInstance()->getActivityBroker()->getById($this->identifier->getEntityId());
		}
		
		return null;
	}
	
	final public function getCurrentYear(): string
	{
		return $this->currentDate->format('Y');
	}
	
	final public function getCurrentMonth(): string
	{
		return $this->currentDate->format('m');
	}
	
	final public function getCurrentDay(): string
	{
		return $this->currentDate->format('d');
	}
	
	protected function getPayload(): IPayload
	{
		if (!$this->isMarketsValid($this->markers))
		{
			throw new RuntimeException('Markers are not valid');
		}
		
		return (new Prompt($this->getPayloadCode()))->setMarkers($this->markers);
	}
	
	private function isMarketsValid(array $input): bool
	{
		// check that keys are strings
		foreach ($input as $key => $value)
		{
			if (!is_string($key))
			{
				return false;
			}
		}
		
		// @todo: add additional checks ...
		
		return true;
	}
}
