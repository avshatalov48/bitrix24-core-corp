<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Grid;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Internals\UI;
use Bitrix\Recyclebin\Internals\User;
use Bitrix\Recyclebin\Recyclebin;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:recyclebin.base");

class RecyclebinListComponent extends RecyclebinBaseComponent
{
	protected $pageSizes = [
		["NAME" => "5", "VALUE" => "5"],
		["NAME" => "10", "VALUE" => "10"],
		["NAME" => "20", "VALUE" => "20"],
		["NAME" => "50", "VALUE" => "50"],
		["NAME" => "100", "VALUE" => "100"],
	];

	private static function getRequest($unEscape = false)
	{
		$request = Context::getCurrent()->getRequest();

		if ($unEscape)
		{
			$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);
		}

		return $request->getPostList();
	}

	protected function doPreActions()
	{
		Loader::includeModule('recyclebin');

		if(!isset($this->arParams['GRID_ID']))
		{
			$this->arParams['GRID_ID'] = 'RECYCLEBIN_LIST';
		}

		$this->arParams['FILTER_ID'] = $this->arParams['GRID_ID'];
	}

	protected function getData()
	{
		$modules = Recyclebin::getAvailableModules();
		$additionalData = Recyclebin::getAdditionalData();

		if (!$this->arParams['MODULE_ID'])
		{
			foreach ($modules as $moduleId => $data)
			{
				$this->arResult['MODULES_LIST'][$moduleId] = $data['NAME'];

				foreach ($data['LIST'] as $typeId => $typeData)
				{
					$this->arResult['ENTITY_TYPES'][$typeId] = $typeData['NAME'];
					$this->arResult['ENTITY_MESSAGES'][$typeId] = $typeData['HANDLER']::getNotifyMessages();
					$this->arResult['ENTITY_ADDITIONAL_DATA'][$typeId] = $additionalData[$moduleId]['ADDITIONAL_DATA'][$typeId];
					$this->arResult['ENTITY_ADDITIONAL_DATA'][$typeId]['MODULE_ID'] = $moduleId;
				}
			}
		}
		else
		{
			if (!$this->arParams['ENTITY_TYPE'] && $modules)
			{
				$moduleId = $this->arParams['MODULE_ID'];
				foreach ($modules[$moduleId]['LIST'] as $typeId => $typeData)
				{
					$this->arResult['ENTITY_TYPES'][$typeId] = $typeData['NAME'];
					$this->arResult['ENTITY_MESSAGES'][$typeId] = $typeData['HANDLER']::getNotifyMessages();
					$this->arResult['ENTITY_ADDITIONAL_DATA'][$typeId] = $additionalData[$moduleId]['ADDITIONAL_DATA'][$typeId];
					$this->arResult['ENTITY_ADDITIONAL_DATA'][$typeId]['MODULE_ID'] = $moduleId;
				}
			}
		}

		$this->arResult['GRID']['HEADERS'] = $this->getGridHeaders();
		$this->arResult['GRID']['DATA'] = $this->getGridData();

		$this->arResult['FILTER']['FIELDS'] = $this->getFilterFields();
		$this->arResult['FILTER']['PRESETS'] = $this->getFilterPresets();
	}

	private function getGridHeaders()
	{
		$list = [
			[
				'id'       => 'ENTITY_ID',
				'name'     => GetMessage('RECYCLEBIN_COLUMN_ENTITY_ID'),
				'editable' => false,
				'default'  => true,
				'shift'    => true,
				'sort'     => false,
			],
			[
				'id'       => 'NAME',
				'name'     => GetMessage('RECYCLEBIN_COLUMN_NAME'),
				'editable' => false,
				'default'  => true,
				'sort'     => 'NAME',
			],
			[
				'id'       => 'TIMESTAMP',
				'name'     => GetMessage('RECYCLEBIN_COLUMN_TIMESTAMP'),
				'editable' => false,
				'default'  => true,
				'sort'     => 'TIMESTAMP',
			]
		];

		if (User::isSuper())
		{
			$list[] = [
				'id'       => 'USER_ID',
				'name'     => GetMessage('RECYCLEBIN_COLUMN_USER_ID'),
				'editable' => false,
				'default'  => true
			];
		}

		if (!$this->arParams['MODULE_ID'])
		{
			$list[] = [
				'id'       => 'MODULE_ID',
				'name'     => GetMessage('RECYCLEBIN_COLUMN_MODULE_ID'),
				'editable' => false,
				'default'  => false,
				'sort'     => 'MODULE_ID',
			];
		}

		if (!$this->arParams['ENTITY_TYPE'])
		{
			$list[] = [
				'id'       => 'ENTITY_TYPE',
				'name'     => GetMessage('RECYCLEBIN_COLUMN_ENTITY_TYPE'),
				'editable' => false,
				'default'  => true,
				'sort'     => 'ENTITY_TYPE',
			];
		}

		return $list;
	}

	private function getGridData()
	{
		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)->setPageSize($this->getPageSize())->initFromUri();

		$select = array_column($this->getGridHeaders(), 'id');
		$select[] = 'ID';

		$select['USER_NAME'] = 'USER.NAME';
		$select['USER_LAST_NAME'] = 'USER.LAST_NAME';
		$select['USER_SECOND_NAME'] = 'USER.SECOND_NAME';
		$select['USER_TITLE'] = 'USER.TITLE';
		$select['USER_LOGIN'] = 'USER.LOGIN';
		$select['USER_PERSONAL_PHOTO'] = 'USER.PERSONAL_PHOTO';

        if (Loader::includeModule('crm'))
        {
            $select['USER_UF_USER_CRM_ENTITY'] = 'USER.UF_USER_CRM_ENTITY';
        }

		$select['USER_EXTERNAL_AUTH_ID'] = 'USER.EXTERNAL_AUTH_ID';

		$getListParameters = array(
			'order' => $this->getOrder(),

			'count_total' => true,

			'select' => $select,
			'filter' => $this->processFilter(),

			'offset' => $nav->getOffset(),
			'limit'  => $nav->getLimit(),
		);

		$this->arResult['TOTAL_RECORD_COUNT'] = 0;

		try
		{
			$list = RecyclebinTable::getList($getListParameters);
			$nav->setRecordCount($list->getCount());
			$this->arResult['TOTAL_RECORD_COUNT'] = $list->getCount();

			$list = $list->fetchAll();

			foreach ($list as &$row)
			{
				$row['USER_DISPLAY_NAME'] = User::formatName(
					[
						'NAME'        => $row['USER_NAME'],
						'LAST_NAME'   => $row['USER_LAST_NAME'],
						'SECOND_NAME' => $row['USER_SECOND_NAME'],
						'TITLE'       => $row['USER_TITLE'],
						'LOGIN'       => $row['USER_LOGIN'],
					]
				);
				$row['USER_PROFILE_URL'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_USER_PROFILE'],
					["user_id" => $row["USER_ID"]]
				);
				$row['USER_AVATAR'] = UI::getAvatar($row['USER_PERSONAL_PHOTO'], 100, 100);
				$row['USER_IS_EXTERNAL'] = User::isExternalUser($row['USER_ID']);
				$row['USER_IS_CRM'] = Loader::includeModule('crm') && array_key_exists('USER_UF_USER_CRM_ENTITY', $row) &&
									  !empty($row['USER_UF_USER_CRM_ENTITY']);
			}
		}
		catch (\Exception $e)
		{
			$list = [];

			$this->arResult['MESSAGES'] = array(
				"TYPE" => \Bitrix\Main\Grid\MessageType::ERROR,
				"TEXT" => $e->getMessage()
			);
		}

		//region NAV
		$this->arResult['NAV_OBJECT'] = $nav;
		$this->arResult['PAGE_SIZES'] = $this->pageSizes;

		//endregion

		return $list;
	}

	private function getPageSize()
	{
		$navParams = $this->getGridOptions()->getNavParams(['nPageSize' => 50]);

		return (int)$navParams['nPageSize'];
	}

	/**
	 * @return Grid\Options
	 */
	private function getGridOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			return new Grid\Options($this->arParams['GRID_ID']);
		}

		return $instance;
	}

	protected function getOrder()
	{
		$gridSort = $this->getGridOptions()->GetSorting(
			[
				'sort' => ['ID' => 'asc'],
				'vars' => ['by' => 'by', 'order' => 'order']
			]
		);

		$gridSort = $gridSort['sort'];

		return $gridSort;
	}

	private function processFilter()
	{
		static $filter = [];

		if (empty($filter))
		{
			if ($this->getFilterFieldData('FIND'))
			{
				$filter['*%NAME'] = $this->getFilterFieldData('FIND');
			}

			if ($this->arParams['MODULE_ID'])
			{
				$filter['=MODULE_ID'] = $this->arParams['MODULE_ID'];
			}

			if ($this->arParams['ENTITY_TYPE'])
			{
				$filter['=ENTITY_TYPE'] = $this->arParams['ENTITY_TYPE'];
			}

			if ($this->arParams['USER_ID'] && !User::isSuper())
			{
				$filter['=USER_ID'] = $this->arParams['USER_ID'];
			}
			if ($this->getFilterFieldData('FILTER_APPLIED', false) != true)
			{
				return $filter;
			}

			foreach ($this->getFilterFields() as $item)
			{
				switch ($item['type'])
				{
					default:
						$field = $this->getFilterFieldData($item['id']);
						if ($field)
						{
							$filter['%'.$item['id']] = $field;
						}
						break;
					case 'date':
						if ($this->getFilterFieldData($item['id'].'_from'))
						{
							$filter['>='.$item['id']] = $this->getFilterFieldData($item['id'].'_from');
						}
						if ($this->getFilterFieldData($item['id'].'_to'))
						{
							$filter['<='.$item['id']] = $this->getFilterFieldData($item['id'].'_to');
						}
						break;
					case 'number':
						if ($this->getFilterFieldData($item['id'].'_from'))
						{
							$filter['>='.$item['id']] = $this->getFilterFieldData($item['id'].'_from');
						}
						if ($this->getFilterFieldData($item['id'].'_to'))
						{
							$filter['<='.$item['id']] = $this->getFilterFieldData($item['id'].'_to');
						}

						if (array_key_exists('>='.$item['id'], $filter) &&
							array_key_exists('<='.$item['id'], $filter) &&
							$filter['>='.$item['id']] == $filter['<='.$item['id']])
						{
							$filter[$item['id']] = $filter['>='.$item['id']];
							unset($filter['>='.$item['id']], $filter['<='.$item['id']]);
						}

						break;

					case 'custom_entity':
					case 'list':
						if ($this->getFilterFieldData($item['id']))
						{
							$filter[$item['id']] = $this->getFilterFieldData($item['id']);
						}
						break;
				}
			}
		}

		return $filter;
	}

	private function getFilterFieldData($field, $default = null)
	{
		static $filterData;

		if (!$filterData)
		{
			$filterData = $this->getFilterOptions()->getFilter($this->getFilterFields());
		}

		return array_key_exists($field, $filterData) ? $filterData[$field] : $default;
	}

	/**
	 * @return Filter\Options
	 */
	private function getFilterOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			return new Filter\Options($this->arParams['FILTER_ID']);
		}

		return $instance;
	}

	private function getFilterFields()
	{
		$list = [
			//			'ID'        => array(
			//				'id'   => 'ID',
			//				'name' => Loc::getMessage('TASKS_COLUMN_ID'),
			//				'type' => 'number'
			//			),
			'ENTITY_ID' => array(
				'id'   => 'ENTITY_ID',
				'name' => Loc::getMessage('RECYCLEBIN_COLUMN_ENTITY_ID'),
				'type' => 'number'
			),
			'NAME'      => array(
				'id'      => 'NAME',
				'name'    => Loc::getMessage('RECYCLEBIN_COLUMN_NAME'),
				'type'    => 'string',
				'default' => true
			),
			'TIMESTAMP' => [
				'id'      => 'TIMESTAMP',
				'name'    => Loc::getMessage('RECYCLEBIN_COLUMN_TIMESTAMP'),
				'type'    => 'date',
				"exclude" => array(
					\Bitrix\Main\UI\Filter\DateType::TOMORROW,
					\Bitrix\Main\UI\Filter\DateType::PREV_DAYS,
					\Bitrix\Main\UI\Filter\DateType::NEXT_DAYS,
					\Bitrix\Main\UI\Filter\DateType::NEXT_WEEK,
					\Bitrix\Main\UI\Filter\DateType::NEXT_MONTH
				),
			],
		];

		if (User::isSuper())
		{
			$list['USER_ID'] = [
				'id'       => 'USER_ID',
				'name'     => Loc::getMessage('RECYCLEBIN_COLUMN_USER_ID'),
				'params'   => ['multiple' => 'Y'],
				'type'     => 'custom_entity',
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array(
						'ID'       => 'user',
						'FIELD_ID' => 'USER_ID'
					)
				)
			];
		}

		if (!$this->arParams['MODULE_ID'])
		{
			$list['MODULE_ID'] = [
				'id'     => 'MODULE_ID',
				'name'   => Loc::getMessage('RECYCLEBIN_COLUMN_MODULE_ID'),
				'params' => ['multiple' => 'Y'],
				'type'   => 'list',
				'items'  => $this->arResult['MODULES_LIST']
			];
		}

		if (!$this->arParams['ENTITY_TYPE'])
		{
			$list['ENTITY_TYPE'] = [
				'id'     => 'ENTITY_TYPE',
				'name'   => Loc::getMessage('RECYCLEBIN_COLUMN_ENTITY_TYPE'),
				'params' => ['multiple' => 'Y'],
				'type'   => 'list',
				'items'  => $this->arResult['ENTITY_TYPES']
			];
		}

		return $list;
	}

	private function getFilterPresets()
	{
		$presets = [
			'preset_today' => [
				'name'    => Loc::getMessage('RECYCLEBIN_PRESET_CURRENT_DAY'),
				'default' => false,
				'fields'  => [
					"TIMESTAMP_datesel" => \Bitrix\Main\UI\Filter\DateType::CURRENT_DAY
				]
			],
			'preset_week'  => [
				'name'    => Loc::getMessage('RECYCLEBIN_PRESET_CURRENT_WEEK'),
				'default' => false,
				'fields'  => [
					"TIMESTAMP_datesel" => \Bitrix\Main\UI\Filter\DateType::CURRENT_WEEK
				]
			],
			'preset_month' => [
				'name'    => Loc::getMessage('RECYCLEBIN_PRESET_CURRENT_MONTH'),
				'default' => false,
				'fields'  => [
					"TIMESTAMP_datesel" => \Bitrix\Main\UI\Filter\DateType::CURRENT_MONTH
				]
			],
		];

		$customPresets = isset($this->arParams['FILTER_PRESETS']) && is_array($this->arParams['FILTER_PRESETS'])
			? $this->arParams['FILTER_PRESETS'] : [];

		$hasDefault = false;
		if(!empty($customPresets))
		{
			foreach($customPresets as $customPreset)
			{
				if(isset($customPreset['default']) && $customPreset['default'] === true)
				{
					$hasDefault = true;
					break;
				}
			}

			$presets = array_merge($presets, $customPresets);
		}

		if(!$hasDefault)
		{
			$presets['preset_month']['default'] = true;
		}

		return $presets;
	}
}