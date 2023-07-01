<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

trait EditorInitialMode
{
	private string $initialMode;

	abstract protected function getEntityId();

	private function getInitialMode(bool $isCopyMode = false): string
	{
		if (!isset($this->initialMode))
		{
			$this->initialMode = (string)($this->request->get('init_mode') ?: '');

			if ($this->initialMode !== '')
			{
				$this->initialMode = mb_strtolower($this->initialMode);
				if ($this->initialMode !== 'edit' && $this->initialMode !== 'view')
				{
					$this->initialMode = '';
				}
			}

			if ($this->initialMode === '')
			{
				$entityId = $isCopyMode ? 0 : $this->getEntityId();
				$this->initialMode = $entityId > 0 ? 'view' : 'edit';
			}
		}

		return $this->initialMode;
	}
}
