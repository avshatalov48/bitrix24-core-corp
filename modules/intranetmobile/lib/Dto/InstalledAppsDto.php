<?php

namespace Bitrix\IntranetMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

class InstalledAppsDto extends Dto
{
	public function __construct(
		public readonly ?bool $windows = null,
		public readonly ?bool $linux = null,
		public readonly ?bool $mac = null,
		public readonly ?bool $ios = null,
		public readonly ?bool $android = null,
	)
	{
		parent::__construct();
	}
}