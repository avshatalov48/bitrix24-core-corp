<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmWhatsNewComponent extends \CBitrixComponent
{
	/**
	 * @return $this
	 */
	protected function prepareParams()
	{
		if (empty($this->arParams['SEF_MODE']))
		{
			$this->arParams['SEF_MODE'] = 'N';
		}

		return $this;
	}

	public function executeComponent()
	{
		if ($this->isAjaxRequest())
		{
			return;
		}

		$this->includeComponentTemplate();
	}

	private function isAjaxRequest(): bool
	{
		return $this->request->isAjaxRequest();
	}
}
