<?php

namespace Bitrix\DocumentGenerator\Registry;

use Bitrix\DocumentGenerator\DataProvider\Filterable;
use Bitrix\DocumentGenerator\Registry;
use Bitrix\Main\Application;

class DataProvider extends Registry
{
	static $result = null;

	/**
	 * @param array $params
	 * @return array|null
	 */
	public static function getList(array $params = [])
	{
		if(static::$result === null)
		{
			$result = parent::getList($params);
			foreach($result as $key => $data)
			{
				$provider = $data['CLASS'];
				if(is_a($provider, Filterable::class, true))
				{
					/** @var Filterable $provider */
					unset($result[$key]);
					$extendedList = $provider::getExtendedList();
					if(!empty($extendedList))
					{
						foreach($extendedList as $item)
						{
							$result[$item['PROVIDER']] = [
								'NAME' => $item['NAME'],
								'CLASS' => $item['PROVIDER'],
								'MODULE' => $data['MODULE'],
								'ORIGINAL' => $data['CLASS'],
								'ORIGINAL_NAME' => $data['NAME'],
							];
						}
					}
				}
			}

			static::$result = $result;
		}

		return static::$result;
	}

	/**
	 * @inheritdoc
	 */
	protected function getBaseClassName()
	{
		return \Bitrix\DocumentGenerator\DataProvider::class;
	}

	/**
	 * @inheritdoc
	 */
	protected function getPath()
	{
		return Application::getDocumentRoot().'/bitrix/modules/documentgenerator/lib/dataprovider/';
	}

	/**
	 * @inheritdoc
	 */
	protected function getEventName()
	{
		return 'onGetDataProviderList';
	}
}