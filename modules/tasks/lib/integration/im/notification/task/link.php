<?php

namespace Bitrix\Tasks\Integration\IM\Notification\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\TaskObject;

class Link
{
	public const MODE_EMAIL = 'EMAIL';
	public const MODE_BBCODE = 'BBCODE';
	public const MODE_NONE = 'NONE';

	private TaskObject $task;
	private User $recepient;
	private string $mode;
	private int|null $commentId;

	public function __construct(TaskObject $task, User $recepient, string $mode = self::MODE_NONE, int|null $commentId = null)
	{
		$this->task = $task;
		$this->recepient = $recepient;
		$this->mode = $mode;
		$this->commentId = $commentId;
	}

	public function placeLinkAnchor(string $message): string
	{
		$url = $this->getUrl();

		if($this->mode === self::MODE_BBCODE && !empty($url))
		{
			return str_replace(
				[
					'#TASK_URL_BEGIN#',
					'#URL_END#'
				],
				[
					'[URL=' . $url . ']',
					'[/URL]'
				],
				$message
			);
		}


		$message = str_replace(
			[
				'#TASK_URL_BEGIN#',
				'#URL_END#'
			],
			[
				'',
				''
			],
			$message
		);

		if($this->mode === self::MODE_EMAIL && !empty($url))
		{
			$message .= ' #BR# ' . Loc::getMessage('TASKS_MESSAGE_LINK_GENERAL', null, $this->recepient->getLang()) . ': ' . $url; // #BR# will be converted to \n by IM
		}

		return $message;
	}

	private function getUrl(): string
	{
		$sites = \Bitrix\Tasks\Util\Site::getPair();
		// TODO: refactor
		$url = new Uri(
			$this->appendCommentAnchor(
				\CTaskNotifications::getNotificationPath(['ID' => $this->recepient->getId()], $this->task->getId(), true, $sites)
			)
		);

		$url->addParams([
			'ta_sec' => Analytics::SECTION['chat'],
			'ta_el' => Analytics::ELEMENT['title_click'],
		]);

		return $url->getUri();
	}

	private function appendCommentAnchor(string $url): string
	{
		$querySymbol = mb_strpos($url, '?') > 0 ? '&' : '?';

		return $this->commentId
			? $url . $querySymbol . 'MID=' . $this->commentId . '#com' . $this->commentId
			: $url;
	}
}