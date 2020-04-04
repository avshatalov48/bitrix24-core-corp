<?php

/** @noinspection PhpUnusedParameterInspection */

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Engine\CheckPermissions;
use Bitrix\DocumentGenerator\Model\RegionPhraseTable;
use Bitrix\DocumentGenerator\Model\RegionTable;
use Bitrix\DocumentGenerator\UserPermissions;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Region extends Base
{
	/**
	 * @return array
	 */
	public function getDefaultPreFilters()
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new CheckPermissions(UserPermissions::ENTITY_TEMPLATES);

		return $filters;
	}

	/**
	 * @param string|int $id
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getAction($id)
	{
		Loc::loadLanguageFile(__FILE__);
		if(is_object($id) || is_array($id))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('DOCGEN_CONTROLLER_REGION_NOT_FOUND_ERROR'))]);
			return null;
		}
		if(is_numeric($id))
		{
			$region = RegionTable::getById($id)->fetch();
		}
		else
		{
			$region = Driver::getInstance()->getDefaultRegions()[$id];
		}

		if(!$region)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('DOCGEN_CONTROLLER_REGION_NOT_FOUND_ERROR'))]);
			return null;
		}

		$region = $this->convertKeysToCamelCase($region);
		$region['phrases'] = DataProviderManager::getInstance()->getRegionPhrases($id);

		return [
			'region' => $region,
		];
	}

	/**
	 * @param array $fields
	 * @return array|null
	 * @throws \Exception
	 */
	public function addAction(array $fields)
	{
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		$regionData = $converter->process($fields);

		$addResult = RegionTable::add($regionData);
		if($addResult->isSuccess())
		{
			$regionId = $addResult->getId();
			$phrases = $fields['phrases'];
			if(is_array($phrases))
			{
				foreach($phrases as $code => $text)
				{
					$phraseResult = RegionPhraseTable::add([
						'REGION_ID' => $regionId,
						'CODE' => $code,
						'PHRASE' => $text,
					]);
					if(!$phraseResult->isSuccess())
					{
						$this->errorCollection->add($phraseResult->getErrors());
					}
				}
			}
			return $this->getAction($regionId);
		}
		else
		{
			$this->errorCollection->add($addResult->getErrors());
			return null;
		}
	}

	/**
	 * @return array
	 */
	public function listAction()
	{
		return [
			'regions' => $this->convertKeysToCamelCase(Driver::getInstance()->getRegionsList())
		];
	}

	/**
	 * @param $id
	 */
	public function deleteAction($id)
	{
		$deleteResult = RegionTable::delete($id);
		if(!$deleteResult->isSuccess())
		{
			$this->errorCollection = $deleteResult->getErrorCollection();
		}
	}

	/**
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 * @throws \Exception
	 */
	public function updateAction($id, array $fields)
	{
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		$regionData = $converter->process($fields);

		$result = RegionTable::update($id, $regionData);
		if($result->isSuccess())
		{
			$phrases = $fields['phrases'];
			if(is_array($phrases))
			{
				$phraseIds = [];
				$phraseList = RegionPhraseTable::getList(['filter' => ['=REGION_ID' => $id]]);
				while($phrase = $phraseList->fetch())
				{
					$phraseIds[$phrase['CODE']] = $phrase['ID'];
				}
				foreach($phrases as $code => $text)
				{
					$phraseData = [
						'REGION_ID' => $id,
						'CODE' => $code,
						'PHRASE' => $text,
					];
					if(isset($phraseIds[$code]))
					{
						$phraseResult = RegionPhraseTable::update($phraseIds[$code], $phraseData);
					}
					else
					{
						$phraseResult = RegionPhraseTable::add($phraseData);
					}
					if(!$phraseResult->isSuccess())
					{
						$this->errorCollection->add($phraseResult->getErrors());
					}
				}
			}
			return $this->getAction($id);
		}
		else
		{
			$this->errorCollection->add($result->getErrors());
			return null;
		}
	}
}