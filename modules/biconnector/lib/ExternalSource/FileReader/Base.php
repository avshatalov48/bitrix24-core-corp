<?php

namespace Bitrix\BIConnector\ExternalSource\FileReader;

interface Base
{
	public function getHeaders(): ?array;

	public function readFirstNRows(int $n): array;

	public function readAllRows(): array;

	public function readAllRowsByOne(): \Generator;
}
