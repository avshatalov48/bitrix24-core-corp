<?php

namespace Bitrix\Sign\Contract\Grid\MyDocuments;

interface ActionCellTemplate
{
	public function render(): ?string;
}