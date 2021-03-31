<?php

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Engine\CheckNumeratorType;
use Bitrix\DocumentGenerator\Engine\CheckPermissions;
use Bitrix\DocumentGenerator\UserPermissions;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Numerator\Model\NumeratorTable;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Json;

class Numerator extends Base
{
	protected $restCreatedOptionName = 'restNumeratorsIds';

	/**
	 * @return array
	 */
	public function getDefaultPreFilters()
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new CheckPermissions(UserPermissions::ENTITY_TEMPLATES);
		$filters[] = new CheckNumeratorType();

		return $filters;
	}

	/**
	 * @param \Bitrix\Main\Numerator\Numerator $numerator
	 * @return array
	 */
	public function getAction(\Bitrix\Main\Numerator\Numerator $numerator)
	{
		return ['numerator' => $this->prepareNumeratorConfig($numerator->getConfig())];
	}

	/**
	 * @param PageNavigation|null $pageNavigation
	 * @return Page
	 */
	public function listAction(PageNavigation $pageNavigation = null)
	{
		$numerators = NumeratorTable::getList([
			'select' => ['ID', 'NAME', 'TEMPLATE', 'SETTINGS'],
			'filter' => ['=TYPE' => Driver::NUMERATOR_TYPE],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
		])->fetchAll();
		foreach($numerators as &$numerator)
		{
			$numerator = $this->convertKeysToCamelCase($numerator);
			$numerator['settings'] = Json::decode($numerator['settings']);
		}
		return new Page('numerators', $numerators, function() use ($numerators)
		{
			return count($numerators);
		});
	}

	/**
	 * @param array $fields
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function addAction(array $fields, \CRestServer $restServer = null)
	{
		$emptyFields = $this->checkArrayRequiredParams($fields, ['name', 'template']);
		if(!isset($fields['settings']) || !is_array($fields['settings']))
		{
			$fields['settings'] = [];
		}
		if(!empty($emptyFields))
		{
			$this->errorCollection[] = new Error('Empty required fields: '.implode(', ', $emptyFields));
			return null;
		}
		$numerator = \Bitrix\Main\Numerator\Numerator::create();
		$numerator->setConfig(array_merge($fields['settings'], [
			$numerator::getType() => [
				'name'     => $fields['name'],
				'template' => $fields['template'],
				'type'     => Driver::NUMERATOR_TYPE,
			],
		]));
		$saveResult = $numerator->save();
		if($saveResult->isSuccess())
		{
			if($restServer)
			{
				$restNumeratorIds = $this->getRestNumeratorIds();
				$restNumeratorIds[] = $saveResult->getId();
				$this->setRestNumeratorIds($restNumeratorIds);
			}
			return $this->getAction($numerator);
		}
		else
		{
			$this->errorCollection = $saveResult->getErrorCollection();
			return null;
		}
	}

	/**
	 * @param \Bitrix\Main\Numerator\Numerator $numerator
	 * @param array $fields
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function updateAction(\Bitrix\Main\Numerator\Numerator $numerator, array $fields, \CRestServer $restServer = null)
	{
		$numeratorId = $this->getNumeratorId($numerator);
		if($restServer && !$this->checkAccess($numeratorId))
		{
			$this->errorCollection[] = new Error('Access denied', static::ERROR_ACCESS_DENIED);
			return null;
		}
		$typeConfig = ['type' => Driver::NUMERATOR_TYPE];
		if(isset($fields['name']))
		{
			$typeConfig['name'] = $fields['name'];
		}
		if(isset($fields['template']))
		{
			$typeConfig['template'] = $fields['template'];
		}
		if(!isset($fields['settings']) || !is_array($fields['settings']))
		{
			$fields['settings'] = [];
		}
		$config = array_merge($numerator->getConfig(), [
			$numerator::getType() => $typeConfig,
		], $fields['settings']);
		$numerator->setConfig($config);
		$saveResult = $numerator->save();
		if($saveResult->isSuccess())
		{
			return $this->prepareNumeratorConfig($numerator->getConfig());
		}
		else
		{
			$this->errorCollection = $saveResult->getErrorCollection();
			return null;
		}
	}

	/**
	 * @param \Bitrix\Main\Numerator\Numerator $numerator
	 * @param \CRestServer|null $restServer
	 * @return null
	 * @throws \Exception
	 */
	public function deleteAction(\Bitrix\Main\Numerator\Numerator $numerator, \CRestServer $restServer = null)
	{
		$numeratorId = $this->getNumeratorId($numerator);
		if($restServer && !$this->checkAccess($numeratorId))
		{
			$this->errorCollection[] = new Error('Access denied', static::ERROR_ACCESS_DENIED);
			return null;
		}
		$deleteResult = $numerator::delete($numeratorId);
		if(!$deleteResult->isSuccess())
		{
			$this->errorCollection = $deleteResult->getErrorCollection();
		}
		else
		{
			if($restServer)
			{
				$restNumeratorIds = $this->getRestNumeratorIds();
				unset($restNumeratorIds[array_search($numeratorId, $restNumeratorIds)]);
				$this->setRestNumeratorIds($restNumeratorIds);
			}
		}

		return null;
	}

	protected function getNumeratorId(\Bitrix\Main\Numerator\Numerator $numerator)
	{
		return $numerator->getConfig()[$numerator::getType()]['id'];
	}

	/**
	 * @return array
	 */
	protected function getRestNumeratorIds()
	{
		return unserialize(
			Option::get(
				Driver::MODULE_ID,
				$this->restCreatedOptionName,
				serialize([])
			),
			[
				'allowed_classes' => false,
			]
		);
	}

	/**
	 * @param $numeratorId
	 * @return bool
	 */
	protected function checkAccess($numeratorId)
	{
		return in_array($numeratorId, $this->getRestNumeratorIds());
	}

	/**
	 * @param array $ids
	 */
	protected function setRestNumeratorIds(array $ids)
	{
		Option::set(Driver::MODULE_ID, $this->restCreatedOptionName, serialize($ids));
	}

	/**
	 * @param array $config
	 * @return array
	 */
	protected function prepareNumeratorConfig(array $config)
	{
		$result = $config[\Bitrix\Main\Numerator\Numerator::getType()];
		unset($result['type']);
		unset($config[\Bitrix\Main\Numerator\Numerator::getType()]);
		return array_merge($result, ['settings' => $config]);
	}
}