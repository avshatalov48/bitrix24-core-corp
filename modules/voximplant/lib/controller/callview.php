<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
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

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if ($entityId > 0 && !\CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityId, $userPermissions))
		{
			$this->addError(new Error('Access denied', 'ACCESS_DENIED'));
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

	public function getLinesAction($showResApps = true)
	{
		return \CVoxImplantConfig::GetLinesEx([
			'showRestApps' => $showResApps
		]);
	}

	public function configureActions()
	{
		$result = parent::configureActions();
		$result['getCrmCard'] = array(
			'-prefilters' => array(
				Engine\ActionFilter\Csrf::class
			)
		);

		// support for legacy mode of loading rest applications (@see bitrix:app.placement component class)
		$result['loadRestApp'] = array(
			'+prefilters' => array(
				function(Event $event)
				{
					$request = \Bitrix\Main\Context::getCurrent()->getRequest();
					$isLegacyMode = $request->get("placement_action") === "load";
					if($isLegacyMode)
					{
						/** @var \Bitrix\Main\Engine\ActionFilter\Base $this */
						$this->getAction()->getController()->setSourceParametersList([[
							'appId' => $request->get("app"),
							'placementOptions' => $request->get("placement_options")
						]]);
					}
				}
			)
		);

		return $result;
	}
}