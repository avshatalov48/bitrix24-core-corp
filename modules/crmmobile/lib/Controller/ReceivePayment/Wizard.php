<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\ProductGridDocumentQuery;
use Bitrix\CrmMobile\ProductGrid\ProductGridReceivePaymentQuery;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\SalesCenter\Component\ReceivePaymentHelper;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use CIntranetUtils;

Loader::requireModule('salescenter');

class Wizard extends Base
{
	public function configureActions()
	{
		return [
			'initializeAction' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function initializeAction(Item $entity, array $resendData = []): array
	{
		return [
			'steps' => [
				'product' => $this->getProductStepProps($entity, $resendData),
				'paySystems' => $this->getPaySystemStepProps(),
				'sendMessage' => $this->getSendMessageStepProps($entity),
			],
		];
	}

	private function getProductStepProps(Item $entity, array $resendData): array
	{
		if (isset($resendData['resendMessageMode']) && $resendData['resendMessageMode'] === true)
		{
			$grid = (new ProductGridDocumentQuery($resendData['documentId']))->execute();
		}
		else
		{
			$grid = (new ProductGridReceivePaymentQuery($entity))->execute();
		}

		return [
			'grid' => $grid,
		];
	}

	private function getPaySystemStepProps(): array
	{
		return [
			'paymentSystemList' => $this->getPaymentSystemList(),
			'cashboxList' => $this->getCashboxList(),
			'isCashboxEnabled' => \Bitrix\SalesCenter\Driver::getInstance()->isCashboxEnabled(),
			'isNeedToSkipPaymentSystems' => $this->getIsNeedToSkipPaymentSystems(),
			'isYookassaAvailable' => $this->isYookassaAvailable(),
		];
	}

	private function getPaymentSystemList(): array
	{
		$paymentSystemList = SaleManager::getInstance()->getPaySystemList([
			'!=ACTION_FILE' => [
				'inner',
				'cash',
			],
		]);
		\Bitrix\Main\Type\Collection::sortByColumn($paymentSystemList, ['SORT' => SORT_ASC]);

		return $paymentSystemList;
	}

	private function getCashboxList(): array
	{
		return array_values(SaleManager::getInstance()->getCashboxList());
	}

	private function getIsNeedToSkipPaymentSystems(): bool
	{
		return \CUserOptions::GetOption(
			'salescenter',
			'is_need_to_skip_payment_systems',
		);
	}

	private function isYookassaAvailable(): bool
	{
		$licensePrefix = Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : '';
		$portalZone = Loader::includeModule('intranet') ? CIntranetUtils::getPortalZone() : '';

		if (Loader::includeModule('bitrix24'))
		{
			if ($licensePrefix !== 'ru')
			{
				return false;
			}
		}
		elseif (Loader::includeModule('intranet') && $portalZone !== 'ru')
		{
			return false;
		}

		return true;
	}

	private function getSendMessageStepProps(Item $entity): array
	{
		$entityResponsible = [
			'name' => '',
			'photo' => '',
		];

		$userTableResult = \CUser::GetList(
			'ID',
			'ASC',
			['ID' => $entity->getAssignedById()],
			['FIELDS' => ['ID', 'PERSONAL_PHOTO', 'NAME']]
		);
		if ($user = $userTableResult->Fetch())
		{
			$entityResponsible['name'] = $user['NAME'];

			$fileInfo = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'] ?? '',
				['width' => 40, 'height' => 40],
				BX_RESIZE_IMAGE_EXACT,
				true,
				false,
				true
			);

			if (is_array($fileInfo) && isset($fileInfo['src']))
			{
				$entityResponsible['photo'] = $fileInfo['src'];
			}
		}

		$currentSender = \Bitrix\Crm\MessageSender\SenderPicker::getCurrentSender();
		$currentSenderCode = $currentSender ? $currentSender::getSenderCode() : '';

		return [
			'contactPhone' => CrmManager::getInstance()->getItemContactPhoneFormatted($entity),
			'entityResponsible' => $entityResponsible,
			'orderPublicUrl' => UrlManager::getInstance()->getHostUrl() . '/',
			'currentSenderCode' => $currentSenderCode,
			'senders' => ReceivePaymentHelper::getSendersData(),
			'sendingMethod' => 'sms',
			'sendingMethodDesc' => ReceivePaymentHelper::getSendingMethodDescByType('sms', 'create'),
		];
	}
}
