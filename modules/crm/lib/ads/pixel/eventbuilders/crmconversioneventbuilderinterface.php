<?php

namespace Bitrix\Crm\Ads\Pixel\EventBuilders;

interface CrmConversionEventBuilderInterface
{
	/**
	 * @return array
	 */
	public function buildEvents() : array ;
}