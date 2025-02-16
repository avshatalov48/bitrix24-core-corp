<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Main\ORM;
use Bitrix\Main\Web\Json;
use Bitrix\Call\Model\EO_CallOutcome;
use Bitrix\Call\Integration\AI\Outcome\Property;
use Bitrix\Call\Model\EO_CallOutcomeProperty_Collection;
use Bitrix\Call\Model\CallOutcomePropertyTable;
use Bitrix\Call\Model\CallOutcomeTable;


class Outcome extends EO_CallOutcome
{
	private ?EO_CallOutcomeProperty_Collection $propertyCollection = null;

	public function getSenseContentClass(): ?string
	{
		return match ($this->getType())
		{
			SenseType::TRANSCRIBE->value => Outcome\Transcription::class,
			SenseType::SUMMARY->value => Outcome\Summary::class,
			SenseType::OVERVIEW->value => Outcome\Overview::class,
			SenseType::INSIGHTS->value => Outcome\Insights::class,
			default => null
		};
	}

	public function getSenseContent(): mixed
	{
		$class = $this->getSenseContentClass();
		if ($class)
		{
			return new $class($this);
		}

		return $this->getContent();
	}

	public function setProperty(string $code, mixed $value): self
	{
		if (empty($value))
		{
			return $this;
		}

		if ($this->propertyCollection === null)
		{
			$this->propertyCollection = new EO_CallOutcomeProperty_Collection;
		}

		$prop = new Property;
		$prop
			->setOutcome($this)
			->setCode($code)
			->setContent(!is_string($value) ? Json::encode($value) : $value)
		;

		$this->propertyCollection->add($prop);

		return $this;
	}

	public function getProperty(string $code): ?Property
	{
		foreach ($this->getProps() as $prop)
		{
			if ($prop->getCode() == $code)
			{
				return $prop;
			}
		}

		return null;
	}

	public function saveProps(): ORM\Data\Result|ORM\Data\AddResult|ORM\Data\UpdateResult
	{
		if ($this->propertyCollection !== null)
		{
			return $this->propertyCollection->save();
		}

		return new ORM\Data\Result;
	}

	public function getProps(): EO_CallOutcomeProperty_Collection
	{
		if ($this->propertyCollection === null)
		{
			if ($this->getId() > 0)
			{
				$this->propertyCollection = CallOutcomePropertyTable::query()
					->setSelect(['*'])
					->where('OUTCOME_ID', $this->getId())
					->fetchCollection()
				;
			}
			else
			{
				$this->propertyCollection = new EO_CallOutcomeProperty_Collection;
			}
		}

		return $this->propertyCollection;
	}

	public function fillProps(EO_CallOutcomeProperty_Collection $propertyCollection): self
	{
		$this->propertyCollection = $propertyCollection;
		return $this;
	}

	public function appendProps(Property $property): self
	{
		if ($this->propertyCollection === null)
		{
			$this->propertyCollection = new EO_CallOutcomeProperty_Collection;
		}

		$this->propertyCollection->add($property);

		return $this;
	}

	/**
	 * @param array $jsonData
	 * @return self
	 */
	public function fillFromJson(array $jsonData): self
	{
		foreach ($jsonData as $code => $value)
		{
			$this->setProperty($code, $value);
		}

		return $this;
	}

	public function toRestFormat(): array
	{
		return [
			'outcomeId' => $this->getId(),
			'trackId' => $this->getId(),
			'type' => $this->getType(),
			'callId' => $this->getCallId(),
			'content' => $this->getContent(),
		];
	}

	public function toArray(): array
	{
		return [
			'OUTCOME_ID' => $this->getId(),
			'TRACK_ID' => $this->getId(),
			'TYPE' => $this->getType(),
			'CALL_ID' => $this->getCallId(),
			'CONTENT' => $this->getContent(),
		];
	}

	public static function getOutcomeForCall(int $callId, SenseType $senseType): ?self
	{
		return CallOutcomeTable::getList([
			'filter' => [
				'=CALL_ID' => $callId,
				'=TYPE' => $senseType->value
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1
		])?->fetchObject();
	}
}