<?php

namespace Bitrix\Call\Integration\AI\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Call\Model\EO_CallAITask;
use Bitrix\Call\Model\CallAITaskTable;
use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Main\Web\Json;

/**
 * @see EO_CallAITask
 * @method string|null getLanguageId()
 * @method AITask setLanguageId(string $languageId)
 * @method string|null getType()
 * @method AITask setType(string $type)
 * @method int|null getOutcomeId()
 * @method AITask setOutcomeId(int $outcomeId)
 * @method int|null getTrackId()
 * @method AITask setTrackId(int $trackId)
 * @method int|null getCallId()
 * @method AITask setCallId(int $callId)
 * @method string|null getStatus()
 * @method AITask setStatus(string $status)
 * @method string|null getHash()
 * @method AITask setHash(string $status)
 * @method string|null getErrorMessage()
 * @method AITask setErrorMessage(string $status)
 * @method string|null getErrorCode()
 * @method AITask setErrorCode(string $status)
 * @method DateTime getDateCreate()
 * @method AITask setDateCreate(DateTime $dateCreate)
 * @method DateTime|null getDateFinished()
 * @method AITask setDateFinished(DateTime $dateFinished)
 * @method \Bitrix\Im\Model\EO_Call fillCall()
 * @method \Bitrix\Call\Track fillTrack()
 * @method \Bitrix\Call\Integration\AI\Outcome fillOutcome()
 * @method \Bitrix\Main\DB\Result save()
 * @method \Bitrix\Main\DB\Result delete()
 */
abstract class AITask
{
	public const
		STATUS_READY = 'ready',
		STATUS_PENDING = 'pending',
		STATUS_FINISHED = 'finished',
		STATUS_FAILED = 'failed'
	;

	protected ?EO_CallAITask $task = null;


	/**
	 * @param EO_CallAITask|null $source
	 */
	public function __construct(?EO_CallAITask $source = null)
	{
		if ($source instanceof EO_CallAITask)
		{
			$this->task = $source;
		}
		else
		{
			$this->task = new EO_CallAITask();
			$this->task->setType($this->getAISenseType());
		}
	}

	public static function loadById(int $taskId): ?self
	{
		$row = CallAITaskTable::getById($taskId)?->fetchObject();
		if ($row)
		{
			return static::buildBySource($row);
		}

		return null;
	}

	public static function buildBySource(EO_CallAITask $source): self
	{
		$class = match ($source->getType())
		{
			SenseType::TRANSCRIBE->value => TranscribeCallRecord::class,
			SenseType::SUMMARY->value => TranscriptionSummary::class,
			SenseType::OVERVIEW->value => TranscriptionOverview::class,
			SenseType::INSIGHTS->value => TranscriptionInsights::class,
			default => ''
		};
		if (!$class)
		{
			return new class($source) extends AITask
			{
				public function setPayload($payload): AITask
				{
					return $this;
				}
				public function getAIPayload(): Result
				{
					return new Result;
				}
				public function getAIEngineCategory(): string
				{
					return '';
				}
				public function getAISenseType(): string
				{
					return '';
				}
			};
		}

		return new $class($source);
	}

