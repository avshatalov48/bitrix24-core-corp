<?php

use Bitrix\Rpa\UserField\UserField;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaItemEditorComponent extends Bitrix\Rpa\Components\ItemDetail
{
	public function executeComponent()
	{
		$this->init();
		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['formParams'] = $this->prepareFormParams();
		$this->arResult['formParams']['FORCE_DEFAULT_CONFIG'] = true;
		$this->arResult['jsParams'] = [
			'typeId' => $this->type->getId(),
			'id' => $this->item->getId(),
		];

		$this->getApplication()->setTitle($this->getTitle());

		$this->includeComponentTemplate();
	}

	protected function isFieldVisible(UserField $userField): bool
	{
		return (
			($this->item->getId() > 0 && $userField->isVisible())
			|| ($this->item->getId() <= 0 && $userField->isAvailableOnCreate())
		);
	}
}