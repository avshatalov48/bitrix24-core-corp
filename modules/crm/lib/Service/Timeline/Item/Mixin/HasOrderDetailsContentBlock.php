<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm\Service\Sale\EntityLinkBuilder;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait HasOrderDetailsContentBlock
{
	public function getOrderDetailsContentBlock(array $options = []): ContentBlock\LineOfTextBlocks
	{
		$blockData = $this->getOrderDetailsContentBlockData();
		if (!$blockData)
		{
			return new ContentBlock\LineOfTextBlocks();
		}

		$id = $blockData['ID'] ?? 0;
		$accountNumber = $blockData['ACCOUNT_NUMBER'] ?? null;
		$date = $blockData['DATE'] ?? null;

		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_ORDER_ENTITY_TITLE_MSGVER_1',
			[
				'#NUMBER#' => empty($accountNumber) ? $id : $accountNumber,
				'#DATE#' => $date,
			]
		);

		$pregMatchResult = null;
		preg_match('/\<a\>(.*)\<\/a\>/', $title, $pregMatchResult);
		[$linkNumberAndDate, $numberAndDate] = $pregMatchResult;
		$title = str_replace($linkNumberAndDate, '#NUMBER_AND_DATE#', $title);

		$entityNameBlock =
			(new ContentBlock\Text())
				->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_ORDER_ENTITY_NAME'))
		;
		$action = $this->getOrderDetailsEntityNameAction($id, $options);

		if ($action)
		{
			$entityNameBlock
				->setFontSize(ContentBlock\Text::FONT_SIZE_SM)
				->setColor(ContentBlock\Text::COLOR_BASE_70)
			;
			$numberAndDateBlock = (new ContentBlock\Link())
				->setValue($numberAndDate)
				->setAction($action)
			;
		}
		else
		{
			$entityNameBlock->setColor(ContentBlock\Text::COLOR_BASE_90);
			$numberAndDateBlock = (new ContentBlock\Text())
				->setValue($numberAndDate)
				->setColor(ContentBlock\Text::COLOR_BASE_90)
			;
		}

		$price = $blockData['PRICE'] ?? null;
		$currency = $blockData['CURRENCY'] ?? null;
		$amountBlock = (new ContentBlock\Money())
			->setOpportunity((float)$price)
			->setCurrencyId((string)$currency)
		;

		return ContentBlockFactory::createLineOfTextFromTemplate(
			$title,
			[
				'#NAME#' => $entityNameBlock,
				'#NUMBER_AND_DATE#' => $numberAndDateBlock,
				'#AMOUNT#' => $amountBlock,
			],
		);
	}

	abstract protected function getAssociatedEntityModel(): ?AssociatedEntityModel;

	private function getOrderDetailsEntityNameAction(int $id, array $options = []): ?Action
	{
		$contextEntityTypeId = $this->getContext()->getEntityTypeId();

		/**
		 * Currently Order, Payment and Shipment pages are forced to work in \CCrmOwnerType::Order context
		 * which makes it impossible to show any links to these entities in this context
		 */
		if ($contextEntityTypeId === \CCrmOwnerType::Order)
		{
			return null;
		}

		$linkContext =
			(
				isset($options['LINK_CONTEXT'])
				&& $options['LINK_CONTEXT'] instanceof EntityLinkBuilder\Context
			)
				? $options['LINK_CONTEXT']
				: null
		;
		$detailLink = EntityLinkBuilder\EntityLinkBuilder::getInstance()->getOrderDetailsLink($id, $linkContext);
		if ($detailLink)
		{
			return new Action\Redirect(new Uri($detailLink));
		}

		return null;
	}

	private function getOrderDetailsContentBlockData(): ?array
	{
		if ($this->getModel()->getAssociatedEntityTypeId() === \CCrmOwnerType::Order)
		{
			$id = $this->getAssociatedEntityModel()->get('ID');
			$accountNumber = $this->getAssociatedEntityModel()->get('ACCOUNT_NUMBER');
			$date = $this->getAssociatedEntityModel()->get('DATE');
			$price = $this->getAssociatedEntityModel()->get('PRICE');
			$currency = $this->getAssociatedEntityModel()->get('CURRENCY');
		}
		else
		{
			$associatedEntityModelOrder = $this->getModel()->getAssociatedEntityModel()->get('ORDER');
			$associatedEntityModelOrder = $associatedEntityModelOrder ?? [];
			$orderFieldValues = $associatedEntityModelOrder['FIELD_VALUES'] ?? [];

			if (!$orderFieldValues)
			{
				return null;
			}

			$id  = $orderFieldValues['ID'] ?? null;
			$accountNumber = $orderFieldValues['ACCOUNT_NUMBER'] ?? null;
			$dateInsert =
				(
					isset($orderFieldValues['DATE_INSERT'])
					&& $orderFieldValues['DATE_INSERT'] instanceof DateTime
				)
					? $orderFieldValues['DATE_INSERT']
					: null
			;
			$date = $dateInsert
				? \FormatDate(
					Context::getCurrent()->getCulture()->getLongDateFormat(),
					$dateInsert->getTimestamp()
				)
				: ''
			;
			$price = $orderFieldValues['PRICE'] ?? null;
			$currency = $orderFieldValues['CURRENCY'] ?? null;
		}

		return [
			'ID' => $id,
			'ACCOUNT_NUMBER' => $accountNumber,
			'DATE' => $date,
			'PRICE' => $price,
			'CURRENCY' => $currency,
		];
	}
}
