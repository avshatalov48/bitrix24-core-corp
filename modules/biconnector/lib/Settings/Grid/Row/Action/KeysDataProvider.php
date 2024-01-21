<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Action;

use Bitrix\BiConnector\Settings\Grid\KeysSettings;
use Bitrix\BiConnector\Settings\Grid\Row\Action\Keys\DeleteAction;
use Bitrix\BiConnector\Settings\Grid\Row\Action\Settings\EditAction;
use Bitrix\Main\Grid\Row\Action\DataProvider;

/**
 * @method KeysSettings getSettings()
 */
class KeysDataProvider extends DataProvider
{
	public function __construct(?KeysSettings $settings = null)
	{
		parent::__construct($settings);
	}

	public function prepareActions(): array
	{
		$result = [];

		if ($this->getSettings()->isCanWrite())
		{
			$result = [
				new EditAction($this->getSettings()->getEditUrl(), 650),
				new DeleteAction(),
			];
		}

		return $result;
	}
}
