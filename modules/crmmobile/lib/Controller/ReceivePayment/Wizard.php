<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\ProductGrid\ProductGridDocumentQuery;
use Bitrix\CrmMobile\ProductGrid\ProductGridCreatePaymentQuery;
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
				'contact' => $this->getClientStepProps(),
				'product' => $this->getProductStepProps($entity, $resendData),
				'paySystems' => $this->getPaySystemStepProps(),
				'sendMessage' => $this->getSendMessageStepProps($entity),
			],
		];
	}

	private function getClientStepProps()
	{
		return [
			'hasSmsProviders' => SmsManager::isConnected() || NotificationsManager::isConnected(),
			'required' => true,
			'multiple' => false,
			'data' => [
				'compound' => [
					[
						'name' => 'COMPANY_ID',
						'type' => 'company',
						'entityTypeName' => \CCrmOwnerType::CompanyName,
						'tagName' => \CCrmOwnerType::CompanyName,
					],
					[
						'name' => 'CONTACT_IDS',
						'type' => 'multiple_contact',
						'entityTypeName' => \CCrmOwnerType::ContactName,
						'tagName' => \CCrmOwnerType::ContactName,
					],
				],
				'map' => ['data' => 'CLIENT_DATA'],
				'info' => 'CLIENT_INFO',
				'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
				'lastContactInfos' => 'LAST_CONTACT_INFOS',
				'loaders' => [
					'primary' => [
						\CCrmOwnerType::CompanyName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
						],
						\CCrmOwnerType::ContactName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
						],
					],
					'secondary' => [
						\CCrmOwnerType::CompanyName => [
							'action' => 'GET_SECONDARY_ENTITY_INFOS',
							'url' => '/bitrix/components/bitrix/crm.store.document.detail/ajax.php?'.bitrix_sessid_get(),
						],
					],
				],
				'clientEditorFieldsParams' => $this->prepareClientEditorFieldsParams(),
				'useExternalRequisiteBinding' => true,
				'permissions' => $this->getClientPermissions(),
				'hasSolidBorder' => true,
				'showClientAdd' => true,
				'showClientInfo' => true,
				'fixedLayoutType' => 'contact',
				'entityList' => [
					'contact' => [],
					'company' => [],
				],
			],
		];
	}

	private function prepareClientEditorFieldsParams(): array
	{
		$result = [
			\CCrmOwnerType::ContactName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact, 'requisite'),
			],
			\CCrmOwnerType::CompanyName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company, 'requisite'),
			],
		];
		if (\Bitrix\Main\Loader::includeModule('location'))
		{
			$result[\CCrmOwnerType::ContactName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact,'requisite_address');
			$result[\CCrmOwnerType::CompanyName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company,'requisite_address');
		}

		return $result;
	}

	protected function getClientPermissions(): array
	{
		$entityTypeIds = [\CCrmOwnerType::Contact, \CCrmOwnerType::Company];
		$permissions = [];
		foreach ($entityTypeIds as $entityTypeId)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			$serviceUserPermissions = Container::getInstance()->getUserPermissions();
			$permissions[$entityTypeName] = [
				'read' => $serviceUserPermissions->checkReadPermissions($entityTypeId),
				'add' => $serviceUserPermissions->checkAddPermissions($entityTypeId),
			];
		}

		return $permissions;
	}

	private function getProductStepProps(Item $entity, array $resendData): array
	{
		if (isset($resendData['resendMessageMode']) && $resendData['resendMessageMode'] === true)
		{
			$grid = (new ProductGridDocumentQuery($resendData['documentId']))->execute();
		}
		else
		{
			$grid = (new ProductGridCreatePaymentQuery($entity))->execute();
		}

		return [
			'grid' => $grid,
			'hasSmsProviders' => SmsManager::isConnected() || NotificationsManager::isConnected(),
			'requireSmsProvider' => true,
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
