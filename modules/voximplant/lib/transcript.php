<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Voximplant\Model\TranscriptLineTable;
use Bitrix\Voximplant\Model\TranscriptTable;

class Transcript
{
	const SIDE_USER = "User";
	const SIDE_CLIENT = "Client";

	protected $id = 0;
	protected $url;
	protected $content;
	protected $sessionId;
	protected $callId;
	protected $cost;
	protected $costCurrency;
	protected $lines = array();

	/**
	 * Use one of the named constructors.
	 */
	protected function __construct(){}

	/**
	 * Creates instance of the current class with the given source url.
	 * @param string $url Transcript's URL.
	 * @return static
	 */
	public static function createWithUrl($url)
	{
		$instance = new static();
		$instance->url = $url;
		return $instance;
	}

	/**
	 * Creates instance of the current class with the given array of lines.
	 * @param array $lines Lines of the transcription in format [SIDE, START_TIME, STOP_TIME, MESSAGE]
	 * @return Transcript
	 */
	public static function createWithLines(array $lines)
	{
		$instance = new static();
		$instance->lines = $lines;
		return $instance;
	}

	public function fetch()
	{
		$httpClient = HttpClientFactory::create();
		$response = $httpClient->get($this->url);
		$responseCharset = $httpClient->getCharset();
		if($responseCharset != '')
		{
			$content = Encoding::convertEncoding($response, $responseCharset, SITE_CHARSET);
		}
		else
		{
			$content = $response;
		}

		if(!$content)
		{
			return false;
		}

		$this->content = $content;
		$this->lines = $this->parse($content);
		return true;
	}

	public function save()
	{
		if($this->id == 0)
		{
			$insertResult = TranscriptTable::add($this->toArray());
			$this->id = $insertResult->getId();
		}
		else
		{
			TranscriptTable::update($this->id, $this->toArray());
			TranscriptLineTable::deleteByTranscriptId($this->id);
		}

		foreach ($this->lines as $line)
		{
			$line['TRANSCRIPT_ID'] = $this->id;
			TranscriptLineTable::add($line);
		}
	}

	public function attachToCall($sessionId)
	{
		if($this->id == 0)
			return false;

		$statisticRecord = StatisticTable::getList(array(
			'filter' => array(
				'=SESSION_ID' => $sessionId
			)
		))->fetch();
		if(!$statisticRecord)
		{
			return false;
		}

		StatisticTable::update($statisticRecord['ID'], array(
			'TRANSCRIPT_ID' => $this->id
		));
		return true;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return array
	 */
	public function getLines()
	{
		return $this->lines;
	}

	/**
	 * @param mixed $sessionId
	 */
	public function setSessionId($sessionId)
	{
		$this->sessionId = $sessionId;
	}

	/**
	 * @return string
	 */
	public function getCallId()
	{
		return $this->callId;
	}

	/**
	 * @param string $callId
	 */
	public function setCallId($callId)
	{
		$this->callId = $callId;
	}

	/**
	 * @return mixed
	 */
	public function getCost()
	{
		return $this->cost;
	}

	/**
	 * @param mixed $cost
	 */
	public function setCost($cost)
	{
		$this->cost = $cost;
	}

	/**
	 * @return mixed
	 */
	public function getCostCurrency()
	{
		return $this->costCurrency;
	}

	/**
	 * @param mixed $costCurrency
	 */
	public function setCostCurrency($costCurrency)
	{
		$this->costCurrency = $costCurrency;
	}

	/**
	 * @param $content
	 * @return array()
	 */
	public static function parse($content)
	{
		$result = array();
		$lines = explode("\n", $content);
		foreach ($lines as $line)
		{
			if(preg_match("/^(\w+) (\d\d:\d\d:\d\d) - (\d\d:\d\d:\d\d) : (.+)$/", $line, $matches))
			{
				$result[] = array(
					'SIDE' => $matches[1],
					'START_TIME' => self::convertTimeToSeconds($matches[2]),
					'STOP_TIME' => self::convertTimeToSeconds($matches[3]),
					'MESSAGE' => $matches[4]
				);
			}
		}

		return $result;
	}

	/**
	 * Handler for the TranscriptionComplete callback
	 * @param array $params Function parameters:
	 * <li> SESSION_ID int
	 * <li> TRANSCRIPTION_URL string
	 * @return boolean
	 */
	public static function onTranscriptionComplete($params)
	{
		$sessionId = (int)$params['SESSION_ID'];
		$statisticRecord = StatisticTable::getList(array(
			'filter' => array(
				'=SESSION_ID' => $sessionId
			)
		))->fetch();

		$transcriptionUrl = (string)$params['TRANSCRIPTION_URL'];
		$transcript = self::createWithUrl($transcriptionUrl);
		$transcript->setSessionId($sessionId);
		if($statisticRecord)
		{
			$transcript->setCallId($statisticRecord['CALL_ID']);
		}
		$transcript->setCost((double)$params['COST']);
		$transcript->setCostCurrency((string)$params['COST_CURRENCY']);
		$transcript->fetch();

		$transcript->save();

		if(!$statisticRecord)
		{
			return false;
		}

		StatisticTable::update($statisticRecord['ID'], array(
			'TRANSCRIPT_ID' => $transcript->getId(),
			'TRANSCRIPT_PENDING' => 'N'
		));
		
		if ($statisticRecord['CRM_ACTIVITY_ID'] > 0)
		{
			\CVoxImplantCrmHelper::createActivityUpdateEvent($statisticRecord['CRM_ACTIVITY_ID']);
		}
		
		if(Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				self::getTagForCall($statisticRecord['CALL_ID']),
				array(
					'module_id' => 'voximplant',
					'command' => 'transcriptComplete',
					'params' => array(
						'CALL_ID' => $statisticRecord['CALL_ID']
					)
				)
			);
		}

		return true;
	}

