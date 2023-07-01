<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

trait InitializeExternalContextId
{
	private function initializeExternalContextId(): void
	{
		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context_id');

		if ($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
			if ($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
			{
				$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
			}
		}
	}
}
