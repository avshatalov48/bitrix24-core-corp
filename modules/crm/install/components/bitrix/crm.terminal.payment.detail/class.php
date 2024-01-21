<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;
use Bitrix\SalesCenter;

Main\Loader::includeModule('crm');

class CrmTerminalPaymentDetail extends \CBitrixComponent
{
	private const PATH_TO_USER_PROFILE_DEFAULT = '/company/personal/user/#USER_ID#/';

	private Main\ErrorCollection $errorCollection;
	private ?Crm\Order\Payment $payment = null;
	private $userPermissions;

	public function onPrepareComponentParams($arParams)
	{
		$this->errorCollection = new Main\ErrorCollection();
		$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		$arParams['ID'] =
			isset($arParams['ID']) && (int)$arParams['ID'] > 0
			? (int)$arParams['ID']
			: null
		;

		if (!$arParams['ID'])
		{
			$this->errorCollection->setError(
				new Main\Error(
					Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_ERROR_ID_NOT_FOUND')
				)
			);
		}

		return parent::onPrepareComponentParams($arParams);
	}

	private function initResult(): void
	{
		$this->arResult = [
			'ACCOUNT_NUMBER' => '',
			'ENTITY_FIELDS' => [],
			'ENTITY_CONFIG' => [],
			'ENTITY_DATA' => [],
			'MARKED_MESSAGES' => [],
			'ERROR_MESSAGES' => [],
		];
	}

	private function prepareFormData(): void
	{
		$fields = $this->getFields();

		$this->arResult['ENTITY_FIELDS'] = $this->getEntityFields($fields);
		$this->arResult['ENTITY_CONFIG'] = $this->getEntityConfig($fields);
		$this->arResult['ENTITY_DATA'] = $this->getEntityData();
		$this->arResult['MARKED_MESSAGES'] = $this->getMarkedMessages();
	}

