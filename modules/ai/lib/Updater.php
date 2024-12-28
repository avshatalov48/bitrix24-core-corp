<?php

namespace Bitrix\AI;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Synchronization\Enum\SyncMode;
use Bitrix\AI\Synchronization\ImageStylePromptSync;
use Bitrix\AI\Synchronization\PlanSync;
use Bitrix\AI\Synchronization\PromptSync;
use Bitrix\AI\Synchronization\RoleIndustrySync;
use Bitrix\AI\Synchronization\RoleSync;
use Bitrix\AI\Synchronization\SectionSync;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use CAgent;
use Exception;

/**
 * Class Updater. Refreshes local DB from remote host.
 * @package Bitrix\AI
 */
final class Updater
{
	private const OPTION_CODE_EXPIRED_TIME = 'prompt_expired_time';
	private const OPTION_CODE_CURRENT_VERSION = 'prompt_version';
	private const OPTION_CODE_FORMAT_CURRENT_VERSION = 'format_version';
	private const OPTION_CODE_FORMAT_CURRENT_SUBVERSION = 'format_subversion';

	private const OPTION_JSON_DB_ETAG = 'option_json_db_etag';

	private const CURRENT_JSON_FORMAT_VERSION = 2;

	private const CURRENT_JSON_FORMAT_SUBVERSION = 2;

	private const TTL_HOURS = 3;

	/**
	 * Refreshes local DB from remote host.
	 *
	 * @return void
	 */
	public static function refreshFromRemote(): void
	{
		$http = new HttpClient();
		$http->setHeader('Content-Type', 'application/json');
		if (self::isCurrentFormatSubVersion())
		{
			$http->setHeader('If-None-Match', self::getJsonDbEtag());
		}
		$remoteDbUri = self::getRemoteDbUri();
		$currentVersion = self::getVersion();
		if (self::tryPartitionalUpdate($http, $remoteDbUri, $currentVersion))
		{
			return;
		}
		$response = $http->get($remoteDbUri);
		$etag = $http->getHeaders()->get('Etag') ?? '';

		if ($http->getStatus() === 304)
		{
			return;
		}

		self::refreshFromJson($response, $etag);
	}

	private static function tryPartitionalUpdate(HttpClient $http, string $remoteDbUri, int $currentVersion): bool
	{
		$nextVersion = $currentVersion + 1;
		$lastVersion = self::getRemoteDbVersion($remoteDbUri);
		if (!$lastVersion || $nextVersion !== $lastVersion)
		{
			return false;
		}
		if ($currentVersion === $lastVersion)
		{
			return self::isCurrentFormatSubVersion();
		}

		$remoteDbUriNextVersion = str_replace('.json', '-' . $nextVersion . '.json', $remoteDbUri);
		$responseNext = $http->get($remoteDbUriNextVersion);
		$etag = $http->getHeaders()->get('Etag') ?? '';
		if ($http->getStatus() === 304)
		{
			return true;
		}
		if ($http->getStatus() === 200 && $http->getContentType() === 'application/json')
		{
			self::refreshFromJson($responseNext, $etag, SyncMode::Partitional);

			return true;
		}

		return false;
	}

	private static function getRemoteDbVersion(string $remoteDbUri): int
	{
		$http = new HttpClient();
		$removeDbVersion = $http->get(preg_replace('/[^\/]+\.json$/', 'version.txt', $remoteDbUri));

		return (int)$removeDbVersion;
	}

	/**
	 * Refreshes local DB from local file.
	 *
	 * @param string $jsonFile JSON file.
	 * @return void
	 */
	public static function refreshFromLocalFile(string $jsonFile): void
	{
		self::refreshFromJson(Facade\File::getContents($jsonFile), '');
	}

	private static function getRemoteDbUri(): string
	{
		if (Bitrix24::shouldUseB24() === false)
		{
			return 'https://static-ai-proxy.bitrix.info/v2/box.json';
		}

		return Config::getValue('ai_prompt_db_uri');
	}

	/**
	 * Refreshes local DB from remote JSON file.
	 *
	 * @param string $rawJson JSON string.
	 * @return void
	 */
	private static function refreshFromJson(string $rawJson, string $etag, SyncMode $mode = SyncMode::Standard): void
	{
		if (!Application::getConnection()->lock('ai_prompt_update'))
		{
			return;
		}

		try
		{
			$response = Json::decode($rawJson);
		}
		catch (Exception)
		{
			return;
		}

		if ((int)$response['format_version'] !== self::CURRENT_JSON_FORMAT_VERSION)
		{
			return;
		}

		if (empty($response['version']))
		{
			$response['version'] = 1;
		}

		if (
			$response['version'] > self::getVersion()
			|| $response['format_version'] > self::getFormatVersion()
			|| self::getFormatSubVersion() !== self::CURRENT_JSON_FORMAT_SUBVERSION
		)
		{
			static::sendInfo('start. Version ' . $response['version'] . ' ' . time());

			(new RoleSync())->sync($response['roles'] ?? [], ['=IS_SYSTEM' => 'Y'], $mode);
			(new RoleIndustrySync())->sync($response['industries'] ?? [], [], $mode);
			(new PromptSync())->sync($response['abilities'] ?? [], ['=IS_SYSTEM' => 'Y'], $mode);
			(new PlanSync())->sync($response['plans'] ?? [], [], $mode);
			(new SectionSync())->sync($response['sections'] ?? [], [], $mode);
			(new ImageStylePromptSync())->sync($response['image_style_prompts'] ?? [], [], $mode);

			if ($mode === SyncMode::Partitional && isset($response['deleted']))
			{
				(new RoleSync())->deleteByCodes($response['deleted']['roles'] ?? []);
				(new RoleIndustrySync())->deleteByCodes($response['deleted']['industries'] ?? []);
				(new PromptSync())->deleteByCodes($response['deleted']['abilities'] ?? []);
				(new PlanSync())->deleteByCodes($response['deleted']['plans'] ?? []);
				(new SectionSync())->deleteByCodes($response['deleted']['sections'] ?? []);
				(new ImageStylePromptSync())->deleteByCodes($response['deleted']['image_style_prompts'] ?? []);
			}

			self::setVersion($response['version']);
			self::setFormatVersion(self::CURRENT_JSON_FORMAT_VERSION);
			self::setFormatSubVersion(self::CURRENT_JSON_FORMAT_SUBVERSION);
			self::setJsonDbEtag($etag);

			static::sendInfo('end. Version ' . time());
		}

		self::makeExpired(self::TTL_HOURS);
	}

