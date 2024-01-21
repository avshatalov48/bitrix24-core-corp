<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Sale;

class TerminalPaymentProvider extends Main\Filter\EntityDataProvider
{
	public function getSettings()
	{
		// TODO: Implement getSettings() method.
	}

	public function prepareFields()
	{
		return [
			'ACCOUNT_NUMBER' => $this->createField('ACCOUNT_NUMBER', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_ACCOUNT_NUMBER'),
				'default' => false,
				'type' => 'text',
			]),
			'SUM' => $this->createField('SUM', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_SUM'),
				'default' => false,
				'type' => 'number',
			]),
			'DATE_PAID' => $this->createField('DATE_PAID', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_DATE_PAID'),
				'default' => true,
				'type' => 'date',
				'data' => [
					'exclude' => [
						\Bitrix\Main\UI\Filter\DateType::TOMORROW,
						\Bitrix\Main\UI\Filter\DateType::NEXT_DAYS,
						\Bitrix\Main\UI\Filter\DateType::NEXT_WEEK,
						\Bitrix\Main\UI\Filter\DateType::NEXT_MONTH,
					],
				],
			]),
			'PAY_SYSTEM_NAME' => $this->createField('PAY_SYSTEM_NAME', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_PAY_SYSTEM_NAME'),
				'default' => false,
				'type' => 'list',
				'partial' => true,
			]),
			'PAID' => $this->createField('PAID', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_PAID'),
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'CLIENT' => $this->createField('CLIENT', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_CLIENT'),
				'default' => false,
				'type' => 'dest_selector',
				'partial' => true,
			]),
			'RESPONSIBLE_ID' => $this->createField('RESPONSIBLE_ID', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_RESPONSIBLE'),
				'default' => false,
				'type' => 'entity_selector',
				'partial' => true,
			]),
			'MARKED' => $this->createField('MARKED', [
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_MARKED'),
				'default' => false,
				'type' => 'list',
				'partial' => true,
			]),
		];
	}

	protected function getFieldName($fieldID)
	{
		return Main\Localization\Loc::getMessage("CRM_TERMINAL_PAYMENT_PROVIDER_{$fieldID}");
	}

	public function prepareFieldData($fieldID)
	{
		if ($fieldID === 'PAID')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => [
					'Y' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_PAID_Y'),
					'N' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_PAID_N'),
				],
			];
		}

		if ($fieldID === 'MARKED')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => [
					'Y' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_MARKED_Y'),
					'N' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_MARKED_N'),
				],
			];
		}

		if ($fieldID === 'CLIENT')
		{
			return [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TIMELINE_FILTER_CLIENT',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'enableCrmContacts' => 'Y',
					'enableCrmCompanies' => 'Y',
					'addTabCrmContacts' => 'Y',
					'addTabCrmCompanies' => 'Y',
					'convertJson' => 'Y',
					'multiple' => 'Y',
				],
			];
		}

		$userFields = ['RESPONSIBLE_ID'];
		if (in_array($fieldID, $userFields))
		{
			return $this->getUserEntitySelectorParams($fieldID . '_filter', ['fieldName' => $fieldID]);
		}

		if ($fieldID === 'PAY_SYSTEM_NAME')
		{
			$items = [];
			$paymentIterator = Sale\Payment::getList([
				'select' => ['PAY_SYSTEM_ID', 'PAY_SYSTEM_NAME'],
				'filter' => [
					'!=PAY_SYSTEM_ID' => Sale\PaySystem\Manager::getInnerPaySystemId(),
				],
				'group' => ['PAY_SYSTEM_ID', 'PAY_SYSTEM_NAME'],
				'runtime' => [
					Container::getInstance()->getTerminalPaymentService()->getRuntimeReferenceField()
				],
			]);
			while ($paymentData = $paymentIterator->fetch())
			{
				$items[$paymentData['PAY_SYSTEM_ID']] = $paymentData['PAY_SYSTEM_NAME'];
			}

			return [
				'params' => [
					'multiple' => 'Y',
				],
				'items' => $items,
			];
		}

		return null;
	}

	public function getGridColumns(): array
	{
		return [
			[
				'id' => 'ACCOUNT_NUMBER',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_ACCOUNT_NUMBER'),
				'sort' => 'ACCOUNT_NUMBER',
				'default' => true,
			],
			[
				'id' => 'SUM',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_SUM'),
				'sort' => 'SUM',
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'DATE_PAID',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_DATE_PAID'),
				'sort' => 'DATE_PAID',
				'default' => true,
			],
			[
				'id' => 'PAY_SYSTEM_NAME',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_PAY_SYSTEM_NAME'),
				'sort' => 'PAY_SYSTEM_NAME',
				'default' => true,
			],
			[
				'id' => 'PAID',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_PAID'),
				'sort' => 'PAID',
				'default' => true,
				'type' => Main\Grid\Column\Type::LABELS,
			],
			'CLIENT' => [
				'id' => 'CLIENT',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_CLIENT'),
				'default' => true,
				'sort' => false,
			],
			'RESPONSIBLE_ID' => [
				'id' => 'RESPONSIBLE_ID',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_RESPONSIBLE'),
				'default' => true,
				'sort' => 'RESPONSIBLE_ID',
			],
			[
				'id' => 'MARKED',
				'name' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_PROVIDER_MARKED'),
				'sort' => 'MARKED',
				'default' => false,
				'type' => Main\Grid\Column\Type::LABELS,
			],
		];
	}
}
