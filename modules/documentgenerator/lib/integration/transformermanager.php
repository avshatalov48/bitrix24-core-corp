<?php

namespace Bitrix\DocumentGenerator\Integration;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Transformer\Command;
use Bitrix\Transformer\DocumentTransformer;
use Bitrix\Transformer\FileTransformer;
use Bitrix\Transformer\InterfaceCallback;

final class TransformerManager implements InterfaceCallback
{
	const QUEUE_NAME = 'documentgenerator_create';
	const PATH = 'documentgenerator_preview';

	public const ERROR_CODE_TRANSFORM_FORMATS_FILLED = 'TRANSFORM_FORMATS_FILLED';
	public const ERROR_CODE_TRANSFORM_FORMATS_PROCESSED = 'TRANSFORM_FORMATS_PROCESSED';

	protected $result;
	protected $document;
	protected $transformInfo;

	/**
	 * Function to process results after transformation.
	 *
	 * @param int $status Status of the command.
	 * @param string $command Name of the command.
	 * @param array $params Input parameters of the command.
	 * @param array $result Result of the command from controller
	 *      Here keys are identifiers to result information. If result is file it will be in 'files' array.
	 *      'files' - array of the files, where key is extension, and value is absolute path to the result file.
	 *
	 * This method should return true on success or string on error.
	 *
	 * @return bool|string
	 */
	public static function call($status, $command, $params, $result = array())
	{
		if(!isset($params['documentId']) || empty($params['documentId']))
		{
			return 'wrong parameters: no documentId';
		}

		if($status != Command::STATUS_UPLOAD && $status != Command::STATUS_ERROR)
		{
			return 'wrong command status';
		}

		$document = Document::loadById($params['documentId']);
		if(!$document)
		{
			static::fireEvent($params['documentId']);
			return 'document '.$params['documentId'].' not found';
		}

		try
		{
			$updatedData = self::updateDocument($document, $result);
		}
		catch (\Throwable $throwable)
		{
			// this exception should never happen normally, it's truly an unexpected error. but sometimes it happens.
			// however, we still should correctly notify observers that the transformation has completed

			Application::getInstance()->getExceptionHandler()->writeToLog($throwable);

			$updatedData = [];
		}

		$data = $document->getFile(false)->getData();

		static::addToStack($data);
		$pdfId = null;
		if(isset($updatedData['PDF_ID']) && $updatedData['PDF_ID'] > 0)
		{
			$pdfId = $updatedData['PDF_ID'];
		}
		$data['pdfId'] = $pdfId;
		static::fireEvent($params['documentId'], $data);

		return true;
	}

	/**
	 * @param int $documentId
	 * @param array $data
	 */
	protected static function fireEvent($documentId, array $data = [])
	{
		EventManager::getInstance()->send(new Event(Driver::MODULE_ID, 'onDocumentTransformationComplete', ['documentId' => $documentId, 'data' => $data]));
	}

	private static function updateDocument(Document $document, array $result): array
	{
		$updateData = [];
		foreach (static::getFormats() as $extension => $format)
		{
			if (isset($result['files'][$extension]))
			{
				$fileArray = \CFile::MakeFileArray($result['files'][$extension], $format['TYPE']);
				$fileArray['MODULE_ID'] = Driver::MODULE_ID;
				$fileArray['name'] = $fileArray['fileName'] = $document->getFileName($extension);
				$saveResult = FileTable::saveFile($fileArray);
				if($saveResult->isSuccess())
				{
					$updateData[$format['KEY']] = $saveResult->getId();
					$document->{$format['METHOD']}($saveResult->getId());
				}
			}
		}

		if (!empty($updateData))
		{
			$updateResult = DocumentTable::update($document->ID, $updateData);
			if (!$updateResult->isSuccess())
			{
				foreach($updateData as $fileId)
				{
					FileTable::delete($fileId);
				}
			}
		}

		return $updateData;
	}

	/**
	 * @see Document::setPdfId()
	 * @see Document::setImageId()
	 * @return array
	 */
	protected static function getFormats()
	{
		return [
			'jpg' => [
				'TYPE' => 'image/jpg',
				'KEY' => 'IMAGE_ID',
				'METHOD' => 'setImageId',
			],
			'pdf' => [
				'TYPE' => 'application/pdf',
				'KEY' => 'PDF_ID',
				'METHOD' => 'setPdfId',
			],
		];
	}

	public function __construct(Document $document)
	{
		$this->result = new Result();
		$this->document = $document;
	}

