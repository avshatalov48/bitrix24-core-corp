<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer;

use Bitrix\BIConnector;

final class ViewerBuilder
{
	private BIConnector\ExternalSource\Type $type;
	private ?array $file = null;
	private ?int $sourceId = null;
	private ?array $externalTableData = null;
	private ?array $settings = null;

	public function setType(BIConnector\ExternalSource\Type $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function setFile(array $file): self
	{
		$this->file = $file;

		return $this;
	}

	public function setSourceId(int $sourceId): self
	{
		$this->sourceId = $sourceId;

		return $this;
	}

	public function setExternalTableData(array $externalTableData): self
	{
		$this->externalTableData = $externalTableData;

		return $this;
	}

	public function setSettings(array $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	public function build(): Viewer
	{
		$provider = $this->getProvider();

		return new Viewer($provider);
	}

	private function getProvider(): Provider\Provider
	{
		$provider = new Provider\NullProvider();

		if ($this->type === BIConnector\ExternalSource\Type::Csv && $this->file)
		{
			$provider = new Provider\Csv();
			$provider->setFile($this->file);

			return $provider;
		}

		if ($this->type === BIConnector\ExternalSource\Type::Source1C)
		{
			$provider = new Provider\Source1C();
			$settings = $this->settings;
			$settings['dataset'] = $this->externalTableData;
			$provider
				->setSourceId($this->sourceId)
				->setSettings($settings)
			;
		}

		return $provider;
	}
}
