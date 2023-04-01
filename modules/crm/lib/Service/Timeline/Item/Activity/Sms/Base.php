<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Sms;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Payload\SmsActivityPayload;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

abstract class Base extends Activity
{
	public function getIconCode(): ?string
	{
		return 'comment';
	}

	public function getContentBlocks(): ?array
	{
		$result = [
			'messageBlock' => (new Layout\Body\ContentBlock\SmsMessage())->setText(
				$this->getMessageText()
			),
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

			$result['resendPayment'] =
				(new Layout\Footer\Button(
					Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_SMS_NOTIFICATION_RESEND'),
					Layout\Footer\Button::TYPE_SECONDARY
				))
					->setAction(
						(new Layout\Action\JsEvent('SalescenterApp:Start'))
							->addActionParamString(
								'mode',
								$ownerTypeId === \CCrmOwnerType::Deal
									? 'payment_delivery'
									: 'payment'
							)
							->addActionParamInt('orderId', $orderId)
							->addActionParamInt('paymentId', $paymentId)
							->addActionParamInt('ownerTypeId', $ownerTypeId)
							->addActionParamInt('ownerId', $this->getContext()->getEntityId())
							->addActionParamString(
								'analyticsLabel',
								\CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeId)
									? 'crmDealTimelineSmsResendPaymentSlider'
									: 'crmDynamicTypeTimelineSmsResendPaymentSlider'
							)
					)
			;
		}

		return $result;
	}

	public function getPayload(): ?SmsActivityPayload
	{
		return
			(new SmsActivityPayload())
				->addValueMessage('message', $this->getMessageId())
				->addValuePull(
					'pull',
					$this->getPullModuleId(),
					$this->getPullCommand(),
					$this->getPullTagName()
				)
		;
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

	abstract protected function getMessageId(): ?int;
	abstract protected function getMessageText(): ?string;
	abstract protected function getMessageSentViaContentBlock(): ?Layout\Body\ContentBlock;

	abstract protected function getPullModuleId(): string;
	abstract protected function getPullCommand(): string;
	abstract protected function getPullTagName(): string;
	protected function buildUserContentBlock(): ?ContentBlock
	{
		return null;
	}
}
