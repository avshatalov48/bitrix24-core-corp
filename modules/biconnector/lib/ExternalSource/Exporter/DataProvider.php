<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

interface DataProvider
{
	public function getTotalSize(): int;

	public function fetchChunk(int $chunkSize, int $chunkOffset): iterable;
}
