<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class Link extends Text
{
	use Actionable;

	public function getRendererName(): string
	{
		return 'LinkBlock';
	}

	protected function getProperties(): array
	{
		return [
			'text' => html_entity_decode($this->getValue()),
			'bold' => $this->getIsBold(),
			'action' => $this->getAction(),
		];
	}
}
