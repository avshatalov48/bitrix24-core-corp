<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Calendar\Core\Managers\Accessibility;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use CCalendarSect;

class Calendar
{
    public const EVENT_FIELD_NAME = 'UF_CRM_CAL_EVENT';
    public const USER_FIELD_ENTITY_ID = 'CALENDAR_EVENT';

	private static ?bool $isResourceBookingEnabled = null;
	private static array $fieldsMap = [];
	private static array $calendarEvents = [];

	public static function isResourceBookingEnabled()
	{
		if (is_null(self::$isResourceBookingEnabled))
		{
			self::$isResourceBookingEnabled = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled()
				&& \Bitrix\Main\ModuleManager::isModuleInstalled('calendar')
				&& class_exists('\Bitrix\Calendar\UserField\ResourceBooking');
		}
		return self::$isResourceBookingEnabled;
	}

	public static function isResourceBookingAvailableForEntity($entity)
	{
		if (in_array($entity, array('CRM_LEAD', 'CRM_DEAL')))
		{
			return \Bitrix\Main\Loader::includeModule('calendar') && self::isResourceBookingEnabled();
		}
		return false;
	}

	public static function getCalendarViewFieldOption($type, $defaultValue = '')
	{
		$settingsFilterSelect = \CUserOptions::getOption("calendar", "resourceBooking");
		$filterSelect = $settingsFilterSelect[$type];
		if (empty($filterSelect))
		{
			$filterSelect = $defaultValue;
		}
		return $filterSelect;
	}

