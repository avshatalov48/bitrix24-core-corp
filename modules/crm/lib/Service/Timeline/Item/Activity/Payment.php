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

		$fields = $this->getAssociatedEntityModelFields();

		$result = [
			'amountMoneyPill' =>
				(new ContentBlock\MoneyPill())
					->setOpportunity($this->getPaymentSum($payment, $fields))
					->setCurrencyId($this->getPaymentCurrency($payment, $fields))
			,
			'paymentDetails' => $this->getPaymentDetailsContentBlock($payment),
		];

		$paymentSystemName = $fields['PAY_SYSTEM_NAME'] ?? '';
		if ($paymentSystemName)
		{
			$isTerminalPayment = ($fields['IS_TERMINAL_PAYMENT'] ?? 'N') === 'Y';

			$paymentMethodValueLocPhrase =
				$isTerminalPayment
					? 'CRM_TIMELINE_ECOMMERCE_PAYMENT_METHOD_VALUE_VIA_TERMINAL'
					: 'CRM_TIMELINE_ECOMMERCE_PAYMENT_METHOD_VALUE'
			;

			$paymentMethodValue = Loc::getMessage(
				$paymentMethodValueLocPhrase,
				[
					'#PAYMENT_METHOD#' => (string)$paymentSystemName,
				]
			);

			$result['paymentMethod'] = (
					(new ContentBlock\Text())
						->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_METHOD_MSGVER_1', ['#PAYMENT_METHOD#' => (string)$paymentMethodValue]))
						->setFontSize(ContentBlock\Text::FONT_SIZE_SM)
						->setColor(ContentBlock\Text::COLOR_BASE_70)
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
		$fields = $this->getAssociatedEntityModelFields();

		$dateBillTimestamp = $this->getPaymentDateBillTimestamp($payment, $fields);

		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_TITLE_WITHOUT_NAME',
			[
				'#NUMBER#' => $this->getPaymentAccountNumber($payment, $fields),
				'#DATE#' =>
					$dateBillTimestamp
						? FormatDate(
							Context::getCurrent()->getCulture()->getLongDateFormat(),
							$dateBillTimestamp
						)
						: ''
				,
			]
		);

		$pregMatchResult = null;
		preg_match('/<a>(.*)<\/a>/', $title, $pregMatchResult);
		$linkNumberAndDate = $pregMatchResult[0] ?? '';
		$numberAndDate = $pregMatchResult[1] ?? '';
		$title = str_replace($linkNumberAndDate, '#NUMBER_AND_DATE#', $title);

		$openPaymentAction = $this->getOpenPaymentAction();
		if ($openPaymentAction)
		{
			$numberAndDateBlock = (new ContentBlock\Link())
				->setValue($numberAndDate)
				->setAction($openPaymentAction)
			;
		}
		else
		{
			$numberAndDateBlock = (new ContentBlock\Text())
				->setValue($numberAndDate)
				->setColor(ContentBlock\Text::COLOR_BASE_90)
			;
		}

		$sum = $this->getPaymentSum($payment, $fields);
		$currency = $this->getPaymentCurrency($payment, $fields);
		$amountBlock = (new Money())
			->setOpportunity((float)$sum)
			->setCurrencyId((string)$currency)
			->setColor(ContentBlock\Text::COLOR_BASE_90)
		;
		$contentBlock = ContentBlockFactory::createLineOfTextFromTemplate(
			$title,
			[
				'#NUMBER_AND_DATE#' => $numberAndDateBlock,
				'#AMOUNT#' => $amountBlock,
			],
		);

		return
			(new ContentBlockWithTitle())
				->setInline()
				->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_NAME'))
				->setContentBlock($contentBlock)
		;
	}

	private function getOpenPaymentAction(): ?Action
	{
		$payment = $this->getPayment();
		if (!$payment)
		{
			return null;
		}

		$fields = $this->getAssociatedEntityModelFields();

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

			$paymentDateBillTimestamp = $this->getPaymentDateBillTimestamp($payment, $fields);

			if (($fields['IS_TERMINAL_PAYMENT'] ?? 'N') === 'Y')
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
					->addActionParamInt(
						'orderId',
						$payment->getOrder()->getId()
					)
					->addActionParamInt(
						'paymentId',
						$payment->getId()
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
						$paymentDateBillTimestamp ? ConvertTimeStamp($paymentDateBillTimestamp) : null,
					)
					->addActionParamString(
						'accountNumber',
						$this->getPaymentAccountNumber($payment, $fields),
					)
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

	private function getAssociatedEntityModelFields(): array
	{
		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');
		$settings = is_array($settings) ? $settings : [];

		return isset($settings['FIELDS']) && is_array($settings['FIELDS']) ? $settings['FIELDS'] : [];
	}

	private function getPaymentSum(Sale\Payment $payment, array $fields): ?float
	{
		$result = $fields['SUM'] ?? $payment->getField('SUM');

		return is_null($result) ? null : (float)$result;
	}

	private function getPaymentCurrency(Sale\Payment $payment, array $fields): ?string
	{
		$result = $fields['CURRENCY'] ?? $payment->getField('CURRENCY');

		return is_null($result) ? null : (string)$result;
	}

	private function getPaymentAccountNumber(Sale\Payment $payment, array $fields): ?string
	{
		$result = $fields['ACCOUNT_NUMBER'] ?? $payment->getField('ACCOUNT_NUMBER');

		return is_null($result) ? null : (string)$result;
	}

	private function getPaymentDateBillTimestamp(Sale\Payment $payment, array $fields): ?int
	{
		$timestamp = null;

		if (isset($fields['DATE_BILL']))
		{
			$timestamp = $fields['DATE_BILL'];
		}
		else
		{
			$dateBill = $payment->getField('DATE_BILL');
			if ($dateBill instanceof DateTime)
			{
				$timestamp = $dateBill->getTimestamp();
			}
		}

		return is_null($timestamp) ? null : (int)$timestamp;
	}
}
