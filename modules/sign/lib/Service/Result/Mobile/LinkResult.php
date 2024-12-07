<?php

namespace Bitrix\Sign\Service\Result\Mobile;

use Bitrix\Main\Result;
use Bitrix\Sign\Item\Mobile\Link;

class LinkResult extends Result
{
	public function getLink(): ?Link
	{
		return $this->getData()['link'] ?? null;
	}

	public function setLink(Link $link): self
	{
		$this->setData(['link' => $link]);
		return $this;
	}
}
