<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Action;
use Bitrix\Crm;

class OrderBuyer extends Main\Engine\Controller
{
	/** @var \CCrmPerms|null  */
	private static $userPermissions = null;

	protected function processBeforeAction(Action $action)
	{
		return parent::processBeforeAction($action)
			&& \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission(
				self::getCurrentUserPermissions()
			);
	}

	public function configureActions()
	{
		return array(
			'search' => array(
				'class' => Crm\Controller\Action\Order\SearchBuyerAction::class,
				'+prefilters' => [new Scope(Scope::AJAX)]
			)
		);
	}

	protected static function getCurrentUserPermissions()
	{
		if(self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}
		return self::$userPermissions;
	}
}
