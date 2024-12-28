<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Tab;

class DistributedProjectDataFilter extends ProjectDataFilter
{
	public function apply(array $items, Dialog $dialog): void
	{
		parent::apply($items, $dialog);

		$dialog->removeTab('projects');

		$footer = $dialog->getFooter();
		$footerOptions = $dialog->getFooterOptions();

		$myProjectsTab = new Tab([
			'id' => 'my-projects',
			'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_DISTRIBUTED_PROJECT_FILTER_MY_PROJECTS'),
			'icon' => [
				'default' => $this->getIcon(),
				'selected' => $this->getSelectedIcon(),
			],
			'footer' => $footer,
			'footerOptions' => array_merge($footerOptions ?? [], ['isProject' => true]),
		]);
		$dialog->addTab($myProjectsTab);

		$myGroupsTab = new Tab([
			'id' => 'my-groups',
			'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_DISTRIBUTED_PROJECT_FILTER_MY_GROUPS'),
			'icon' => [
				'default' => $this->getIcon(),
				'selected' => $this->getSelectedIcon(),
			],
		]);
		$dialog->addTab($myGroupsTab);

		foreach ($items as $item)
		{
			if ($item->getCustomData()->get('project') === true)
			{
				$item->setSupertitle(Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_DISTRIBUTED_PROJECT_FILTER_PROJECT'));
				$item->addTab('my-projects');
			}
			else
			{
				$item->setSupertitle(Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_DISTRIBUTED_PROJECT_FILTER_GROUP'));
				$item->addTab('my-groups');
			}
		}
	}

	private function getSelectedIcon(): string
	{
		return str_replace('ABB1B8', 'fff', $this->getIcon());
	}

	private function getIcon(): string
	{
		return
			'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2223%22%20height%3D%2223%22%20'
			. 'fill%3D%22none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M11'
			. '.934%202.213a.719.719%200%2001.719%200l3.103%201.79c.222.13.36.367.36.623V8.21a.719.71'
			. '9%200%2001-.36.623l-3.103%201.791a.72.72%200%2001-.719%200L8.831%208.832a.719.719%200%'
			. '2001-.36-.623V4.627c0-.257.138-.495.36-.623l3.103-1.791zM7.038%2010.605a.719.719%200%2'
			. '001.719%200l3.103%201.792a.72.72%200%2001.359.622v3.583a.72.72%200%2001-.36.622l-3.102'
			. '%201.792a.719.719%200%2001-.72%200l-3.102-1.791a.72.72%200%2001-.36-.623v-3.583c0-.257'
			. '.138-.494.36-.622l3.103-1.792zM20.829%2013.02a.719.719%200%2000-.36-.623l-3.102-1.792a'
			. '.719.719%200%2000-.72%200l-3.102%201.792a.72.72%200%2000-.36.622v3.583a.72.72%200%2000'
			. '.36.622l3.103%201.792a.719.719%200%2000.719%200l3.102-1.791a.719.719%200%2000.36-.623v'
			. '-3.583z%22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E'
			;
	}
}
