<?php

namespace Bitrix\Transformer;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Transformer\Command\Deadline;
use Bitrix\Transformer\Entity\CommandTable;

/**
 * Class Command
 * @package Bitrix\Transformer
 */
class Command
{
	public const STATUS_CREATE = 100;
	public const STATUS_SEND = 200;
	public const STATUS_UPLOAD = 300;
	public const STATUS_SUCCESS = 400;
	public const STATUS_ERROR = 1000;

	public const ERROR_CORRUPTED_FILE = 30;
	public const ERROR_EMPTY_CONTROLLER_URL = 40;
	public const ERROR_CONNECTION = 50;
	public const ERROR_CONNECTION_COUNT = 51;
	public const ERROR_CONNECTION_RESPONSE = 60;
	public const ERROR_DEADLINE_EXCEEDED = 70;
	public const ERROR_CONTROLLER_DOWNLOAD_STATUS = 100;
	public const ERROR_CONTROLLER_DOWNLOAD_TYPE = 101;
	public const ERROR_CONTROLLER_DOWNLOAD_SIZE = 102;
	public const ERROR_CONTROLLER_BANNED = 103;
	public const ERROR_CONTROLLER_QUEUE_CANCELED_BY_EVENT = 150;
	public const ERROR_CONTROLLER_QUEUE_ADD_FAIL = 151;
	public const ERROR_CONTROLLER_QUEUE_NOT_FOUND = 152;
	public const ERROR_CONTROLLER_MODULE_NOT_INSTALLED = 153;
	public const ERROR_CONTROLLER_RIGHT_CHECK_FAILED = 154;
	public const ERROR_CONTROLLER_LIMIT_EXCEED = 155;
	public const ERROR_CONTROLLER_BACK_URL_HOST_MISMATCH = 156;
	public const ERROR_CONTROLLER_DOMAIN_IS_PRIVATE = 157;
	public const ERROR_CONTROLLER_URL_INVALID = 158;
	public const ERROR_CONTROLLER_STATUS_AFTER_DOWNLOAD = 200;
	public const ERROR_CONTROLLER_DOWNLOAD = 201;
	public const ERROR_CONTROLLER_AFTER_DOWNLOAD_SIZE = 202;
	public const ERROR_CONTROLLER_UPLOAD = 203;
	public const ERROR_CONTROLLER_TRANSFORMATION = 300;
	public const ERROR_CONTROLLER_TRANSFORMATION_COMMAND = 301;
	public const ERROR_CONTROLLER_COMMAND_NOT_FOUND = 302;
	public const ERROR_CONTROLLER_COMMAND_ERROR = 303;
	public const ERROR_CONTROLLER_TRANSFORMATION_TIMED_OUT = 304;
	public const ERROR_CONTROLLER_UNKNOWN_ERROR = 250;
	public const ERROR_CALLBACK = 400;

	private static ?array $errorMessagesCache = null;
	private static ?array $jsonErrorCodesCache = null;

	protected $command;
	protected $params;
	protected $status;
	/** @var array */
	protected $module;
	/** @var array */
	protected $callback;
	protected $guid;
	protected $id;
	protected $file;
	protected $time;
	protected $error;
	protected $errorCode;

	private ?DateTime $deadline = null;
	private ?DateTime $sendTime = null;
	private ?string $controllerUrl = null;

	/**
	 * Command constructor.
	 * @param string $command Class name of the controller.
	 * @param array $params Params to be passed.
	 * @param string|array $module Module name (one or array) to be included before callback.
	 * @param string|array $callback Callback (one or array) to be called with results.
	 * @param int $status Current status.
	 * @param string $id Primary key.
	 * @param string $guid Unique key of the command.
	 * @throws ArgumentNullException
	 */
	public function __construct(
		$command,
		$params,
		$module,
		$callback,
		$status = self::STATUS_CREATE,
		$id = '',
		$guid = '',
		$time = null,
		$error = '',
		$errorCode = 0,
	)
	{
		if (empty($command))
		{
			throw new ArgumentNullException('command');
		}
		if (empty($module))
		{
			throw new ArgumentNullException('module');
		}
		if (empty($callback))
		{
			throw new ArgumentNullException('callback');
		}
		$this->command = $command;
		$this->params = $params;
		$this->module = (array)$module;
		$this->callback = (array)$callback;
		$this->status = intval($status);
		$this->id = $id;
		$this->guid = $guid;
		$this->time = $time;
		$this->error = $error;
		$this->errorCode = $errorCode;
		if (isset($params['file']))
		{
			$this->file = $params['file'];
		}
	}