	public static function showCalendarSpotlight()
	{
		global $USER;
		$userId = false;
		if (is_object($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
		{
			$userId = intval($USER->GetID());
		}

		if (!self::isResourceBookingEnabled() || !$userId)
		{
			return;
		}

		$spotlight = new \Bitrix\Main\UI\Spotlight("CRM_CALENDAR_VIEW");
		if(!$spotlight->isViewed($userId))
		{
			\CJSCore::init("spotlight");

			$message = \CUtil::JSEscape(Loc::getMessage('CRM_CALENDAR_VIEW_SPOTLIGHT'));
			$message .= ' <a href="javascript:void(0);" onclick="BX.Helper.show(\\\'redirect=detail&code=7481073\\\')">'.Loc::getMessage('CRM_CALENDAR_HELP_LINK').'</a>';
			?>
			<script>
				BX.ready(function()
				{
					var i, viewSwitcherListItems = document.querySelectorAll(".crm-view-switcher-list-item");

					if (viewSwitcherListItems && viewSwitcherListItems.length > 0)
					{
						for (i = 0; i < viewSwitcherListItems.length; i++)
						{
							if (viewSwitcherListItems[i].id.indexOf('_calendar') !== -1)
							{
								setTimeout(function(){
									new BX.SpotLight({
										targetElement: viewSwitcherListItems[i],
										targetVertex: "middle-center",
										content: '<?= $message?>',
										id: "CRM_CALENDAR_VIEW",
										autoSave: true
									}).show();
								}, 2000);
								break;
							}
						}
					}
				});
			</script>
			<?
		}
	}

	public static function showViewModeCalendarSpotlight($entityName)
	{
		global $USER;
		$userId = false;
		if (is_object($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
		{
			$userId = intval($USER->GetID());
		}

		if (!self::isResourceBookingEnabled() || !$userId)
		{
			return;
		}

		$spotlight = new \Bitrix\Main\UI\Spotlight("CRM_CALENDAR_VIEW_MODE_SELECTOR_".$entityName);
		if(!$spotlight->isViewed($userId))
		{
			\CJSCore::init("spotlight");
			$message = $entityName == 'LEAD'
				? \CUtil::JSEscape(Loc::getMessage('CRM_CALENDAR_VIEW_MODE_SPOTLIGHT_LEAD'))
				: \CUtil::JSEscape(Loc::getMessage('CRM_CALENDAR_VIEW_MODE_SPOTLIGHT_DEAL'));

			$message .= ' <a href="javascript:void(0);" onclick="BX.Helper.show(\\\'redirect=detail&code=18797220\\\')">'.Loc::getMessage('CRM_CALENDAR_HELP_LINK').'</a>';
			?>
			<script>
				BX.ready(function()
				{
					var viewMode = document.querySelector(".calendar-view-switcher-text-mode-inner");
					if (viewMode && viewMode.innerHTML)
					{
						setTimeout(function(){
							new BX.SpotLight({
								targetElement: viewMode,
								targetVertex: "middle-center",
								content: '<?= $message?>',
								id: "CRM_CALENDAR_VIEW_MODE_SELECTOR_<?= $entityName?>",
								autoSave: true
							}).show();
						}, 2000);
					}
				});
			</script>
			<?
		}
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Crm\Entity\EntityEditorConfig::isFormFieldVisible
	 */
	public static function isUserfieldShownInForm($userfield, $entityType, $categoryId = 0)
	{
		$map = [];
		$categoryId = intval($categoryId);
		if ($entityType == 'CRM_DEAL')
		{
			$configId = \Bitrix\Crm\Category\DealCategory::prepareFormID(
				$categoryId,
				'deal_details',
				false
			);

			self::fillCrmFormFieldMap($configId);

			if (is_array(self::$fieldsMap[$configId]))
			{
				$map = self::$fieldsMap[$configId];
			}
		}
		elseif($entityType == 'CRM_LEAD')
		{
			$configId = "lead_details";
			self::fillCrmFormFieldMap($configId);
			if (is_array(self::$fieldsMap[$configId]))
			{
				$map = self::$fieldsMap[$configId];
			}

			if (!empty($map))
			{
				$configId = "returning_lead_details";
				self::fillCrmFormFieldMap($configId);
				if (is_array(self::$fieldsMap[$configId]))
				{
					$map = array_merge($map, self::$fieldsMap[$configId]);
				}
			}
		}
		else
		{
			return false;
		}

		if (empty($map))
		{
			return true;
		}

		return $map[$userfield['FIELD_NAME']];
	}

	private static function fillCrmFormFieldMap($configId)
	{
		if (!isset(self::$fieldsMap[$configId]))
		{
			self::$fieldsMap[$configId] = [];
			$formSettings = self::getFormFieldsMap($configId);

			if (is_array($formSettings))
			{
				foreach($formSettings as $section)
				{
					if(is_array($section['elements']))
					{
						foreach($section['elements'] as $field)
						{
							if (is_array($field))
							{
								if (is_array($field['elements']))
								{
									foreach ($field['elements'] as $item)
									{
										self::$fieldsMap[$configId][$item['name']] = true;
									}
								}
							}
						}
					}
				}
			}
		}
	}

	private static function getFormFieldsMap($configId)
	{
		$scope = new \Bitrix\Crm\Component\EntityDetails\Config\Scope();
		$configScope = $scope->getByConfigId($configId, 'crm.entity.editor');

		if($configScope === EntityEditorConfigScope::UNDEFINED)
		{
			$formSettings = \CUserOptions::GetOption(
				'crm.entity.editor',
				$configId,
				null
			);

			if(!is_array($formSettings) || empty($formSettings))
			{
				$formSettings = \CUserOptions::GetOption(
					'crm.entity.editor',
					"{$configId}_common",
					null,
					0
				);
			}
		}
		elseif($configScope === EntityEditorConfigScope::COMMON)
		{
			$formSettings = \CUserOptions::GetOption(
				'crm.entity.editor',
				"{$configId}_common",
				null,
				0
			);
		}
		else
		{
			$formSettings = \CUserOptions::GetOption(
				'crm.entity.editor',
				$configId,
				null
			);
		}

		if ($formSettings === null)
		{
			$formSettings = \CUserOptions::GetOption('crm.entity.editor', $configId, null);
		}

		return $formSettings;
	}

	public static function getUserfieldKey($userfield)
	{
		return $userfield['ID'].'|'.$userfield['USER_TYPE_ID'].'|'.$userfield['FIELD_NAME'];
	}

	public static function parseUserfieldKey($key)
	{
		return explode('|', $key);
	}

	public static function prepareNewEntityUrlFromCalendar($url = '', $filterSelect = '')
	{
		$filterSelectId = null;
		$filterSelectType = null;
		$filterSelectName = null;

		$parsedKeys = self::parseUserfieldKey($filterSelect);
		if (count($parsedKeys) > 1)
		{
			[$filterSelectId, $filterSelectType, $filterSelectName] = self::parseUserfieldKey($filterSelect);
		}

		if ((int)$filterSelectId > 0 && ($filterSelectType === 'resourcebooking' || is_null($filterSelectType)))
		{
			$url .= '#calendar:'.urlencode($filterSelectId).'|#DATE_FROM#|#DATE_TO#';
		}
		elseif ((int)$filterSelectId > 0 && ($filterSelectType === 'date' || $filterSelectType === 'datetime'))
		{
			$url = \CHTTP::urlAddParams($url, array($filterSelectName => '#DATE_FROM#'));
		}
		elseif($filterSelectId == 'CLOSEDATE' || $filterSelectId === 'BEGINDATE')
		{
			$url = \CHTTP::urlAddParams($url, array($filterSelectId => '#DATE_FROM#'));
		}

		return $url;
	}

	public static function handleCrmEntityBookingEntry($data, $result = array())
	{
		$result['SKIP_TIME'] = $data['RES_BOOKING_SKIP_TIME'];
		$currentUserID = \CCalendar::GetCurUserId();

		if ($result['SKIP_TIME'])
		{
			$fromTs = \CCalendar::Timestamp($data['RES_BOOKING_FROM']) - \CCalendar::GetOffset($currentUserID);
			$toTs = \CCalendar::Timestamp($data['RES_BOOKING_TO']) - \CCalendar::GetOffset($currentUserID);
			$result['DATE_FROM'] = \CCalendar::Date($fromTs);
			$result['DATE_TO'] = \CCalendar::Date($toTs);

			$result['DURATION'] = \CCalendar::Timestamp($result['DATE_TO']) - \CCalendar::Timestamp($result['DATE_FROM']) + \CCalendar::DAY_LENGTH;

			if ($result['DURATION'] <= 0)
			{
				$result['DURATION'] = \CCalendar::DAY_LENGTH;
			}
		}
		else
		{
			$fromTs = \CCalendar::Timestamp($data['RES_BOOKING_FROM']) - \CCalendar::GetOffset($currentUserID);
			$toTs = \CCalendar::Timestamp($data['RES_BOOKING_TO']) - \CCalendar::GetOffset($currentUserID);

			$userOffsetFrom = \CCalendar::GetTimezoneOffset($data['RES_BOOKING_TZ_FROM'], $fromTs) - \CCalendar::GetCurrentOffsetUTC($currentUserID);
			$userOffsetTo = \CCalendar::GetTimezoneOffset($data['RES_BOOKING_TZ_TO'], $toTs) - \CCalendar::GetCurrentOffsetUTC($currentUserID);

			$result['DATE_FROM'] = \CCalendar::Date($fromTs - $userOffsetFrom);
			$result['DATE_TO'] = \CCalendar::Date($toTs - $userOffsetTo);

			$result['DURATION'] = $toTs - $fromTs - 1;

			if ($result['DURATION'] <= 0)
			{
				$result['DURATION'] = 3600;
			}
		}

		return $result;
	}

	public static function loadResourcebookingUserfieldExtention()
	{
		if (Extension::getConfig('calendar.resourcebookinguserfield') !== null)
		{
			Extension::load(['calendar.resourcebookinguserfield']);
		}
		else
		{
			Extension::load(['userfield_resourcebooking']);
		}
	}

	public static function loadResourcebookingExtention()
	{
		if (Extension::getConfig('calendar.resourcebooking') !== null
			&&
			Extension::getConfig('ui.vue.components.datepick') !== null)
		{
			Extension::load(['calendar.resourcebooking', 'ui.vue.components.datepick']);
		}
		else
		{
			Extension::load(['userfield_resourcebooking']);
		}
	}

	public static function getCalendarSettingsOpenJs($settingsParams = [])
	{
		if (Extension::getConfig('calendar.resourcebookinguserfield') !== null)
		{
			$js = 'BX.Calendar.ResourcebookingUserfield.openExternalSettingsSlider('.\Bitrix\Main\Web\Json::encode($settingsParams).')';
		}
		else
		{
			$js = 'BX.Calendar.UserField.ResourceBooking.openExternalSettingsSlider('.\Bitrix\Main\Web\Json::encode($settingsParams).')';
		}
		return $js;
	}

	public static function getEvent(int $id, bool $checkPermissions = false): ?array
	{
		if (
			empty(self::$calendarEvents[$id])
			&& $id > 0
			&& Loader::includeModule('calendar')
		)
		{
			$calendarEvent = \CCalendarEvent::GetById($id, $checkPermissions);
			if (is_array($calendarEvent))
			{
				self::$calendarEvents[$id] = $calendarEvent;
			}
		}

		return self::$calendarEvents[$id] ?? null;
	}

	public static function getSectionListAvailableForUser(int $userId): array
	{
		if (!Loader::includeModule('calendar'))
		{
			return [];
		}

		return \CCalendar::getSectionListAvailableForUser($userId);
	}

	public static function getCrmSectionId(int $userId, bool $autoCreate = false): ?int
	{
		if (!Loader::includeModule('calendar'))
		{
			return null;
		}

		$crmSection = \CCalendar::GetCrmSection($userId, $autoCreate);

		return is_string($crmSection) ? (int)$crmSection : null;
	}

	public static function createDefault(array $params = []): ?array
	{
		if (!Loader::includeModule('calendar'))
		{
			return null;
		}

		$result = CCalendarSect::CreateDefault([
			'type' => $params['type'] ?? null,
			'ownerId' => $params['ownerId'] ?? null,
		]);

		return is_array($result) ? $result : null;
	}

	public static function getBusyUsersIds(array $userIds, int $fromTs, int $toTs, ?int $curEventId = null): array
	{
		if (!\Bitrix\Main\Loader::includeModule('calendar'))
		{
			return [];
		}

		$accessibility = (new Accessibility());
		if ($curEventId !== null)
		{
			$accessibility->setSkipEventId($curEventId);
		}

		return array_unique($accessibility->getBusyUsersIds($userIds, $fromTs, $toTs));
	}
}
