<?php

namespace Bitrix\Crm\Service\Timeline\Item\Interfaces;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;

interface HasPaymentDetailsContentBlock
{
	public function getPaymentDetailsContentBlock(): LineOfTextBlocks;
}
