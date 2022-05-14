<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\ImConnector;
use Bitrix\Crm;

class ImConnectorManager extends Base
{
	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'imconnector';
	}

	public function isNotificationsEnabled()
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		$notifications = new ImConnector\Tools\Connectors\Notifications();
		return $notifications->isEnabled();
	}

	public function sendTelegramPaymentNotification(Crm\Order\Payment $payment, array $sendingInfo)
	{
		if (!$this->isEnabled())
		{
			return;
		}

		$paymentLink = LandingManager::getInstance()->getUrlInfoByOrder(
			$payment->getOrder(),
			['paymentId' => $payment->getId()]
		)['shortUrl'];

		$message = str_replace(
			'#LINK#',
			$paymentLink,
			$sendingInfo['text']
		);

		$contact = $this->getPrimaryContact($payment->getOrder());
		if ($contact)
		{
			$crmEntityType = Crm\Order\Contact::getEntityTypeName();
			$crmEntityId = $contact->getField('ENTITY_ID');

			(new ImConnector\Connectors\TelegramBot())
				->sendAutomaticMessage($message, $crmEntityType, $crmEntityId)
			;
		}
	}

	private function getPrimaryContact(Crm\Order\Order $order): ?Crm\Order\Contact
	{
		$contactCompanyCollection = $order->getContactCompanyCollection();
		if ($contactCompanyCollection)
		{
			$contacts = $contactCompanyCollection->getContacts();
			/** @var Crm\Order\Contact $contact */
			foreach ($contacts as $contact)
			{
				if ($contact->isPrimary())
				{
					return $contact;
				}
			}

			return current($contacts) ?: null;
		}

		return null;
	}
}