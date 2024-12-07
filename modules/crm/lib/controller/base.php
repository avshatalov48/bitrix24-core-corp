<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Field;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

abstract class Base extends Controller
{
	protected function init(): void
	{
		parent::init();

		Container::getInstance()->getLocalization()->loadMessages();
	}

	protected function processBeforeAction(Action $action)
	{
		$this->checkModules();

		return count($this->getErrors()) === 0;
	}

	private function checkModules() : void
	{
		$requiredModules = $this->getRequiredModules();
		if (!$requiredModules)
		{
			return;
		}

		foreach ($requiredModules as $module)
		{
			if (!Loader::includeModule($module))
			{
				$this->addError(
					new Error(
						Loc::getMessage(
							'CRM_CONTROLLER_BASE_MODULE_NO_INSTALLED',
							['#MODULE_ID#' => $module]
						)
					)
				);
			}
		}
	}

	protected function getRequiredModules() : array
	{
		return [];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				\Bitrix\Crm\Model\Dynamic\Type::class,
				'type',
				static function($className, $typeId)
				{
					return Container::getInstance()->getType($typeId);
				}
			),
			new ExactParameter(
				ItemIdentifier::class,
				'itemIdentifier',
				static function($className, $entityTypeId, $entityId)
				{
					return new $className($entityTypeId, $entityId);
				}
			),
		];
	}

	protected function getDefaultPreFilters(): array
	{
		$defaultPreFilter = parent::getDefaultPreFilters();

		if (
			!Container::getInstance()->getUserPermissions()->isAdmin()
			&& Loader::includeModule('intranet')
		)
		{
			$defaultPreFilter[] = new IntranetUser();
		}

		$defaultPreFilter[] = new class extends \Bitrix\Main\Engine\ActionFilter\Base {
			public function onBeforeAction(Event $event) {
				/** @var Controller $controller */
				$controller = $event->getParameter('controller');

				if ($controller && $controller->getScope() === Context::SCOPE_REST)
				{
					Container::getInstance()->getContext()->setScope(Context::SCOPE_REST);
				}
			}
		};

		return $defaultPreFilter;
	}

	protected function convertKeysToUpper(array $data): array
	{
		return Container::getInstance()->getOrmObjectConverter()->convertKeysToUpperCase($data);
	}

	public function convertKeysToCamelCase($data): array
	{
		return Container::getInstance()->getOrmObjectConverter()->convertKeysToCamelCase($data);
	}

	protected function convertValuesToUpper(array $data, int $format = null): array
	{
		$converter = new Converter($format ?? (Converter::TO_UPPER | Converter::VALUES | Converter::TO_SNAKE));

		return $converter->process($data);
	}

	protected function removeDotsFromKeys(array $data): array
	{
		$result = [];

		foreach($data as $name => $value)
		{
			if(is_array($value))
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

		foreach($data as $name => $value)
		{
			if(is_array($value))
			{
				$value = $this->removeDotsFromValues($value);
			}
			$result[$name] = str_replace('.', '', $value);
		}

		return $result;
	}

	public function prepareDateTimeFieldsForFilter(
		array &$filter,
		Field\Collection $fields
	): void
	{
		foreach($filter as $name => &$value)
		{
			if(is_array($value))
			{
				$this->prepareDateTimeFieldsForFilter($value, $fields);
				continue;
			}
			foreach ($fields as $field)
			{
				if($this->isCorrectFieldName($name, $field->getName()))
				{
					$type = $field->getType();
					if ($type === Field::TYPE_DATE)
					{
						$value = \CRestUtil::unConvertDate($value);
						break;
					}
					if ($type === Field::TYPE_DATETIME)
					{
						$value = \CRestUtil::unConvertDateTime($value);
						break;
					}
				}
			}
		}
	}

	protected function isCorrectFieldName(string $filterName, string $field): bool
	{
		return Validator\Filter::isCorrectFilterFieldName($filterName, $field);
	}

	protected function validateOrder(array $order, array $allowedFields): bool
	{
		$result = (new Validator\Order($allowedFields))->validate($order);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	protected function validateFilter(array $filter, array $allowedFields): bool
	{
		$result = (new Validator\Filter($allowedFields))->validate($filter);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	protected function validateReadPermission(ItemIdentifier $itemIdentifier): bool
	{
		$result = (new Validator\Entity\ReadPermission())->validate($itemIdentifier);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	protected function validateUpdatePermission(ItemIdentifier $itemIdentifier): bool
	{
		$result = (new Validator\Entity\UpdatePermission())->validate($itemIdentifier);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	protected function uploadFile(Field $field, $fileContent): ?int
	{
		if (empty($fileContent))
		{
			return null;
		}

		$fileArray = \CRestUtil::saveFile($fileContent);
		if (!$fileArray)
		{
			$this->addError(new Error(Loc::getMessage('CRM_CONTROLLER_BASE_UPLOAD_FILE_ERROR')));
			return null;
		}

		return Container::getInstance()->getFileUploader()->saveFileTemporary($field, $fileArray);
	}

	protected function prepareFieldsInfo(array $fieldsInfo): array
	{
		foreach ($fieldsInfo as &$fieldInfo)
		{
			$fieldInfo['CAPTION'] = $fieldInfo['TITLE'] ?? null;
		}
		unset($fieldInfo);

		$ormObjectConverter = Container::getInstance()->getOrmObjectConverter();
		$fieldsInfo = \CCrmRestHelper::prepareFieldInfos($fieldsInfo);
		$convertedFieldsInfo = [];
		foreach ($fieldsInfo as $fieldName => $info)
		{
			$convertedFieldName = $ormObjectConverter->convertFieldNameFromUpperCaseToCamelCase($fieldName);
			$info['upperName'] = $fieldName;
			$convertedFieldsInfo[$convertedFieldName] = $info;
		}

		return $convertedFieldsInfo;
	}

	protected function prepareDatetime(string $datetime): ?DateTime
	{
		if ($this->getScope() === self::SCOPE_REST)
		{
			$datetime = \CRestUtil::unConvertDateTime($datetime, true);
			if (!$datetime)
			{
				$this->addError(new Error(Loc::getMessage('CRM_CONTROLLER_BASE_WRONG_DATE_FORMAT'), 'WRONG_DATETIME_FORMAT'));

				return null;
			}
		}

		try
		{
			return DateTime::createFromUserTime($datetime);
		}
		catch(\Bitrix\Main\ObjectException $e)
		{
			$this->addError(new Error(Loc::getMessage('CRM_CONTROLLER_BASE_WRONG_DATE_FORMAT'), 'WRONG_DATETIME_FORMAT'));

			return null;
		}
	}

	final protected function isAjax(): bool
	{
		return $this->getScope() === self::SCOPE_AJAX;
	}

	final protected function isRest(): bool
	{
		return $this->getScope() === self::SCOPE_REST;
	}

	final protected function isCli(): bool
	{
		return $this->getScope() === self::SCOPE_CLI;
	}

	final protected function setAccessDenied(): void
	{
		\Bitrix\Main\Context::getCurrent()->getResponse()->setStatus(403);

		$this->addError(
			new Error(
				'Access denied',
				ErrorCode::ACCESS_DENIED
			)
		);
	}
}