	/**
	 * @return string
	 */
	protected static function generateGuid()
	{
		return randString(10) . uniqid();
	}

	/**
	 * Check current status of the command and send it through $http.
	 *
	 * @param Http $http Class to send command.
	 * @return Result
	 * @throws ArgumentNullException
	 * @throws ArgumentTypeException
	 * @throws InvalidOperationException
	 */
	public function send(Http $http)
	{
		if($this->status != self::STATUS_CREATE)
		{
			throw new InvalidOperationException(
				'command should be in status ' . self::getStatusText(self::STATUS_CREATE),
			);
		}
		if(empty($this->guid))
		{
			throw new InvalidOperationException('command should be saved before send');
		}

		$updateData = ['SEND_TIME' => new DateTime()];

		$result = new Result();
		$queryResult = $http->query($this->command, $this->guid, $this->params);
		if (!empty($queryResult['controllerUrl']))
		{
			$updateData['CONTROLLER_URL'] = $queryResult['controllerUrl'];
		}

		if ($queryResult['success'] !== false)
		{
			$updateData['DEADLINE'] = Deadline::createByCommand($this)->getDeadline();

			$this->updateStatusInner(self::STATUS_SEND, '', 0, $updateData);
			$result->setData(['commandId' => $this->id]);
		}
		else
		{
			Log::logger()->error(
				'{error}',
				[
					'error' => $queryResult['result']['msg'],
					'errorCode' => $queryResult['result']['code'],
					'guid' => $this->guid,
				],
			);

			$result = $this->processError($queryResult['result']['code'], $queryResult['result']['msg'], $updateData);
		}

		ServiceLocator::getInstance()->get('transformer.service.integration.analytics.registrar')->registerCommandSend(
			$this,
		);

		return $result;
	}

	/**
	 * Include modules and call all the callbacks. Return true on success of all callbacks.
	 * If at least one of callbacks returned false, this method return false.
	 *
	 * @param array $result Result from the controller.
	 * @return bool
	 */
	public function callback($result = [])
	{
		foreach($this->module as $module)
		{
			if(!Loader::includeModule($module))
			{
				Log::logger()->critical(
					'callback cant load module {module}',
					['module' => $module, 'guid' => $this->guid]
				);

				return false;
			}
		}
		$count = count($this->callback);
		$success = 0;
		$result['command'] = $this;
		foreach($this->callback as $callback)
		{
			if(is_a($callback, 'Bitrix\Transformer\InterfaceCallback', true))
			{
				$throwable = null;
				try
				{
					/* @var $callback InterfaceCallback*/
					$resultCallback = $callback::call($this->status, $this->command, $this->params, $result);
				}
				catch (\Throwable $throwable)
				{
					Application::getInstance()->getExceptionHandler()->writeToLog($throwable);
					$resultCallback = $throwable->getMessage();
				}
				if($resultCallback === true)
				{
					$success++;
				}
				else
				{
					Log::logger()->error(
						'Error doing callback. Result: {resultCallback}',
						[
							'resultCallback' => $resultCallback,
							'guid' => $this->guid,
							'isThrowable' => $throwable instanceof \Throwable,
							'throwableClass' => $throwable ? $throwable::class : null,
							'throwableFile' => $throwable?->getFile(),
							'throwableTrace' => $throwable?->getTraceAsString(),
						],
					);
				}
			}
			else
			{
				Log::logger()->error(
					'{callback} does not implements Bitrix\Transformer\InterfaceCallback',
					[
						'callback' => $callback,
						'guid' => $this->guid,
					],
				);
			}
		}
		return ($count == $success);
	}

