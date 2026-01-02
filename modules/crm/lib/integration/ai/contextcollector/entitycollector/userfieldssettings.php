<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector;

final class UserFieldsSettings
{
	private bool $isCollect = true;
	private bool $isCollectName = true;

	public function isCollect(): bool
	{
		return $this->isCollect;
	}

	public function setIsCollect(bool $isCollect): self
	{
		$this->isCollect = $isCollect;

		return $this;
	}

	public function isCollectName(): bool
	{
		return $this->isCollectName;
	}

	public function setIsCollectName(bool $isCollectName): self
	{
		$this->isCollectName = $isCollectName;

		return $this;
	}
}