	public static function getTaskForCall(int $callId, SenseType $senseType): ?self
	{
		$task = CallAITaskTable::getList([
			'filter' => [
				'=CALL_ID' => $callId,
				'=TYPE' => $senseType->value,
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])?->fetchObject();
		if ($task)
		{
			return self::buildBySource($task);
		}

		return null;
	}

	public function getContextId(): string
	{
		return (new \ReflectionClass(static::class))->getShortName();
	}

	/**
	 * Proxies method call to EO_CallAITask type object.
	 * @see EO_CallAITask
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws NotImplementedException
	 */
	public function __call(string $name, $arguments)
	{
		if (
			$this->task instanceof EO_CallAITask
			&& (
				str_starts_with($name, 'get')
				|| str_starts_with($name, 'set')
				|| str_starts_with($name, 'fill')
				|| in_array($name, ['save', 'delete'])
			)
		)
		{
			return call_user_func_array([$this->task, $name], $arguments);
		}

		throw new NotImplementedException("Class ".$this->task::class." does not implement method '{$name}'");
	}

	/**
	 * Provides payload for AI task.
	 * @param mixed $payload
	 * @return self
	 */
	abstract public function setPayload($payload): self;

	/**
	 * Returns IPayload object for AI service.
	 * @return Result<\Bitrix\AI\Payload\IPayload: payload>
	 */
	abstract public function getAIPayload(): Result;

	public function getAIEngine(\Bitrix\AI\Context $context): ?\Bitrix\AI\Engine
	{
		$engine = \Bitrix\AI\Engine::getByCode($this->getAIEngineCode(), $context, $this->getAIEngineCategory());
		if (!$engine instanceof \Bitrix\AI\Engine)
		{
			$engine = \Bitrix\AI\Engine::getByCategory($this->getAIEngineCategory(), $context);
		}

		return $engine;
	}

	public function getAIEngineContext(): \Bitrix\AI\Context
	{
		$context = new \Bitrix\AI\Context('call', $this->getContextId());

		$context->setParameters([
			'taskId' => $this->task->getId(),
		]);

		//$context->setLanguage($this->task->getLanguageId() ?? '');

		return $context;
	}

	abstract public function getAIEngineCategory(): string;

	abstract public function getAISenseType(): string;

	public function getAIEngineCode(): string
	{
		return '';
	}

	public function getCost(): int
	{
		return 1;
	}

	public static function getAIPromptFields(): array
	{
		return [];
	}

	public function filterResult(array $jsonData): array
	{
		return $jsonData;
	}

	/**
	 * @param \Bitrix\AI\Result $aiResult
	 * @return Outcome
	 */
	public function buildOutcome(\Bitrix\AI\Result $aiResult): Outcome
	{
		$outcome = new Outcome();
		$outcome
			->setType($this->getAISenseType())
			->setCallId($this->getCallId())
		;
		if ($this->getTrackId())
		{
			$outcome->setTrackId($this->getTrackId());
		}
		if ($this->getLanguageId())
		{
			$outcome->setLanguageId($this->getLanguageId());
		}

		$jsonData = $this->extractJsonData($aiResult);
		if (is_array($jsonData))
		{
			$jsonData = $this->filterResult($jsonData);
			$outcome->fillFromJson($jsonData);
		}
		else
		{
			$outcome->setContent($aiResult->getPrettifiedData());
		}

		return $outcome;
	}

	/**
	 * @param \Bitrix\AI\Result $aiResult
	 * @return array|null
	 */
	public function extractJsonData(\Bitrix\AI\Result $aiResult): ?array
	{
		if (is_array($aiResult->getJsonData()))
		{
			return $aiResult->getJsonData();
		}

		$jsonData = null;
		try
		{
			if (preg_match('/```json\s*(\{(.|\s)*?\})\s*```/', $aiResult->getPrettifiedData(), $matches))
			{
				$jsonData = Json::decode($matches[1]);
			}
			else
			{
				$jsonData = Json::decode($aiResult->getPrettifiedData());
			}
		}
		catch (ArgumentException $exception)
		{
		}

		return $jsonData;
	}

	public function detectRowError(string $againstError): string
	{
		$history = \Bitrix\AI\Model\HistoryTable::getList([
			'filter' => [
				'=CONTEXT_MODULE' => 'call',
				'=CONTEXT_ID' => $this->getContextId()
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		]);
		if (
			($row = $history->fetch())
			&& !empty($row['RESULT_TEXT'])
			&& $row['RESULT_TEXT'] != $againstError
		)
		{
			return $row['RESULT_TEXT'];
		}

		return '';
	}

	public function decodePayload(string $str): string
	{
		return preg_replace_callback(
			'/\\\\u([0-9a-fA-F]{4})/',
			function ($match) {
				return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
			},
			$str
		);
	}

	public function drop(): Result
	{
		$result = $this->task->delete();
		unset($this->task);

		return $result;
	}
}