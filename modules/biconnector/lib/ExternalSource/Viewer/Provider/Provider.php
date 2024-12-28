<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

interface Provider
{
	public function getData(): ProviderDataDto;
}
