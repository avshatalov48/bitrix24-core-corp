<?php

namespace Bitrix\Crm\UI\Barcode;

use Bitrix\Crm\UI\Barcode\Payment\TransactionData;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\UI\Barcode\Barcode;
use Bitrix\UI\Barcode\BarcodeDictionary;
use Bitrix\UI\Barcode\DataGenerator\FinancialTransactionsRu;

final class Payment
{
	public const ERROR_CODE_API_NOT_AVAILABLE = 'CRM_BARCODE_PAYMENT_API_NOT_AVAILABLE';

	private const TMP_DIR_NAME = 'crm_payment_qr';

	/** @var TransactionData */
	private $data;

	/** @var string|null */
	private $pathToTmpFile;

	/** @var Result|null */
	private $initResult;

	//todo if we add new countries, add strategies for each country and replace direct usage of FinancialTransactionsRu
	/** @var FinancialTransactionsRu|null */
	private $dataGenerator;
	/** @var Barcode|null */
	private $barcode;

	public function __construct(TransactionData $data)
	{
		$this->data = $data;
	}

	public function validate(): Result
	{
		$initResult = $this->init();
		if (!$initResult->isSuccess())
		{
			return $initResult;
		}

		return $this->dataGenerator->validate();
	}

	private function init(): Result
	{
		if ($this->initResult)
		{
			return $this->initResult;
		}

		$this->initResult = $this->initDataGenerator();
		if (!$this->initResult->isSuccess())
		{
			return $this->initResult;
		}

		if (!static::isBarcodeApiAvailable())
		{
			return $this->initResult->addError(
				new Error('UI Barcode API is not available', static::ERROR_CODE_API_NOT_AVAILABLE)
			);
		}

		$this->barcode = new Barcode();
		$this->barcode->type(BarcodeDictionary::TYPE_QR);
		$this->barcode->format(BarcodeDictionary::FORMAT_PNG);

		return $this->initResult;
	}

	private function initDataGenerator(): Result
	{
		$result = new Result();

		if (!static::isBarcodeApiAvailable())
		{
			return $result->addError(
				new Error('UI Barcode API is not available', static::ERROR_CODE_API_NOT_AVAILABLE)
			);
		}

		$this->dataGenerator = new FinancialTransactionsRu();
		$this->fillDataGenerator($this->dataGenerator, $this->data);

		return $result;
	}

	private static function isBarcodeApiAvailable(): bool
	{
		return (
			Loader::includeModule('ui')
			&& class_exists('\Bitrix\UI\Barcode\Barcode')
			&& class_exists('\Bitrix\UI\Barcode\BarcodeDictionary')
			&& class_exists('\Bitrix\UI\Barcode\DataGenerator\FinancialTransactionsRu')
		);
	}

	private function fillDataGenerator(
		FinancialTransactionsRu $dataGenerator,
		TransactionData $data
	): void
	{
		$receiverData = $data->getReceiverData();
		$senderData = $data->getSenderData();

		$sum = $data->getSum();
		if (!is_null($sum))
		{
			//sum should be in kopeika's
			$sum = (int)($sum * 100);
		}

		$dataGenerator->setFields([
			//receiver info
			FinancialTransactionsRu::FIELD_NAME => $receiverData->getName(),
			FinancialTransactionsRu::FIELD_PAYEE_INN => $receiverData->getInn(),
			FinancialTransactionsRu::FIELD_PERSONAL_ACCOUNT => $receiverData->getAccountNumber(),
			FinancialTransactionsRu::FIELD_BANK_NAME => $receiverData->getBankName(),
			FinancialTransactionsRu::FIELD_BIC => $receiverData->getBic(),
			FinancialTransactionsRu::FIELD_CORRESPONDENT_ACCOUNT => $receiverData->getCorrAccountNumber(),
			FinancialTransactionsRu::FIELD_KPP => $receiverData->getKpp(),

			//general transaction info
			FinancialTransactionsRu::FIELD_SUM => $sum,

			//sender info
			FinancialTransactionsRu::FIELD_PAYER_INN => $senderData->getInn(),
		]);
	}

	/**
	 * @return string|false
	 */
	public function render()
	{
		if ($this->validate()->isSuccess())
		{
			$data = $this->dataGenerator->getData();

			return $this->barcode->render($data);
		}

		return false;
	}

	public function isSavedTemporary(): bool
	{
		return !empty($this->pathToTmpFile);
	}

	/**
	 * Renders and saves the barcode to a temporary file
	 *
	 * @param int $hoursToKeep
	 * @return string|null - path to temporary file or null on failure
	 * @throws InvalidOperationException
	 */
	public function saveToTemporaryFile(int $hoursToKeep = 1): ?string
	{
		if ($this->isSavedTemporary())
		{
			throw new InvalidOperationException('This payment barcode is already saved');
		}

		if (!$this->validate()->isSuccess())
		{
			return null;
		}

		$tmpDirName = \CTempFile::GetDirectoryName($hoursToKeep, static::TMP_DIR_NAME);

		// maybe you should the file change extension if you change barcode format
		/** @see Barcode::format() */
		$tmpFileName = Path::combine($tmpDirName, Random::getString(5) . '.png');
		if (!Path::validate($tmpFileName))
		{
			return null;
		}

		$tmpFile = new File($tmpFileName);

		$writeResult = $tmpFile->putContents($this->render());
		if ($writeResult === false)
		{
			return null;
		}

		$this->pathToTmpFile = $tmpFile->getPath();

		return $this->pathToTmpFile;
	}
}