	private function getFields(): array
	{
		return [
			'MAIN' => [
				'ITEMS' => [
					'SUM' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_SUM'),
						'TYPE' => 'html',
					],
					'PAID' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PAID'),
						'TYPE' => 'html',
					],
					'DATE_PAID' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_DATE_PAID'),
						'TYPE' => 'text',
					],
					'CLIENT' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_CLIENT'),
						'TYPE' => 'client_light',
					],
					'PHONE' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PHONE'),
						'TYPE' => 'text',
					],
					'PAY_SYSTEM_NAME' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PAY_SYSTEM_NAME'),
						'TYPE' => 'text',
					],
					'SLIP_LINK' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_SLIP_LINK'),
						'TYPE' => 'html',
					],
					'RESPONSIBLE_ID' =>
					[
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_RESPONSIBLE_ID'),
						'TYPE' => 'user',
					],
				],
			],
			'PS' => [
				'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_SECTION_PS'),
				'ITEMS' => [
					'PS_STATUS_CODE' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PS_STATUS_CODE'),
						'TYPE' => 'text',
					],
					'PS_INVOICE_ID' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PS_INVOICE_ID'),
						'TYPE' => 'text',
					],
					'PS_SUM' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PS_SUM'),
						'TYPE' => 'html',
					],
					'PS_RESPONSE_DATE' => [
						'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_PS_RESPONSE_DATE'),
						'TYPE' => 'text',
					],
				],
			],
		];
	}

	private function getEntityFields(array $fields): array
	{
		$result = [];

		foreach ($fields as $value)
		{
			foreach ($value['ITEMS'] as $name => $item)
			{
				if ($item['TYPE'] === 'text' || $item['TYPE'] === 'html')
				{
					$result[] = [
						'id' => $name,
						'title' => $item['NAME'],
						'name' => $name,
						'type' => $item['TYPE'],
						'editable' => false,
						'optionFlags' => 1,
					];
				}
			}
		}

		$categoryParams = [
			\CCrmOwnerType::Company => Crm\Integration\Catalog\Contractor\CategoryRepository::getIdByEntityTypeId(\CCrmOwnerType::Company) ?? 0,
			\CCrmOwnerType::Contact => Crm\Integration\Catalog\Contractor\CategoryRepository::getIdByEntityTypeId(\CCrmOwnerType::Contact) ?? 0,
		];
		$result[] = [
			'name' => 'CLIENT',
			'title' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_CLIENT'),
			'type' => 'client_light',
			'editable' => false,
			'data' => [
				'categoryParams' => $categoryParams,
				'map' => ['data' => 'CLIENT'],
				'info' => 'CLIENT_INFO',
				'clientEditorFieldsParams' => \CCrmComponentHelper::prepareClientEditorFieldsParams(
					['categoryParams' => $categoryParams]
				),
			]
		];

		$result[] = [
			'name' => 'RESPONSIBLE_ID',
			'title' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_FIELD_RESPONSIBLE_ID'),
			'type' => 'user',
			'editable' => false,
			'data' => [
				'enableEditInView' => false,
				'formated' => 'RESPONSIBLE_FORMATTED_NAME',
				'position' => 'RESPONSIBLE_WORK_POSITION',
				'photoUrl' => 'RESPONSIBLE_PHOTO_URL',
				'showUrl' => 'PATH_TO_RESPONSIBLE_USER',
				'pathToProfile' => \CrmCheckPath(
					'PATH_TO_USER_PROFILE',
					$this->getUserPersonalUrlTemplate(),
					self::PATH_TO_USER_PROFILE_DEFAULT
				),
			],
		];

		return $result;
	}

	private function getEntityConfig(array $fields): array
	{
		$result = [];

		foreach ($fields as $section => $sectionData)
		{
			$elements = [];
			foreach (array_keys($sectionData['ITEMS']) as $fieldName)
			{
				$elements[] = ['name' => $fieldName];
			}

			$result[] = [
				'title' => $sectionData['NAME'] ?? '',
				'enableTitle' => !empty($sectionData['NAME']),
				'name' => $section,
				'type' => 'section',
				'elements' => $elements,
				'data' => [
					'isChangeable' => false,
					'isRemovable' => false,
				],
			];
		}

		return $result;
	}

	private function getEntityData(): array
	{
		$paymentFields = $this->payment->getFields();

		$result = [
			'SUM' => \CCurrencyLang::CurrencyFormat($paymentFields['SUM'], $paymentFields['CURRENCY']),
			'PAID' => $this->getPaidData(),
			'DATE_PAID' => ($paymentFields['DATE_PAID'] instanceof Main\Type\DateTime) ? $paymentFields['DATE_PAID']->toString() : '',
			'PAY_SYSTEM_NAME' => htmlspecialcharsbx($this->getPaySystemName()),
			'PS_STATUS_CODE' => htmlspecialcharsbx($paymentFields['PS_STATUS_CODE']),
			'PS_INVOICE_ID' => htmlspecialcharsbx($paymentFields['PS_INVOICE_ID']),
			'PS_SUM' => \CCurrencyLang::CurrencyFormat($paymentFields['PS_SUM'], $paymentFields['PS_CURRENCY']),
			'PS_RESPONSE_DATE' => ($paymentFields['PS_RESPONSE_DATE'] instanceof Main\Type\DateTime) ? $paymentFields['PS_RESPONSE_DATE']->toString() : '',
			'PS_CARD_NUMBER' => htmlspecialcharsbx($paymentFields['PS_CARD_NUMBER']),
			'SLIP_LINK' => $this->getSlimCheckLink(),
			'PHONE' => htmlspecialcharsbx(Crm\Terminal\OrderProperty::getTerminalPhoneValue($this->payment->getOrder())) ?? '',
			'RESPONSIBLE_ID' => $paymentFields['RESPONSIBLE_ID'],
		];

		$responsibleUserFields = $this->getUserDataToEntity($paymentFields['RESPONSIBLE_ID']);

		$result = array_merge($result, $this->getClientData(), $responsibleUserFields);

		return $result;
	}

	protected function getClientData(): array
	{
		$result = [];

		$companyId = 0;
		$contactIds = [];

		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($this->arParams['ID']);
		$order = $payment->getOrder();

		$clientCollection = $order->getContactCompanyCollection();
		if ($clientCollection && !$clientCollection->isEmpty())
		{
			/** @var Crm\Order\Company $company */
			if ($company = $clientCollection->getPrimaryCompany())
			{
				$companyId = $company->getField('ENTITY_ID');
			}

			$contacts = $clientCollection->getContacts();
			/** @var Crm\Order\Contact $contact */
			foreach ($contacts as $contact)
			{
				$contactIds[] = $contact->getField('ENTITY_ID');
			}
		}

		if (!empty($companyId) || !empty($contactIds))
		{
			$result['CLIENT'] = [
				'COMPANY_ID' => $companyId,
				'CONTACT_IDS' => $contactIds,
			];
		}

		$clientInfo = [
			'COMPANY_DATA' => [],
			'CONTACT_DATA' => [],
		];

		if ($companyId > 0)
		{
			\CCrmComponentHelper::prepareMultifieldData(
				\CCrmOwnerType::Company,
				[$companyId],
				[
					'PHONE',
					'EMAIL',
				],
				$result
			);
			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyId, $this->userPermissions);
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				\CCrmOwnerType::CompanyName,
				$companyId,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => Crm\Format\PersonNameFormatter::getFormat(),
				]
			);

			$clientInfo['COMPANY_DATA'] = [$companyInfo];
		}

		$iteration= 0;
		\CCrmComponentHelper::prepareMultifieldData(
			\CCrmOwnerType::Contact,
			$contactIds,
			[
				'PHONE',
				'EMAIL',
			],
			$result
		);
		foreach ($contactIds as $contactID)
		{
			$isEntityReadPermitted = \CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['CONTACT_DATA'][] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				\CCrmOwnerType::ContactName,
				$contactID,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => ($iteration === 0),
					'REQUIRE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => Crm\Format\PersonNameFormatter::getFormat(),
					'NORMALIZE_MULTIFIELDS' => true,
				]
			);
			$iteration++;
		}

		$result['CLIENT_INFO'] = $clientInfo;

		return $result;
	}

	private function getMarkedMessages(): array
	{
		$result = [];

		if (!$this->payment->isMarked())
		{
			return $result;
		}

		$entityMarkerIterator = Sale\EntityMarker::getList([
			'select' => ['MESSAGE'],
			'filter' => [
				'=ENTITY_ID' => $this->payment->getId(),
				'=ENTITY_TYPE' => Sale\EntityMarker::ENTITY_TYPE_PAYMENT,
				'!=SUCCESS' => Sale\EntityMarker::ENTITY_SUCCESS_CODE_DONE
			],
			'order' => ['ID' => 'ASC']
		]);
		while ($entityMarker = $entityMarkerIterator->fetch())
		{
			$result[] = $entityMarker['MESSAGE'];
		}

		return $result;
	}

	private function getPaySystemName(): string
	{
		if (
			$this->payment->getPaymentSystemId() === (int)Sale\PaySystem\Manager::getInnerPaySystemId()
			|| $this->payment->getPaySystem()?->getField('ACTION_FILE') === 'cash'
		)
		{
			return '';
		}

		return $this->payment->getPaymentSystemName();
	}

	private function getSlimCheckLink(): string
	{
		$link = '';

		if ($this->isPaid() && Main\Loader::includeModule('salescenter'))
		{
			$link = SalesCenter\Component\PaymentSlip::getLink($this->payment->getId());
		}

		if ($link)
		{
			$request = Main\Application::getInstance()->getContext()->getRequest();
			$host = $request->isHttps() ? 'https' : 'http';

			$link = new Main\Web\Uri($host . '://' . $request->getHttpHost() . $link);

			$link = '<a href="' . $link . '">' . Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_SLIP_LINK_OPEN') . '</a>';
		}

		return $link;
	}

	private function getPaidData(): string
	{
		if ($this->isPaid())
		{
			return '<span class="ui-label ui-label-lightgreen ui-label-fill">'
				. '<span class="ui-label-inner">'
				. Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_PAYMENT_PAID_Y')
				. '</span>'
				. '</span>'
			;
		}

		if ($this->payment->isMarked())
		{
			return '<span class="ui-label ui-label-danger ui-label-fill">'
				. '<span class="ui-label-inner">'
				. Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_PAYMENT_PAID_N_M')
				. '</span>'
				. '</span>'
			;
		}

		return '<span class="ui-label ui-label-lightorange ui-label-fill">'
			. '<span class="ui-label-inner">'
			. Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_PAYMENT_PAID_N')
			. '</span>'
			. '</span>'
		;
	}

	private function isPaid(): bool
	{
		return $this->payment->isPaid();
	}

	protected function getUserDataToEntity(int $userId): array
	{
		$result = [];

		$user = Crm\Service\Container::getInstance()->getUserBroker()->getById($userId);
		if (is_array($user))
		{
			$result['RESPONSIBLE_LOGIN'] = $user['LOGIN'];
			$result['RESPONSIBLE_NAME'] = $user['NAME'] ?? '';
			$result['RESPONSIBLE_SECOND_NAME'] = $user['SECOND_NAME'] ?? '';
			$result['RESPONSIBLE_LAST_NAME'] = $user['LAST_NAME'] ?? '';
			$result['RESPONSIBLE_PERSONAL_PHOTO'] = $user['PERSONAL_PHOTO'] ?? '';
		}

		$result['RESPONSIBLE_FORMATTED_NAME'] =
			\CUser::FormatName(
				\CSite::GetNameFormat(false),
				[
					'LOGIN' => $result['RESPONSIBLE_LOGIN'],
					'NAME' => $result['RESPONSIBLE_NAME'],
					'LAST_NAME' => $result['RESPONSIBLE_LAST_NAME'],
					'SECOND_NAME' => $result['RESPONSIBLE_SECOND_NAME'],
				],
				true,
				false
			)
		;

		$photoId = isset($result['RESPONSIBLE_PERSONAL_PHOTO'])
			? (int)$result['RESPONSIBLE_PERSONAL_PHOTO']
			: 0
		;

		if ($photoId > 0)
		{
			$fileInfo = \CFile::ResizeImageGet(
				$photoId,
				[
					'width' => 60,
					'height'=> 60,
				],
				BX_RESIZE_IMAGE_EXACT
			);
			if (is_array($fileInfo) && isset($fileInfo['src']))
			{
				$result['RESPONSIBLE_PHOTO_URL'] = $fileInfo['src'];
			}
		}

		$pathToProfile = \CrmCheckPath(
			'PATH_TO_USER_PROFILE',
			$this->getUserPersonalUrlTemplate(),
			self::PATH_TO_USER_PROFILE_DEFAULT
		);

		$result['PATH_TO_RESPONSIBLE_USER'] =
			\CComponentEngine::MakePathFromTemplate(
				$pathToProfile,
				[
					'USER_ID' => $userId,
				]
		);

		return $result;
	}

	private function getUserPersonalUrlTemplate(): string
	{
		return Main\Config\Option::get('intranet', 'path_user', self::PATH_TO_USER_PROFILE_DEFAULT, $this->getSiteId());
	}

	private function loadPayment(): void
	{
		$this->payment = Sale\Repository\PaymentRepository::getInstance()->getById($this->arParams['ID']);

		if ($this->payment)
		{
			$this->arResult['ACCOUNT_NUMBER'] = $this->payment->getField('ACCOUNT_NUMBER');
		}
		else
		{
			$this->errorCollection->setError(
				new Main\Error(
					Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_ERROR_PAYMENT_NOT_FOUND')
				)
			);
		}
	}

	private function prepareErrors(): void
	{
		/** @var Main\Error $error */
		foreach ($this->errorCollection as $error)
		{
			$this->arResult['ERROR_MESSAGES'][] = $error->getMessage();
		}
	}

	private function checkPermission(int $paymentId): bool
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!Crm\Order\Permissions\Payment::checkReadPermission($paymentId, $userPermissions))
		{
			$this->arResult['ERROR_MESSAGES'][] = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_PERMISSION_DENIED');
			return false;
		}

		return true;
	}

	public function executeComponent()
	{
		$this->initResult();

		$this->arResult['TOOLBAR_ID'] = 'uiToolbarContainer';

		$this->loadPayment();
		if ($this->payment)
		{
			if ($this->checkPermission($this->payment->getId()))
			{
				$this->prepareFormData();
			}
		}

		$this->prepareErrors();

		$this->includeComponentTemplate();
	}
}