	/**
	 * Refreshes local DB from remote if expired.
	 *
	 * @return void
	 */
	public static function refreshIfExpired(): void
	{
		if (self::isExpired())
		{
			self::refreshFromRemote();
		}
	}

	/**
	 * Refreshes local DB from remote if needed.
	 * @return string
	 */
	public static function refreshDbAgent(): string
	{
		self::refreshIfExpired();

		return __CLASS__ . '::refreshDbAgent();';
	}

	/**
	 * Delayed refreshed local DB from remote if expired.
	 *
	 * @param int $seconds Delay in seconds.
	 * @return void
	 */
	public static function refreshIfExpiredDelayed(int $seconds = 30): void
	{
		if (self::isExpired())
		{
			$funcName = __CLASS__ . '::refreshFromRemote();';
			$res = CAgent::getList(
				[],
				[
					'MODULE_ID' => 'ai',
					'NAME' => $funcName,
				]
			);
			if (!$res->fetch())
			{
				CAgent::addAgent($funcName, 'ai', next_exec: (new DateTime())->add("+$seconds seconds"));
			}
		}
	}

	/**
	 * Makes local Prompts' DB expired after specified hours.
	 *
	 * @param int $hours Expired in hours.
	 * @return void
	 */
	public static function makeExpired(int $hours): void
	{
		Config::setOptionsValue(self::OPTION_CODE_EXPIRED_TIME, time() + $hours * 3600);
	}

	/**
	 * Returns UNIX time when Prompts' local DB will be expired.
	 *
	 * @return bool
	 */
	public static function isExpired(): bool
	{
		return (int)Config::getValue(self::OPTION_CODE_EXPIRED_TIME) <= time();
	}


	/**
	 * Returns current version of Prompts' local DB.
	 *
	 * @return int
	 */
	public static function getVersion(): int
	{
		return (int)Config::getValue(self::OPTION_CODE_CURRENT_VERSION);
	}

	/**
	 * Sets new version of Prompts' local DB.
	 *
	 * @param int $version New version.
	 * @return void
	 */
	public static function setVersion(string $version): void
	{
		Config::setOptionsValue(self::OPTION_CODE_CURRENT_VERSION, $version);
	}

	/**
	 * Sets new version of Prompts' local DB.
	 *
	 * @param int $version New version.
	 * @return void
	 */
	public static function setFormatVersion(int $version): void
	{
		Config::setOptionsValue(self::OPTION_CODE_FORMAT_CURRENT_VERSION, $version);
	}

	/**
	 * Returns current format version of local DB.
	 *
	 * @return int
	 */
	public static function getFormatVersion(): int
	{
		return (int)Config::getValue(self::OPTION_CODE_FORMAT_CURRENT_VERSION);
	}

	/**
	 * Sets new format subversion of Prompts' local DB.
	 *
	 * @param int $subVersion New subversion.
	 * @return void
	 */
	public static function setFormatSubVersion(int $subVersion): void
	{
		Config::setOptionsValue(self::OPTION_CODE_FORMAT_CURRENT_SUBVERSION, $subVersion);
	}

	/**
	 * Returns current format subVersion of local DB.
	 *
	 * @return int
	 */
	public static function getFormatSubVersion(): int
	{
		return (int)Config::getValue(self::OPTION_CODE_FORMAT_CURRENT_SUBVERSION);
	}

	/**
	 * Return result of compare format subversion.
	 *
	 * @return bool
	 */
	private static function isCurrentFormatSubVersion(): bool
	{
		return self::getFormatSubVersion() === self::CURRENT_JSON_FORMAT_SUBVERSION;
	}


	/**
	 * @return string
	 */
	private static function getJsonDbEtag(): string
	{
		return Config::getValue(self::OPTION_JSON_DB_ETAG) ?? '';
	}

	/**
	 * @param string $etag
	 *
	 * @return void
	 */
	private static function setJsonDbEtag(string $etag): void
	{
		Config::setOptionsValue(self::OPTION_JSON_DB_ETAG, $etag);
	}

	/**
	 * Decreases version of Prompts' local DB.
	 *
	 * @return void
	 */
	public static function decreaseVersion(): void
	{
		self::setVersion(max(0, self::getVersion() - 1));
	}

	private static function sendInfo($msg)
	{
		AddMessage2Log('Prompt Updater ' . $msg, 'ai');
	}
}
