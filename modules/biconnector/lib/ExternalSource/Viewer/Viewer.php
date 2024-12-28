<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer;

class Viewer
{
	protected Provider\ProviderDataDto $providerData;

	public function __construct(Provider\Provider $provider)
	{
		$this->providerData = $provider->getData();
	}

	public function getNames(): array
	{
		return $this->providerData->names;
	}

	public function getExternalCodes(): array
	{
		return $this->providerData->externalCodes;
	}

	public function getTypes(): array
	{
		return $this->providerData->types;
	}

	public function getData(): array
	{
		$result = [];

		$rowCollection = $this->providerData->data;
		/** @var Provider\Row $row */
		foreach ($rowCollection as $row)
		{
			$result[] = $row->getData();
		}

		return $result;
	}
}
