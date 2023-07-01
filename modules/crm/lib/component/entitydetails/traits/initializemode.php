<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

use Bitrix\Crm\Component\EntityDetails\ComponentMode;

trait InitializeMode
{
	abstract protected function getEntityId();

	private function initializeMode(): void
	{
		if ($this->getEntityId() > 0)
		{
			$componentMode = $this->arParams['COMPONENT_MODE'] ?? null;

			if ($componentMode === ComponentMode::COPING || $this->request->get('copy') !== null)
			{
				$this->isCopyMode = true;
			}
			else
			{
				$this->isEditMode = true;
			}
		}

		$this->arResult['IS_COPY_MODE'] = $this->isCopyMode;
		$this->arResult['IS_EDIT_MODE'] = $this->isEditMode;
	}
}
