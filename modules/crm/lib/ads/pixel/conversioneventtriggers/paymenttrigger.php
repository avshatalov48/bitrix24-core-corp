<?php

namespace Bitrix\Crm\Ads\Pixel\ConversionEventTriggers;

use Bitrix\Crm\Ads\Pixel\EventBuilders\AbstractFacebookBuilder;
use Bitrix\Crm\Ads\Pixel\EventBuilders\CrmConversionEventBuilderInterface;
use Bitrix\Crm\Order\Order;
use Bitrix\Sale\Payment;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Seo\Conversion\Facebook;
use Throwable;

/**
 * Class PaymentTrigger
 * @package Bitrix\Crm\Ads\Pixel\ConversionEventTriggers
 */
final class PaymentTrigger extends BaseTrigger
{

	protected const PAYMENT_CODE = 'payment';

	protected const FACEBOOK_TYPE = 'facebook';

	/** @var Payment $payment */
	protected $payment;

	/**
	 * PaymentTrigger constructor.
	 *
	 * @param Payment $payment
	 */
	public function __construct(Payment $payment)
	{
		$this->payment = $payment;
		parent::__construct();
	}

	/**
	 * @param Event $event
	 *
	 * @return EventResult
	 */
	public static function onPaid(Event $event): EventResult
	{
		try
		{
			if (($payment = $event->getParameter('ENTITY')) && $payment instanceof Payment)
			{
				$paymentTrigger = new PaymentTrigger($payment);
				$paymentTrigger->execute();
			}
		}
		catch (Throwable $throwable)
		{
		}
		finally
		{
			return new EventResult(EventResult::SUCCESS, null, 'crm');
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function checkConfiguration(): bool
	{
		if ($configuration = $this->getConfiguration())
		{
			return $configuration->has('enable') && filter_var($configuration->get('enable'), FILTER_VALIDATE_BOOLEAN);
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCode(): string
	{
		return self::FACEBOOK_TYPE.'.'.self::PAYMENT_CODE;
	}

	/**
	 * @inheritDoc
	 */
	protected function getType(): string
	{
		return self::FACEBOOK_TYPE;
	}

	/**
	 * @inheritDoc
	 */
	protected function getConversionEventBuilder(): CrmConversionEventBuilderInterface
	{
		return new class($this->payment) extends AbstractFacebookBuilder implements CrmConversionEventBuilderInterface {

			/**@var Payment|null $deal */
			protected $payment;

			public function __construct(Payment $payment)
			{
				$this->payment = $payment;
			}

			public function getUserData(): array
			{
				$userData = [];

				if (($order = $this->payment->getOrder()) && $order instanceof Order)
				{
					$binding = $order->getEntityBinding();
					if (
						$binding
						&& $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
					)
					{
						$userData = $this->getDealUserData($this->getDeal($binding->getOwnerId()));
					}
					elseif ($collection = $order->getContactCompanyCollection())
					{
						if (
							($company = $collection->getPrimaryCompany()) &&
							!empty($data = $this->getCompanyUserData($company->getId()))
						)
						{
							$userData[] = $data;
						}
						if (
							($contact = $collection->getPrimaryContact()) &&
							!empty($data = $this->getContactUserData($contact->getId()))
						)
						{
							$userData[] = $data;
						}
					}
				}

				return $userData;
			}

			public function getEventParams($entity): ?array
			{
				return [
					'event_name' => Facebook\Event::EVENT_PURCHASE,
					'action_source' => Facebook\Event::ACTION_SOURCE_SYSTEM_GENERATED,
					'user_data' => $entity,
					'custom_data' => [
						'value' => $this->payment->getSumPaid() ?? $this->payment->getSum(),
						'currency' => $this->payment->getOrder()->getCurrency(),
					],
				];
			}
		};
	}
}