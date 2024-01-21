<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Item\HistoryItemModel;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Sale\EntityLinkBuilder\EntityLinkBuilder;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait HasPaymentDetailsContentBlock
{
	public function getPaymentDetailsContentBlock(): ContentBlock\LineOfTextBlocks
	{
		$result = new ContentBlock\LineOfTextBlocks();
		if ($this->getModel()->getAssociatedEntityTypeId() !== \CCrmOwnerType::OrderPayment)
		{
			return $result;
		}

		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_TITLE',
			[
				'#NUMBER#' => $this->getAssociatedEntityModel()->get('ACCOUNT_NUMBER'),
				'#DATE#' => $this->getAssociatedEntityModel()->get('DATE'),
			]
		);

		$entityNameLocPhrase =
			$this->isTerminalPayment()
				? 'CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_NAME_VIA_TERMINAL'
				: 'CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_NAME'
		;

		$entityNameBlock = (new ContentBlock\Text())->setValue(
			Loc::getMessage($entityNameLocPhrase)
		);

		$action = $this->getPaymentDetailsEntityNameAction();
		if ($action)
		{
			$entityNameBlock
				->setFontSize(ContentBlock\Text::FONT_SIZE_SM)
				->setColor(ContentBlock\Text::COLOR_BASE_70)
			;
			$titleBlock = (new ContentBlock\Link())
				->setValue($title)
				->setAction($action)
			;
		}
		else
		{
			$entityNameBlock->setColor(ContentBlock\Text::COLOR_BASE_90);
			$titleBlock = (new ContentBlock\Text())
				->setValue($title)
				->setColor(ContentBlock\Text::COLOR_BASE_90)
			;
		}
		$result
			->addContentBlock('entityName', $entityNameBlock)
			->addContentBlock('title', $titleBlock)
		;

		$sum = $this->getAssociatedEntityModel()->get('RAW_SUM');
		$currency = $this->getAssociatedEntityModel()->get('RAW_CURRENCY');
		if ($sum && $currency)
		{
			$amountBlocks = ContentBlockFactory::getBlocksFromTemplate(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_FOR_AMOUNT'),
				[
					'#AMOUNT#' => (new ContentBlock\Money())
						->setOpportunity((float)$sum)
						->setCurrencyId((string)$currency)
					,
				]
			);

			foreach ($amountBlocks as $index => $amountBlock)
			{
				if (!$amountBlock instanceof ContentBlock\TextPropertiesInterface)
				{
					continue;
				}

				$result->addContentBlock(
					'amountBlock' . $index,
					$amountBlock->setColor(ContentBlock\Text::COLOR_BASE_90)
				);
			}
		}

		return $result;
	}

	abstract public function getModel(): Model;

	abstract protected function getAssociatedEntityModel(): ?AssociatedEntityModel;

	abstract protected function getHistoryItemModel(): ?HistoryItemModel;

	private function getPaymentDetailsEntityNameAction(): ?Action
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

		$factory = Container::getInstance()->getFactory($this->getContext()->getEntityTypeId());
		if ($factory && $factory->isPaymentsEnabled())
		{
			$ownerTypeId = $this->getContext()->getEntityTypeId();

			if ($this->isTerminalPayment())
			{
				$mode = 'terminal_payment';
			}
			elseif ($ownerTypeId === \CCrmOwnerType::Deal)
			{
				$mode = 'payment_delivery';
			}
			else
			{
				$mode = 'payment';
			}

			return
				(new JsEvent('SalescenterApp:Start'))
					->addActionParamString('mode', $mode)
					->addActionParamInt('orderId', $this->getAssociatedEntityModel()->get('ORDER_ID'))
					->addActionParamInt('paymentId', $this->getAssociatedEntityModel()->get('ID'))
					->addActionParamInt('ownerTypeId', $ownerTypeId)
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamString(
						'isTerminalPayment',
						$this->isTerminalPayment() ? 'Y' : 'N'
					)
					->addActionParamString('isPaid', $this->getAssociatedEntityModel()->get('PAID'))
					->addActionParamString(
						'analyticsLabel',
						\CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeId)
							? 'crmDynamicTypeTimelineSmsResendPaymentSlider'
							: 'crmDealTimelineSmsResendPaymentSlider'
					)
				;
		}

		$detailLink = EntityLinkBuilder::getInstance()->getPaymentDetailsLink(
			$this->getAssociatedEntityModel()->get('ID')
		);
		if ($detailLink && !$this->isItemAboutCurrentEntity())
		{
			return new Action\Redirect(new Uri($detailLink));
		}

		return null;
	}

	private function isTerminalPayment(): bool
	{
		$fields = $this->getHistoryItemModel()->get('FIELDS');

		return ($fields['IS_TERMINAL_PAYMENT'] ?? 'N') === 'Y';
	}
}
