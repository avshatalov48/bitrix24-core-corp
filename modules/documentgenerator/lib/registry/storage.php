<?php

namespace Bitrix\DocumentGenerator\Registry;

use Bitrix\DocumentGenerator\Registry;
use Bitrix\Main\Application;

class Storage extends Registry
{
	/**
	 * @inheritdoc
	 */
	protected function getBaseClassName()
	{
		return \Bitrix\DocumentGenerator\Storage::class;
	}

	/**
	 * @inheritdoc
	 */
	protected function getPath()
	{
		return Application::getDocumentRoot().'/bitrix/modules/documentgenerator/lib/storage/';
	}

	/**
	 * @inheritdoc
	 */
	protected function getEventName()
	{
		return 'onGetStorageTypeList';
	}
}