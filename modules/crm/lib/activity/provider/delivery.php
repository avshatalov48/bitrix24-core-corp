<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Delivery
 * @package Bitrix\Crm\Activity\Provider
 */
class Delivery extends Activity\Provider\Base
{
	/**
	 * @inheritdoc
	 */
	public static function getId()
	{
		return 'CRM_DELIVERY';
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypeId(array $activity)
	{
		return 'DELIVERY';
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypes()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_NAME'),
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => 'DELIVERY'
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_NAME');
	}


	/**
	 * @inheritdoc
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_NAME');
	}

	/**
	 * @param array $activity Activity data.
	 * @return array Fields.
	 */
	public static function getFieldsForEdit(array $activity)
	{
		return array(
			array(
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_ACTIVITY_NAME_LABEL'),
				'TYPE' => 'SUBJECT',
				'VALUE' => $activity['SUBJECT']
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public static function getCustomViewLink(array $activityFields): ?string
	{
		if ($activityFields['OWNER_ID'] == \CCrmOwnerType::Deal)
		{
			return parent::getCustomViewLink($activityFields);
		}

		return \CComponentEngine::MakePathFromTemplate(
			CrmCheckPath('PATH_TO_DEAL_DETAILS', '', ''),
			['deal_id' => $activityFields['OWNER_ID']]
		);
	}
}
