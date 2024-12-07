<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * crm.api.entity.prepareWhatsAppMessage
 */
class PrepareWhatsAppMessageAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		return $this->getController()->forward(
			Crm\Controller\Autorun\WhatsAppMessage::class,
			'prepare',
		);
	}
}
