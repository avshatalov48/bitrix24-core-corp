<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Entity\EntityEditorConfigScope;

class Calendar
{
	private static
		$isResourceBookingEnabled,
		$fieldsMap = array();

	public static function isResourceBookingEnabled()
	{
		if (is_null(self::$isResourceBookingEnabled))
		{
			self::$isResourceBookingEnabled = \Bitrix\Main\ModuleManager::isModuleInstalled('calendar') && class_exists('\Bitrix\Calendar\UserField\ResourceBooking');
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
		$user_id = false;
		if (is_object($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
		{
			$user_id = intval($USER->GetID());
		}

		if (!self::isResourceBookingEnabled() || !$user_id)
		{
			return;
		}

		$spotlight = new \Bitrix\Main\UI\Spotlight("CRM_CALENDAR_VIEW");
		//$spotlight->unsetViewDate($user_id);
		if(!$spotlight->isViewed($user_id))
		{
			\CJSCore::init("spotlight");

			$message = \CUtil::JSEscape(Loc::getMessage('CRM_CALENDAR_VIEW_SPOTLIGHT'));
			$message .= ' <a href="javascript:void(0);" onclick="BX.Helper.show(\\\'redirect=detail&code=7481073\\\')">'.Loc::getMessage('CRM_CALENDAR_HELP_LINK').'</a>';
			?>
			<script type="text/javascript">
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
		$user_id = false;
		if (is_object($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
		{
			$user_id = intval($USER->GetID());
		}

		if (!self::isResourceBookingEnabled() || !$user_id)
		{
			return;
		}

		$spotlight = new \Bitrix\Main\UI\Spotlight("CRM_CALENDAR_VIEW_MODE_SELECTOR_".$entityName);
		//$spotlight->unsetViewDate($user_id);
		if(!$spotlight->isViewed($user_id))
		{
			\CJSCore::init("spotlight");
			$message = $entityName == 'LEAD'
				? \CUtil::JSEscape(Loc::getMessage('CRM_CALENDAR_VIEW_MODE_SPOTLIGHT_LEAD'))
				: \CUtil::JSEscape(Loc::getMessage('CRM_CALENDAR_VIEW_MODE_SPOTLIGHT_DEAL'));

			$message .= ' <a href="javascript:void(0);" onclick="BX.Helper.show(\\\'redirect=detail&code=7481073\\\')">'.Loc::getMessage('CRM_CALENDAR_HELP_LINK').'</a>';
			?>
			<script type="text/javascript">
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

	public static function isUserfieldShownInForm($userfield, $entityType, $categoryId = 0)
	{
		$map = array();
		$categoryId = intVal($categoryId);
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
			self::$fieldsMap[$configId] = array();
			$formSettings = self::getFormFieldsMap($configId);

			if (is_array($formSettings))
			{
				foreach($formSettings as $section)
				{
					if(is_array($section['elements']))
					{
						foreach($section['elements'] as $field)
						{
							if (is_array($field) && isset($field['name']))
							{
								self::$fieldsMap[$configId][$field['name']] = true;
							}
						}
					}
				}
			}
		}
	}

	private static function getFormFieldsMap($configId)
	{
		$configScope = \CUserOptions::GetOption(
			'crm.entity.editor',
			"{$configId}_scope",
			EntityEditorConfigScope::UNDEFINED
		);

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
		list($filterSelectId, $filterSelectType, $filterSelectName) = self::parseUserfieldKey($filterSelect);

		if (intval($filterSelectId) > 0 && ($filterSelectType == 'resourcebooking' || is_null($filterSelectType)))
		{
			$url .= '#calendar:'.urlencode($filterSelectId).'|#DATE_FROM#|#DATE_TO#';
		}
		elseif (intval($filterSelectId) > 0 && ($filterSelectType == 'date' || $filterSelectType == 'datetime'))
		{
			$url = \CHTTP::urlAddParams($url, array($filterSelectName => '#DATE_FROM#'));
		}
		elseif($filterSelectId == 'CLOSEDATE' || $filterSelectId == 'BEGINDATE')
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
}