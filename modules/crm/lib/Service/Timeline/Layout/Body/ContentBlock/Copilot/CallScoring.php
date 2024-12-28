<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Copilot;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin;

final class CallScoring extends ContentBlock
{
	use Mixin\Actionable;

	protected ?string $userName = null;
	protected ?string $userAvatarUrl = null;
	protected ?array $scoringData = null;

	public function getRendererName(): string
	{
		return 'CallScoring';
	}

	public function getUserName(): ?string
	{
		return $this->userName;
	}

	public function setUserName(?string $userName): self
	{
		$this->userName = $userName;

		return $this;
	}

	public function getUserAvatarUrl(): ?string
	{
		return $this->userAvatarUrl;
	}

	public function setUserAvatarUrl(?string $userAvatarUrl): self
	{
		$this->userAvatarUrl = $userAvatarUrl;

		return $this;
	}

	public function getScoringData(): ?array
	{
		return $this->scoringData;
	}

	public function setScoringData(?array $scoringData): self
	{
		$this->scoringData = $scoringData;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'userName' => $this->getUserName(),
			'userAvatarUrl' => $this->getUserAvatarUrl(),
			'scoringData' => $this->getScoringData(),
			'action' => $this->getAction(),
		];
	}
}
