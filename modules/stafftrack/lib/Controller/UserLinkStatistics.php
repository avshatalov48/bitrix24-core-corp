<?php

namespace Bitrix\StaffTrack\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\StaffTrack\Integration\Im\MessageService;
use Bitrix\StaffTrack\Item\User;
use Bitrix\StaffTrack\Service\UserService;
use Bitrix\Stafftrack\Integration\Pull;

class UserLinkStatistics extends Controller
{
	private ?int $userId = null;
	private UserService $userService;
	private MessageService $messageService;

	/**
	 * @return array[]
	 */
	public function configureActions(): array
	{
		return [
			'get' => [
				'+prefilters' => [
					new CloseSession()
				],
			],
		];
	}

	/**
	 * @return void
	 */
	public function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->messageService = new MessageService($this->userId);
		$this->userService = UserService::getInstance();
	}

	/**
	 * @param int $userId
	 * @param string $hash
	 * @return array|null
	 */
	public function getAction(int $userId, string $hash): ?array
	{
		if (!$this->userService->checkUser($userId, $hash))
		{
			return null;
		}

		/** @var User $user */
		$user = $this->userService->getUser($userId);

		Pull\PushService::subscribeToTag(Pull\Tag::getUserTag($user->id));

		return [
			'user' => $user->toArray(),
		];
	}

	/**
	 * @param string $dialogId
	 * @param string $link
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendAction(string $dialogId, string $link): array
	{
		$result = [];

		$sendResult = $this->messageService->sendUserStatisticsLink($dialogId, $link);
		if (!$sendResult->isSuccess())
		{
			$this->addErrors($sendResult->getErrors());
		}

		return $result;
	}
}