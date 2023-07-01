<?php

namespace Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider;

interface ActionProvider
{
	public function provide(): ?array;
}
