<?php

namespace Bitrix\Crm\Ads;

/**
 * Class AdsAudienceConfig.
 * @package Bitrix\Crm\Ads
 */
class AdsAudienceConfig
{
	public $accountId;
	public $audienceId;
	public $contactType = null;
	public $type = null;
	public $autoRemoveDayNumber = null;

	public function __construct(\stdClass $config = null)
	{
		if (!$config)
		{
			return;
		}

		if ($config->accountId)
		{
			$this->accountId = $config->accountId;
		}
		if ($config->audienceId)
		{
			$this->audienceId = $config->audienceId;
		}
		if ($config->contactType)
		{
			$this->contactType = $config->contactType;
		}
		if ($config->type)
		{
			$this->type = $config->type;
		}
		if ($config->autoRemoveDayNumber)
		{
			$this->autoRemoveDayNumber = $config->autoRemoveDayNumber;
		}
	}
}