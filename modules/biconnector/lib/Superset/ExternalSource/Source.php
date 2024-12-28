<?php

namespace Bitrix\BIConnector\Superset\ExternalSource;

interface Source
{
	public function getCode(): string;
	public function getOnClickConnectButtonScript(): string;
	public function isConnected(): bool;

	public function getTitle(): string;
	public function getDescription(): string;
}
