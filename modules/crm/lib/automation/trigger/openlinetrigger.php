<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		$unsupported = [\CCrmOwnerType::Quote, \CCrmOwnerType::SmartInvoice, \CCrmOwnerType::SmartDocument];
		if (in_array($entityTypeId, $unsupported, true))
		{
			return false;
		}

		return parent::isSupported($entityTypeId);
	}

	public static function isEnabled()
	{
		return Integration\OpenLineManager::isEnabled();
	}

	public static function getCode()
	{
		return 'OPENLINE';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_NAME_2');
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

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => 'config_id',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_PROPERTY_CONFIG'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_DEFAULT_CONFIG'),
				'Options' => static::getConfigList(),
			],
		];
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
				'value' => $config['ID'],
				'name' => $config['LINE_NAME']
			);
		}

		return $configs;
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}
}