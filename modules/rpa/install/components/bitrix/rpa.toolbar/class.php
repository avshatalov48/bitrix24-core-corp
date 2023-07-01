<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('rpa');

class RpaToolbarComponent extends Bitrix\Rpa\Components\Base
{
	public function executeComponent()
	{
		parent::loadBaseLanguageMessages();

		$this->arResult = $this->arParams;

		if(!empty($this->arResult['filter']) && is_array($this->arResult['filter']))
		{
			\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter(
				$this->arResult['filter'] + ['THEME' => \Bitrix\Main\UI\Filter\Theme::MUTED]
			);
		}

		if(is_array($this->arResult['buttons']))
		{
			\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.icons']);
			foreach($this->arResult['buttons'] as $location => $buttons)
			{
				foreach($buttons as $button)
				{
					if($button instanceof \Bitrix\UI\Buttons\Button)
					{
						\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($button, $location);
					}
				}
			}
		}

		if(!isset($this->arResult['tasksUrl']))
		{
			$urlManager = \Bitrix\Rpa\Driver::getInstance()->getUrlManager();
			$this->arResult['tasksUrl'] = $urlManager->getTasksUrl();
		}

		$this->arResult['taskCountersPullTag'] = \Bitrix\Rpa\Driver::getInstance()->getPullManager()->subscribeOnTaskCounters();

		$this->includeComponentTemplate();
	}
}