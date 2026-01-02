<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Component\ComponentError;

Loc::loadMessages(__FILE__);

class CCrmOrderPaymentVoucherComponent extends \CBitrixComponent
{
	public function executeComponent()
	{
		global $APPLICATION;
		//region Params
		$this->arResult['ENTITY_ID'] = $this->arParams['~ENTITY_ID'] ?? 0;
		$this->arResult['PAYMENT_TYPE'] = isset($this->arParams['~PAYMENT_TYPE']) ? (int)$this->arParams['~PAYMENT_TYPE'] : Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_VOUCHER;

		$serviceContainer = Container::getInstance();
		$payment = Crm\Order\Manager::getPaymentObject($this->arResult['ENTITY_ID']);
		if (empty($payment))
		{
			$checkPermissions = $serviceContainer
				->getUserPermissions()
				->entityType()
				->canAddItems(\CCrmOwnerType::Order)
			;
		}
		else
		{
			$checkPermissions = $serviceContainer
				->getUserPermissions()
				->item()
				->canUpdate(\CCrmOwnerType::Order, $payment->getOrderId())
			;
		}

		if (!$checkPermissions)
		{
			ShowError(ComponentError::getMessage(ComponentError::PERMISSION_DENIED));

			return;
		}

		$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_PAYMENT_VOUCHER_TITLE'));

		$this->arResult['GUID'] = $this->arParams['GUID'] ?? "order_payment_voucher_{$this->arResult['ENTITY_ID']}";

		$this->arResult['EDITOR_CONFIG_ID'] = $this->arParams['EDITOR_CONFIG_ID'] ?? 'order_payment_voucher_detail';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderPaymentName.'_'.$this->arResult['ENTITY_ID'];

		if ($this->arResult['PAYMENT_TYPE'] === Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_VOUCHER)
		{
			$this->arResult['ENTITY_FIELDS'] = [
				[
					'name' => 'PAY_VOUCHER',
					'type' => 'order_subsection',
					'required' => true,
					'elements' => [
						[
							'name' => 'PAY_VOUCHER_NUM',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_VOUCHER_NUM'),
							'type' => 'text',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
						],
						[
							'name' => 'PAY_VOUCHER_DATE',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_VOUCHER_DATE'),
							'type' => 'datetime',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
							'data' => [
								'enableTime' => false,
							],
						],
					],
				],
			];

			$this->arResult['ENTITY_CONFIG'] = [
				[
					'name' => 'voucher',
					'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_VOUCHER_TITLE'),
					'type' => 'section',
					'data' => [
						'showButtonPanel' => false,
					],
					'elements' => [
						[
							'name' => 'PAY_VOUCHER',
						],
					],
				],
			];
		}
		elseif (
			$this->arResult['PAYMENT_TYPE'] === Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_RETURN
			|| $this->arResult['PAYMENT_TYPE'] === Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_CANCEL
		)
		{
			$this->arResult['ENTITY_FIELDS'] = [
				[
					'name' => 'PAY_RETURN',
					'type' => 'order_subsection',
					'required' => true,
					'elements' => [
						[
							'name' => 'PAY_RETURN_NUM',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_NUM'),
							'type' => 'text',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
						],
						[
							'name' => 'PAY_RETURN_DATE',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_DATE'),
							'type' => 'datetime',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
							'data' => [
								'enableTime' => false,
							]
						],
						[
							'name' => 'PAY_RETURN_COMMENT',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_COMMENT'),
							'type' => 'text',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
						],
					],
				],
			];

			if ($this->arResult['PAYMENT_TYPE'] === Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_RETURN)
			{
				$returnVariants =  [
					[
						'NAME' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_INNER_ACC'),
						'VALUE' => 'Y',
					],
				];

				if ($payment)
				{
					$paySystem = $payment->getPaySystem();

					if ($paySystem && $paySystem->getField('ID') != \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId())
					{
						if ($paySystem->isRefundable())
						{
							$returnVariants[] = [
								'NAME' => htmlspecialcharsbx($paySystem->getField('NAME')),
								'VALUE' => 'P',
							];
						}
					}
				}

				$this->arResult['ENTITY_FIELDS'][0]['elements'][] = [
					'name' => 'IS_RETURN',
					'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_TO'),
					'type' => 'list',
					'editable' => true,
					'enabledMenu' => false,
					'transferable' => false,
					'data' => [
						'items' =>  $returnVariants,
						'isHtml' => false,
					],
				];
			}

			$this->arResult['ENTITY_CONFIG'] = [
				[
					'name' => 'voucher',
					'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_TITLE'),
					'type' => 'section',
					'data' => [
						'showButtonPanel' => false,
					],
					'elements' => [
						[
							'name' => 'PAY_RETURN',
						],
					],
				],
			];
		}

		$this->arResult['ENTITY_DATA'] = [];
		if (!empty($payment))
		{
			$this->arResult['ENTITY_DATA'] = $payment->getFieldValues();
		}
		elseif (!empty($this->arParams['ENTITY_DATA']) && is_array($this->arParams['ENTITY_DATA']))
		{
			$this->arResult['ENTITY_DATA'] = $this->arParams['ENTITY_DATA'];
		}

		$this->includeComponentTemplate();
	}

	protected function prepareFieldInfos()
	{
		return $this->arResult['ENTITY_FIELDS'];
	}
}
