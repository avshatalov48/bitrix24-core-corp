<?php

namespace Bitrix\Crm\Service\Timeline\Item\Interfaces;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;

interface HasPaymentMethodContentBlock
{
	public function getPaymentMethodContentBlock(): LineOfTextBlocks;
}
