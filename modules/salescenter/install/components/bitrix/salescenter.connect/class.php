<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\ImManager;
use Bitrix\SalesCenter\Integration\PullManager;

class CSalesCenterConnectComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			return;
		}

		if(!Driver::getInstance()->isEnabled())
		{
			$this->arResult['isShowFeature'] = true;
			$this->includeComponentTemplate('limit');
			return;
		}

		if(!ImManager::getInstance()->isApplicationInstalled())
		{
			$this->showError(Loc::getMessage('SALESCENTER_IM_APP_ERROR'));
			return;
		}

		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult = \Bitrix\SalesCenter\Driver::getInstance()->getManagerParams();
		$this->arResult['withRedirect'] = (bool)$this->arParams['withRedirect'];
		$context = $this->arParams['context'];
		$type = 'payments_chat';
		if(!empty($context) && is_string($context))
		{
			$type = $this->getTypeInfoByContext($context);
		}

		$this->arResult['blocks'] = $this->getBlocks($type);

		$this->includeComponentTemplate();
	}

	protected function showError($error)
	{
		ShowError($error);
	}

	protected function isSmsContext(string $context): bool
	{
		return in_array($context, [
			'sms',
			'salescenter_sms',
			'payments-sms',
		], true);
	}

	protected function getTypeInfoByContext(string $context): string
	{
		if($this->isSmsContext($context))
		{
			return 'payments_sms';
		}
		elseif($context === 'services-chat')
		{
			return 'services-chat';
		}
		elseif($context === 'services-sms')
		{
			return 'services-sms';
		}
		elseif($context === 'consultations')
		{
			return 'consultations';
		}

		return 'payments_chat';
	}

	protected function getBlocks(string $type): array
	{
		$result = [];
		
		if($type === 'payments_chat')
		{
			$result = $this->getPaymentsChatBlocks();
		}
		elseif($type === 'payments_sms')
		{
			$result = $this->getSmsBlocks();
		}
		elseif($type === 'services-chat')
		{
			$result = $this->getServicesChatBlocks();
		}
		elseif($type === 'services-sms')
		{
			$result = $this->getServicesSmsBlocks();
		}
		elseif($type === 'consultations')
		{
			$result = $this->getConsultationsBlocks();
		}
		
		return $result;
	}

	protected function getPaymentsChatBlocks(): array
	{
		$result = [];

		$result[] = [
			'isLogo' => true,
			'logo' => 'logo_payments_chat.svg',
			'title' => 'SALESCENTER_CONNECT_BLOCK_PAYMENTS_CHAT_TITLE',
			'description' => 'SALESCENTER_CONNECT_BLOCK_PAYMENTS_CHAT_DESCRIPTION',
			'links' => [
				[
					'onclick' => 'BX.Salescenter.Manager.openHowItWorks(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_LINK',
				],
				[
					'onclick' => 'BX.Salescenter.Manager.openHowToConfigOpenLines(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_SOCIAL',
				],
			],
		];

		$result[] = [
			'title' => 'SALESCENTER_CONNECT_TEMPLATE_HOW',
			'description' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_DESCRIPTION',
			'image' => 'preview',
		];

		return $result;
	}

	protected function getSmsBlocks(): array
	{
		$result = [];

		$result[] = [
			'isLogo' => true,
			'logo' => 'logo_payments_sms.svg',
			'logoColor' => 'EF678B',
			'title' => 'SALESCENTER_CONNECT_BLOCK_PAYMENTS_SMS_TITLE',
			'description' => 'SALESCENTER_CONNECT_BLOCK_PAYMENTS_SMS_DESCRIPTION',
			'links' => [
				[
					'onclick' => 'BX.Salescenter.Manager.openHowSmsWorks(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_LINK_SMS',
				],
			],
		];

		$result[] = [
			'title' => 'SALESCENTER_CONNECT_TEMPLATE_HOW',
			'description' => 'SALESCENTER_CONNECT_BLOCK_PAYMENTS_SMS_HOW_DESCRIPTION',
			'image' => 'sms.png',
		];

		$result[] = [
			'title' => 'SALESCENTER_CONNECT_TEMPLATE_TITLE',
			'description' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_DESCRIPTION',
			'image' => 'preview',
		];

		return $result;
	}

	protected function getServicesChatBlocks(): array
	{
		$result = [];

		$result[] = [
			'isLogo' => true,
			'logo' => 'logo_services_chat.svg',
			'logoColor' => 'FEA800',
			'title' => 'SALESCENTER_CONNECT_BLOCK_SERVICES_CHAT_TITLE',
			'description' => 'SALESCENTER_CONNECT_BLOCK_SERVICES_CHAT_DESCRIPTION',
			'links' => [
				[
					'onclick' => 'BX.Salescenter.Manager.openHowItWorks(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_LINK',
				],
				[
					'onclick' => 'BX.Salescenter.Manager.openHowToConfigOpenLines(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_SOCIAL',
				],
			],
		];

		$result[] = [
			'title' => 'SALESCENTER_CONNECT_TEMPLATE_HOW',
			'description' => 'SALESCENTER_CONNECT_BLOCK_SERVICES_CHAT_HOW_DESCRIPTION',
			'image' => 'preview',
		];

		return $result;
	}

	protected function getServicesSmsBlocks(): array
	{
		$result = [];

		$result[] = [
			'isLogo' => true,
			'logo' => 'logo_services_sms.svg',
			'logoColor' => '2DC5F5',
			'title' => 'SALESCENTER_CONNECT_BLOCK_SERVICES_SMS_TITLE',
			'description' => 'SALESCENTER_CONNECT_BLOCK_SERVICES_SMS_DESCRIPTION',
			'links' => [
				[
					'onclick' => 'BX.Salescenter.Manager.openHowSmsWorks(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_LINK_SMS',
				],
			],
		];

		$result[] = [
			'title' => 'SALESCENTER_CONNECT_TEMPLATE_HOW',
			'description' => 'SALESCENTER_CONNECT_BLOCK_SERVICES_SMS_HOW_DESCRIPTION',
			'image' => 'sms.png',
		];

		return $result;
	}

	protected function getConsultationsBlocks(): array
	{
		$result = [];

		$result[] = [
			'isLogo' => true,
			'logo' => 'logo_consultations.svg',
			'logoColor' => '9BCD00',
			'title' => 'SALESCENTER_CONNECT_BLOCK_CONSULTATIONS_TITLE',
			'description' => 'SALESCENTER_CONNECT_BLOCK_CONSULTATIONS_DESCRIPTION',
			'links' => [
				[
					'onclick' => 'BX.Salescenter.Manager.openHowItWorks(event);',
					'text' => 'SALESCENTER_CONNECT_TEMPLATE_HOW_LINK_SMS',
				],
			],
		];

		$result[] = [
			'title' => 'SALESCENTER_CONNECT_TEMPLATE_HOW',
			'description' => 'SALESCENTER_CONNECT_BLOCK_CONSULTATIONS_HOW_DESCRIPTION',
			'image' => 'preview',
		];

		return $result;
	}
}