<?php

namespace Bitrix\BIConnector\DataSource;

use Bitrix\BIConnector\DataSource\Field\DateField;
use Bitrix\BIConnector\DataSource\Field\DateTimeField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\BIConnector\MemoryCache;
use Bitrix\BIConnector\PrettyPrinter;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class UserFieldDataset extends Dataset
{
	protected array $userFields = [];

	abstract protected function getRawUserFields(): array;

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		$this->userFields = $this->getRawUserFields();

		if (empty($this->userFields))
		{
			$result->addError(new Error('`User fields` not found'));
		}

		return $result;
	}

	protected function getFields(): array
	{
		$fields = [];

		foreach ($this->userFields as $userField)
		{
			$dbType = '';
			if ($userField['USER_TYPE'] && is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype']))
			{
				$dbType = call_user_func_array([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype'], [$userField]);
			}

			if ($dbType === 'date' && $userField['MULTIPLE'] === 'N')
			{
				$field = (new DateField($userField['FIELD_NAME']))
					->setSystem(false)
				;
			}
			elseif ($dbType === 'datetime' && $userField['MULTIPLE'] === 'N')
			{
				$field = (new DateTimeField($userField['FIELD_NAME']))
					->setSystem(false)
				;
			}
			else
			{
				$field =
					(new StringField($userField['FIELD_NAME']))
						->setSystem(false)
						->setCallback(
							function($value, $dateFormats) use($userField, $dbType)
							{
								global $USER_FIELD_MANAGER;

								if ($dbType === 'date')
								{
									return PrettyPrinter::formatUserFieldAsDate($userField, $value, $dateFormats['date_format_php']);
								}

								if ($dbType === 'datetime')
								{
									return PrettyPrinter::formatUserFieldAsDate($userField, $value, $dateFormats['datetime_format_php']);
								}

								$cacheKey = serialize($value);
								$cachedResult = MemoryCache::get($userField['ID'], $cacheKey);
								if (isset($cachedResult))
								{
									return $cachedResult;
								}

								if ($userField['MULTIPLE'] == 'Y')
								{
									$result = $USER_FIELD_MANAGER->onAfterFetch(
										$userField,
										unserialize($value, ['allowed_classes' => PrettyPrinter::$allowedUnserializeClassesList])
									);
								}
								else
								{
									$result = [$USER_FIELD_MANAGER->onAfterFetch($userField, $value)];
								}

								$localUF = $userField;
								$localUF['VALUE'] = $result;

								$returnResult = $USER_FIELD_MANAGER->getPublicText($localUF);
								MemoryCache::set($userField['ID'], $cacheKey, $returnResult);

								return $returnResult;
							}
						)
				;
			}

			if (!empty($userField['EDIT_FORM_LABEL']))
			{
				$field->setDescription($userField['EDIT_FORM_LABEL']);
			}
			elseif (!empty($userField['USER_TYPE']['DESCRIPTION']))
			{
				$field->setDescription($userField['USER_TYPE']['DESCRIPTION']);
			}

			$fields[] = $field;
		}

		return $fields;
	}
}
