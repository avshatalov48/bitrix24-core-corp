<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OpenLine extends Base
{
	const ACTIVITY_PROVIDER_ID = 'IMOPENLINES_SESSION';

	static $isActive = null;
	static $activeLine = 0;

	public static function getId()
	{
		return static::ACTIVITY_PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_NAME');
	}

	public static function isActive()
	{
		if (is_null(static::$isActive))
		{
			$active = Main\Loader::includeModule('imopenlines');
			if ($active)
			{
				static::$activeLine = \Bitrix\ImOpenLines\Model\ConfigTable::getCount(array('=TEMPORARY' => 'N'));
				$active = static::$activeLine > 0;
			}
			static::$isActive = $active;
		}

		return static::$isActive;
	}

	public static function getStatusAnchor()
	{
		$isBitrix24 = Main\Loader::includeModule('bitrix24');
		if (static::isActive())
		{
			$text = Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_ACTIVE', Array('#NUMBER#' => static::$activeLine));
		}
		else
		{
			$text = Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_INACTIVE');
		}

		return array(
			'TEXT' => $text,
			'URL' => $isBitrix24 ? '/contact_center/' : '/services/contact_center/'
		);
	}

	/**
	 * @param array $activity Activity data.
	 * @return bool
	 */
	public static function checkForWaitingCompletion(array $activity)
	{
		return !(isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y')
			|| isset($activity['DIRECTION']) && $activity['DIRECTION'] == \CCrmActivityDirection::Incoming;
	}

	/**
	 * Returns supported provider's types
	 * @return array
	 */
	public static function getTypes()
	{
		$types = array();
		if (!Main\Loader::includeModule('imopenlines'))
			return $types;

		$orm = \Bitrix\ImOpenLines\Model\ConfigTable::getList(Array(
			'filter' => Array(
				'=TEMPORARY' => 'N'
			)
		));
		while ($config = $orm->fetch())
		{
			$types[] = array(
				'NAME' => Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_TYPE_TEMPLATE', Array('#NAME#' => $config['LINE_NAME'])),
				'PROVIDER_ID' => static::ACTIVITY_PROVIDER_ID,
				'PROVIDER_TYPE_ID' => $config['ID'],
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Incoming => Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_SESSION_INCOMING'),
					\CCrmActivityDirection::Outgoing => Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_SESSION_OUTGOING'),
				),
			);
		}

		return $types;
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_SESSION_INCOMING'),
				'DIRECTION' => \CCrmActivityDirection::Incoming
			),
			array(
				'NAME' => Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_SESSION_OUTGOING'),
				'DIRECTION' => \CCrmActivityDirection::Outgoing
			)
		);
	}

	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_UNDEFINED;
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
	 * @inheritdoc
	 */
	public static function renderView(array $activity)
	{
		if(!Main\ModuleManager::isModuleInstalled('imopenlines'))
			return '';

		$closeSliderOnClick = '';
		if (isset($activity['__template']) && $activity['__template'] === 'slider')
		{
			$closeSliderOnClick = 'if (top.BX.Bitrix24 && top.BX.Bitrix24.Slider) {top.BX.Bitrix24.Slider.closeAll();};';
		}

		return '<div class="crm-task-list-chat">
			<div class="crm-task-list-chat-inner">
				<div class="webform-small-button webform-small-button-blue crm-task-list-chat-button" onclick="top.BXIM.openHistory(\'imol|'.$activity['ASSOCIATED_ENTITY_ID'].'\');'.$closeSliderOnClick.'">'.Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_SESSION_RENDER_VIEW').'</div>
				<div class="webform-small-button webform-small-button-green crm-task-list-chat-button" onclick="top.BXIM.openMessengerSlider(\'imol|'.$activity['PROVIDER_PARAMS']['USER_CODE'].'\', {RECENT: \'N\', MENU: \'N\'})">'.Loc::getMessage('IMOPENLINES_ACTIVITY_PROVIDER_SESSION_RENDER_START').'</div>
			</div>
		</div>';
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY,
			CommunicationStatistics::STATISTICS_STATUSES,
			CommunicationStatistics::STATISTICS_MARKS
		);
	}

	public static function getResultSources()
	{
		if (!Main\Loader::includeModule('imconnector'))
			return Array();

		return \Bitrix\ImConnector\Connector::getListConnector();
	}

	/**
	 * @inheritdoc
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Main\Result();
		//Only END TIME can be taken for DEADLINE!
		if (isset($fields['END_TIME']) && $fields['END_TIME'] !== '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}
		return $result;
	}
}