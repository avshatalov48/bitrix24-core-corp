<?php

namespace Bitrix\Sign\Result\Service\Integration\HumanResources;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Result\SuccessResult;

class HcmLinkSignedFileInfo extends SuccessResult
{
	public function __construct(
		public string $company,
		public string $employee,
		public DateTime $documentDate,
		public string $documentName,
		public string $fileName,
	)
	{
		parent::__construct();
	}
}