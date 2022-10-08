<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Automation\QR;
use Bitrix\UI\Barcode\Barcode;

class CrmAutomationPubQrComponent extends \CBitrixComponent implements
	Main\Engine\Contract\Controllerable,
	Main\Errorable
{
	/** @var ErrorCollection */
	protected $errorCollection;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new ErrorCollection();
	}

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code): ?Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		$configureActions = [];
		$configureActions['complete'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Authentication::class,
			],
		];

		return $configureActions;
	}

	protected function listKeysSignedParameters()
	{
		return ['QR_ID'];
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		$qrCode = $this->getQr($this->arParams['QR_ID']);

		if (!$qrCode)
		{
			ShowError(Loc::getMessage('CRM_AUTOMATION_QR_NOT_FOUND'));
			return;
		}

		if ($this->arParams['VIEW'] === 'code')
		{
			return $this->showCode($qrCode);
		}

		$this->arResult['QR'] = $qrCode->collectValues();

		$this->includeComponentTemplate();
	}

	public function completeAction()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return;
		}

		$qrCode = $this->getQr($this->arParams['QR_ID']);

		if (!$qrCode)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_AUTOMATION_QR_NOT_FOUND'));

			return;
		}

		if ($qrCode->getId() !== 'test')
		{
			$result = \Bitrix\Crm\Automation\Trigger\QrTrigger::execute(
				[
					[
						'OWNER_TYPE_ID' => $qrCode->getEntityTypeId(),
						'OWNER_ID' => $qrCode->getEntityId(),
					],
				],
				[
					'id' => $qrCode->getId(),
					'ownerId' => $qrCode->getOwnerId(),
				]
			);

			return true;
		}
	}

	protected function showCode($qrCode)
	{
		Main\Loader::includeModule('ui');

		$page = $this->getUri() . '?' . $qrCode->getId();

		$content = (new Barcode())
			->option('w', 300)
			->option('h', 300)
			->render($page);
		$this->arResult['codeContent'] = $content;

		$this->includeComponentTemplate('code');
	}

	protected function getQr($id): ?Main\ORM\Objectify\EntityObject
	{
		if ($id === 'test')
		{
			return $this->createTestQr();
		}
		elseif ($id)
		{
			return QR\QrTable::getById($id)->fetchObject();
		}

		return null;
	}

	protected function createTestQr()
	{
		$user = Main\Engine\CurrentUser::get();

		$qrCode = QR\QrTable::createObject();
		$qrCode->setId('test');
		$qrCode->setDescription(Loc::getMessage(
			'CRM_AUTOMATION_QR_DEFAULT_DESCRIPTION',
			['#CONTACT_NAME#' => $user->getFirstName()]
		));

		return $qrCode;
	}

	protected function getUri(): string
	{
		$hostUrl = Main\Engine\UrlManager::getInstance()->getHostUrl();
		$path = '/pub/crm/qr/';

		return $hostUrl . $path;
	}

	public function convertBBtoText(string $text): string
	{
		$textParser = new CTextParser();
		$textParser->allow = [
			'HTML' => 'N',
			'USER' => 'N',
			'ANCHOR' => 'Y',
			'BIU' => 'Y',
			'IMG' => 'Y',
			'QUOTE' => 'N',
			'CODE' => 'N',
			'FONT' => 'Y',
			'LIST' => 'Y',
			'SMILES' => 'N',
			'NL2BR' => 'Y',
			'VIDEO' => 'N',
			'TABLE' => 'N',
			'CUT_ANCHOR' => 'N',
			'ALIGN' => 'N'
		];

		return $textParser->convertText(strip_tags($text));
	}
}
