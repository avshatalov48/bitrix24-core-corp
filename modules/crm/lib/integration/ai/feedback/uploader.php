<?php

namespace Bitrix\Crm\Integration\AI\Feedback;

use Bitrix\Crm\Entity\FieldDataProvider;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Dto\FillItemFieldsFromCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\SummarizeCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\TranscribeCallRecordingPayload;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Http;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Form\FeedbackForm;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class Uploader
{
	private const ENDPOINT = 'https://product-feedback.bitrix24.com/bitrix/services/main/ajax.php';
	private const FORM_ID = 654;
	private const SECURITY_CODE = 'so05ig';

	private const FIELD_NAMES = [
		'RECORDING' => 'DEAL_UF_CRM_1696423915586',
		'TRANSCRIPTION' => 'DEAL_UF_CRM_1696423934083',
		'SUMMARY' => 'DEAL_UF_CRM_1696423954251',
		'FIELDS' => 'DEAL_UF_CRM_1696423967344',
		'LANGUAGE_ID' => 'copilot_crm_language',
	];

	private LoggerInterface $logger;

	public function __construct(
		private string $recordingFileName,
		private string $recordingFileContent,
		private TranscribeCallRecordingPayload $transcribe,
		private SummarizeCallTranscriptionPayload $summarize,
		private ItemIdentifier $fillTarget,
		private FillItemFieldsFromCallTranscriptionPayload $fill,
		private string $languageId,
	)
	{
		$this->logger = AIManager::logger();
	}

	public function sendAsync(callable $onFulfilled = null, callable $onRejected = null): void
	{
		(new HttpClient())->sendAsyncRequest($this->prepareRequest())->then(
			function (ResponseInterface $response) use ($onFulfilled): ResponseInterface {
				$this->logger->debug(
					'{date}: {class}: Sent request to feedback CRM webform: {response}',
					['class' => self::class, 'response' => $response],
				);

				if ($onFulfilled)
				{
					$onFulfilled();
				}

				return $response;
			},
			function (Http\ClientException $exception) use ($onRejected): Http\ClientException {
				$this->logger->error(
					'{date}: {class}: Error sending feedback to CRM form: {exception}',
					[
						'class' => self::class,
						'exception' => $exception,
					]
				);

				if ($onRejected)
				{
					$onRejected();
				}

				return $exception;
			}
		);
	}

	public function send(): Result
	{
		$result = new Result();

		try
		{
			$response = (new HttpClient())->sendRequest($this->prepareRequest());

			$result->setData([
				'response' => [
					'statusCode' => $response->getStatusCode(),
					'body' => (string)$response->getBody(),
				]
			]);
		}
		catch (Http\ClientException $exception)
		{
			$this->logger->error(
				'{date}: {class}: Error sending feedback to CRM form: {exception}',
				[
					'class' => self::class,
					'exception' => $exception,
				]
			);

			$result->addError(Error::createFromThrowable($exception));
		}

		return $result;
	}

	private function prepareRequest(): Http\Request
	{
		$feedbackForm = new FeedbackForm('crm-ai-item-fill');

		return new Http\Request(
			Http\Method::POST,
			(new Uri(self::ENDPOINT))->addParams([
				'action' => 'crm.site.form.fill',
			]),
			null,
			new Http\FormStream([
				'values' => Json::encode([
					self::FIELD_NAMES['RECORDING'] => [
						[
							'name' => $this->recordingFileName,
							'content' => base64_encode($this->recordingFileContent),
						],
					],
					self::FIELD_NAMES['TRANSCRIPTION'] => [$this->transcribe->transcription],
					self::FIELD_NAMES['SUMMARY'] => [$this->summarize->summary],
					self::FIELD_NAMES['FIELDS'] => [self::prepareFields($this->fillTarget, $this->fill)],
				]),
				'id' => self::FORM_ID,
				'sec' => self::SECURITY_CODE,
				'properties' => Json::encode(array_merge(
					[self::FIELD_NAMES['LANGUAGE_ID'] => $this->languageId],
					$feedbackForm->getPresets(),
				)),
			])
		);
	}

	private static function prepareFields(
		ItemIdentifier $target,
		FillItemFieldsFromCallTranscriptionPayload $payload,
	): string
	{
		$fields = (new FieldDataProvider($target->getEntityTypeId(), Context::SCOPE_AI))->getFieldData();

		$result = [
			'comment=' . $payload->unallocatedData,
		];

		foreach ($payload->singleFields as $singleField)
		{
			if (!isset($fields[$singleField->name]))
			{
				continue;
			}

			$result[] = $fields[$singleField->name]['NAME'] . '=' . $singleField->aiValue;
		}

		foreach ($payload->multipleFields as $multipleField)
		{
			if (!isset($fields[$multipleField->name]))
			{
				continue;
			}

			$result[] = $fields[$multipleField->name]['NAME'] . '=[' . implode(',', $multipleField->aiValues) . ']';
 		}

		return implode('|', $result);
	}
}
