<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

class SignDocument extends Presenter
{
	public const DOCUMENT_DATA_KEY = 'DOCUMENT_DATA';
	public const MESSAGE_DATA_KEY = 'MESSAGE_DATA';

	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		$data[static::DOCUMENT_DATA_KEY] = $settings[static::DOCUMENT_DATA_KEY] ?? [];
		$data[static::MESSAGE_DATA_KEY] = $settings[static::MESSAGE_DATA_KEY] ?? [];

		return $data;
	}
}
