<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Numerator;
use Bitrix\Main\Result;

class Number extends Field
{
	protected const MAX_TRIES = 10;
	protected $numerator;

	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		if(!$item->isNew() && $this->isItemValueEmpty($item))
		{
			$item->set($this->getName(), $item->getId());
		}

		return $result;
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		if($itemBeforeSave->isNew())
		{
			$number = $this->getNumberByEvent($item->getId());
			if(!$number)
			{
				$number = $this->getNumberByNumerator($item->getId());
			}
			if(!$number)
			{
				$number = $item->getId();
				if(!$this->isValueUnique($number))
				{
					$number = null;
				}
			}
			if(!$number)
			{
				$number = $this->getByMaxNumber();
				if($number)
				{
					$this->createNumeratorTemplateIfNotExists($number);
				}
			}
			if($number)
			{
				$result->setNewValue($this->getName(), $number);
			}
			else
			{
				$result->addError(new Error('Could not generate new number'));
			}
		}

		return $result;
	}

	protected function getNumberByEvent(int $id): ?string
	{
		$number = null;

		if(!isset($this->settings['eventName']))
		{
			return null;
		}

		foreach(GetModuleEvents("crm", $this->settings['eventName'], true) as $arEvent)
		{
			$eventResult = ExecuteModuleEventEx($arEvent, [$id, 'NUMBER']);
			if (is_string($eventResult))
			{
				$number = $eventResult;
			}
		}

		if($number && !$this->isValueUnique($number))
		{
			return null;
		}

		return $number;
	}

	protected function getNumberByNumerator(int $id): ?string
	{
		$numerator = $this->getNumerator($id);

		if($numerator)
		{
			$tries = 0;
			while($tries < static::MAX_TRIES)
			{
				$tries++;
				$number = $numerator->getNext();
				if($this->isValueUnique($number))
				{
					return $number;
				}
			}
		}

		return null;
	}

	protected function getNumerator(int $id): ?Numerator\Numerator
	{
		if($this->numerator === null)
		{
			if(empty($this->settings['numeratorType']) || empty($this->settings['numeratorIdSettings']))
			{
				return null;
			}
			$numeratorSettings = Numerator\Numerator::getOneByType($this->settings['numeratorType']);
			if(!$numeratorSettings)
			{
				return null;
			}

			$this->numerator = Numerator\Numerator::load($numeratorSettings['id'], [$this->settings['numeratorIdSettings'] => $id]);
		}

		return $this->numerator;
	}

	protected function getByMaxNumber(): ?string
	{
		$tableClassName = $this->settings['tableClassName'] ?? null;
		if(!$tableClassName || !is_a($tableClassName, DataManager::class, true))
		{
			return null;
		}

		$tries = 0;
		while($tries < static::MAX_TRIES)
		{
			$number = null;
			$tries++;
			$record = $tableClassName::getList([
				'select' => [
					new ExpressionField('LAST_NUMBER', 'MAX(CAST(%s AS UNSIGNED))', [$this->getName()]),
				],
			])->fetch();
			if($record && !empty($record['LAST_NUMBER']))
			{
				$number = $record['LAST_NUMBER'] + 1;
			}
			if($this->isValueUnique($number))
			{
				return $number;
			}
		}

		return null;
	}

	protected function createNumeratorTemplateIfNotExists(int $maxLastId): self
	{
		if($this->numerator || empty($this->settings['numeratorType']))
		{
			return $this;
		}

		$numeratorForQuotes = Numerator\Numerator::create();
		$numeratorForQuotes->setConfig([
			Numerator\Numerator::getType() =>
				[
					'name' => $this->getName(),
					'template' => '{NUMBER}',
					'type' => $this->settings['numeratorType'],
				],
			Numerator\Generator\SequentNumberGenerator::getType() =>
				[
					'start' => $maxLastId,
					'isDirectNumeration' => true,
				],
		]);
		$numeratorForQuotes->save();

		return $this;
	}
}