<?php

namespace Bitrix\HumanResources\Config;

use Bitrix\Main;

class Storage
{
	private static ?self $instance = null;

	public static function instance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	private const MODULE_NAME = 'humanresources';
	private const STRUCT_CONVERTED_OPTION_NAME = 'company_structure_converted';
	private const STRUCT_EMPLOYEES_OPTION_NAME = 'company_employees_transferred';
	private const STRUCT_DISABLE_INTRANET_UTILS = 'disable_intranet_utilities';
	private const STRUCT_PUBLIC_IS_AVAILABLE_OPTION_NAME = 'public_structure_is_available';

	public function isCompanyStructureConverted(bool $checkIsEmployeesTransferred = true): bool
	{
		$result = Main\Config\Option::get(self::MODULE_NAME, self::STRUCT_CONVERTED_OPTION_NAME, false);

		if ($checkIsEmployeesTransferred)
		{
			$transferred = $this->isEmployeesTransferred();
			if ($result && !$transferred)
			{
				$agents = \CAgent::GetList(
					arFilter: ['MODULE_ID' => self::MODULE_NAME],
				);

				while ($agent = $agents->Fetch())
				{
					if (mb_strpos($agent['NAME'], 'moveEmployees') !== false)
					{
						return false;
					}
				}
				$this->setEmployeeTransferred(true);
				$transferred = true;
			}

			return $result && $transferred;
		}

		return $result;
	}

	public function isEmployeesTransferred(): bool
	{
		return Main\Config\Option::get(self::MODULE_NAME, self::STRUCT_EMPLOYEES_OPTION_NAME, false);
	}

	public function setCompanyStructureConverted(bool $value): self
	{
		Main\Config\Option::set(self::MODULE_NAME, self::STRUCT_CONVERTED_OPTION_NAME, $value);

		return $this;
	}

	public function setEmployeeTransferred(bool $value): self
	{
		Main\Config\Option::set(self::MODULE_NAME, self::STRUCT_EMPLOYEES_OPTION_NAME, $value);

		return $this;
	}

	public function setDisableIntranetUtils(bool $value): self
	{
		Main\Config\Option::set(self::MODULE_NAME, self::STRUCT_DISABLE_INTRANET_UTILS, $value);

		return $this;
	}

	public function isIntranetUtilsDisabled(): bool
	{
		return Main\Config\Option::get(self::MODULE_NAME, self::STRUCT_DISABLE_INTRANET_UTILS, false);
	}

	public function isPublicStructureAvailable(): bool
	{
		return Main\Config\Option::get(self::MODULE_NAME, self::STRUCT_PUBLIC_IS_AVAILABLE_OPTION_NAME, false);
	}
}