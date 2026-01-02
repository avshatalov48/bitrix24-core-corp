<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\HumanResources\Item\Collection\HcmLink\EmployeeCollection;
use Bitrix\HumanResources\Type\HcmLink\EmployeeDataType;

/**
 * Class for formatting Person data
 */
class PersonUtil
{
	private const FIRST_NAME_TEMPLATE = '#FIRST_NAME#';
	private const LAST_NAME_TEMPLATE = '#LAST_NAME#';
	private const PATRONYMIC_NAME_TEMPLATE = '#PATRONYMIC_NAME#';

	/**
	 * Format additional information about Person
	 * must show positions and employee numbers across all employees
	 * and only one instance of snils
	 *
	 * @param EmployeeCollection $employees
	 * @return string
	 */
	public static function formatPersonSubtitle(EmployeeCollection $employees): string
	{
		$positions = [];
		foreach ($employees as $employee)
		{
			$data = $employee->data;

			// employee number is different in each person position, so we concatenate them close to each other
			$positionWithNumber = [];

			if (!empty($data[EmployeeDataType::POSITION->value]))
			{
				$positionWithNumber[] = $data[EmployeeDataType::POSITION->value];
			}

			if (!empty($data[EmployeeDataType::EMPLOYEE_NUMBER->value]))
			{
				$positionWithNumber[] = (string)$data[EmployeeDataType::EMPLOYEE_NUMBER->value];
			}

			$positions[] = implode(', ', $positionWithNumber);
		}
		$snils = self::getSnils($employees);

		return implode(', ', array_filter([...$positions, $snils]));
	}

	public static function getSnils(EmployeeCollection $employees): ?string
	{
		$snils = null;
		foreach ($employees as $employee)
		{
			$snils ??= $employee->data[EmployeeDataType::SNILS->value] ?? null;
		}
		return $snils;
	}

	/**
	 * Sanitize template with white list
	 *
	 * @param string $template
	 * @return string
	 */
	public static function sanitizeNameTemplate(string $template): string
	{
		$patternBody = implode('|', [
			self::FIRST_NAME_TEMPLATE,
			self::LAST_NAME_TEMPLATE,
			self::PATRONYMIC_NAME_TEMPLATE,
			'\s',
			',',
		]);

		preg_match_all(
			'/' . $patternBody . '/',
			urldecode($template),
			$matches,
		);

		return implode('', $matches[0]);
	}

	/**
	 * Format Person full name by Employees collection
	 *
	 * @param EmployeeCollection $employees - person Employees
	 * @param string|null $template - formatting template. If null, returns default formatting.
	 * 		IMPORTANT: $template must be already sanitized before ending up in this function
	 * @return string
	 */
	public static function formatFullName(EmployeeCollection $employees, ?string $template = null): string
	{
		$firstName = null;
		$lastName = null;
		$patronymicName = null;
		foreach ($employees as $employee)
		{
			$firstName ??= $employee->data[EmployeeDataType::FIRST_NAME->value] ?? null;
			$lastName ??= $employee->data[EmployeeDataType::LAST_NAME->value] ?? null;
			$patronymicName ??= $employee->data[EmployeeDataType::PATRONYMIC_NAME->value] ?? null;
		}

		if ($template)
		{
			return str_replace(
				[self::FIRST_NAME_TEMPLATE, self::LAST_NAME_TEMPLATE, self::PATRONYMIC_NAME_TEMPLATE],
				[$firstName, $lastName, $patronymicName],
				$template
			);
		}

		return implode(' ', [$firstName, $lastName, $patronymicName]);
	}
}
