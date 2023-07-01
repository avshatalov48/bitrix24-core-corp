<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\LogMessageEntry;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

Loc::loadMessages(__FILE__);

class OpenLine extends Base
{
	const ACTIVITY_PROVIDER_ID = 'IMOPENLINES_SESSION';

	static $isActive;
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

	public static function onAfterAdd($activityFields, array $params = null)
	{
		if (
			$activityFields['DIRECTION'] === \CCrmActivityDirection::Incoming
			&& isset($activityFields['PROVIDER_PARAMS']['USER_CODE'])
			&& $activityFields['ID'] > 0
		)
		{
			$logMessageId = LogMessageEntry::detectIdByParams(
				$activityFields['PROVIDER_PARAMS']['USER_CODE'],
				LogMessageType::OPEN_LINE_INCOMING,
			);
			if (isset($logMessageId))
			{
				TimelineTable::update($logMessageId, [
					'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
					'ASSOCIATED_ENTITY_ID' => $activityFields['ID'],
				]);
			}
		}
	}

	public static function onBeforeComplete(int $id, array $activityFields, array $params = null)
	{
		if (
			isset($activityFields['COMPLETED'])
			&& $activityFields['COMPLETED'] === 'N'
			&& !empty($activityFields['PROVIDER_PARAMS']['USER_CODE'])
		)
		{
			OpenLineManager::closeDialog($activityFields['PROVIDER_PARAMS']['USER_CODE']);
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$badge = Container::getInstance()->getBadge(
			Badge\Type\OpenLineStatus::OPENLINE_STATUS_TYPE,
			Badge\Type\OpenLineStatus::CHAT_NOT_READ_VALUE,
		);

		$sourceIdentifier = new Badge\SourceIdentifier(
			Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			CCrmOwnerType::Activity,
			$activityId,
		);

		$userCode = $activityFields['PROVIDER_PARAMS']['USER_CODE'] ?? null;
		$responsibleId = $activityFields['RESPONSIBLE_ID'] ?? null;
		$isNotReadChat = OpenLineManager::getChatUnReadMessages($userCode, $responsibleId) > 0;
		if ($isNotReadChat)
		{
			foreach ($bindings as $singleBinding)
			{
				$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
				$badge->bind($itemIdentifier, $sourceIdentifier);
			}
		}
		else
		{
			foreach ($bindings as $singleBinding)
			{
				$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
				$badge->unbind($itemIdentifier, $sourceIdentifier);
			}
		}
	}

	public static function hasPlanner(array $activity): bool
	{
		return !Crm::isUniversalActivityScenarioEnabled();
	}
}
