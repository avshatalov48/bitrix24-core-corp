<?php
namespace Bitrix\Crm\Conversion;
class QuoteConversionPhase
{
	const INTERMEDIATE = 0;
	const DEAL_CREATION = 1;
	const INVOICE_CREATION = 2;
	const FINALIZATION = 16;

	public static function isDefined($phaseID)
	{
		if(!is_numeric($phaseID))
		{
			return false;
		}

		if(!is_int($phaseID))
		{
			$phaseID = (int)$phaseID;
		}

		return $phaseID === self::DEAL_CREATION
			|| $phaseID === self::INVOICE_CREATION
			|| $phaseID === self::FINALIZATION;
	}

	public static function isFinal($phaseID)
	{
		if(!is_numeric($phaseID))
		{
			return false;
		}

		if(!is_int($phaseID))
		{
			$phaseID = (int)$phaseID;
		}

		return $phaseID === self::FINALIZATION;
	}
}