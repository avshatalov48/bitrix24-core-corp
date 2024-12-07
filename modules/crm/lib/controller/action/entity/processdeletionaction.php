<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm;
use Bitrix\Main;

/**
 * Class ProcessDeletionAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.processDeletion", { data: { params: { hash: "fea80f2db003d4ebc4536023814aa885" } } });
 */
class ProcessDeletionAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		/** @see Crm\Controller\Autorun\Delete::processAction */
		return $this->getController()->forward(
			Crm\Controller\Autorun\Delete::class,
			'process'
		);
	}
}
