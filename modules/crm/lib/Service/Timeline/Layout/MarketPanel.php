<?php

namespace Bitrix\Crm\Service\Timeline\Layout;


class MarketPanel extends Base
{
	protected string $text;
	protected ?string $detailsText = null;
	private ?Action $detailsTextAction = null;


	public function getText(): string
	{
		return $this->text;
	}

	public function setText(string $text): self
	{
		$this->text = $text;

		return $this;
	}

	public function getDetailsText(): ?string
	{
		return $this->detailsText;
	}

	public function setDetailsText(?string $detailsText): self
	{
		$this->detailsText = $detailsText;

		return $this;
	}

	public function getDetailsTextAction(): ?Action
	{
		return $this->detailsTextAction;
	}

	public function setDetailsTextAction(?Action $detailsTextAction): self
	{
		$this->detailsTextAction = $detailsTextAction;

		return $this;
	}

	public function __construct(string $text)
	{
		$this->text = $text;
	}

	public function toArray(): array
	{
		return array_merge(
			[
				'text' => $this->getText(),
				'detailsText' => $this->getDetailsText(),
				'detailsTextAction' => $this->getDetailsTextAction(),
			]
		);
	}
}
