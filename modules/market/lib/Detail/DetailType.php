<?php

namespace Bitrix\Market\Detail;

use Bitrix\Market\Detail\Types\AppType;
use Bitrix\Market\Detail\Types\RestMethodInfo;
use Bitrix\Market\Detail\Types\SiteType;

enum DetailType
{
	case App;
	case Site;

	public function getRestMethod(): RestMethodInfo
	{
		return match($this) {
			DetailType::App => new AppType(),
			DetailType::Site => new SiteType()
		};
	}
}
