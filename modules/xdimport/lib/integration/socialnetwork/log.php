<?
/**
 * @access private
 */

namespace Bitrix\XDImport\Integration\SocialNetwork;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\XDImport\Internals\Utils;
use Bitrix\Socialnetwork\Item\LogIndex;

class Log
{
	const EVENT_ID_DATA = 'data';

	/**
	 * Returns set EVENT_ID processed by handler to generate content for full index.
	 *
	 * @param void
	 * @return array
	 */
	public static function getEventIdList()
	{
		return array(
			self::EVENT_ID_DATA
		);
	}

	/**
	 * Returns content for LogIndex.
	 *
	 * @param Event $event Event from LogIndex::setIndex().
	 * @return EventResult
	 */
	public static function onIndexGetContent(Event $event)
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			array(),
			'xdimport'
		);

		$eventId = $event->getParameter('eventId');
		$itemId = $event->getParameter('itemId');

		if (!in_array($eventId, self::getEventIdList()))
		{
			return $result;
		}

		$content = "";

		if (intval($itemId) > 0)
		{
			$res = \Bitrix\Socialnetwork\LogTable::getList(array(
				'filter' => array(
					'=ID' => $itemId
				),
				'select' => array('TITLE', 'MESSAGE', 'URL', 'PARAMS')
			));

			if ($logFields = $res->fetch())
			{
				$content .= \CTextParser::clearAllTags($logFields["TITLE"]);
				$content .= " ".\CTextParser::clearAllTags($logFields["MESSAGE"]);
				$content .= (!empty($logFields["URL"]) ? " ".$logFields["URL"] : "");

				$destinationsList = array();
				$res = \CSocNetLogRights::getList(
					array(),
					array('LOG_ID' => $itemId)
				);
				while ($right = $res->fetch())
				{
					$destinationsList[] = $right["GROUP_CODE"];
				}
				if (!empty($destinationsList))
				{
					$content .= ' '.join(' ', LogIndex::getEntitiesName($destinationsList));
				}

				if (!empty($logFields["PARAMS"]))
				{
					$params = Utils::getParamsFromString($logFields["PARAMS"]);

					if (
						is_array($params)
						&& !empty($params["SCHEME_ID"])
					)
					{
						$res = \CXDILFScheme::getByID($params["SCHEME_ID"]);
						if ($scheme = $res->fetch())
						{
							$content .= $scheme["NAME"];
						}
					}
				}
			}
		}

		$result = new EventResult(
			EventResult::SUCCESS,
			array(
				'content' => $content,
			),
			'xdimport'
		);

		return $result;
	}
}