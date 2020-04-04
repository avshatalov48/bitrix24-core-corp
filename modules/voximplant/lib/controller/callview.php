<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Voximplant\Rest\Helper;

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

	public function loadRestAppAction(int $appId, $placementOptions)
	{
		return new Engine\Response\Component(
			'bitrix:app.placement',
			'',
			[
				'PLACEMENT' => Helper::PLACEMENT_CALL_CARD,
				"PLACEMENT_OPTIONS" => $placementOptions,
				'PARAM' => [
					'FRAME_HEIGHT' => '100%',
				],
				'INTERFACE_EVENT' => 'onPlacementMessageInterfaceInit',
				'SAVE_LAST_APP' => 'N',
				'PLACEMENT_APP' => $appId
			]
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