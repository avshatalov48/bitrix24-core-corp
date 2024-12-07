<?php

namespace Bitrix\Crm\Service\Timeline\Item\Interfaces;

use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Main\Type\DateTime;

interface Deadlinable
{
	public function getDeadline(): DateTime|null;
	public function getChangeDeadlineAction(): RunAjaxAction;
}
