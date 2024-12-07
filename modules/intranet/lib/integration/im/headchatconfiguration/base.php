<?php

namespace Bitrix\Intranet\Integration\Im\HeadChatConfiguration;

use Bitrix\Intranet\CurrentUser;

abstract class Base
{
	protected ?int $headId = null;

	public function __construct(private string $code)
	{
		$this->initializationHeadId();
	}

	public function isValid(): bool
	{
		return (bool)$this->getHeadId();
	}

	abstract public function getChatTitle(): string;

	abstract public function getAvatar(): string;

	abstract public function getBannerId(): string;

	abstract public function getBannerDescription(): string;

	public function getHeadId(): ?int
	{
		return $this->headId;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	private function initializationHeadId(): void
	{
		$currentUser = CurrentUser::get();
		$heads = \CIntranetUtils::GetDepartmentManager($currentUser->getDepartmentIds(), $currentUser->getId(), true);

		foreach ($heads as $head)
		{
			if (!empty($head) && isset($head['ID']))
			{
				$this->headId = (int)$head['ID'];

				return;
			}
		}
	}
}