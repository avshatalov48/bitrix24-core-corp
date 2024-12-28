<?php

namespace Bitrix\Intranet\User\Grid;

use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User\Filter\ExtranetUserSettings;
use Bitrix\Intranet\User\Filter\IntranetUserSettings;
use Bitrix\Intranet\User\Filter\Provider\PhoneUserDataProvider;
use Bitrix\Intranet\User\Filter\UserFilter;
use Bitrix\Intranet\User\Grid\Row\Assembler\UserRowAssembler;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Pagination\PaginationFactory;
use Bitrix\Main\Grid\Pagination\LazyLoadTotalCount;
use Bitrix\Main\Grid\Row\Rows;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UI\PageNavigation;

/**
 * @method UserSettings getSettings()
 */
final class UserGrid extends Grid
{
	use LazyLoadTotalCount;

	private \Bitrix\Main\UI\Filter\Options $filterOptions;

	protected function createColumns(): Columns
	{
		return new Columns(
			new \Bitrix\Intranet\User\Grid\Column\Provider\UserDataProvider($this->getSettings()),
		);
	}

	public function getOrmParams(): array
	{
		$params = parent::getOrmParams();
		array_push($params['select'], 'ID', 'ACTIVE', 'CONFIRM_CODE', 'EXTERNAL_AUTH_ID');
		$selectedSortField = '';

		// remove this after b_extranet_user migration
		if (\Bitrix\Main\Loader::includeModule('extranet'))
		{
			$params['select'][] = 'EXTRANET_GROUP';
		}

		if (!empty($params['order']))
		{
			$selectedSortField = is_array($params['order']) ? array_key_first($params['order']) : $params['order'];
		}

		if (
			empty($selectedSortField)
			|| (str_starts_with($selectedSortField, 'UF_') && !in_array($selectedSortField, $this->getSettings()->getViewFields()))
		)
		{
			$params['order'] = [
				'STRUCTURE_SORT' => 'DESC',
			];
		}

		if (key_exists('STRUCTURE_SORT', $params['order']))
		{
			$currentUser = new \Bitrix\Intranet\User();
			$sort = $currentUser->getStructureSort(false);

			if (!empty($sort))
			{
				$sqlHelper = \Bitrix\Main\Application::getInstance()->getConnection()->getSqlHelper();
				$params['select'][]
					= new \Bitrix\Main\Entity\ExpressionField(
						'STRUCTURE_SORT',
						$sqlHelper->getOrderByIntField('%s', $sort, false),
						'ID');
			}
			else
			{
				unset($params['order']['STRUCTURE_SORT']);
			}
		}

		$params['group'] = ['ID'];

		return $params;
	}

	protected function createRows(): Rows
	{
		\Bitrix\Main\UI\Extension::load([
			$this->getSettings()->getExtensionLoadName(),
			'ui.common',
			'ui.avatar',
		]);

		$rowAssembler = new UserRowAssembler($this->getVisibleColumnsIds(), $this->getSettings());
		$actionsProvider = new \Bitrix\Intranet\User\Grid\Row\Action\UserDataProvider($this->getSettings());

		return new Rows($rowAssembler, $actionsProvider);
	}

	public function getOrmFilter(): array
	{
		if (!$this->getSettings()->getFilterFields())
		{
			$result = parent::getOrmFilter();

			$ufCodesList = array_keys($this->getSettings()->getUserFields());

			foreach ($result as $key => $value)
			{
				if (
					preg_match('/(.*)_from$/iu', $key, $match)
					&& in_array($match[1], $ufCodesList)
				)
				{
					\Bitrix\Main\Filter\Range::prepareFrom($result, $match[1], $value);
				}
				elseif (
					preg_match('/(.*)_to$/iu', $key, $match)
					&& in_array($match[1], $ufCodesList)
				)
				{
					\Bitrix\Main\Filter\Range::prepareTo($result, $match[1], $value);
				}
				elseif (!in_array($key, $ufCodesList))
				{
					continue;
				}
				elseif (
					!empty($ufList[$key])
					&& !empty($ufList[$key]['SHOW_FILTER'])
					&& !empty($ufList[$key]['USER_TYPE_ID'])
					&& $ufList[$key]['USER_TYPE_ID'] === 'string'
					&& $ufList[$key]['SHOW_FILTER'] === 'E'
				)
				{
					$result[$key] = $value . '%';
				}
				else
				{
					$result[$key] = $value;
				}
			}

			$this->getSettings()->setFilterFields($result);
		}

		return $this->getSettings()->getFilterFields();
	}

	protected function createFilter(): ?Filter
	{
		$params = [
			'ID' => $this->getId(),
			'WHITE_LIST' => $this->getSettings()->getViewFields(),
		];
		$filterSettings = ModuleManager::isModuleInstalled('extranet')
			? new ExtranetUserSettings($params)
			: new IntranetUserSettings($params);

		$extraProviders = [
			new \Bitrix\Main\Filter\UserUFDataProvider($filterSettings),
			new \Bitrix\Intranet\User\Filter\Provider\IntranetUserDataProvider($filterSettings),
			new \Bitrix\Intranet\User\Filter\Provider\IntegerUserDataProvider($filterSettings),
			new \Bitrix\Intranet\User\Filter\Provider\StringUserDataProvider($filterSettings),
			new \Bitrix\Intranet\User\Filter\Provider\DateUserDataProvider($filterSettings),
			new PhoneUserDataProvider($filterSettings),
		];

		if (ModuleManager::isModuleInstalled('extranet'))
		{
			$extraProviders[] = new \Bitrix\Intranet\User\Filter\Provider\ExtranetUserDataProvider($filterSettings);
		}

		return new UserFilter(
			$this->getId(),
			new UserDataProvider($filterSettings),
			$extraProviders,
			[
				'FILTER_SETTINGS' => $filterSettings,
			],
		);
	}

	public function setRawRows(iterable $rawValue): void
	{
		parent::setRawRows($rawValue);

		if (is_array($rawValue))
		{
			$userCollection = ServiceContainer::getInstance()
				->userRepository()
				->makeUserCollectionFromModelArray($rawValue);
			$this->getSettings()->setUserCollection($userCollection);
		}
	}

	protected function getFilterOptions(): \Bitrix\Main\UI\Filter\Options
	{
		if (!empty($this->filterOptions))
		{
			return $this->filterOptions;
		}

		$this->filterOptions = new \Bitrix\Main\UI\Filter\Options($this->getId());

		return $this->filterOptions;
	}

	protected function createPagination(): ?PageNavigation
	{
		return (new PaginationFactory($this, $this->getPaginationStorage()))->create();
	}

	protected function createPanel(): \Bitrix\Main\Grid\Panel\Panel
	{
		return new \Bitrix\Main\Grid\Panel\Panel(
			new Panel\Action\UserDataProvider($this->getSettings()),
		);
	}
}
