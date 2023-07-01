<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sale\EntityLinkBuilder\EntityLinkBuilder;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Sale;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Context;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class Payment extends Activity
{
	protected function getActivityTypeId(): string
	{
		return 'Payment';
	}

	public function getTitle(): ?string
	{
		return
			$this->isScheduled()
			? Loc::getMessage('CRM_TIMELINE_ECOMMERCE_RECEIVED_PAYMENT_FROM_CLIENT')
			: Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_PROCESSED')
		;
	}

	public function getIconCode(): ?string
	{
		return Icon::WALLET;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return  Layout\Common\Logo::getInstance(Layout\Common\Logo::BANK_CARD)
			->createLogo()
		;
	}

	public function getContentBlocks(): ?array
	{
		$payment = $this->getPayment();
		if (!$payment)
		{
			return null;
		}

		$result = [
			'amountMoneyPill' =>
				(new ContentBlock\MoneyPill())
					->setOpportunity($payment->getField('SUM'))
					->setCurrencyId($payment->getField('CURRENCY'))
			,
			'paymentDetails' => $this->getPaymentDetailsContentBlock($payment),
		];

		$paymentSystem = $payment->getPaySystem();
		if ($paymentSystem && $paymentSystem->getField('NAME'))
		{
			$result['paymentMethod'] = (new ContentBlockWithTitle())
				->setInline()
				->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_METHOD'))
				->setContentBlock(
					(new ContentBlock\Text())
						->setValue($paymentSystem->getField('NAME'))
						->setColor(ContentBlock\Text::COLOR_BASE_90)
				)
			;
		}

		return $result;
	}

	public function getButtons(): array
	{
		if (!$this->isScheduled())
		{
			$openPaymentAction = $this->getOpenPaymentAction();
			if (!$openPaymentAction)
			{
				return [];
			}

			$openButton = (new Button(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_OPEN'),
				Button::TYPE_SECONDARY,
			))
				->setAction($openPaymentAction)
			;
			return [
				'open' => $openButton,
			];
		}

		$completeButton =
			(new Button(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PROCESSED'),
				Button::TYPE_PRIMARY,
			))
				->setAction($this->getCompleteAction())
		;
		$result = [
			'complete' => $completeButton
		];

		if (
			\CCrmSaleHelper::isRealizationCreationAvailable()
			&& $this->isPaymentsEnabledForContextEntity()
		)
		{
			$paymentId = $this->getPaymentId();
			if ($paymentId)
			{
				$createRealizationButton =
					(new Button(
						Loc::getMessage('CRM_TIMELINE_ECOMMERCE_REALIZATION'),
						Button::TYPE_SECONDARY,
					))
						->setAction(
							(new JsEvent('Payment:OpenRealization'))
								->addActionParamInt('paymentId', $paymentId)
						)
				;
				$result['createRealization'] = $createRealizationButton;
			}
		}

		return $result;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function getPaymentDetailsContentBlock(Sale\Payment $payment): ContentBlock
	{
		$contentBlock = new LineOfTextBlocks();

		$date = $payment->getField('DATE_BILL');
		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_TITLE',
			[
				'#NUMBER#' => $payment->getField('ACCOUNT_NUMBER'),
				'#DATE#' =>
					($date instanceof DateTime)
						? FormatDate(
							Context::getCurrent()->getCulture()->getLongDateFormat(),
							$date->getTimestamp()
						)
						: ''
				,
			]
		);

		$openPaymentAction = $this->getOpenPaymentAction();
		if ($openPaymentAction)
		{
			$titleBlock = (new ContentBlock\Link())
				->setValue($title)
				->setAction($openPaymentAction)
			;
		}
		else
		{
			$titleBlock = (new ContentBlock\Text())
				->setValue($title)
				->setColor(ContentBlock\Text::COLOR_BASE_90)
			;
		}
		$contentBlock->addContentBlock('title', $titleBlock);

		$sum = $payment->getField('SUM') ?? null;
		$currency = $payment->getField('CURRENCY') ?? null;
		if ($sum && $currency)
		{
			$amountBlocks = ContentBlockFactory::getBlocksFromTemplate(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_FOR_AMOUNT'),
				[
					'#AMOUNT#' =>
						(new Money())
							->setOpportunity((float)$sum)
							->setCurrencyId((string)$currency)
							->setColor(ContentBlock\Text::COLOR_BASE_90)
					,
				]
			);
			foreach ($amountBlocks as $index => $amountBlock)
			{
				if (!$amountBlock instanceof ContentBlock\TextPropertiesInterface)
				{
					continue;
				}

				$contentBlock->addContentBlock(
					'amountBlock' . $index,
					$amountBlock->setColor(ContentBlock\Text::COLOR_BASE_90)
				);
			}
		}

		return
			(new ContentBlockWithTitle())
				->setInline()
				->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_NAME'))
				->setContentBlock($contentBlock)
		;
	}

	private function getOpenPaymentAction(): ?Action
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

		// @todo @see \Bitrix\Crm\Service\Timeline\Item\Mixin\HasPaymentDetailsContentBlock
		if ($this->isPaymentsEnabledForContextEntity())
		{
			$ownerTypeId = $this->getContext()->getEntityTypeId();

			$payment = $this->getPayment();
			$formattedDate = $payment ? ConvertTimeStamp($payment->getField('DATE_BILL')->getTimestamp()) : null;
			$accountNumber = $payment ? $payment->getField('ACCOUNT_NUMBER') : null;

			return
				(new JsEvent('SalescenterApp:Start'))
					->addActionParamString(
						'mode',
						$ownerTypeId === \CCrmOwnerType::Deal
							? 'payment_delivery'
							: 'payment'
					)
					->addActionParamInt(
						'orderId',
						$this->getAssociatedEntityModel()->get('OWNER_ID')
					)
					->addActionParamInt(
						'paymentId',
						$this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID')
					)
					->addActionParamInt(
						'ownerTypeId',
						$ownerTypeId
					)
					->addActionParamInt(
						'ownerId',
						$this->getContext()->getEntityId()
					)
					->addActionParamString(
						'formattedDate',
						$formattedDate,
					)
					->addActionParamString(
						'accountNumber',
						$accountNumber,
					)
					->addActionParamString(
						'analyticsLabel',
						\CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeId)
							? 'crmDealTimelineSmsResendPaymentSlider'
							: 'crmDynamicTypeTimelineSmsResendPaymentSlider'
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

	private function getPayment(): ?Sale\Payment
	{
		$paymentId = $this->getPaymentId();
		if (!$paymentId)
		{
			return null;
		}

		return PaymentRepository::getInstance()->getById($paymentId);
	}

	private function getPaymentId(): ?int
	{
		$associatedEntityId = $this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID') ?? null;
		if (!$associatedEntityId)
		{
			return null;
		}

		return (int)$associatedEntityId;
	}

	private function isPaymentsEnabledForContextEntity(): bool
	{
		$factory = Container::getInstance()->getFactory($this->getContext()->getEntityTypeId());

		return $factory && $factory->isPaymentsEnabled();
	}
}
