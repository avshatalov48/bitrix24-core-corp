<?php

namespace Bitrix\AI\Facade;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Internals\AgreementTable;
use Bitrix\Main\UserConsent\Internals\ConsentTable;

Loc::loadMessages(__FILE__);

class Agreement
{
	/**
	 * Return agreement data by code (depends on language).
	 *
	 * @param string $code Agreement code.
	 * @param bool $create Create if it doesn't exist.
	 * @return array|null
	 */
	public static function getByCode(string $code, bool $create = false): ?array
	{
		$record = AgreementTable::query()
			->setSelect(['*'])
			->where('CODE', $code)
			->setLimit(1)
			->fetch()
		;

		if (!$record && $create)
		{
			if (self::create($code))
			{
				return self::getByCode($code);
			}
		}

		return is_array($record) ? $record : null;
	}

	/**
	 * Returns true if specified user accepted AI's agreement.
	 *
	 * @param int $agreementId Agreement id.
	 * @param int $userId User id.
	 * @return bool
	 */
	public static function isAcceptedByUser(int $agreementId, int $userId): bool
	{
		$res = ConsentTable::query()
			->setSelect(['ID'])
			->where('USER_ID', $userId)
			->where('AGREEMENT_ID', $agreementId)
			->fetch()
		;

		return !empty($res);
	}

	/**
	 * Makes acceptation of AI agreement by user.
	 *
	 * @param int $agreementId Agreement id.
	 * @param int $userId User id.
	 * @return bool
	 */
	public static function acceptByUser(int $agreementId, int $userId): bool
	{
		return Consent::addByContext($agreementId, params: ['USER_ID' => $userId]) !== null;
	}

	/**
	 * Redefines language if required and returns it.
	 *
	 * @return string
	 */
	private static function getLanguageCode(): string
	{
		$zone = Bitrix24::getPortalZone();

		// for kz and by take file from ru-namespace
		if (in_array($zone, ['kz', 'by']))
		{
			Loc::loadLanguageFile(__DIR__ . '/Agreement_' . $zone . '.php', 'ru');
			return 'ru';
		}
		elseif ($zone === 'ru')
		{
			Loc::loadLanguageFile(__DIR__ . '/Agreement.php', 'ru');
			return 'ru';
		}

		Loc::loadLanguageFile(__DIR__ . '/Agreement.php', 'en');
		return 'en';
	}

	/**
	 * Creates AI's agreement for current LANG.
	 *
	 * @param string $code Agreement code.
	 * @return bool
	 */
	private static function create(string $code): bool
	{
		$code = mb_strtoupper($code);
		$lang = self::getLanguageCode();

		$res = AgreementTable::add([
			'CODE' => $code,
			'LANGUAGE_ID' => $lang,
			'TYPE' => UserConsent\Agreement::TYPE_CUSTOM,
			'NAME' => Loc::getMessage("{$code}_TITLE", null, $lang) ?? ' ',
			'AGREEMENT_TEXT' => Loc::getMessage("{$code}_TEXT", null, $lang) ?? ' ',
			'LABEL_TEXT' => Loc::getMessage("{$code}_LABEL", null, $lang) ?? ' ',
			'IS_AGREEMENT_TEXT_HTML' => 'Y',
			'ACTIVE' => 'Y',
		]);

		return $res->isSuccess();
	}
}