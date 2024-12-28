<?php

namespace Bitrix\Intranet\User\Filter;

use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User\Filter\Presets\FilterPresetManager;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\PhoneNumber;

class UserFilter extends Filter
{
	private Options $filterOptions;
	private array $filterPresets;
	private ?IntranetUserSettings $filterSettings = null;
	protected $uiFilterServiceFields = [
		'FIRED',
		'ADMIN',
		'EXTRANET',
		'VISITOR',
		'INVITED',
		'INTEGRATOR',
		'TAGS',
		'DEPARTMENT',
		'GENDER',
		'BIRTHDAY',
		'PHONE_MOBILE',
		'PHONE',
		'POSITION',
		'COMPANY',
		'FULL_NAME',
		'WAIT_CONFIRMATION',
		'IN_COMPANY',
		'PHONE_APPS',
		'DESKTOP_APPS',
		'COLLABER',
	];

	public function __construct(
		$ID,
		DataProvider $entityDataProvider,
		array $extraDataProviders = null,
		array $params = null,
		array $additionalPresets = [],
	)
	{
		parent::__construct($ID, $entityDataProvider, $extraDataProviders, $params);

		$fields = $this->getFields();

		$defaultFilterIds = $this->getDefaultFieldIDs();
		$defaultFieldsValues = [];

		foreach ($defaultFilterIds as $fieldId)
		{
			$value = match ($fields[$fieldId]) {
				'dest_selector' => false,
				default => '',
			};
			$defaultFieldsValues[$fieldId] = $value;
		}

		if (isset($params['FILTER_SETTINGS']) && $params['FILTER_SETTINGS'] instanceof IntranetUserSettings)
		{
			$this->filterSettings = $params['FILTER_SETTINGS'];
		}

		$presetManager = new FilterPresetManager($this->filterSettings, $additionalPresets);
		$this->filterPresets = $presetManager->getPresets();

		$this->filterOptions = new Options(
			$this->getId(),
			$presetManager->getPresetsArrayData($defaultFieldsValues)
		);

		if (\CUserOptions::GetOption('intranet', 'isUserListPresetsUpdated') !== 'Y')
		{
			foreach ($presetManager->getPresets() as $preset)
			{
				$this->filterOptions->setFilterSettings(
					$preset->getId(),
					$preset->toArray()
				);
			}

			\CUserOptions::SetOption('intranet', 'isUserListPresetsUpdated', 'Y');
		}

		foreach ($presetManager->getDisabledPresets() as $preset)
		{
			$this->filterOptions->deleteFilter($preset->getId(), false);
		}

		$this->filterOptions->save();
	}

	public function getFilterSettings(): ?IntranetUserSettings
	{
		return $this->filterSettings;
	}

	/**
	 * @return array of default and saved presets
	 */
	public function getFilterPresets(): array
	{
		return array_merge(
			$this->filterOptions->getPresets(),
			$this->filterOptions->getDefaultPresets()
		);
	}

	public function getDefaultFilterPresets(): array
	{
		return $this->filterPresets;
	}

	public function removeServiceUiFilterFields(array &$filter): void
	{
		parent::removeServiceUiFilterFields($filter);

		foreach ($filter as $fieldId => $fieldValue)
		{
			if (in_array($fieldId, $this->uiFilterServiceFields, true))
			{
				unset($filter[$fieldId]);
			}
		}
	}

	public function getValue(?array $rawValue = null): array
	{
		if (!isset($rawValue))
		{
			$rawValue =
				$this->filterOptions->getFilter()
				+ $this->filterOptions->getFilterLogic($this->getFieldArrays())
			;
		}

		if (!empty($rawValue['FIND']))
		{
			$searchString = $rawValue['FIND'];
		}
		else
		{
			$searchString = $this->filterOptions->getSearchString();
		}

		$result = $rawValue;
		$this->removeNotUiFilterFields($result);
		$this->prepareListFilterParams($result);
		$this->prepareFilterValue($result);
		$this->removeServiceUiFilterFields($result);
		$this->addSearchFilter($result, $searchString);
		$result['=IS_REAL_USER'] = 'Y';

		if (isset($result['=UF_DEPARTMENT']))
		{
			$selectedDepartment = ServiceContainer::getInstance()
				->departmentRepository()
				->getById((int)$result['=UF_DEPARTMENT']);

			// filter by all sub departments, as it was in old user grid
			if ($selectedDepartment)
			{
				$subDepartments = ServiceContainer::getInstance()
					->departmentRepository()
					->getAllTree($selectedDepartment);

				if (!$subDepartments->empty())
				{
					$result['@UF_DEPARTMENT'] = $subDepartments->map(fn(Department $department) => $department->getId());
					unset($result['=UF_DEPARTMENT']);
				}
			}
		}

		return $result;
	}

	private function addSearchFilter(&$result, string $searchString): void
	{
		if ($searchString !== '')
		{
			$matchesPhones = [];
			$phoneParserManager = PhoneNumber\Parser::getInstance();
			preg_match_all('/'.$phoneParserManager->getValidNumberPattern().'/i', $searchString, $matchesPhones);

			if (
				!empty($matchesPhones)
				&& !empty($matchesPhones[0])
			)
			{
				foreach ($matchesPhones[0] as $phone)
				{
					$convertedPhone = PhoneNumber\Parser::getInstance()
						->parse($phone)
						->format(PhoneNumber\Format::E164);
					$searchString = str_replace($phone, $convertedPhone, $searchString);
				}
			}

			$findFilter = \Bitrix\Main\UserUtils::getAdminSearchFilter([
				'FIND' => $searchString
			]);

			if (!empty($findFilter))
			{
				$result = array_merge($result, $findFilter);
			}
		}
	}
}