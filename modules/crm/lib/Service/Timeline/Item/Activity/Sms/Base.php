<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Sms;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\SmsMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Repository\PaymentRepository;
use CCrmOwnerType;

abstract class Base extends Activity
{
	abstract protected function getMessageText(): ?string;
	abstract protected function getMessageSentViaContentBlock(): ?ContentBlock;

	public function getIconCode(): ?string
	{
		return Icon::COMMENT;
	}

	public function getContentBlocks(): ?array
	{
		$result = [
			'messageBlock' => (new SmsMessage())->setText($this->getMessageText()),
			'messageSentViaBlock' => $this->getMessageSentViaContentBlock(),
		];

		$client = $this->buildClientBlock(self::BLOCK_WITH_FORMATTED_VALUE);
		if ($client)
		{
			$result['client'] = $client;
		}
		$user = $this->buildUserContentBlock();
		if ($user)
		{
			$result['user'] = $user;
		}

		return $result;
	}

	public function getButtons(): ?array
	{
		$result = [];

		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');
		$settings = is_array($settings) ? $settings : [];
		$fields = isset($settings['FIELDS']) && is_array($settings['FIELDS']) ? $settings['FIELDS'] : [];

		$orderId = $fields['ORDER_ID'] ?? null;
		$paymentId = $fields['PAYMENT_ID'] ?? null;
		$factory = Container::getInstance()->getFactory($this->getContext()->getEntityTypeId());

		if (
			$orderId
			&& $paymentId
			&& $factory
			&& $factory->isPaymentsEnabled()
			&& $factory->isStagesEnabled()
		)
		{
			$ownerTypeId = $this->getContext()->getEntityTypeId();
			$payment = PaymentRepository::getInstance()->getById($paymentId);
			$formattedDate = $payment ? ConvertTimeStamp($payment->getField('DATE_BILL')->getTimestamp()) : null;
			$accountNumber = $payment ? $payment->getField('ACCOUNT_NUMBER') : null;

			$result['resendPayment'] =
				(new Button(
					Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_SMS_NOTIFICATION_RESEND_MSGVER_1'),
					Button::TYPE_SECONDARY
				))
					->setAction(
						(new JsEvent('SalescenterApp:Start'))
							->addActionParamString(
								'mode',
								$ownerTypeId === CCrmOwnerType::Deal
									? 'payment_delivery'
									: 'payment'
							)
							->addActionParamInt('orderId', $orderId)
							->addActionParamInt('paymentId', $paymentId)
							->addActionParamInt('ownerTypeId', $ownerTypeId)
							->addActionParamInt('ownerId', $this->getContext()->getEntityId())
							->addActionParamString('formattedDate', $formattedDate)
							->addActionParamString('accountNumber', $accountNumber)
							->addActionParamString(
								'analyticsLabel',
								CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeId)
									? 'crmDealTimelineSmsResendPaymentSlider'
									: 'crmDynamicTypeTimelineSmsResendPaymentSlider'
							)
					)
			;
		}

		return $result;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();
		unset($items['view']);

		return $items;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	protected function buildUserContentBlock(): ?ContentBlock
	{
		return null;
	}
}
