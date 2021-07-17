<?
namespace Bitrix\Crm\Order\Builder;

use Bitrix\Sale\Helpers\Order\Builder\BasketBuilder;

/**
 * Class BasketBuilderCrm
 * @package Bitrix\Crm\Order\Builder
 */
class BasketBuilderCrm extends BasketBuilder
{
	public function initBasket()
	{
		$this->formData = $this->builder->getFormData();
		$orderId = $this->formData['ID'];
		$this->delegate = $this->getDelegate($orderId);
		if ((int)$orderId > 0)
		{
			$changedProducts = $this->formData['PRODUCT'];
			$basket = $this->getBasket();
			/** @var \Bitrix\Sale\BasketItem $basketItem */
			foreach ($basket as $basketItem)
			{
				$basketItemId = $basketItem->getBasketCode();
				if (is_array($this->formData['DELETED_PRODUCT_IDS']) && in_array($basketItemId, $this->formData['DELETED_PRODUCT_IDS']))
				{
					continue;
				}
				if (!isset($changedProducts[$basketItemId]))
				{
					$changedProducts[$basketItemId] = $basketItem->getFieldValues();
					$changedProducts[$basketItemId]['OFFER_ID'] = $changedProducts[$basketItemId]['PRODUCT_ID'];
				}
			}
			$this->formData['PRODUCT'] = $changedProducts;
		}
		\Bitrix\Sale\ProviderBase::setUsingTrustData(true);
		return $this;
	}

}