<?php

namespace Bitrix\Crm\UserField\DisplayStrategy;

class DefaultStrategy extends BaseStrategy
{
	public function processValues(array $items): array
	{
		$view = new \Bitrix\Main\UserField\Display(\Bitrix\Main\UserField\Display::MODE_VIEW);
		$view->setAdditionalParameter('FILE_MAX_WIDTH', 300, true);
		$view->setAdditionalParameter('FILE_SHOW_POPUP', 'Y', true);
		$view->setAdditionalParameter('FILE_MAX_HEIGHT', 300, true);

		foreach ($items as $id => $values)
		{
			if (!empty($this->processedValues[$id]))
			{
				continue;
			}

			foreach ($values as $fieldName => $value)
			{
				if (!empty($this->getUserFields()[$fieldName]))
				{
					$userField = $this->getUserFields()[$fieldName];
					$userField['VALUE'] = $value;

					$view->setField($userField);
					$this->processedValues[$id][$fieldName] = $view->display();
					$view->clear();
				}
			}
		}

		return $this->processedValues;
	}
}
