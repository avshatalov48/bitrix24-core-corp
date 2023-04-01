<?php

namespace Bitrix\Crm\Service\Timeline\Item\Interfaces;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;

interface HasCheckDetails
{
	public function getCheckTitleContentBlock(): LineOfTextBlocks;

	public function getCheckDetailsContentBlock(): LineOfTextBlocks;

	public function getOpenCheckAction(): ?Action;

	public function getCheckInFiscalDataOperatorAction(): ?Action;
}
