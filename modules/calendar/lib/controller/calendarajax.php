<?
namespace Bitrix\Calendar\Controller;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Error;
use \Bitrix\Main\Engine\Response;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class CalendarAjax
 */
class CalendarAjax extends \Bitrix\Main\Engine\Controller
{
	public function configureActions()
	{
		return [
			'getTimezoneList' => [
				'-prefilters' => [
					\Bitrix\Main\Engine\ActionFilter\Authentication::class
				]
			]
		];
	}

	public function getTimezoneListAction()
	{
		$timezones = \CCalendar::getTimezoneList();
		$defaultTimezone = \CCalendar::GetGoodTimezoneForOffset(\CCalendar::GetCurrentOffsetUTC(\CCalendar::GetCurUserId()));
		if (isset($timezones[$defaultTimezone]))
		{
			$timezones[$defaultTimezone]['default'] = true;
		}

		return $timezones;
	}

	public function editCalendarSectionAction()
	{
		$request = $this->getRequest();
		$response = [];

		$id = $request->getPost('id');
		$isNew = (!isset($id) || !$id);
		$type = $request->getPost('type');
		$ownerId = intval($request->getPost('ownerId'));
		$name = trim($request->getPost('name'));
		$color = $request->getPost('color');
		$customization = $request->getPost('customization') === 'Y';
		$userId = \CCalendar::GetUserId();
		$isPersonal = $type == 'user' && $ownerId == $userId;

		$fields = Array(
			'ID' => $id,
			'NAME' => $name,
			'COLOR' => $color,
			'CAL_TYPE' => $type,
			'OWNER_ID' => $ownerId,
			'ACCESS' => $request->getPost('access')
		);

		if ($customization && !$isNew)
		{
			\Bitrix\Calendar\UserSettings::setSectionCustomization($userId, [$id => ['name' => $name, 'color' => $color]]);
		}
		else
		{
			if($isNew) // For new sections
			{
				if($type === 'group')
				{
					// It's for groups
					if(!\CCalendarType::CanDo('calendar_type_edit_section', 'group'))
					{
						$this->addError(new Error('[se01]'.Loc::getMessage('EC_ACCESS_DENIED'), 'access_denied'));
					}
				}
				else if($type === 'user')
				{
					if (!$isPersonal)
					{
						$this->addError(new Error('[se02]'.Loc::getMessage('EC_ACCESS_DENIED'), 'access_denied'));
					}
				}
				else // other types
				{
					if (!\CCalendarType::CanDo('calendar_type_edit_section', $type))
					{
						$this->addError(new Error('[se03]'.Loc::getMessage('EC_ACCESS_DENIED'), 'access_denied'));
					}
				}

				$fields['IS_EXCHANGE'] = $request->getPost('is_exchange') == 'Y';
			}
			else
			{
				$section = \CCalendarSect::GetById($id);
				if (!$section && !$isPersonal && !\CCalendarSect::CanDo('calendar_edit_section', $id, $userId))
				{
					$this->addError(new Error('[se04]'.Loc::getMessage('EC_ACCESS_DENIED'), 'access_denied'));
				}

				$fields['CAL_TYPE'] = $section['CAL_TYPE'];
			}

			$id = intVal(\CCalendar::SaveSection(array('arFields' => $fields)));
			if ($id > 0)
			{
				\CCalendarSect::SetClearOperationCache(true);
				$response['section'] =  \CCalendarSect::GetById($id, true, true);
				if (!$response['section'])
				{
					$this->addError(new Error('[se05]'.Loc::getMessage('EC_CALENDAR_SAVE_ERROR'), 'saving_error'));
				}
				$response['accessNames'] = \CCalendar::GetAccessNames();
			}
			else
			{
				$this->addError(new Error('[se06]'.Loc::getMessage('EC_CALENDAR_SAVE_ERROR'), 'saving_error'));
			}
		}

		return $response;
	}

	public function deleteCalendarSectionAction()
	{
		$request = $this->getRequest();
		$response = [];
		$id = $request->getPost('id');

		if (!\CCalendar::IsPersonal() && !\CCalendarSect::CanDo('calendar_edit_section', $id, \CCalendar::GetUserId()))
		{
			$this->addError(new Error('[sd01]'.Loc::getMessage('EC_ACCESS_DENIED'), 'access_denied'));
		}
		else
		{
			\CCalendar::DeleteSection($id);
		}

		return $response;
	}

	public function hideExternalCalendarSectionAction()
	{
		$request = $this->getRequest();
		$response = [];
		$id = $request->getPost('id');

		if (!\CCalendar::IsPersonal() && !\CCalendarSect::CanDo('calendar_edit_section', $id, \CCalendar::GetUserId()))
		{
			$this->addError(new Error('[sd02]'.Loc::getMessage('EC_ACCESS_DENIED'), 'access_denied'));
		}

		$section = \CCalendarSect::GetById($id);
		// For exchange we change only calendar name
		if ($section && $section['CAL_DAV_CON'])
		{
			\CCalendarSect::Edit(array(
				'arFields' => array(
					"ID" => $id,
					"ACTIVE" => "N"
				)));

			// Check if it's last section from connection - remove it
			$sections = \CCalendarSect::GetList(
				array('arFilter' => array(
					'CAL_DAV_CON' => $section['CAL_DAV_CON'],
					'ACTIVE' => 'Y'
				)));

			if(!$sections || count($sections) == 0)
			{
				\CCalendar::RemoveConnection(array('id' => intval($section['CAL_DAV_CON']), 'del_calendars' => 'Y'));
			}
		}

		return $response;
	}
}