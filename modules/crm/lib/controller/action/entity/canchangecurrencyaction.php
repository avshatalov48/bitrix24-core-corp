<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm\Entity\PaymentDocumentsRepository;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use CCrmOwnerType;

/**
 * Checking whether it is possible to change the currency of the entity.
 *
 * For example:
 * ```js
 * BX.ajax.runAction("crm.api.entity.canChangeCurrency", { data: { entityId, ?entityType, ?entityTypeId } });
 * ```
 */
class CanChangeCurrencyAction extends Action
{
	/**
	 * Run action.
	 *
	 * If not setted `entityTypeId`, entity id resolved from `entityType` parameter.
	 *
	 * @param int $entityId
	 * @param int|null $entityTypeId
	 * @param string|null $entityType
	 *
	 * @return void
	 */
	public function run(int $entityId, ?int $entityTypeId = null, ?string $entityType = null)
	{
		if (!isset($entityTypeId))
		{
			$entityTypeId = CCrmOwnerType::ResolveID($entityType);
		}

		if(!EntityAuthorization::checkReadPermission($entityTypeId, $entityId))
		{
			$this->addError(new Error('Access denied.'));
			return null;
		}

		if ($this->existPaymentAndDeliveryDocuments($entityId, $entityTypeId))
		{
			return false;
		}

		return true;
	}

	/**
	 * Checking whether the entity has payment documents.
	 *
	 * @param int $entityId
	 * @param int $entityTypeId
	 *
	 * @return bool
	 */
	private function existPaymentAndDeliveryDocuments(int $entityId, int $entityTypeId): bool
	{
		/**
		 * @var PaymentDocumentsRepository $repository
		 */
		$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		$result = $repository->getDocumentsForEntity($entityTypeId, $entityId);
		if ($result->isSuccess())
		{
			$documents = $result->getData()['DOCUMENTS'] ?? [];
			return !empty($documents);
		}

		return false;
	}
}
