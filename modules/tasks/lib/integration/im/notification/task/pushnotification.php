<?php

namespace Bitrix\Tasks\Integration\IM\Notification\Task;

use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Integration\IM\Notification\NotificationInterface;

class PushNotification implements NotificationInterface
{
	public const PUSH_MESSAGE_MAX_LENGTH = 255;
	private const PATTERN = '/#[a-zA-Z_0-9]+#/';

	private Notification $notification;

	public function __construct(Notification $notification)
	{
		$this->notification = $notification;
	}

	public function getMessage(): string
	{
		$preparedMessage = $this->cropMessage(
			$this->notification->getGenderMessage('_PUSH'),
			$this->prepareTemplates($this->notification->getTemplates()),
		);

		return (new Link($this->notification->getTask(), $this->notification->getRecepient(), Link::MODE_NONE))
			->placeLinkAnchor($preparedMessage);
	}

	public function getParams(Notification\Tag $tag): array
	{
		// user should be able to open the task window to see the changes ...
		// see /mobile/install/components/bitrix/mobile.rtc/templates/.default/script.js for handling details
		$params = [
			'ACTION' => 'tasks',
			'TAG' => $tag->getName(),
			'ADVANCED_PARAMS' => [],
		];

		$onAnswer = $this->notification->getParams()['NOTIFY_ANSWER'] ?? false;
		if ($onAnswer)
		{
			// ... and open an answer dialog in mobile
			$params['CATEGORY'] = 'ANSWER';
			$params['URL'] = SITE_DIR . 'mobile/ajax.php?mobile_action=task_answer';
			$params['PARAMS'] = [
				'TASK_ID' => $this->notification->getTask()->getId(),
			];
		}

		return $params;
	}

	/**
	 * @param string $message
	 * @param Notification\Template[] $templates
	 * @return string
	 */
	private function cropMessage(string $message, array $templates): string
	{
		$left = self::PUSH_MESSAGE_MAX_LENGTH - mb_strlen(preg_replace(self::PATTERN, '', $message));
		$result = $message;

		// todo: make more clever algorithm here
		foreach($templates as $template)
		{
			$value = $template->getReplace();
			$fullValue = $template->getReplace();
			$placeHolder = '#'. $template->getSearch() .'#';

			if ($left <= 0)
			{
				$result = str_replace($placeHolder, '', $result);
				continue;
			}

			if (mb_strlen($template->getReplace()) > $left)
			{
				$value = mb_substr($template->getReplace(), 0, $left - 3).'...';
			}

			$result = str_replace($placeHolder, $value, $result);
			$left -= mb_strlen($fullValue);
		}

		return $result;
	}

	/**
	 * @param Notification\Template[] $templates
	 * @return Notification\Template[]
	 */
	private function prepareTemplates(array $templates): array
	{
		$pushTemplates = [];

		// TODO: get rid of CUser, CSite
		$formatedUserName = \CUser::FormatName(\CSite::GetNameFormat(false), $this->notification->getSender()->toArray());

		$pushTemplates[] = new Notification\Template('USER_NAME', $formatedUserName);

		foreach ($templates as $template)
		{
			$pushTemplates[] = new Notification\Template(
				str_replace('#', '', $template->getSearch()),
				$template->getReplace()
			);
		}

		return $pushTemplates;
	}
}