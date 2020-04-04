<?php
namespace Bitrix\Crm\Integration\Numerator;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\Numerator\Generator\SequentNumberGenerator;
use Bitrix\Main\Web\Json;

/**
 * Class QuoteNumberCompatibilityManager
 * @package Bitrix\Sale\Integration\Numerator
 */
class QuoteNumberCompatibilityManager
{
	/** If numerator template is the same as it was in an old version of API
	 * we save quote_number_template into b_option as if it was before
	 * for compatibility reasons
	 * @param Event $event
	 * @return EventResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function updateQuoteNumberType(Event $event)
	{
		$result = new EventResult();
		$numeratorFields = $event->getParameter("fields");
		if ($numeratorFields['TYPE'] == REGISTRY_TYPE_CRM_QUOTE)
		{
			$numberTemplate = isset($numeratorFields['TEMPLATE']) ? $numeratorFields['TEMPLATE'] : '';
			$settings = Json::decode($numeratorFields['SETTINGS']);
			if ($numberTemplate)
			{
				$type = '';
				switch ($numberTemplate)
				{
					case '{NUMBER}':
						$settingsSequent = $settings[SequentNumberGenerator::getType()];
						if (isset($settingsSequent['step']) && ($settingsSequent['step'] == 1)
							&&
							key_exists('periodicBy', $settingsSequent) && ($settingsSequent['periodicBy'] === null)
						)
						{
							$type = 'NUMBER';
						}
						break;
					case '{PREFIX}{QUOTE_ID}':
						$type = 'PREFIX';
						break;
					case '{RANDOM}':
						$type = 'RANDOM';
						break;
					case '{USER_ID_QUOTES_COUNT}':
						$type = 'USER';
						break;
					case '{DAY}{MONTH}{YEAR} / {NUMBER}':
						$settingsSequent = $settings[SequentNumberGenerator::getType()];
						if (isset($settingsSequent['step']) && $settingsSequent['step'] == 1
							&&
							key_exists('periodicBy', $settingsSequent) && $settingsSequent['periodicBy'] == SequentNumberGenerator::DAY
						)
						{
							$type = 'DATE';
						}
						break;
					case '{MONTH}{YEAR} / {NUMBER}':
						$settingsSequent = $settings[SequentNumberGenerator::getType()];
						if (isset($settingsSequent['step']) && $settingsSequent['step'] == 1
							&&
							key_exists('periodicBy', $settingsSequent) && $settingsSequent['periodicBy'] == SequentNumberGenerator::MONTH
						)
						{
							$type = 'DATE';
						}
						break;
					case '{YEAR} / {NUMBER}':
						$settingsSequent = $settings[SequentNumberGenerator::getType()];
						if (isset($settingsSequent['step']) && $settingsSequent['step'] == 1
							&&
							key_exists('periodicBy', $settingsSequent) && $settingsSequent['periodicBy'] == SequentNumberGenerator::YEAR
						)
						{
							$type = 'DATE';
						}
						break;
					default:
						break;
				}

				Option::set("crm", "quote_number_template", $type);
			}
		}

		return $result;
	}
}