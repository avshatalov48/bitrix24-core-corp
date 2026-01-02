<?php

namespace Bitrix\BIConnector\Superset\ExternalSource\Rest;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnector;
use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\BIConnector\Superset\ExternalSource;

final class Source implements ExternalSource\Source
{
	public function __construct(
		protected ExternalSourceRestConnector $connector,
		protected bool $isConnected
	)
	{}

	public function getCode(): string
	{
		return $this->connector->getCode();
	}

	public function getOnClickConnectButtonScript(): string
	{
		$typeRest = Type::Rest->value;
		$link = "/bitrix/components/bitrix/biconnector.externalconnection/slider.php?connectorCode={$this->getCode()}&connectorType={$typeRest}";

		return "BX.SidePanel.Instance.open('{$link}', {width: 564, cacheable: false})";
	}

	public function isConnected(): bool
	{
		return $this->isConnected;
	}

	public function getTitle(): string
	{
		return $this->connector->getTitle() ?? '';
	}

	public function getDescription(): string
	{
		return $this->connector->getDescription() ?? '';
	}

	public function getLogo(): ?string
	{
		return $this->connector->getLogo();
	}
}
