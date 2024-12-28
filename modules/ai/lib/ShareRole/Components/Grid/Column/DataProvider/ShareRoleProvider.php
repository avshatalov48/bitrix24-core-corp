<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Column\DataProvider;

use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Localization\Loc;


class ShareRoleProvider extends DataProvider
{

	/**
	 * @inheritDoc
	 */
	public function prepareColumns(): array
	{

		return [
			$this->createColumn('NAME')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_NAME'))
				->setWidth(300)
				->setSort('NAME'),
			$this->createColumn('IS_ACTIVE')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_ACTIVE')),
			$this->createColumn('AUTHOR')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_AUTHOR'))
				->setSort('AUTHOR'),
			$this->createColumn('DATE_CREATE')
				->setDefault(false)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_DATE_CREATE'))
				->setSort('DATE_CREATE'),
			$this->createColumn('EDITOR')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_EDITOR'))
				->setSort('EDITOR'),
			$this->createColumn('DATE_MODIFY')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_DATE_MODIFY'))
				->setSort('DATE_MODIFY'),
			$this->createColumn('SHARE')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_SHARE')),
			$this->createColumn('IS_DELETED')
				->setDefault(true)
				->setName(Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_DELETED')),
		];
	}
}