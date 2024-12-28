<?php

namespace Bitrix\BIConnector\Superset\ExternalSource\Source1C;

use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\BIConnector\Superset\ExternalSource;
use Bitrix\Main\Localization\Loc;

final class Source implements ExternalSource\Source
{
	public function __construct(
		protected bool $isConnected
	)
	{}

	public function getCode(): string
	{
		return Type::Source1C->value;
	}

	public function getOnClickConnectButtonScript(): string
	{
		$link = '/bitrix/components/bitrix/biconnector.externalconnection/slider.php';

		return "BX.SidePanel.Instance.open('{$link}', {width: 564, cacheable: false})";
	}

	public function isConnected(): bool
	{
		return $this->isConnected;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_1C_TITLE');
	}

	public function getDescription(): string
	{
		return Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_1C_DESCRIPTION');
	}
}
