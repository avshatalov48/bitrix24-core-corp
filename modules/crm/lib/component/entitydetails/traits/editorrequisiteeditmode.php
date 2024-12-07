<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

trait EditorRequisiteEditMode
{
	private string $requisiteEditMode;

	private function isRequisiteEditMode(): bool
	{
		if (!isset($this->requisiteEditMode))
		{
			$this->requisiteEditMode = mb_strtolower((string)($this->request->get('rqedit') ?: 'n'));

			if ($this->requisiteEditMode !== 'y' && $this->requisiteEditMode !== 'n')
			{
				$this->requisiteEditMode = 'n';
			}
		}

		return ($this->requisiteEditMode === 'y');
	}
}
