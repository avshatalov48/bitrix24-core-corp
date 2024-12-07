<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;
/**
 * @example BX.ajax.runAction("crm.api.entity.processWhatsAppMessage", { data: { params: { hash: "fea80f2db003d4ebc4536023814aa885" } } });
 */
class ProcessWhatsAppMessageAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		return $this->getController()->forward(
			Crm\Controller\Autorun\WhatsAppMessage::class,
			'process',
		);
	}
}