	/**
	 * @param array $formats
	 * @return Result
	 */
	public function transform(array $formats)
	{
		if(!$this->checkFormats($formats))
		{
			$this->result->addError(new Error('Wrong format'));
			return $this->result;
		}

		if(!$this->document->FILE_ID)
		{
			$this->result->addError(new Error('Empty FILE_ID'));
			return $this->result;
		}

		foreach($formats as $extension)
		{
			if($this->document->{static::getFormats()[$extension]['KEY']})
			{
				unset($formats[$extension]);
			}
		}

		if(empty($formats))
		{
			return $this->result->setData([
				'cancelReason' => new Error(
					'All transform formats are present',
					static::ERROR_CODE_TRANSFORM_FORMATS_FILLED,
				),
			]);
		}

		if($this->isConverted($formats))
		{
			$status = $this->transformInfo['status'] ?? 0;
			if ($status === Command::STATUS_SUCCESS)
			{
				$this->result->setData([
					'cancelReason' => new Error(
						'All transform formats already processed',
						static::ERROR_CODE_TRANSFORM_FORMATS_PROCESSED,
					),
				]);
			}

			return $this->result;
		}

		if($this->result->isSuccess())
		{
			$transformer = new DocumentTransformer();
			$this->result = $transformer->transform($this->getBFileId(), $formats, Driver::MODULE_ID, static::class, ['documentId' => $this->document->ID, 'queue' => static::QUEUE_NAME]);
		}

		return $this->result;
	}

	/**
	 * @param array $formats
	 * @return bool
	 */
	protected static function checkFormats(array &$formats)
	{
		$result = [];
		foreach($formats as $key => $format)
		{
			if(!isset(static::getFormats()[$format]))
			{
				return false;
			}
			else
			{
				unset($formats[$key]);
				$result[$format] = $format;
			}
		}

		$formats = $result;

		return true;
	}

	/**
	 * @param array $formats
	 * @return boolean
	 */
	protected function isConverted(array $formats)
	{
		$this->loadTransformInfo();
		if(!$this->transformInfo)
		{
			return false;
		}

		if($this->transformInfo['status'] == Command::STATUS_ERROR)
		{
			return false;
		}
		if($this->transformInfo['status'] !== Command::STATUS_SUCCESS)
		{
			/** @var DateTime $date */
			$date = $this->transformInfo['time'];
			if($date && time() - $date->getTimestamp() > 24*3600)
			{
				return false;
			}
		}
		$formatsConverted = count($formats);
		foreach($this->transformInfo['params']['formats'] as $format)
		{
			if(isset($formats[$format]))
			{
				$formatsConverted--;
			}
		}

		if($formatsConverted == 0)
		{
			return true;
		}

		return false;
	}

	final public function getLastTransformationResult(): ?Result
	{
		$this->loadTransformInfo();
		if (!is_array($this->transformInfo))
		{
			return null;
		}

		$result = new Result();

		$status = $this->transformInfo['status'] ?? null;

		$isErrorStatus = $status === Command::STATUS_ERROR;
		if (!$isErrorStatus)
		{
			return $result;
		}

		$error = $this->transformInfo['error'] ?? null;
		if (!is_array($error))
		{
			$error = [];
		}

		$code = isset($error['code']) && is_int($error['code']) ? $error['code'] : Command::ERROR_CONTROLLER_UNKNOWN_ERROR;
		if (isset($error['message']) && is_string($error['message']) && $error['message'] !== '')
		{
			$message = $error['message'];
		}
		else
		{
			$message = Loc::getMessage('DOCUMENTGENERATOR_TRANSFORMER_ERROR_UNKNOWN');
		}
		$customData = isset($error['customData']) && is_array($error['customData']) ? $error['customData'] : null;

		$result->addError(new Error($message, $code, $customData));

		return $result;
	}

	/**
	 * @return null|string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getPullTag(): ?string
	{
		if(Loader::includeModule("pull"))
		{
			$pullTag = static::getPullTagName($this->document->ID);
			\CPullWatch::Add(Driver::getInstance()->getUserId(), $pullTag, true);
			return $pullTag;
		}

		return null;
	}

	/**
	 *
	 */
	protected function loadTransformInfo()
	{
		$this->transformInfo = false;

		$bFileId = $this->getBFileId();
		if(!$bFileId)
		{
			$this->result->addError(new Error('b_file id not found'));
		}
		else
		{
			$this->transformInfo = FileTransformer::getTransformationInfoByFile($bFileId);
		}
	}

	/**
	 * @return bool|int
	 */
	protected function getBFileId()
	{
		return FileTable::getBFileId($this->document->FILE_ID);
	}

	protected static function getPullTagName($id)
	{
		return 'TRANSFORMDOCUMENT'.$id;
	}

	protected static function getPullTagCommand()
	{
		return 'showImage';
	}

	/**
	 * @param array $data
	 */
	public static function addToStack(array $data)
	{
		if(Loader::includeModule("pull"))
		{
			\CPullWatch::AddToStack(static::getPullTagName($data['id']), [
				'module_id' => Driver::MODULE_ID,
				'command' => static::getPullTagCommand(),
				'params' => $data,
			]);
		}
	}
}