	/**
	 * Subscribes user on the pull event transcriptionComplete for the call.
	 * @return boolean
	 */
	public static function subscribeOnTranscriptionComplete($callId, $userId)
	{
		if(!Loader::includeModule('pull'))
			return false;

		\CPullWatch::Add($userId, self::getTagForCall($callId), true);
		return true;
	}

	public static function isEnabled()
	{
		if(!Loader::includeModule('bitrix24'))
			return true;

		$licensePrefix = \CBitrix24::getLicensePrefix();
		if(in_array($licensePrefix, static::getAllowedRegions()))
			return true;

		return \CVoxImplantAccount::IsPro();
	}

	public static function isDemo()
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$licensePrefix = \CBitrix24::getLicensePrefix();
		if(in_array($licensePrefix, static::getAllowedRegions()))
		{
			return false;
		}

		return \CVoxImplantAccount::IsDemo();
	}

	public static function getAllowedRegions()
	{
		return ['ru'];
	}

	public static function getHiddenRegions()
	{
		return ['ua', 'kz', 'by'];
	}

	/**
	 * Converts time string to number of seconds from the start of the conversation.
	 * @param string $timeString Time string, in format HH:mi:ss.
	 * @return int
	 */
	protected static function convertTimeToSeconds($timeString)
	{
		list($hours, $minutes, $seconds) = explode(":", $timeString);
		return intval($hours) * 60 * 60 + intval($minutes) * 60 + intval($seconds);
	}

	/**
	 * Returns subscription tag for the event of transcription complete for the call.
	 * @param string $callId Id of the call.
	 * @return string
	 * @throws ArgumentException
	 */
	protected static function getTagForCall($callId)
	{
		if(!is_string($callId) || $callId == '')
			throw new ArgumentException("callId should be not empty string", "callId");

		return "transcriptionComplete_".$callId;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'ID' => $this->id,
			'URL' => $this->url,
			'CONTENT' => $this->content,
			'CALL_ID' => $this->callId,
			'SESSION_ID' => $this->sessionId,
			'COST' => $this->cost,
			'COST_CURRENCY' => $this->costCurrency,
		);
	}
}