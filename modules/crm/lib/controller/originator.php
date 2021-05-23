<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Crm;
use Bitrix\Main;

class Originator extends Main\Engine\Controller
{
	public function setAction(array $fields)
	{
		if ($this->getScope() === Main\Engine\Controller::SCOPE_REST)
		{
			$icon = [];
			if ($files = $this->getRequest()->getFile('fields'))
			{
				foreach ($files as $fieldName => $fieldValue)
				{
					if (!array_key_exists('ICON', $fieldValue))
					{
						break;
					}
					$icon[$fieldName] = $fieldValue['ICON'];
				}
			}
			if (empty($icon)
				&& array_key_exists('ICON', $fields)
				&& is_array($fields['ICON']))
			{
				$icon = \CRestUtil::saveFile($fields['ICON']);
			}
			if (!empty($icon))
			{
				$fields['ICON'] = $icon;
			}
		}
		return Crm\Integration\Originator::set([
			'ORIGINATOR_ID' => $fields['ORIGINATOR_ID'],
			'ICON' => $fields['ICON']
		]);
	}

	public function getAction(string $id)
	{
		if ($res = Crm\Integration\Originator::get($id))
		{
			$res += ['ICON' => Crm\Integration\Originator::getIcon($id)];
			return $res;
		}
		return null;
	}
}
