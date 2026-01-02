<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use CSite;
use CUser;

class NameService
{
	public function format(array $user): string
	{
		return CUser::formatName(
			CSite::GetNameFormat(),
			$user,
			true,
			false,
		);
	}
}
