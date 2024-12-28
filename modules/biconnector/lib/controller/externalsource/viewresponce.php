<?php

namespace Bitrix\BIConnector\Controller\ExternalSource;

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Engine\Response;

class ViewResponce extends Response\AjaxJson
{
	public function __construct($status = self::STATUS_SUCCESS, ErrorCollection $errorCollection = null)
	{
		parent::__construct(status: $status, errorCollection: $errorCollection);

		$this->jsonEncodingOptions = Json::DEFAULT_OPTIONS;
	}
}
