<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Mixin;

class EditableText extends Text
{
	use Mixin\Actionable;

	public function getRendererName(): string
	{
		return 'EditableText';
	}

	protected function getProperties(): array
	{
		$properties = parent::getProperties();
		$properties['action'] = $this->getAction();

		return $properties;
	}
}
