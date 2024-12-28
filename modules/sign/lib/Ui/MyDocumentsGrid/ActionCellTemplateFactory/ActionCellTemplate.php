<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

interface ActionCellTemplate
{
	public function get(): ?string;
}