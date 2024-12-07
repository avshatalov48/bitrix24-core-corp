<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Forum;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\ProviderInterface;

class NotificationProvider implements ProviderInterface
{
	/** @var Message[]  */
	private array $messages;

	public function addMessage(Message $message): void
	{
		$this->messages[] = $message;
	}

	public function pushMessages(): void
	{
		foreach ($this->messages as $message)
		{
			$task = $message->getMetaData()->getTask();
			if ($task === null)
			{
				continue;
			}

			$text = $message->getMetaData()->getParams()['text'] ?? null;
			if ($text === null)
			{
				continue;
			}

			$entityCodeOperation = $message->getMetaData()->getEntityCode() . ':' . $message->getMetaData()->getEntityOperation();

			switch ($entityCodeOperation)
			{
				case EntityCode::CODE_COMMENT . ':' . EntityOperation::REPLY:
					$this->onCommentReply($message, $text);
					break;
			}
		}
	}

	private function onCommentReply(Message $message, string $text): void
	{
		$task = new \CTaskItem($message->getMetaData()->getTask()->getId(), $message->getSender()->getId());

		$commentId = \CTaskCommentItem::add($task, ['POST_MESSAGE' => $text]);

		if (
			$commentId > 0
			&& \Bitrix\Main\Loader::includeModule('socialnetwork')
		)
		{
			$res = \Bitrix\Socialnetwork\LogCommentTable::getList(array(
				'filter' => array(
					'EVENT_ID' => array('crm_activity_add_comment', 'tasks_comment'),
					'SOURCE_ID' => $commentId
				),
				'select' => array('ID', 'LOG_ID')
			));
			if ($logCommentFields = $res->fetch())
			{
				$res = \Bitrix\Socialnetwork\LogTable::getList(array(
					'filter' => array(
						"=ID" => $logCommentFields['LOG_ID']
					),
					'select' => array("ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "SOURCE_ID")
				));
				if ($logEntry = $res->fetch())
				{
					$logCommentFields = \Bitrix\Socialnetwork\Item\LogComment::getById($logCommentFields['ID'])->getFields();

					$res = \CSite::getByID(SITE_ID);
					$site = $res->fetch();

					$userPage = Option::get('socialnetwork', 'user_page', $site['DIR'] . 'company/personal/');
					$userPath = $userPage.'user/'.$logEntry['USER_ID'].'/';

					\Bitrix\Socialnetwork\ComponentHelper::addLiveComment(
						$logCommentFields,
						$logEntry,
						\CSocNetLogTools::findLogCommentEventByLogEventID($logEntry["EVENT_ID"]),
						array(
							"ACTION" => 'ADD',
							"SOURCE_ID" => $logCommentFields['SOURCE_ID'],
							"TIME_FORMAT" => \CSite::getTimeFormat(),
							"PATH_TO_USER" => $userPath,
							"NAME_TEMPLATE" => \CSite::getNameFormat(null, SITE_ID),
							"SHOW_LOGIN" => "N",
							"AVATAR_SIZE" => 100,
							"LANGUAGE_ID" => $site["LANGUAGE_ID"],
							"SITE_ID" => SITE_ID,
							"PULL" => "N",
						)
					);
				}
			}
		}
	}
}