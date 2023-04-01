<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\TextPropertiesInterface;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Timeline\DeliveryController;
use Bitrix\Main\Type\DateTime;

class Universal extends Delivery
{
	public function getTitle(): ?string
	{
		$fields = $this->getFields();

		return $fields['MESSAGE_DATA']['TITLE'] ?? null;
	}

	public function getTags(): ?array
	{
		$fields = $this->getFields();

		$statusMap = [
			DeliveryController::MESSAGE_STATUS_SEMANTIC_SUCCESS => Tag::TYPE_SUCCESS,
			DeliveryController::MESSAGE_STATUS_SEMANTIC_PROCESS => Tag::TYPE_WARNING,
			DeliveryController::MESSAGE_STATUS_SEMANTIC_ERROR => Tag::TYPE_FAILURE,
		];
		$messageStatus = $fields['MESSAGE_DATA']['STATUS'] ?? null;
		$messageStatusSemantic = $fields['MESSAGE_DATA']['STATUS_SEMANTIC'] ?? null;

		$hasStatus = (
			$messageStatus
			&& $messageStatusSemantic
			&& isset($statusMap[$messageStatusSemantic])
		);
		if (!$hasStatus)
		{
			return null;
		}

		return [
			'status' => new Tag(
				$messageStatus,
				$statusMap[$messageStatusSemantic]
			),
		];
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$fields = $this->getFields();

		$descriptionsList = [];
		if (isset($fields['MESSAGE_DATA']['DESCRIPTION']))
		{
			$descriptionsList =
				is_array($fields['MESSAGE_DATA']['DESCRIPTION'])
					? $fields['MESSAGE_DATA']['DESCRIPTION']
					: [$fields['MESSAGE_DATA']['DESCRIPTION']]
			;
		}
		$moneyValues = $fields['MESSAGE_DATA']['MONEY_VALUES'] ?? [];
		$currency = $fields['MESSAGE_DATA']['CURRENCY'] ?? null;
		$dateValues = $fields['MESSAGE_DATA']['DATE_VALUES'] ?? [];

		foreach ($descriptionsList as $descriptionIndex => $description)
		{
			$contentBlock = new LineOfTextBlocks();
			$contentId = 'content' . $descriptionIndex;

			$lineContentBlocks = ContentBlockFactory::getBlocksFromTemplate(
				$description,
				array_merge(
					$this->getMoneyValuesReplacementBlocks($moneyValues, $currency),
					$this->getDateValuesReplacementBlocks($dateValues)
				),
			);
			foreach ($lineContentBlocks as $lineBlockIndex => $lineContentBlock)
			{
				if ($lineContentBlock instanceof TextPropertiesInterface)
				{
					$lineContentBlock->setColor(
						$lineContentBlock instanceof Money
							? Text::COLOR_BASE_90
							: Text::COLOR_BASE_70
					);
				}

				$lineId = $contentId . 'Line' . $lineBlockIndex;
				$contentBlock->addContentBlock($lineId, $lineContentBlock);
			}

			$result[$contentId] = $contentBlock;
		}

		return $result;
	}

	private function getMoneyValuesReplacementBlocks(array $moneyValues, ?string $currency): array
	{
		if ($currency === null)
		{
			return [];
		}

		return array_map(
			static function ($moneyValue) use ($currency)
			{
				if (!Currency::isCurrencyIdDefined($currency))
				{
					return (new Text())->setValue((string)$moneyValue);
				}

				return
					(new Money())
						->setOpportunity((float)$moneyValue)
						->setCurrencyId((string)$currency)
				;
			},
			$moneyValues
		);
	}

	private function getDateValuesReplacementBlocks(array $dateValues): array
	{
		return array_map(
			static function ($dateValue)
			{
				if (!isset($dateValue['VALUE']) || !isset($dateValue['FORMAT']))
				{
					return null;
				}

				return
					(new Date())
						->setDate(
							DateTime::createFromTimestamp((int)$dateValue['VALUE'])
						)
						->setFormat((string)$dateValue['FORMAT'])
					;
			},
			$dateValues
		);
	}

	private function getFields(): array
	{
		$fields = $this->getHistoryItemModel()->get('FIELDS');

		return is_array($fields) ? $fields : [];
	}
}
