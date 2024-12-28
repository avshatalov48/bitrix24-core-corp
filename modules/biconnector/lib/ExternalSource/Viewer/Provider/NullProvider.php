<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

class NullProvider implements Provider
{
	public function getData(): ProviderDataDto
	{
		return new ProviderDataDto([], [], [], new RowCollection());
	}
}
