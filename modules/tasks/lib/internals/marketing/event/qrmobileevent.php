<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing\Event;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\AnalyticLogger;
use Bitrix\Tasks\Internals\Marketing\EventInterface;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\UI\Barcode\Barcode;
use Bitrix\Main\Security\Random;

Loc::loadMessages(__FILE__);

class QrMobileEvent extends BaseEvent
	implements EventInterface
{
	private const TTL = 3*24*60*60;

	private const QR_WIDTH = 200;
	private const QR_HEIGHT = 200;

	private const REDIRECT_URI = 'tasks/go_mobile.php';
	private const QR_URI = 'tasks/qr_mobile.php';

	private const SECRET_OPTION = 'qr_mobile_auth';

	public function execute(): bool
	{
		if (
			!$this->validate(true)
			|| !Loader::includeModule('im')
			|| !Loader::includeModule('ui')
		)
		{
			$this->disableEvent();
			return false;
		}

		$link = $this->createLink();
		$qr = $this->generateQr($link);
		$imgSrc = $this->saveQr($qr);

		$this->sendNotification($imgSrc);

		$this->disableEvent();

		AnalyticLogger::logToFile(
			'send',
			'QrMobile',
			0,
			'QrMobile',
			$this->userId
		);

		return true;
	}

	/**
	 * @param bool $isExecute
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function validate(bool $isExecute = false): bool
	{
		// temporarily disabled
		return false;

		if (!parent::validate())
		{
			return false;
		}

		if (!ModuleManager::isModuleInstalled('mobile'))
		{
			return false;
		}

		if ($this->isHaveMobile())
		{
			return false;
		}

		if ($this->isEventExists($isExecute))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getDateSheduled(): int
	{
		return DateTime::getCurrentTimestamp() + self::TTL;
	}

	/**
	 * @param string $imgSrc
	 * @return bool
	 */
	private function sendNotification(string $imgSrc): bool
	{
		$attach = new \CIMMessageParamAttach(1, \CIMMessageParamAttach::TRANSPARENT);
		$attach->AddMessage('[b]'. Loc::getMessage('TASKS_MARKETING_QR_MOBILE_TITLE') .'[/b][br][br]');
		$attach->AddMessage(Loc::getMessage('TASKS_MARKETING_QR_MOBILE_TEXT') .'[br][br]');
		$attach->AddImages([[
			"LINK" => $imgSrc,
			"PREVIEW" => $imgSrc,
			"WIDTH" => 200,
			"HEIGHT" => 200,
		]]);

		\CIMNotify::Add(array(
			'FROM_USER_ID' => 0,
			'TO_USER_ID' => $this->userId,
			'NOTIFY_MODULE' => 'tasks',
			'NOTIFY_EVENT' => 'notice',
			'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
			'ATTACH' => $attach
		));

		return true;
	}

	/**
	 * @param string $link
	 * @return false|string
	 */
	private function generateQr(string $link)
	{
		return (new Barcode())
			->option('w', self::QR_WIDTH)
			->option('h', self::QR_HEIGHT)
			->render($link);
	}

	/**
	 * @param $qr
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function saveQr($qr): string
	{
		$fileName = Random::getString(64).'.png';
		$arFile = array(
			'name' => $fileName,
			'content'  => $qr,
			'MODULE_ID' => 'tasks'
		);
		$fileId = \CFile::SaveFile($arFile, 'tasks');

		$file = \CFile::GetByID($fileId)->Fetch();

		$externalId = $file['EXTERNAL_ID'];
		$fileName = $file['FILE_NAME'];

		$imgSrc = $this->getBasePath() . '/'. self::QR_URI .'?id=' . $externalId . '_' . $fileName;

		return $imgSrc;
	}

	/**
	 * @return string
	 */
	private function createLink(): string
	{
		$secret = Random::getString(32);
		$hash = password_hash($secret, PASSWORD_BCRYPT);

		$secret = [
			'SECRET' => $secret,
			'CREATED' => DateTime::getCurrentTimestamp()
		];

		\CUserOptions::SetOption('tasks', self::SECRET_OPTION, $secret, false, $this->userId);

		return $this->getBasePath()
			.'/'. self::REDIRECT_URI
			.'?u='. $this->userId
			.'&h='. $hash;
	}

	/**
	 * @return bool
	 */
	private function isHaveMobile(): bool
	{
		return
			\CUserOptions::GetOption('mobile', 'AndroidLastActivityDate', 0, $this->userId)
			|| \CUserOptions::GetOption('mobile', 'iOsLastActivityDate', 0, $this->userId);
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getBasePath(): string
	{
		return (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")
			."://"
			.(
				(defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '')
				? SITE_SERVER_NAME
				: Option::get("main", "server_name", $_SERVER['SERVER_NAME'])
			);
	}
}
