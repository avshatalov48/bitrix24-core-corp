<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Action\Terminal;

use Bitrix\Crm\Order\TradingPlatform\Terminal;
use Bitrix\Crm\Terminal\PullManager;
use Bitrix\CrmMobile\Terminal\DtoItem;
use Bitrix\CrmMobile\Terminal\DtoItemData;
use Bitrix\CrmMobile\Terminal\DtoItemDataConverter;
use Bitrix\CrmMobile\Terminal\EntityEditorFieldsProvider;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Crm\Order\Permissions;

class GetPaymentListAction extends Action
{
	public function run(PageNavigation $pageNavigation, array $extra = [])
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$permissions = $this->getUserPermissions();
		if (!$permissions['read'])
		{
			return self::getResponse([], $permissions);
		}

		if ($pageNavigation->getOffset() === 0)
		{
			PullManager::subscribe((int)$this->getCurrentUser()->getId());
		}

		return self::getResponse(
			$this->getListItems($pageNavigation, $extra),
			$permissions
		);
	}

	private function getListItems(PageNavigation $pageNavigation, array $extra): array
	{
		$result = [];

		$filter = [
			'=ORDER.TRADING_PLATFORM.TRADING_PLATFORM_ID' => (int)Terminal::getInstanceByCode(
				Terminal::TRADING_PLATFORM_CODE
			)->getIdIfInstalled(),
		];
		if (isset($extra['filterParams']['ID']) && is_array($extra['filterParams']['ID']))
		{
			$filter['@ID'] = $extra['filterParams']['ID'];
		}

		$paymentList = PaymentRepository::getInstance()->getList([
			'select' => ['ID', 'ORDER_ID'],
			'filter' => $filter,
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'order' => ['ID' => 'DESC'],
		]);
		foreach ($paymentList as $payment)
		{
			$itemData = DtoItemDataConverter::convert($payment);

			$resultItem = new DtoItem([
				'id' => $itemData->id,
			]);
			$itemData->fields = $this->getItemFields($itemData);
			$resultItem->data = $itemData;

			$result[] = $resultItem;
		}

		return $result;
	}

	private function getItemFields(DtoItemData $itemData): array
	{
		$result = [];

		$fieldsProvider = (new EntityEditorFieldsProvider())->setItemData($itemData);

		$result[] = $fieldsProvider->getSumField(
			[
				'params' => [
					'readOnly' => true,
				],
			],
		);

		if ($itemData->companyId > 0 || !empty($itemData->contactIds))
		{
			$result[] = $fieldsProvider->getClientField();
		}

		$result[] = $fieldsProvider->getStatusField([
			'params' => [
				'readOnly' => true,
			],
		]);

		return $result;
	}

	private static function getResponse(array $items, array $permissions): array
	{
		return [
			'items' => $items,
			'permissions' => $permissions,
		];
	}

	protected function getUserPermissions(): array
	{
		return [
			'read' => Permissions\Payment::checkReadPermission(),
			'write' => Permissions\Payment::checkUpdatePermission(),
			'add' => Permissions\Payment::checkCreatePermission(),
		];
	}
}
