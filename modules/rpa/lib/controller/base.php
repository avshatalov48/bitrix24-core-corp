<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Intranet\ActionFilter;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\PermissionTable;
use Bitrix\Rpa\Model\StageTable;
use Bitrix\Rpa\Model\TimelineTable;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\Permission;

abstract class Base extends Controller
{
	protected function init()
	{
		parent::init();

		\Bitrix\Rpa\Components\Base::loadBaseLanguageMessages();
	}

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();

		if (
			!Driver::getInstance()->getUserPermissions()->isAdmin()
			&& Loader::includeModule('intranet')
		)
		{
			$preFilters[] = new ActionFilter\IntranetUser();
		}

		return $preFilters;
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				\Bitrix\Rpa\Model\Type::class,
				'type',
				static function ($className, $typeId) {
					return TypeTable::getById($typeId)->fetchObject();
				}
			),
			new ExactParameter(
				\Bitrix\Rpa\Model\Stage::class,
				'stage',
				static function($className, $id)
				{
					$stageData = StageTable::getList([
						'select' => ['TYPE_ID'],
						'filter' => [
							'=ID' => $id,
						]
					])->fetch();
					if ($stageData && $stageData['TYPE_ID'] > 0)
					{
						$type = Driver::getInstance()->getType($stageData['TYPE_ID']);
						if ($type)
						{
							return $type->getStage($id);
						}
					}

					return null;
				}
			),
			new ExactParameter(
				\Bitrix\Rpa\Model\Timeline::class,
				'timeline',
				static function ($className, $id) {
					return TimelineTable::getById($id)->fetchObject();
				}
			),
		];
	}

	protected function removeDotsFromKeys(array $data): array
	{
		$result = [];

		foreach ($data as $name => $value)
		{
			if (is_array($value))
			{
				$value = $this->removeDotsFromKeys($value);
			}
			$result[str_replace('.', '', $name)] = $value;
		}

		return $result;
	}

	protected function removeDotsFromValues(array $data): array
	{
		$result = [];

		foreach ($data as $name => $value)
		{
			if (is_array($value))
			{
				$value = $this->removeDotsFromValues($value);
			}
			$result[$name] = $value;
		}

		return $result;
	}

	protected function getEmptyRequiredParameterNames(array $parameters, array $requiredParamsNames): array
	{
		$emptyParams = [];

		foreach ($requiredParamsNames as $param)
		{
			if (!isset($parameters[$param]) || empty($parameters[$param]))
			{
				$emptyParams[] = $param;
			}
		}

		return $emptyParams;
	}

	/**
	 * @param array $filter
	 * @param array $dateTimeFields
	 */
	protected function prepareDateTimeFieldsForFilter(array &$filter, array $dateTimeFields): void
	{
		foreach ($filter as $name => &$value)
		{
			if (is_array($value))
			{
				$this->prepareDateTimeFieldsForFilter($value, $dateTimeFields);
				continue;
			}
			foreach ($dateTimeFields as $field)
			{
				if ($this->isCorrectFieldName($name, $field))
				{
					$value = \CRestUtil::unConvertDateTime($value);
					break;
				}
			}
		}
	}

	protected function prepareDateTimeValue(DateTime $dateTime): string
	{
		if ($this->getScope() === Scope::REST)
		{
			return \CRestUtil::ConvertDateTime($dateTime);
		}

		return $dateTime->format(DateTime::getFormat());
	}

	/**
	 * @param $filterName
	 * @param $field
	 * @return bool
	 */
	protected function isCorrectFieldName($filterName, $field): bool
	{
		static $prefixes = [
			'' => true,
			'=' => true,
			'%' => true,
			'>' => true,
			'<' => true,
			'@' => true,
			'!=' => true,
			'!%' => true,
			'><' => true,
			'>=' => true,
			'<=' => true,
			'=%' => true,
			'%=' => true,
			'!><' => true,
			'!=%' => true,
			'!%=' => true,
		];

		return isset($prefixes[str_replace($field, '', $filterName)]);
	}

	protected function processPermissions(Permission\Containable $model, array $fields): Permission\Result
	{
		$result = new Permission\Result();

		if (isset($fields['PERMISSIONS']))
		{
			if (!is_array($fields['PERMISSIONS']))
			{
				$fields['PERMISSIONS'] = [];
			}
			$permissions = $fields['PERMISSIONS'];
			$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
			foreach ($permissions as $key => $permission)
			{
				$permissions[$key] = $converter->process($permission);
			}
			$processor = new Permission\Processor($model->getPermissions());
			$result = $processor->process($permissions);
		}
		else
		{
			// no need to save
			$result->setSaved();
		}

		return $result;
	}

	protected function savePermissions(Permission\Containable $model, Permission\Result $result): Result
	{
		if ($result->isSaved())
		{
			return $result;
		}
		foreach ($result->getAddPermissions() as $permission)
		{
			$data = $permission;
			$data['ENTITY'] = $model->getPermissionEntity();
			$data['ENTITY_ID'] = $model->getId();
			$addResult = PermissionTable::add($data);
			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());
			}
		}
		foreach ($result->getDeletePermission() as $permission)
		{
			PermissionTable::delete($permission['ID']);
		}
		$result->setSaved();

		return $result;
	}

	protected function uploadFile($fileContent): ?int
	{
		if (empty($fileContent))
		{
			return null;
		}
		$fileArray = \CRestUtil::saveFile($fileContent);
		if (!$fileArray)
		{
			$this->addError(new Error(Loc::getMessage('RPA_CONTROLLER_COULD_NOT_UPLOAD_FILE_ERROR')));
			return null;
		}
		$fileArray['MODULE_ID'] = Driver::MODULE_ID;
		$filePath = Driver::MODULE_ID;
		$fileId = \CFile::SaveFile($fileArray, $filePath);
		if ($fileId > 0)
		{
			return (int)$fileId;
		}

		return null;
	}
}
