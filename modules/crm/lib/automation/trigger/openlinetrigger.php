<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		if ($entityTypeId === \CCrmOwnerType::Quote || $entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return false;
		}

		return parent::isSupported($entityTypeId);
	}

	protected static function areDynamicTypesSupported(): bool
	{
		return false;
	}

	public static function isEnabled()
	{
		return (Integration\OpenLineManager::isEnabled()
			&& class_exists('\Bitrix\ImOpenLines\Crm')
			&& method_exists('\Bitrix\ImOpenLines\Crm', 'executeAutomationTrigger')
		);
	}

	public static function getCode()
	{
		return 'OPENLINE';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_NAME1');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& isset($trigger['APPLY_RULES']['config_id'])
			&& $trigger['APPLY_RULES']['config_id'] > 0
		)
		{
			if (
				(int)$trigger['APPLY_RULES']['config_id'] !== (int)$this->getInputData('CONFIG_ID')
			)
			{
				return false;
			}
		}

		$msg = $this->getInputData('MESSAGE');
		if (
			$msg
			&& is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['msg_text'])
		)
		{
			$msgText = $msg['PLAIN_TEXT'] ?? $msg['TEXT'];
			if ($msgText)
			{
				return (mb_stripos($msgText, $trigger['APPLY_RULES']['msg_text']) !== false);
			}
		}

		return true;
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled())
		{
			$result['CONFIG_LIST'] = static::getConfigList();
		}
		return $result;
	}

	protected static function getConfigList()
	{
		if (!static::isEnabled())
		{
			return [];
		}
		$configs = [];
		$orm = \Bitrix\ImOpenLines\Model\ConfigTable::getList(Array(
			'filter' => Array(
				'=TEMPORARY' => 'N'
			)
		));
		while ($config = $orm->fetch())
		{
			$configs[] = array(
				'ID' => $config['ID'],
				'NAME' => $config['LINE_NAME']
			);
		}
		return $configs;
	}
}