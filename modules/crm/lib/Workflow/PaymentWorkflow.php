<?php

namespace Bitrix\Crm\Workflow;

use Bitrix\Sale\Payment;

final class PaymentWorkflow extends Workflow
{
	/**
	 * @var Payment
	 */
	private $payment;

	public function __construct(Payment $payment)
	{
		$this->payment = $payment;
	}

	public static function createFrom(Payment $payment): PaymentWorkflow
	{
		return new static($payment);
	}

	/**
	 * @inheritDoc
	 */
	public static function getWorkflowCode(): string
	{
		return 'PAYMENT_WORKFLOW';
	}

	/**
	 * @inheritDoc
	 */
	public static function getStages(): array
	{
		return PaymentStage::getValues();
	}

	/**
	 * @inheritDoc
	 */
	public function getInitialStage(): string
	{
		return $this->payment->isPaid() ? PaymentStage::PAID : PaymentStage::NOT_PAID;
	}

	/**
	 * @inheritDoc
	 */
	public function getEntityId(): int
	{
		return $this->payment->getId();
	}

	/**
	 * @inheritDoc
	 */
	public function canSwitchToStage(string $nextStage): bool
	{
		$allowedStages = null;
		$currentStage = $this->getStage();

		if ($currentStage === PaymentStage::PAID)
		{
			$allowedStages = [
				PaymentStage::CANCEL,
				PaymentStage::REFUND,
				PaymentStage::NOT_PAID,
				PaymentStage::PAID,
			];
		}
		elseif ($currentStage === PaymentStage::CANCEL || $currentStage === PaymentStage::REFUND)
		{
			$allowedStages = [
				PaymentStage::PAID,
				PaymentStage::CANCEL,
				PaymentStage::REFUND,
				PaymentStage::SENT_NO_VIEWED,
			];
		}

		if (is_array($allowedStages))
		{
			return in_array($nextStage, $allowedStages);
		}

		return true;
	}
}
