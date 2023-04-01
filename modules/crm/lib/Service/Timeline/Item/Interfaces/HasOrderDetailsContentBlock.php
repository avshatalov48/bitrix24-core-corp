<?php

namespace Bitrix\Crm\Service\Timeline\Item\Interfaces;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;

interface HasOrderDetailsContentBlock
{
	public function getOrderDetailsContentBlock(array $options = []): LineOfTextBlocks;
}
