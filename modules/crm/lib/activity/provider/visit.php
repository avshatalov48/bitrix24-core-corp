<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Faceid\AgreementTable;
use Bitrix\FaceId\FaceId;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CBitrix24;
use CCrmActivityDirection;
use CCrmFieldMulti;
use CCrmOwnerType;
use CUserOptions;
use CUtil;

Loc::loadMessages(__FILE__);

class Visit extends Base
{
	public const PROVIDER_ID = 'VISIT_TRACKER';
	public const TYPE_VISIT = 'VISIT';

	public static function getId()
	{
		return self::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_TITLE');
	}

	public static function getPlannerTitle(array $activity)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_PLANNER_TITLE');
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_TITLE'),
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => self::TYPE_VISIT,
				'DIRECTIONS' => [
					CCrmActivityDirection::Incoming => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_TITLE'),
				],
			],
		];
	}

	public static function getTypesFilterPresets()
	{
		return self::getTypes();
	}

	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		$ownerTypeId = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : CCrmOwnerType::Undefined;
		$ownerId = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;

		$visitParams = self::getPopupParameters();
		if ($ownerTypeId && $ownerId)
		{
			$visitParams['OWNER_TYPE'] = CCrmOwnerType::ResolveName($ownerTypeId);
			$visitParams['OWNER_ID'] = $ownerId;
		}

		$visitButton = [
			'TEXT' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_BUTTON'),
			'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_BUTTON_TITLE'),
			'ONCLICK' => 'BX.CrmActivityVisit.create(' . CUtil::PhpToJSObject($visitParams) . ').showEdit()',
			'ICON' => 'btn-new',
			'CLASS_NAME' => RestrictionManager::getVisitRestriction()->hasPermission() ? '' : 'crm-tariff-lock-behind'
		];

		$buttons[] = $visitButton;

		return 1;
	}

	/**
	 * @inheritdoc
	 */
	public static function renderView(array $activity)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.visit',
			'',
			[
				'ACTIVITY' => $activity
			]
		);

		return ob_get_clean();
	}

	public static function getPopupParameters()
	{
		$faceIdInstalled = Loader::includeModule('faceid');
		$faceIdEnabled = $faceIdInstalled && FaceId::isAvailable();

		return [
			'HAS_CONSENT' => self::hasConsent() ? 'Y' : 'N',
			'FACEID_INSTALLED' => $faceIdInstalled ? 'Y' : 'N',
			'FACEID_ENABLED' => $faceIdEnabled ? 'Y' : 'N',
			'HAS_RECOGNIZE_CONSENT' => self::hasRecognizeConsent() ? 'Y' : 'N',
		];
	}

	public static function isAvailable()
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return CBitrix24::getLicensePrefix() !== 'ua';
	}

	public static function getFormattedLength(array $activity, string $format)
	{
		$duration = (int)$activity['PROVIDER_PARAMS']['RECORD_LENGTH'];
		$minutes = floor($duration / 60);
		$seconds = $duration % 60;

		return $format === 'FULL'
			? ($minutes > 0 ? $minutes . " "
				. GetMessage("CRM_ACTIVITY_PROVIDER_VISIT_MIN") : "")
				. ($minutes > 0 && $seconds > 0 ? ", " : "")
				. ($seconds > 0 ? $seconds . " "
				. GetMessage("CRM_ACTIVITY_PROVIDER_VISIT_SEC") : '')
			: sprintf("%02d:%02d", $minutes, $seconds)
		;
	}

	public static function getVkProfile(array $activity)
	{
		static $cache = [];

		$entityType = CCrmOwnerType::ResolveName($activity['OWNER_TYPE_ID']);
		$entityId = $activity['OWNER_ID'];
		$indexName = $entityType . '_' . $entityId;

		if (isset($cache[$indexName]))
		{
			return $cache[$indexName];
		}

		$found = false;
		$cursor = CCrmFieldMulti::GetList(
			[
				'ID' => 'asc',
			],
			[
				'ENTITY_ID' => $entityType,
				'ELEMENT_ID' => $entityId,
			]
		);

		while ($row = $cursor->Fetch())
		{
			if ($row['TYPE_ID'] === 'WEB' && $row['VALUE_TYPE'] === 'VK')
			{
				$cache[$entityType . '_' . $entityId] = $row['VALUE'];
				$found = true;

				break;
			}
		}

		if (!$found)
		{
			$cache[$entityType . '_' . $entityId] = '';
		}

		return $cache[$entityType . '_' . $entityId];
	}

	protected static function hasConsent()
	{
		$consent = (array)CUserOptions::GetOption('crm.activity.visit', 'consent', []);

		return ($consent['timestamp'] ?? 0) > 0;
	}

	protected static function hasRecognizeConsent()
	{
		if (!Loader::includeModule('faceid'))
		{
			return false;
		}

		global $USER;

		$row = AgreementTable::getList(array(
			'select' => [
				'ID',
			],
			'filter' => [
				'=USER_ID' => $USER->getId(),
			]
		))->fetch();

		return (bool)$row;
	}
}
