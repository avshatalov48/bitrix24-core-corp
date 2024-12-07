<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm;
use Bitrix\Main;

/**
 * Class PrepareDeletionAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.prepareDeletion", { data: { params: { gridId: "DEAL_LIST", entityTypeId: 2, entityIds: [ 100, 101, 102 ] } } });
 */
class PrepareDeletionAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		/** @see Crm\Controller\Autorun\Delete::prepareAction */
		return $this->getController()->forward(
			Crm\Controller\Autorun\Delete::class,
			'prepare',
		);
	}
}
