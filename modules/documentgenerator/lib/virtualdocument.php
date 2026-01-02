<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\Main\Result;

final class VirtualDocument extends Document
{
	public function getProcessedResult(): Result
	{
		return $this->process()->result;
	}

	protected function getCustomFields(): array
	{
		return [];
	}

	protected static function getDocumentClassName(): string
	{
		return Driver::getInstance()->getVirtualDocumentClassName();
	}
}