	/**
	 * @internal
	 */
	final public function appendCallback(array $module, array $callback): Result
	{
		if ($this->id <= 0)
		{
			return (new Result())->addError(new Error("Cant update command that's not saved to DB"));
		}

		if (!in_array($this->status, [self::STATUS_CREATE, self::STATUS_SEND, self::STATUS_UPLOAD]))
		{
			return (new Result())->addError(new Error('Cant update finished command'));
		}

		if ($this->isSubset($module, $this->module) && $this->isSubset($callback, $this->callback))
		{
			// callback already added, nothing to do

			return new Result();
		}

		$newModule = array_unique(array_merge($this->module, $module));
		$newCallback = array_unique(array_merge($this->callback, $callback));

		$updateResult = CommandTable::update($this->id, [
			'MODULE' => CommandTable::encode($newModule),
			'CALLBACK' => CommandTable::encode($newCallback),
		]);
		if (!$updateResult->isSuccess())
		{
			return $updateResult;
		}

		$this->module = $newModule;
		$this->callback = $newCallback;

		return new Result();
	}

	private function isSubset(array $needle, array $haystack): bool
	{
		foreach ($needle as $item)
		{
			if (!in_array($item, $haystack, true))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Update status of command and save it in DB.
	 *
	 * @param int $status CommandTable_STATUS.
	 * @param string $error Error to save to DB.
	 * @param int $errorCode
	 * @return \Bitrix\Main\Entity\UpdateResult
	 * @throws ArgumentOutOfRangeException
	 * @throws InvalidOperationException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function updateStatus($status, $error = '', $errorCode = 0)
	{
		// don't allow passing additionalUpdateData from the outside

		return $this->updateStatusInner((int)$status, (string)$error, (int)$errorCode);
	}

	private function updateStatusInner(
		int $status,
		string $error = '',
		int $errorCode = 0,
		array $additionalUpdateData = []
	): UpdateResult
	{
		if(!self::getStatusText($status))
		{
			throw new ArgumentOutOfRangeException('status');
		}
		if($this->status >= $status)
		{
			throw new InvalidOperationException('new status should be greater than current');
		}
		if(!$this->id)
		{
			throw new InvalidOperationException('command should be saved before update');
		}
		Log::logger()->info(
			'updateStatus in {guid} from {status} to {newStatus}',
			['guid' => $this->guid, 'status' => $this->status, 'newStatus' => $status]
		);

		$time = new DateTime();
		$data = ['STATUS' => $status, 'UPDATE_TIME' => $time] + $additionalUpdateData;
		if(!empty($error))
		{
			$data['ERROR'] = $error;
		}
		if(!empty($errorCode))
		{
			$data['ERROR_CODE'] = $errorCode;
		}

		$result = CommandTable::update($this->id, $data);

		if ($result->isSuccess())
		{
			$this->setStateFromArray($data);
		}

		return $result;
	}

	/**
	 * Get current status of the command.
	 *
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @internal
	 */
	final public function onDeadlineExceeded(): void
	{
		static $message = 'Controller did not sent command result in the given deadline';

		Log::logger()->error(
			$message,
			[
				'error' => $message,
				'errorCode' => self::ERROR_DEADLINE_EXCEEDED,
				'guid' => $this->guid,
				'params' => $this->params,
				'command' => $this->command,
				'module' => $this->module,
			],
		);

		$this->processError(
			self::ERROR_DEADLINE_EXCEEDED,
			$message
		);
	}

	/**
	 * Update status of the command, call callback with error status.
	 *
	 * @param int $errorCode
	 * @param string $message
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function processError($errorCode = 0, $message = '', array $additionalUpdateData = [])
	{
		$error = $this->constructError($errorCode, $message);

		$newMessage = $error->getCustomData()['originalMessage'] ?: $error->getMessage();

		if($this->id > 0)
		{
			$this->updateStatusInner(self::STATUS_ERROR, $newMessage, $error->getCode(), $additionalUpdateData);
		}
		if(!empty($this->callback))
		{
			$this->callback();
		}

		return (new Result())->addError($error);
	}

	/**
	 * Get text description of the status.
	 *
	 * @param int $status Code of the status.
	 * @return int|string - status description.
	 */
	public static function getStatusText($status)
	{
		$statusList = [
			self::STATUS_CREATE => 'create',
			self::STATUS_SEND => 'send',
			self::STATUS_UPLOAD => 'upload',
			self::STATUS_SUCCESS => 'success',
			self::STATUS_ERROR => 'error',
		];
		if(isset($statusList[$status]))
		{
			return $statusList[$status];
		}

		return false;
	}

	/**
	 * Save command in DB.
	 *
	 * @return \Bitrix\Main\Entity\AddResult
	 * @throws InvalidOperationException
	 */
	public function save()
	{
		if($this->id > 0)
		{
			throw new InvalidOperationException('command should not be saved before save');
		}
		$this->guid = self::generateGuid();
		$time = new DateTime();
		$time->setTime($time->format('H'), $time->format('i'), $time->format('s'));
		$this->time = $time;
		$commandItem = [
			'GUID' => $this->guid,
			'STATUS' => $this->status,
			'COMMAND' => $this->command,
			'MODULE' => CommandTable::encode($this->module),
			'CALLBACK' => CommandTable::encode($this->callback),
			'PARAMS' => CommandTable::encode($this->params),
			'FILE' => $this->file,
			'UPDATE_TIME' => $this->time,
		];
		$addResult = CommandTable::add($commandItem);
		if($addResult->isSuccess())
		{
			$this->id = $addResult->getId();
		}
		return $addResult;
	}

	/**
	 * Get command from DB on $guid.
	 *
	 * @param string $guid Unique key to get Command from DB.
	 * @return Command|bool
	 * @throws ArgumentNullException
	 */
	public static function getByGuid($guid)
	{
		if(empty($guid))
		{
			throw new ArgumentNullException('guid');
		}
		$commandItem = CommandTable::getRow(['filter' => ['=GUID' => $guid], 'order' => ['ID' => 'desc']]);
		if($commandItem && $commandItem['ID'] > 0)
		{
			return self::initFromArray($commandItem);
		}
		return false;
	}

	/**
	 * Get last command from DB on $file.
	 *
	 * @param string $file Path to the file command had been created with.
	 * @return Command|bool
	 * @throws ArgumentNullException
	 */
	public static function getByFile($file)
	{
		if(empty($file))
		{
			throw new ArgumentNullException('file');
		}
		$commandItem = CommandTable::getRow(['filter' => ['=FILE' => $file], 'order' => ['ID' => 'desc']]);
		if($commandItem && $commandItem['ID'] > 0)
		{
			return self::initFromArray($commandItem);
		}
		return false;
	}

	/**
	 * Create new object from array.
	 *
	 * @internal
	 *
	 * @param array $commandItem
	 * @return Command
	 */
	public static function initFromArray($commandItem)
	{
		foreach (['PARAMS', 'MODULE', 'CALLBACK'] as $keyToUnserialize)
		{
			$commandItem[$keyToUnserialize] = CommandTable::decode($commandItem[$keyToUnserialize]);
		}

		$self = new self(
			$commandItem['COMMAND'],
			$commandItem['PARAMS'],
			$commandItem['MODULE'],
			$commandItem['CALLBACK'],
		);

		$self->setStateFromArray($commandItem);

		return $self;
	}

	private function setStateFromArray(array $commandItem): void
	{
		if (isset($commandItem['STATUS']))
		{
			$this->status = (int)$commandItem['STATUS'];
		}

		if (isset($commandItem['ID']))
		{
			$this->id = (int)$commandItem['ID'];
		}

		if (isset($commandItem['GUID']))
		{
			$this->guid = (string)$commandItem['GUID'];
		}

		if (isset($commandItem['UPDATE_TIME']) && $commandItem['UPDATE_TIME'] instanceof DateTime)
		{
			$this->time = $commandItem['UPDATE_TIME'];
		}

		if (isset($commandItem['DEADLINE']) && $commandItem['DEADLINE'] instanceof DateTime)
		{
			$this->deadline = $commandItem['DEADLINE'];
		}

		if (isset($commandItem['SEND_TIME']) && $commandItem['SEND_TIME'] instanceof DateTime)
		{
			$this->sendTime = $commandItem['SEND_TIME'];
		}

		if (isset($commandItem['CONTROLLER_URL']))
		{
			$this->controllerUrl = (string)$commandItem['CONTROLLER_URL'];
		}

		if (isset($commandItem['ERROR']))
		{
			$this->error = (string)$commandItem['ERROR'];
		}

		if (isset($commandItem['ERROR_CODE']))
		{
			$this->errorCode = (int)$commandItem['ERROR_CODE'];
		}
	}

	/**
	 * Get update time of the command.
	 *
	 * @return null|DateTime
	 */
	public function getTime()
	{
		return $this->time;
	}

	final public function getSendTime(): ?DateTime
	{
		return $this->sendTime;
	}

	final public function getControllerUrl(): ?string
	{
		return $this->controllerUrl;
	}

	final public function getQueue(): ?string
	{
		if (isset($this->params['queue']))
		{
			return (string)$this->params['queue'];
		}

		return null;
	}

	final public function getFileSize(): ?int
	{
		if (isset($this->params['fileSize']))
		{
			return (int)$this->params['fileSize'];
		}

		return null;
	}

	final public function getCommandName(): string
	{
		return $this->command;
	}

	final public function getGuid(): ?string
	{
		return $this->guid;
	}

	/**
	 * Get id of the command
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Adds new record to push&pull
	 */
	public function push()
	{
		if($this->id > 0 && Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack('TRANSFORMATIONCOMPLETE'.$this->id, [
				'module_id' => 'transformer',
				'command' => 'refreshPlayer',
				'params' => ['id' => $this->id],
			]);
		}
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @return Error|null
	 */
	public function getError()
	{
		if ($this->status === static::STATUS_ERROR)
		{
			return $this->constructError($this->errorCode, $this->error);
		}

		return null;
	}

	final public function getDeadline(): ?DateTime
	{
		return $this->deadline;
	}

	private function constructError($errorCode = 0, $message = ''): Error
	{
		$errorMessages = $this->getErrorMessages();

		if (!isset($errorMessages[$errorCode]))
		{
			// we've got an invalid/unknown error code
			$errorCode = self::ERROR_CONTROLLER_UNKNOWN_ERROR;
		}

		$jsonErrorCode = $this->getJsonErrorCodes()[$errorCode] ?? null;

		return new Error(
			$errorMessages[$errorCode],
			(int)$errorCode,
			[
				'originalMessage' => $message,
				'jsonCode' => $jsonErrorCode,
			],
		);
	}

	/**
	 * Returns a map of error code to a UI-friendly description of the error
	 *
	 * @return array
	 * @noinspection LongLine
	 */
	protected function getErrorMessages()
	{
		if (!self::$errorMessagesCache)
		{
			$tryLater = Loc::getMessage('TRANSFORMER_COMMAND_TRY_LATER');

			self::$errorMessagesCache = [
				static::ERROR_EMPTY_CONTROLLER_URL => $tryLater,
				static::ERROR_CONNECTION => Loc::getMessage('TRANSFORMER_COMMAND_REFRESH_AND_TRY_LATER'),
				static::ERROR_CONNECTION_COUNT => $tryLater,
				static::ERROR_CONNECTION_RESPONSE => $tryLater,
				static::ERROR_DEADLINE_EXCEEDED => $tryLater,
				static::ERROR_CONTROLLER_DOWNLOAD_STATUS => Loc::getMessage('TRANSFORMER_COMMAND_CANT_DOWNLOAD_FILE'),
				static::ERROR_CONTROLLER_DOWNLOAD_TYPE => Loc::getMessage('TRANSFORMER_COMMAND_CANT_DOWNLOAD_FILE'),
				static::ERROR_CONTROLLER_DOWNLOAD_SIZE => Loc::getMessage('TRANSFORMER_COMMAND_FILE_TOO_BIG'),
				static::ERROR_CONTROLLER_BANNED => $tryLater,
				static::ERROR_CONTROLLER_QUEUE_CANCELED_BY_EVENT => Loc::getMessage('TRANSFORMER_COMMAND_CHECK_SERVER_SETTINGS'),
				static::ERROR_CONTROLLER_QUEUE_ADD_FAIL => $tryLater,
				static::ERROR_CONTROLLER_QUEUE_NOT_FOUND => Loc::getMessage('TRANSFORMER_COMMAND_ASK_SUPPORT'),
				static::ERROR_CONTROLLER_MODULE_NOT_INSTALLED => Loc::getMessage('TRANSFORMER_COMMAND_INSTALL_TRANSFORMERCONTROLLER'),
				static::ERROR_CONTROLLER_LIMIT_EXCEED => $tryLater,
				static::ERROR_CONTROLLER_BACK_URL_HOST_MISMATCH => Loc::getMessage('TRANSFORMER_COMMAND_ASK_SUPPORT'),
				static::ERROR_CONTROLLER_DOMAIN_IS_PRIVATE => Loc::getMessage('TRANSFORMER_COMMAND_DOMAIN_IS_PRIVATE'),
				static::ERROR_CONTROLLER_URL_INVALID => Loc::getMessage('TRANSFORMER_COMMAND_DOMAIN_IS_PRIVATE'),
				static::ERROR_CONTROLLER_STATUS_AFTER_DOWNLOAD => Loc::getMessage('TRANSFORMER_COMMAND_CANT_DOWNLOAD_FILE'),
				static::ERROR_CONTROLLER_DOWNLOAD => Loc::getMessage('TRANSFORMER_COMMAND_CANT_DOWNLOAD_FILE'),
				static::ERROR_CONTROLLER_AFTER_DOWNLOAD_SIZE => Loc::getMessage('TRANSFORMER_COMMAND_FILE_TOO_BIG'),
				static::ERROR_CONTROLLER_UPLOAD => $tryLater,
				static::ERROR_CONTROLLER_TRANSFORMATION => Loc::getMessage('TRANSFORMER_COMMAND_FILE_CORRUPTED'),
				static::ERROR_CONTROLLER_TRANSFORMATION_COMMAND => Loc::getMessage('TRANSFORMER_COMMAND_FILE_CORRUPTED'),
				static::ERROR_CONTROLLER_COMMAND_NOT_FOUND => Loc::getMessage('TRANSFORMER_COMMAND_ASK_ADMIN'),
				static::ERROR_CONTROLLER_COMMAND_ERROR => Loc::getMessage('TRANSFORMER_COMMAND_ASK_ADMIN'),
				static::ERROR_CONTROLLER_UNKNOWN_ERROR => $tryLater,
				static::ERROR_CALLBACK => $tryLater,
			];

			if (Loader::includeModule('bitrix24'))
			{
				self::$errorMessagesCache[static::ERROR_CONTROLLER_RIGHT_CHECK_FAILED] = $tryLater;
			}
			elseif (ServiceLocator::getInstance()->get('transformer.service.http.controllerResolver')->isDefaultCloudControllerUsed())
			{
				self::$errorMessagesCache[static::ERROR_CONTROLLER_RIGHT_CHECK_FAILED] = Loc::getMessage('TRANSFORMER_COMMAND_CHECK_LICENSE');
			}
			else
			{
				self::$errorMessagesCache[static::ERROR_CONTROLLER_RIGHT_CHECK_FAILED] = Loc::getMessage('TRANSFORMER_COMMAND_ADD_TO_ALLOWED_LIST');
			}
		}

		return self::$errorMessagesCache;
	}

	private function getJsonErrorCodes(): array
	{
		if (self::$jsonErrorCodesCache)
		{
			return self::$jsonErrorCodesCache;
		}

		$reflectionClass = new \ReflectionClass($this);

		$intCodeToStringCodeMap = [];
		foreach ($reflectionClass->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC) as $const)
		{
			if (str_starts_with($const->getName(), 'ERROR_') && is_int($const->getValue()))
			{
				$intCodeToStringCodeMap[$const->getValue()] = str_replace('ERROR_', '', $const->getName());
			}
		}

		self::$jsonErrorCodesCache = (new Converter(Converter::VALUES | Converter::TO_CAMEL | Converter::LC_FIRST))
			->process($intCodeToStringCodeMap)
		;

		return self::$jsonErrorCodesCache;
	}
}
