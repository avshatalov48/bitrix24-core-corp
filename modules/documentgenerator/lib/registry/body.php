<?php

namespace Bitrix\DocumentGenerator\Registry;

use Bitrix\DocumentGenerator\Registry;
use Bitrix\Main\Application;

class Body extends Registry
{
	/**
	 * @inheritdoc
	 */
	protected function getBaseClassName()
	{
		return \Bitrix\DocumentGenerator\Body::class;
	}

	/**
	 * @inheritdoc
	 */
	protected function getPath()
	{
		return Application::getDocumentRoot().'/bitrix/modules/documentgenerator/lib/body/';
	}

	/**
	 * @inheritdoc
	 */
	protected function getEventName()
	{
		return 'onGetBodyTypeList';
	}
}