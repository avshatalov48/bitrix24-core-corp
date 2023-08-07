<?php
namespace Bitrix\ImOpenLines\Tools;

use Bitrix\Crm\Communication\Normalizer as CrmNormalizer;

/**
 * Class Email
 * @package Bitrix\ImOpenLines
 */
class Email
{
	/**
	 * Validate email.
	 *
	 * @param bool $email Email.
	 * @return string
	 */
	public static function validate($email)
	{
		return \check_email($email);
	}

	/**
	 * Normalize email.
	 *
	 * @param string $email Email.
	 * @return string
	 */
	public static function normalize($email)
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$result = CrmNormalizer::normalizeEmail($email);
		}
		else
		{
			$result = trim(mb_strtolower($email));
		}

		return $result;
	}

	/**
	 * @param string $email1
	 * @param string $email2
	 * @return bool
	 */
	public static function isSame($email1, $email2): bool
	{
		return self::normalize($email1) == self::normalize($email2);
	}

	/**
	 * @param string $text
	 * @return array
	 */
	public static function parseText($text): array
	{
		$result = [];
		$matchesEmails = [];

		preg_match_all("/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/i", $text, $matchesEmails);
		if (!empty($matchesEmails[0]))
		{
			foreach ($matchesEmails[0] as $email)
			{
				if (self::validate($email))
				{
					$result[] = self::normalize($email);
				}
			}

			if (!empty($result))
			{
				$result = array_unique($result);
			}
		}

		return $result;
	}

	/**
	 * @param string[] $emails
	 * @param string $searchEmail
	 * @return bool
	 */
	public static function isInArray($emails, $searchEmail): bool
	{
		$result = false;

		if (!empty($emails) && is_array($emails))
		{
			foreach ($emails as $email)
			{
				if (self::isSame($email, $searchEmail))
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string[] $emails
	 * @return array
	 */
	public static function getArrayUniqueValidate($emails): array
	{
		$resultEmails = [];

		if (!empty($emails) && is_array($emails))
		{
			foreach ($emails as $email)
			{
				if (self::validate($email) && !self::isInArray($resultEmails, $email))
				{
					$resultEmails[] = $email;
				}
			}
		}

		return $resultEmails;
	}
}