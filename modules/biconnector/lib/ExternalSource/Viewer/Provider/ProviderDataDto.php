<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

final class ProviderDataDto
{
	public function __construct(
		public readonly array $names,
		public readonly array $externalCodes,
		public readonly array $types,
		public readonly RowCollection $data,
	)
	{
	}
}
