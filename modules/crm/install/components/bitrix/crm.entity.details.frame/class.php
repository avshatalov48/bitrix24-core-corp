<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::requireModule('crm');

class CCrmEntityDetailsFrameComponent extends \Bitrix\Crm\Component\Base
{
	public function executeComponent()
	{
		$entityTypeId = (int)($this->arParams['~ENTITY_TYPE_ID'] ?? CCrmOwnerType::Undefined);

		$this->arResult['COMPONENT_NAME'] = $this->resolveComponent($entityTypeId);
		if (empty($this->arResult['COMPONENT_NAME']))
		{
			ShowError(
				GetMessage(
					'CRM_ENT_DETAIL_FRAME_COMPONENT_NOT_DEFINED',
					['#TYPE_NAME#' => CCrmOwnerType::GetDescription($entityTypeId)]
				)
			);

			return;
		}

		$this->arResult['COMPONENT_PARAMS'] = [
			'ENTITY_ID' => (int)($this->arParams['~ENTITY_ID'] ?? 0),
			'EXTRAS' => isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
				? $this->arParams['~EXTRAS'] : []
			,
		];

		$this->includeComponentTemplate();
	}

	private function resolveComponent(int $entityTypeId): ?string
	{
		return match ($entityTypeId)
		{
			CCrmOwnerType::Lead => 'bitrix:crm.lead.details',
			CCrmOwnerType::Contact => 'bitrix:crm.contact.details',
			CCrmOwnerType::Company => 'bitrix:crm.company.details',
			CCrmOwnerType::Deal => 'bitrix:crm.deal.details',
			CCrmOwnerType::Order => 'bitrix:crm.order.details',
			CCrmOwnerType::OrderCheck => 'bitrix:crm.order.check.details',
			CCrmOwnerType::CheckCorrection => 'bitrix:crm.check.correction.details',
			CCrmOwnerType::OrderShipment => 'bitrix:crm.order.shipment.details',
			CCrmOwnerType::OrderPayment => 'bitrix:crm.order.payment.details',
			default => null,
		};
	}
}
