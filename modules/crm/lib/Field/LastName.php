<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class LastName extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		if (!$item->isNew() && $this->isNameAndLastNameEmpty($item))
		{
			$factory = Container::getInstance()->getFactory($item->getEntityTypeId());

			$result->addError(
				new Error(
					Loc::getMessage(
						'CRM_FIELD_LAST_NAME_REQUIRED_ERROR',
						[
							'#NAME#' => $factory ? $factory->getFieldCaption(Item::FIELD_NAME_NAME) : Item::FIELD_NAME_NAME,
							'#LAST_NAME#' => $factory ? $factory->getFieldCaption(Item::FIELD_NAME_LAST_NAME) : Item::FIELD_NAME_LAST_NAME,
						]
					),
					self::ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE,
					[
						'fieldName' => $this->getName() . '|' . Item::FIELD_NAME_NAME,
					]
				)
			);
		}

		return $result;
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		$defaultLastName = $item->getTitlePlaceholder();
		if ($itemBeforeSave->isNew() && $this->isNameAndLastNameEmpty($item) && !empty($defaultLastName))
		{
			$result->setNewValue($this->getName(), $defaultLastName);
		}

		return $result;
	}

	private function isNameAndLastNameEmpty(Item $item): bool
	{
		if ($item->hasField(Item::FIELD_NAME_NAME))
		{
			$isNameEmpty = empty($item->getName());
		}
		else
		{
			$isNameEmpty = true;
		}

		return $isNameEmpty && $this->isItemValueEmpty($item);
	}
}
