<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Driver;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;

final class Configuration
{
	/** @var string|null */
	protected $server;
	/** @var string|null */
	protected $secretKey;
	/** @var array|null */
	protected $localValues;

	public function __construct()
	{
		$this->loadLocalValues();
	}

	protected function loadLocalValues(): void
	{
		$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/disk-documents.php";
		if (File::isFileExists($path))
		{
			$localValues = require($path);
			if (is_array($localValues))
			{
				$this->localValues = $localValues;
			}
		}
	}

	public function getServer(): ?string
	{
		if ($this->server === null)
		{
			$this->server = $this->getValue('server', 'disk_onlyoffice_server');
		}

		return $this->server;
	}

	public function getSecretKey(): ?string
	{
		if ($this->secretKey === null)
		{
			$this->secretKey = $this->getValue('secret_key', 'disk_onlyoffice_secret_key');
		}

		return $this->secretKey;
	}

	protected function getValue($key, string $optionName): ?string
	{
		$value = $this->getLocalValues($key);
		if ($value === null)
		{
			return Option::get(Driver::INTERNAL_MODULE_ID, $optionName);
		}

		return $value;
	}

	public function getLocalValues($key)
	{
		return $this->localValues[$key] ?? null;
	}
}