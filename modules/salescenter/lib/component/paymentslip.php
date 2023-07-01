<?php

namespace Bitrix\SalesCenter\Component;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Security\Sign\BadSignatureException;

final class PaymentSlip
{
	public const SLIP_LINK_PATH = '/pub/payment-slip';
	private const SALT = 'slip_payment_id';
    private const LINK = '/pub/payment-slip/#SIGNED_PAYMENT_ID#/';

    public static function getLink(int $paymentId): string
    {
        return str_replace(
            '#SIGNED_PAYMENT_ID#',
            self::signPaymentId($paymentId),
            self::LINK
        );
    }

	public static function getFullPathToSlip(int $paymentId): string
	{
		$link = self::getLink($paymentId);

		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$host = $request->isHttps() ? 'https' : 'http';

		return (new \Bitrix\Main\Web\Uri($host . '://' . $request->getHttpHost() . $link))->getUri();
	}

	public static function signPaymentId(int $paymentId): string
	{
		return (new Signer())->sign((string)$paymentId, self::SALT);
	}

	public static function unsignPaymentId(string $hash): ?int
	{
		try
		{
			$unsignedValue = (new Signer())->unsign($hash, self::SALT);
		}
		catch (BadSignatureException $e)
		{
			return null;
		}

		if ((string)((int)$unsignedValue) !== $unsignedValue)
		{
			return null;
		}

		return (int)$unsignedValue;
	}

	/**
	 * @return array|null
	 */
	public static function getRegionWarning(): ?array
	{
		if (static::getZone() === 'ru')
		{
			$warningMessages = Loc::loadLanguageFile(
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/salescenter/lib/component/paymentslip.php',
				'ru'
			);

			return [
				'TITLE' => $warningMessages['SALESCENTER_PAYMENT_SLIP_WARNING_TITLE'],
				'SUBTITLE' => $warningMessages['SALESCENTER_PAYMENT_SLIP_WARNING_SUBTITLE'],
			];
		}

		return null;
	}

	public static function getZone(): string
	{
		if (Loader::includeModule('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		else
		{
			$iterator = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=DEF' => 'Y',
					'=ACTIVE' => 'Y'
				]
			]);
			$row = $iterator->fetch();
			$zone = $row['ID'];
		}

		return $zone;
	}
}
