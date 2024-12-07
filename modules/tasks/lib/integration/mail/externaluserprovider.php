<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Mail;

use Bitrix\Main\Mail\Address;
use Bitrix\Tasks\Integration\Mail;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\ProviderInterface;
use Bitrix\Tasks\Util\Type;

class ExternalUserProvider implements ProviderInterface
{
	/** @var Message[]  */
	private array $messages = [];

	public function addMessage(Message $message): void
	{
		$this->messages[] = $message;
	}

	public function pushMessages(): void
	{
		if (!Mail::isInstalled())
		{
			return;
		}

		$siteId = $this->getSiteId();
		if ($siteId === null)
		{
			return;
		}

		foreach ($this->messages as $message)
		{
			if(false === \Bitrix\Tasks\Integration\Mail\User::isEmail($message->getRecepient()->toArray()))
			{
				continue;
			}

			$task = $message->getMetaData()->getTask();
			if ($task === null)
			{
				continue;
			}

			switch ($message->getMetaData()->getEntityCode())
			{
				case EntityCode::CODE_TASK:
					$this->sendTaskEmail($message, $siteId);
					break;
				case EntityCode::CODE_COMMENT:
					$this->sendCommentEmail($message, $siteId);
					break;
			}
		}
	}

	private function getSiteId(): ?string
	{
		$sites = \Bitrix\Tasks\Util\Site::getPair();
		if (!is_array($sites['INTRANET']))
		{
			return null;
		}

		$site = $sites['INTRANET'];

		if(empty($site['SITE_ID']))
		{
			$site = \Bitrix\Tasks\Util\Site::get(SITE_ID);
		}

		if(empty($site['SITE_ID'])) // no way, this cant be true
		{
			return null;
		}

		return $site['SITE_ID'];
	}

	private function sendTaskEmail(Message $message, string $siteId): void
	{
		$task = $message->getMetaData()->getTask();
		$entityOperation = $message->getMetaData()->getEntityOperation();

		if (!in_array($entityOperation, [EntityOperation::ADD, EntityOperation::UPDATE]))
		{
			return;
		}

		$subjPrefix = '';
		$eventId = 'TASKS_TASK_' . $entityOperation . '_EMAIL';
		$threadMessageId = Mail::formatThreadId('TASK_'.$task->getId(), $siteId);

		if($entityOperation === EntityOperation::UPDATE)
		{
			$threadMessageId = Mail::formatThreadId(
				sprintf('TASK_UPDATE_%u_%x%x', $task->getId(), time(), rand(0, 0xffffff)),
				$siteId
			);

			$subjPrefix = Mail::getSubjectPrefix();
		}

		$this->sendEmail(
			$message,
			$eventId,
			$siteId,
			$subjPrefix,
			$threadMessageId
		);
	}

	private function sendCommentEmail(Message $message, string $siteId): void
	{
		if($message->getMetaData()->getEntityOperation() === EntityOperation::ADD)
		{
			$commentId = $message->getMetaData()->getCommentId();
			if(!$commentId)
			{
				return;
			}

			$threadMessageId = Mail::formatThreadId('TASK_COMMENT_' . $commentId, $siteId);
			$subjPrefix = Mail::getSubjectPrefix();

			$this->sendEmail(
				$message,
				'TASKS_TASK_COMMENT_ADD_EMAIL',
				$siteId,
				$subjPrefix,
				$threadMessageId
			);
		}
	}

	private function sendEmail(Message $message, string $eventId, string $siteId, string $subjPrefix, string $threadMessageId): void
	{
		$metadata = $message->getMetaData();
		$entityCode = $metadata->getEntityCode();
		$entityOperation = $metadata->getEntityOperation();
		$task = $metadata->getTask();
		$sender = $message->getSender();
		$pathToTask = \Bitrix\Tasks\Integration\Mail\Task::getDefaultPublicPath($task->getId());
		$authorName = str_replace(['<', '>', '"'], '', \Bitrix\Tasks\Util\User::formatName([
			'ID' => $sender->getId(),
			'NAME' => $sender->getName(),
			'LAST_NAME' => $sender->getLastName(),
			'SECOND_NAME' => $sender->getSecondName(),
		], $siteId));

		$receiversData = \Bitrix\Tasks\Integration\Mail\User::getData([$message->getRecepient()->getId()], $siteId);
		if(empty($receiversData))
		{
			return; // nowhere to send
		}

		foreach ($receiversData as $userId => $arUser)
		{
			$email = $arUser['EMAIL'];
			$nameFormatted = str_replace(['<', '>', '"'], '', $arUser['NAME_FORMATTED']);

			$replyTo = \Bitrix\Tasks\Integration\Mail\Task::getReplyTo(
				$userId,
				$task->getId(),
				$pathToTask,
				$siteId
			);

			if ($replyTo == '')
			{
				return;
			}

			$replyTo = new Address($authorName.' <'.$replyTo.'>');
			$emailTo =  new Address(!empty($nameFormatted) ? ''.$nameFormatted.' <'.$email.'>' : $email);
			$emailFrom = new Address($authorName.' <'. Mail::getDefaultEmailFrom($siteId).'>');

			$emailData = [
				"=Reply-To" => $replyTo->getEncoded(),
				"=Message-Id" => $threadMessageId,
				"EMAIL_FROM" => $emailFrom->getEncoded(),
				"EMAIL_TO" => $emailTo->getEncoded(),

				"TASK_ID" => $task->getId(),
				"TASK_COMMENT_ID" => $metadata->getCommentId(),
				"TASK_TITLE" => trim($task->getTitle()),
				"TASK_PREVIOUS_FIELDS" => Type::serializeArray($metadata->getPreviousFields()),

				"RECIPIENT_ID" => $userId,
				"USER_ID" => \Bitrix\Tasks\Util\User::getAdminId(),

				"URL" => $pathToTask,
				"SUBJECT" => $subjPrefix . trim($task->getTitle())
			];

			if (!(EntityCode::CODE_TASK === $entityCode && EntityOperation::ADD === $entityOperation))
			{
				$emailData['=In-Reply-To'] = Mail::formatThreadId('TASK_'.$task->getId(), $siteId);
			}

			\CEvent::Send(
				$eventId,
				$siteId,
				$emailData
			);
		}
	}
}
