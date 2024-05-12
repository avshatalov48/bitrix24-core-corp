<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Terminal\PullManager;
use Bitrix\CrmMobile\Integration\Sale\Payment\DtoItem;
use Bitrix\CrmMobile\Integration\Sale\Payment\DtoItemData;
use Bitrix\CrmMobile\Integration\Sale\Payment\DtoItemDataConverter;
use Bitrix\CrmMobile\Integration\Sale\Payment\EntityEditorFieldsProvider;
use Bitrix\CrmMobile\Terminal\ListSearchPreset;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Crm\Order\Permissions;

class GetPaymentListAction extends Action
{
	final public function run(PageNavigation $pageNavigation, array $extra = []): array
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

		$filter = [];
		if (isset($extra['filterParams']['ID']) && is_array($extra['filterParams']['ID']))
		{
			$filter['@ID'] = $extra['filterParams']['ID'];
		}

		$presetId = $extra['presetId'] ?? null;
		if ($presetId === ListSearchPreset::FILTER_MY)
		{
			$filter['RESPONSIBLE_ID'] = (int)$this->getCurrentUser()->getId();
		}

		$search = isset($extra['search']) ? trim($extra['search']) : '';
		if ($search)
		{
			$filter[] = [
				'LOGIC' => 'OR',
				['ACCOUNT_NUMBER' => '%' . $search . '%'],
				['*ORDER.SEARCH_CONTENT' => $search]
			];
		}

		$paymentList = PaymentRepository::getInstance()->getList([
			'select' => ['ID', 'ORDER_ID'],
			'filter' => $filter,
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'order' => ['ID' => 'DESC'],
			'runtime' => [
				Container::getInstance()->getTerminalPaymentService()->getRuntimeReferenceField()
			],
		]);
		foreach ($paymentList as $payment)
		{
			$itemData = DtoItemDataConverter::convert($payment);

			$resultItem = DtoItem::make([
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
