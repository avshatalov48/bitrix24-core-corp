<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\Main\IO\File;

abstract class Writer
{
	abstract public function open(): void;

	abstract public function close(): void;

	abstract public function writeLine(array $data): void;

	abstract public function writeLines(iterable $data): void;

	abstract public function getFile(): File;
}
