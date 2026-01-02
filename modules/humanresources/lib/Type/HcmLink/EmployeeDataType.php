<?php

namespace Bitrix\HumanResources\Type\HcmLink;

/**
 * Enum for values of json in DATA field at b_hr_hcmlink_employee table
 */
enum EmployeeDataType: string
{
	case LAST_NAME = 'lastName';
	case FIRST_NAME = 'firstName';
	case PATRONYMIC_NAME = 'patronymicName';
	case SNILS = 'snils';
	case POSITION = 'position';
	case BIRTH_DATE = 'birthDate';
	case EMPLOYEE_NUMBER = 'employeeNumber';
	case GENDER = 'gender';
}
