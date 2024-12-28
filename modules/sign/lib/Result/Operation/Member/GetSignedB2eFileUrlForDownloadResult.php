<?php

namespace Bitrix\Sign\Result\Operation\Member;

use Bitrix\Sign\Result\SuccessResult;

class GetSignedB2eFileUrlForDownloadResult extends SuccessResult
{
	public function __construct(
		public readonly string $url,
		public readonly string $ext,
	)
	{
		parent::__construct();
	}
}