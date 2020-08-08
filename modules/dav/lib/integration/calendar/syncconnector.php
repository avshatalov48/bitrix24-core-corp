<?
namespace Bitrix\Dav\Integration\Calendar;

use \Bitrix\Calendar\Integration\Dav\SyncAdapter;

class SyncConnector
{
	/**
	 * Returns collection of sections for user or other entity.
	 *
	 * @param array $calendarId contains combined information about requested collection.
	 * @param array $params additional params.
	 * @return array list of calendar sections
	 */
	public static function getCalendarSectionList($calendarId, $params = [])
	{
		list($sectionId, $entityType, $entityId) = $calendarId;
		$params['sectionId'] = $sectionId;
		$params['active'] = true;
		$params['skipExchange'] = isset($params['skipExchange']) ? $params['skipExchange'] : true;

		return SyncAdapter::getSectionList($entityType, $entityId, $params);
	}

	/**
	 * Returns calendar events
	 *
	 * @param array $calendarId contains combined information about requested collection.
	 * @return array list of calendar sections
	 */
	public static function getCalendarEventList($calendarId, $filter = [])
	{
		list($sectionId, $entityType, $entityId) = $calendarId;
		$params = [
			'filter' => $filter,
			'entityType' => $entityType,
			'entityId' => $entityId,
		];
		return SyncAdapter::getEventList($sectionId, $params);
	}

	/**
	 * Saves calendar event
	 *
	 * @param array $calendarId contains combined information about requested collection.
	 * @param array $params contains fields and other information.
	 * @return id of the event or false
	 */
	public static function modifyEvent($calendarId, $params = [])
	{
		$eventId = \CCalendarSync::ModifyEvent($calendarId, $params['fields']);

		if (count($params['instances']) > 0)
		{
			\CCalendarSync::ModifyReccurentInstances(array(
				'events' => $params['instances'],
				'parentId' => $eventId,
				'calendarId' => $calendarId
			));
		}
		return $eventId;
	}

	/**
	 * Deletes calendar event
	 *
	 * @param array $calendarId contains combined information about requested collection.
	 * @param array $params contains fields and other information.
	 * @return true or false - result of the operation
	 */
	public static function deleteEvent($calendarId, $params = [])
	{
		list($sectionId, $entityType, $entityId) = $calendarId;
		return SyncAdapter::deleteEvent($params['eventId'], [
			'userId' => $params['userId'],
			'sectionId' => $sectionId,
			'entityType' => $entityType,
			'entityId' => $entityId
		]);
	}
}