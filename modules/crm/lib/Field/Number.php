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
use Bitrix\Main\DB\SqlExpression;

class Number extends Field
{
	protected const MAX_TRIES = 10;
	protected $numerator;

	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		if (!$item->isNew() && $this->isItemValueEmpty($item))
		{
			$item->set($this->getName(), $item->getId());
		}

		return $result;
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		if ($itemBeforeSave->isNew() && empty($item->get($this->getName())))
		{
			$number = $this->getNumberByEvent($item->getId());
			if (!$number)
			{
				$number = $this->getNumberByNumerator($item->getId());
			}
			if (!$number)
			{
				$number = $item->getId();
				if (!$this->isValueUnique($number))
				{
					$number = null;
				}
			}
			if ($number)
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

		if (!isset($this->settings['eventName']))
		{
			return null;
		}

		foreach (GetModuleEvents("crm", $this->settings['eventName'], true) as $arEvent)
		{
			$eventResult = ExecuteModuleEventEx($arEvent, [$id, 'NUMBER']);
			if (is_string($eventResult))
			{
				$number = $eventResult;
			}
		}

		if ($number && !$this->isValueUnique($number))
		{
			return null;
		}

		return $number;
	}

	protected function getNumberByNumerator(int $id): ?string
	{
		$numerator = $this->initNumerator($id);

		if ($numerator)
		{
			$tries = 0;
			while ($tries < static::MAX_TRIES)
			{
				$tries++;
				$number = $numerator->getNext();
				if ($this->isValueUnique($number))
				{
					return $number;
				}
			}
		}

		return null;
	}

	public function previewNextNumber(): ?string
	{
		$number = $this->getNumberByEvent(0);
		if (!$number)
		{
			$numerator = $this->initNumerator(0);
			if ($numerator)
			{
				$number = $numerator->previewNextNumber();
			}
		}

		return $number;
	}

	public function getNumeratorType(): ?string
	{
		return $this->settings['numeratorType'] ?? null;
	}

	public function getNumerator(): ?Numerator\Numerator
	{
		if ($this->numerator === null)
		{
			$numeratorType = $this->getNumeratorType();
			if (!$numeratorType)
			{
				return null;
			}
			$numeratorSettings = Numerator\Numerator::getOneByType($numeratorType);
			if (!$numeratorSettings) // try to create if not found
			{
				$this->createNumeratorTemplateIfNotExists($numeratorType, $this->getByMaxNumber());
				$numeratorSettings = Numerator\Numerator::getOneByType($numeratorType);
			}

			if ($numeratorSettings)
			{
				$this->numerator = Numerator\Numerator::load($numeratorSettings['id']);
			}
		}

		return $this->numerator;
	}

	protected function initNumerator(int $id): ?Numerator\Numerator
	{
		$numerator = $this->getNumerator();

		if ($numerator && !empty($this->settings['numeratorIdSettings']))
		{
			$source = [$this->settings['numeratorIdSettings'] => $id];
			$numerator->setDynamicConfig($source);
			$numerator->setHash($source);
		}

		return $numerator;
	}

	protected function getByMaxNumber(): ?string
	{
		global $DB;

		$tableClassName = $this->settings['tableClassName'] ?? null;
		if (!$tableClassName || !is_a($tableClassName, DataManager::class, true))
		{
			return null;
		}

		$tries = 0;
		while ($tries < static::MAX_TRIES)
		{
			$number = null;
			$tries++;
			$record = $tableClassName::getList([
				'select' => [
					new ExpressionField('LAST_NUMBER', 'MAX('.$DB->toNumber(new SqlExpression('?#', $this->getName())).')'),
				],
			])->fetch();
			if ($record && !empty($record['LAST_NUMBER']))
			{
				$number = $record['LAST_NUMBER'] + 1;
			}
			if ($this->isValueUnique($number))
			{
				return $number;
			}
		}

		return null;
	}

	protected function createNumeratorTemplateIfNotExists(string $numeratorType, $maxLastId = null): ?Numerator\Numerator
	{
		if ($this->numerator)
		{
			return $this->numerator;
		}

		$this->numerator = Numerator\Numerator::create();
		$this->numerator->setConfig([
			Numerator\Numerator::getType() =>
				[
					'name' => $this->getName(),
					'template' => '{NUMBER}',
					'type' => $numeratorType,
				],
			Numerator\Generator\SequentNumberGenerator::getType() =>
				[
					'start' => (int)$maxLastId,
					'isDirectNumeration' => true,
				],
		]);
		$this->numerator->save();

		return $this->numerator;
	}
}
