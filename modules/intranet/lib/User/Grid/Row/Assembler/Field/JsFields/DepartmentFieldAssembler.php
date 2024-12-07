<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Grid\Settings;
use CIBlock;

class DepartmentFieldAssembler extends JsExtensionFieldAssembler
{
	private DepartmentCollection $departments;
	private bool $canEdit;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);
		$this->departments = ServiceContainer::getInstance()
			->departmentRepository()
			->getAllTree();

		$iblockId = Option::get('intranet', 'iblock_structure', false);
		$this->canEdit = CIBlock::GetPermission($iblockId) >= 'U';
	}

	protected function getExtensionClassName(): string
	{
		return 'DepartmentField';
	}

	protected function getRenderParams($rawValue): array
	{
		$departmentList = [];

		if (is_array($rawValue['UF_DEPARTMENT']) && !empty($rawValue['UF_DEPARTMENT']))
		{
			$departmentList = $this->departments
				->filterByUsersDepartmentIdList($rawValue['UF_DEPARTMENT'])
				->map(fn(Department $department) => [
					'id' => $department->getIblockSectionId(),
					'name' => htmlspecialcharsbx($department->getName()),
				]);
		}

		return [
			'departments' => $departmentList,
			'canEdit' => $this->canEdit,
			'userId' => $rawValue['ID'],
			'selectedDepartment' => $this->getSettings()->getFilterFields()['=UF_DEPARTMENT'] ?? $this->getSettings()->getFilterFields()['@UF_DEPARTMENT'][0] ?? null
		];
	}

	protected function prepareColumnForExport($data): string
	{
		$departmentNameList = [];

		if (is_array($data['UF_DEPARTMENT']) && !empty($data['UF_DEPARTMENT']))
		{
			$departmentNameList = $this->departments
				->filterByUsersDepartmentIdList($data['UF_DEPARTMENT'])
				->map(fn(Department $department) => htmlspecialcharsbx($department->getName()));
		}

		return implode(', ', $departmentNameList);
	}
}
