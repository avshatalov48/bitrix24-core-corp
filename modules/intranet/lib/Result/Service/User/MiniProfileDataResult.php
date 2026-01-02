<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Result\Service\User;

use Bitrix\Intranet\Dto\User\MiniProfile\UserMiniProfileDto;
use Bitrix\Intranet\Result\PropertyResult;

class MiniProfileDataResult extends PropertyResult
{
	public function __construct(
		public UserMiniProfileDto $userMiniProfileDto,
	)
	{
		parent::__construct();
	}
}
