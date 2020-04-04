<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main;
use Bitrix\Rest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class AppTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return (Main\Loader::includeModule('rest'));
	}

	public static function getCode()
	{
		return 'APP';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_APP_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		$appA = (int)$trigger['APPLY_RULES']['APP_ID'];
		$codeA = (string)$trigger['APPLY_RULES']['CODE'];

		$appB = (int)$this->getInputData('APP_ID');
		$codeB = (string)$this->getInputData('CODE');

		return ($appA === $appB && $codeA === $codeB);
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled())
		{
			$result['APP_LIST'] = static::getAppList();
		}
		return $result;
	}

	/**
	 * @return array
	 */
	private static function getAppList()
	{
		$list = Entity\TriggerAppTable::getList(array(
			'select' => array('APP_ID', 'CODE', 'NAME'),
			'order' => array('DATE_CREATE' => 'DESC')
		))->fetchAll();

		if (!$list)
		{
			return $list;
		}

		$appIds = array();
		foreach ($list as $item)
		{
			$appIds[] = $item['APP_ID'];
		}

		$appNames = static::getAppNames(array_unique($appIds));

		$result = array();
		foreach ($list as $index => $item)
		{
			if (isset($appNames[$item['APP_ID']]))
			{
				$list[$index]['APP_NAME'] = $appNames[$item['APP_ID']];
				$result[] = $list[$index];
			}
		}

		return $result;
	}

	private static function getAppNames(array $ids)
	{
		$appNames = array();
		$appNamesResult = Rest\AppLangTable::getList(array(
			'filter' => array(
				'@APP_ID' => $ids,
				'=LANGUAGE_ID' => Main\Application::getInstance()->getContext()->getLanguage(),
				'=APP.ACTIVE' => 'Y'
			),
			'select' => array('APP_ID', 'MENU_NAME')
		));

		while ($row = $appNamesResult->fetch())
		{
			$appNames[$row['APP_ID']] = $row['MENU_NAME'];
		}

		return $appNames;
	}
}