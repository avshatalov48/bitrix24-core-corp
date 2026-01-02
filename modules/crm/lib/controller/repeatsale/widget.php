<?php

namespace Bitrix\Crm\Controller\RepeatSale;

use Bitrix\Crm\RepeatSale\Widget\Confetti;
use Bitrix\Crm\RepeatSale\Widget\StartBanner;

final class Widget extends Base
{
	public function incrementShowedConfettiCountAction(): void
	{
		(new Confetti())->incrementShowedCount();
	}

	public function incrementShowedFlowStartCountAction(): void
	{
		(new StartBanner())->incrementShowedCount();
	}
}
