<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Sms;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
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
			'messageBlock' => $this->getSmsMessageContentBlock(),
			'messageSentViaBlock' => $this->getMessageSentViaContentBlock(),
		];

		$client = $this->buildClientBlock(Client::BLOCK_WITH_FORMATTED_VALUE);
		if (isset($client))
		{
			$result['client'] = $client;
		}

		$user = $this->buildUserContentBlock();
		if (isset($user))
		{
			$result['user'] = $user;
		}

		return $result;
	}

	final public function getButtons(): ?array
	{
		$result = [];

		$resendPaymentButton = $this->getResendPaymentButton();
		$resendButton = $this->getResendButton();
		if ($resendPaymentButton)
		{
			$result['resendPayment'] = $resendPaymentButton;
		}
		elseif ($resendButton)
		{
			$result['resend'] = $resendButton;
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

	protected function getAssociatedEntityModelFields(): array
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');
		$settings = is_array($settings) ? $settings : [];

		return isset($settings['FIELDS']) && is_array($settings['FIELDS'])
			? $settings['FIELDS']
			: [];
	}

	protected function getSmsMessageContentBlock(): ContentBlock
	{
		$fields = $this->getAssociatedEntityModelFields();
		$messageText = $this->getMessageText();
		$clipboardContent = isset($fields['HIGHLIGHT_URL']) ? (string)$fields['HIGHLIGHT_URL'] : $messageText;
		$copyToClipboardAction = (new JsEvent('Clipboard:Copy'))
			->addActionParamString('content', $clipboardContent)
			->addActionParamString('type', isset($fields['HIGHLIGHT_URL']) ? 'link' : 'text')
		;

		return (new SmsMessage())
			->setText($messageText)
			->setAction($copyToClipboardAction)
		;
	}

	protected function getResendingAction(): ?Action
	{
		return null;
	}

	protected function getMenuBarContext(): TimelineMenuBar\Context
	{
		$menuBarContext = new TimelineMenuBar\Context(
			$this->getContext()->getEntityTypeId(),
			$this->getContext()->getEntityId()
		);
		$menuBarContext->setEntityCategoryId($this->getContext()->getEntityCategoryId());

		return $menuBarContext;
	}

	private function getResendPaymentButton(): ?Button
	{
		$fields = $this->getAssociatedEntityModelFields();
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
			$formattedDate = $payment
				? ConvertTimeStamp($payment->getField('DATE_BILL')?->getTimestamp())
				: null;
			$accountNumber = $payment?->getField('ACCOUNT_NUMBER');

			return (new Button(
				Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_SMS_NOTIFICATION_RESEND_MSGVER_2'),
				Button::TYPE_SECONDARY
			))
				->setAction(
					(new JsEvent('SalescenterApp:Start'))
						->addActionParamString(
							'mode',
							$ownerTypeId === CCrmOwnerType::Deal ? 'payment_delivery' : 'payment'
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
								? 'crmDynamicTypeTimelineSmsResendPaymentSlider'
								: 'crmDealTimelineSmsResendPaymentSlider'
						)
				)
			;
		}

		return null;
	}

	private function getResendButton(): ?Button
	{
		$action = $this->getResendingAction();
		if (!$action)
		{
			return null;
		}

		return (new Button(Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_SMS_NOTIFICATION_RESEND_MSGVER_2'), Button::TYPE_SECONDARY))
			->setAction($action)
			->setScopeWeb() // temporary hide for mobile app
		;
	}
}
