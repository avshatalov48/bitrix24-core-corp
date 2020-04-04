<?
/**
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class Log
{
	const EVENT_ID_TASK = 'tasks';

	/**
	 * Returns set EVENT_ID processed by handler to generate content for full index.
	 *
	 * @param void
	 * @return array
	 */
	public static function getEventIdList()
	{
		return array(
			self::EVENT_ID_TASK
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
			'tasks'
		);

		$eventId = $event->getParameter('eventId');
		$sourceId = $event->getParameter('sourceId');

		if (!in_array($eventId, self::getEventIdList()))
		{
			return $result;
		}

		$content = "";
		$task = false;

		if (intval($sourceId) > 0)
		{
			$task = new \Bitrix\Tasks\Item\Task($sourceId);
		}

		if ($task)
		{
			$controllerDefault = $task->getAccessController();
			$controller = $controllerDefault->spawn();
			$controller->disable();
			$task->setAccessController($controller);

			$taskFields = $task->getData('#', array('bSkipExtraData' => false));
			if (is_array($taskFields))
			{
				$content = \Bitrix\Tasks\Manager\Task::prepareSearchIndex($taskFields);
			}

			$controller->enable();
		}

		$result = new EventResult(
			EventResult::SUCCESS,
			array(
				'content' => $content,
			),
			'tasks'
		);

		return $result;
	}
}