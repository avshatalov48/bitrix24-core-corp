<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

class CallView extends Engine\Controller
{
	public function getCrmCardAction($entityType, $entityId)
	{
		if(!Loader::includeModule("crm"))
		{
			$this->addError(new Error("CRM module is not installed"));
			return null;
		}

		return new Engine\Response\Component(
			'bitrix:crm.card.show',
			'',
			array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => (int)$entityId,
			)
		);
	}

	public function configureActions()
	{
		$result = parent::configureActions();
		$result['getCrmCard'] = array(
			'-prefilters' => array(
				Engine\ActionFilter\Csrf::class
			)
		);

		return $result;
	}
}