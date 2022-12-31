<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\Service\Timeline\Layout\Base;
use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Note;
use Bitrix\Crm\Service\Timeline\Layout\Footer;
use Bitrix\Crm\Service\Timeline\Layout\Header;
use Bitrix\Crm\Service\Timeline\Layout\Icon;
use Bitrix\Crm\Service\Timeline\Layout\MarketPanel;

class Layout extends Base
{
	protected ?Icon $icon = null;
	protected ?Header $header = null;
	protected ?Body $body = null;
	protected ?Footer $footer = null;
	protected ?MarketPanel $marketPanel = null;
	protected ?bool $isLogMessage = null;

	public function getIcon(): ?Icon
	{
		return $this->icon;
	}

	public function setIcon(?Icon $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function getHeader(): ?Header
	{
		return $this->header;
	}

	public function setHeader(?Header $header): self
	{
		$this->header = $header;

		return $this;
	}

	public function getBody(): ?Body
	{
		return $this->body;
	}

	public function setBody(?Body $body): self
	{
		$this->body = $body;

		return $this;
	}

	public function getFooter(): ?Footer
	{
		return $this->footer;
	}

	public function setFooter(?Footer $footer): self
	{
		$this->footer = $footer;

		return $this;
	}

	public function getMarketPanel(): ?MarketPanel
	{
		return $this->marketPanel;
	}

	public function setMarketPanel(?MarketPanel $marketPanel): self
	{
		$this->marketPanel = $marketPanel;

		return $this;
	}

	public function isLogMessage(): ?bool
	{
		return $this->isLogMessage;
	}

	public function setIsLogMessage(bool $isLogMessage = true): self
	{
		$this->isLogMessage = $isLogMessage ?: null;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'icon' => $this->getIcon(),
			'header' => $this->getHeader(),
			'body' => $this->getBody(),
			'footer' => $this->getFooter(),
			'marketPanel' => $this->getMarketPanel(),
			'isLogMessage' => $this->isLogMessage(),
		];
	}
}
