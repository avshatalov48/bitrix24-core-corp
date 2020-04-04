<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class RestApp extends Base
{
	public static function getId()
	{
		return 'REST_APP';
	}

	public static function getTypeId(array $activity)
	{
		return isset($activity['PROVIDER_TYPE_ID']) ? $activity['PROVIDER_TYPE_ID'] : 'LINK';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_REST_APP_NAME');
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_REST_APP_NAME'),
			),
		);
	}

	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_REST_APP_NAME');
	}

	public static function renderView(array $activity)
	{
		if(!Main\Loader::includeModule('rest'))
			return '';

		\CJSCore::Init('applayout');

		$id = (int)$activity['ID'];
		$appId = (int)$activity['ASSOCIATED_ENTITY_ID'];

		return '<div class="crm-task-list-chat">
			<div class="crm-task-list-chat-inner">
				<div class="webform-small-button webform-small-button-blue crm-task-list-chat-button" onclick="BX.rest.AppLayout.openApplication('.$appId.', {action: \'view_activity\', activity_id: '.$id.'});">'.Loc::getMessage('CRM_ACTIVITY_PROVIDER_REST_APP_OPEN_BUTTON').'</div>
			</div>
		</div>';
	}
}
