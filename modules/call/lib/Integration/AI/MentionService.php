<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Im\User;

final class MentionService
{
	private static ?MentionService $service = null;

	private array $mentionsAi = [];
	private array $hashAi = [];
	private array $searchAi = [];
	private array $mentionsBb = [];
	private array $searchBb = [];
	private array $mentionsPage = [];
	private array $userName = [];
	private array $userAiName = [];

	private function __construct()
	{}

	public static function getInstance(): self
	{
		if (!self::$service)
		{
			self::$service = new self();
		}
		return self::$service;
	}

	public function removeBBMentions(?string $text): ?string
	{
		if ($text)
		{
			foreach ($this->searchBb as $userId => $regExp)
			{
				$userName = $this->getUserName($userId);
				$text = preg_replace("#{$regExp}#iu", $userName, $text);
			}
			if (preg_match("#\[user=([0-9]+])\](.+?)\[/user\]#iu", $text))
			{
				$text = preg_replace("#\[user=([0-9]+])\](.+?)\[/user\]#iu", "$2", $text);
			}
		}

		return $text;
	}

	public function replaceBBMentions(?string $text): ?string
	{
		if ($text)
		{
			foreach ($this->searchBb as $userId => $regExp)
			{
				$text = preg_replace("#{$regExp}#iu", $this->getPageMention($userId), $text);
			}
			if (preg_match("#\[user=([0-9]+])\](.+?)\[/user\]#iu", $text))
			{
				$text = preg_replace("#\[user=([0-9]+])\](.+?)\[/user\]#iu", "$2", $text);
			}
		}

		return $text;
	}

	public function replaceAiMentions(?string $text): ?string
	{
		if ($text)
		{
			foreach ($this->searchAi as $userId => $regExp)
			{
				$text = preg_replace("#{$regExp}#iu", $this->getBbMention($userId), $text);
			}
			foreach ($this->hashAi as $hash => $userId)
			{
				$userName = $this->getAIUserName($userId);
				$text = preg_replace("#@?{$userName}\s*_{$hash}#iu", $this->getBbMention($userId), $text);
			}
			if (preg_match_all("#@?([[:alnum:]]+)\s*_\s*([0-9]+)#iu", $text, $mentions))
			{
				foreach ($mentions[0] as $i => $_)
				{
					if ($userId = $this->hashAi[(int)$mentions[2][$i]])
					{
						$text = str_replace($mentions[0][$i], $this->getBbMention($userId), $text);
					}
				}
			}
		}

		return $text;
	}

	public function loadMentionsForCall(int $callId): void
	{
		$call = \Bitrix\Im\Call\Registry::getCallWithId($callId);
		$users = array_unique(array_merge(
			$call->getUsers(),
			$call->getAssociatedEntity()?->getUsers() ?? []
		));
		foreach ($users as $userId)
		{
			$hash = $this->generateShortId($callId, $userId);
			$this->hashAi[$hash] = $userId;
			$this->searchAi[$userId] = "@?([[:alnum:]]+)_({$hash})";
			$this->searchBb[$userId] = "\[user={$userId}\](.+?)\[/user\]";

			$this->getBbMention($userId);
			$this->getAIMention($userId, $callId);
			$this->getPageMention($userId);
		}
	}

	public function getUserName(int $userId): string
	{
		if (!isset($this->userName[$userId]))
		{
			$user = User::getInstance($userId);
			$userName = $user->getFullName() ?: $user->getName();
			$this->userName[$userId] = trim($userName);
		}
		return $this->userName[$userId];
	}

	public function getAIUserName(int $userId): string
	{
		if (!isset($this->userAiName[$userId]))
		{
			$user = User::getInstance($userId);
			$userName = preg_replace("#\s+#", '_', trim($user->getName())) ?: "User";
			$this->userAiName[$userId] = trim($userName);
		}
		return $this->userAiName[$userId];
	}

	public function getBbMention(int $userId): string
	{
		if (!isset($this->mentionsBb[$userId]))
		{
			$userName = $this->getUserName($userId);
			$this->mentionsBb[$userId] = "[user={$userId}]{$userName}[/user]";
		}
		return $this->mentionsBb[$userId];
	}

	public function getAIMention(int $userId, int $callId): string
	{
		if (!isset($this->mentionsAi[$userId]))
		{
			$userName = $this->getAIUserName($userId);
			$hash = $this->generateShortId($callId, $userId);
			$this->mentionsAi[$userId] = "{$userName}_{$hash}";
		}
		return $this->mentionsAi[$userId];
	}

	public function getPageMention(int $userId, string $tag = ''): string
	{
		$tag = $tag ?: "<span class=\"bx-call-mention\" bx-tooltip-user-id=\"%d\">%s</span>";
		if (!isset($this->mentionsPage[$userId]))
		{
			$userName = $this->getUserName($userId);
			$this->mentionsPage[$userId] = sprintf($tag, $userId, $userName);
		}
		return $this->mentionsPage[$userId];
	}

	protected function generateShortId(int $callId, int $userId): int
	{
		$number = (string)($callId + $userId);
		$sum = 0;
		$flag = 0;
		for ($i = strlen($number) - 1; $i >= 0; $i--)
		{
			$add = $flag++ & 1 ? $number[$i] * 2 : $number[$i];
			$sum += $add > 9 ? $add - 9 : $add;
		}

		return $number. $sum;
	}
}