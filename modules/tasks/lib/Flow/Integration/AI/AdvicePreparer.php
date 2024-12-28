<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Integration\AI\Provider\AdviceData;
use Bitrix\Tasks\Flow\Provider\UserProvider;
use Bitrix\Tasks\Flow\User\User;
use Throwable;

class AdvicePreparer
{
	protected string $advice = '';
	protected AdviceData $adviceData;

	protected UserProvider $userProvider;

	public function __construct()
	{
		$this->userProvider = new UserProvider();
	}

	public function prepare(AdviceData $adviceData): string
	{
		$this->adviceData = $adviceData;

		$this
			->prepareAdviceData()
			->prepareUsers()
		;

		return $this->advice;
	}

	private function prepareAdviceData(): static
	{
		$factor = $this->adviceData->factor;
		$advice = $this->adviceData->advice;

		if (!empty($factor) && !empty($advice))
		{
			$this->advice = Loc::getMessage(
				'TASKS_FLOW_INTEGRATION_AI_ADVICE_PREPARER_ADVICE_TEMPLATE',
				['#FACTOR#' => $factor, '#ADVICE#' => $advice]
			);
		}
		elseif (!empty($factor))
		{
			$this->advice = $factor;
		}

		return $this;
	}

	private function prepareUsers(): static
	{
		if (empty($this->advice))
		{
			return $this;
		}

		$userRegExp = Configuration::getUserRegExp();

		$prepareUserNamesCallback = function (array $matches) {
			$userPlaceholder = Loc::getMessage('TASKS_FLOW_INTEGRATION_AI_ADVICE_PREPARER_USER_PLACEHOLDER');
			$userId = (int)$matches[1];
			if ($userId === 0)
			{
				return $userPlaceholder;
			}

			try
			{
				$usersInfo = $this->userProvider->getUsersInfo([$userId]);
			}
			catch (Throwable)
			{
				return $userPlaceholder;
			}

			/** @var User $userInfo */
			$userInfo = $usersInfo[$userId] ?? null;
			if (empty($userInfo))
			{
				return $userPlaceholder;
			}

			return "[USER={$userId}]{$userInfo->name}[/USER]";
		};

		$this->advice = preg_replace_callback(
			$userRegExp,
			$prepareUserNamesCallback,
			$this->advice,
		);

		return $this;
	}
}
