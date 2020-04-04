<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.cancelDeletion", { data: { params: { hash: "fea80f2db003d4ebc4536023814aa885" } } });
 */
class CancelDeletionAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		if(!Crm\Security\EntityAuthorization::isAuthorized())
		{
			$this->addError(new Main\Error('Access denied.'));
			return null;
		}

		$hash = isset($params['hash']) ? $params['hash'] : '';
		if($hash === '')
		{
			$this->addError(new Main\Error('The parameter hash is required.'));
			return null;
		}

		if(isset($_SESSION['CRM_ENTITY_DELETION_DATA']))
		{
			unset($_SESSION['CRM_ENTITY_DELETION_DATA'][$hash]);
		}

		if(isset($_SESSION['CRM_ENTITY_DELETION_PROGRESS']))
		{
			unset($_SESSION['CRM_ENTITY_DELETION_PROGRESS'][$hash]);
		}

		return [ 'hash' => $hash ];
	}
}