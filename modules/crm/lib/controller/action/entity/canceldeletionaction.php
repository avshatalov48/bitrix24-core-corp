<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm;
use Bitrix\Main;

/**
 * Class CancelDeletionAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.cancelDeletion", { data: { params: { hash: "fea80f2db003d4ebc4536023814aa885" } } });
 */
class CancelDeletionAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		/** @see Crm\Controller\Autorun\Delete::cancelAction */
		return $this->getController()->forward(
			Crm\Controller\Autorun\Delete::class,
			'cancel',
		);
	}
}
