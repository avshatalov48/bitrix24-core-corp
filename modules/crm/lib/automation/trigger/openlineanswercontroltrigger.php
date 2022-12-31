<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineAnswerControlTrigger extends OpenLineTrigger
{
	public static function isEnabled()
	{
		return (Integration\OpenLineManager::isEnabled()
			&& class_exists('\Bitrix\ImOpenLines\Crm')
			&& method_exists('\Bitrix\ImOpenLines\Crm', 'executeAutomationAnswerControlTrigger')
		);
	}

	public static function getCode()
	{
		return 'OPENLINE_ANSWER_CTRL';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_CTRL_NAME_1');
	}

	public function setInputData($data)
	{
		if (is_callable([$this, 'setReturnValues']))
		{
			$this->setReturnValues(self::collectSessionValues($data, true));
		}
		return parent::setInputData($data);
	}

	public static function getReturnProperties(): array
	{
		return array_values(
			array_map(
				function ($field)
				{
					if (isset($field['ReturnId']))
					{
						$field['Id'] = $field['ReturnId'];
						unset($field['ReturnId']);
						return $field;
					}
				},
				self::getSessionFields()
			)
		);
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled())
		{
			$docType = Integration\BizProc\Document\TmpDoc::createNewDocument(self::getSessionFields());
			$result['FIELDS'] = array_values(\Bitrix\Bizproc\Automation\Helper::getDocumentFields($docType));
		}
		return $result;
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['imolCondition'])
		)
		{
			$conditionGroup = new ConditionGroup($trigger['APPLY_RULES']['imolCondition']);
			$documentType = $documentId = Integration\BizProc\Document\TmpDoc::createNewDocument(self::getSessionFields());

			return $conditionGroup->evaluateByDocument(
				$documentType,
				$documentId,
				self::collectSessionValues($this->getInputData())
			);
		}
		return true;
	}

	public static function getConditionDocumentType(): array
	{
		return Integration\BizProc\Document\TmpDoc::createNewDocument(self::getSessionFields());
	}

	private static function getSessionFields(): array
	{
		$configs = static::getConfigList();

		return [
			'CONFIG_ID' => [
				'ReturnId' => 'OpenLineAnswerCtrlConfigId',
				'Name' => Loc::getMessage("CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_CTRL_FIELD_CONFIG_ID"),
				'Type' => 'select',
				'Options' => array_combine(array_column($configs, 'ID'), array_column($configs, 'NAME')),
			],
			'ANSWER_TIME_SEC' => [
				'ReturnId' => 'OpenLineAnswerCtrlAnswerTimeSec',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_CTRL_FIELD_ANSWER_TIME'),
				'Type' => 'int',
			],
			'OPERATOR_ID' => [
				'ReturnId' => 'OpenLineAnswerCtrlOperatorId',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_CTRL_RETURN_OPERATOR_ID'),
				'Type' => 'user',
			],
			'DATE_LAST_MESSAGE' => [
				'ReturnId' => 'OpenLineAnswerCtrlDateLastMessage',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_CTRL_RETURN_DATE_LAST_MESSAGE'),
				'Type' => 'datetime',
			],
		];
	}

	private static function collectSessionValues(array $data, $useReturnId = false): array
	{
		$values = [];
		foreach (self::getSessionFields() as $id => $field)
		{
			if (!isset($data[$id]) || $useReturnId && !isset($field['ReturnId']))
			{
				continue;
			}

			$key = $useReturnId ? $field['ReturnId'] : $id;
			$values[$key] = ($field['Type'] === 'user') ? 'user_'.$data[$id] : $data[$id];
		}
		return $values;
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_CTRL_DESCRIPTION') ?? '';
	}
}