<?php
namespace Bitrix\Imopenlines\Update\Update186900;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Update\Stepper,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenLines\Tools\Correction;

Loc::loadMessages(__FILE__);

final class Correction4 extends Stepper
{
	const OPTION_NAME = "imopenlines_186900_correction_4";
	protected static $moduleId = "imopenlines";

	/**
	 * @inheritdoc
	 *
	 * @param array $option
	 * @return bool
	 */
	public function execute(array &$option): bool
	{
		$return = Stepper::FINISH_EXECUTION;

		if (Loader::includeModule(self::$moduleId))
		{
			$stepVars = Option::get(self::$moduleId, self::OPTION_NAME, "");
			$stepVars = ($stepVars !== "" ? @unserialize($stepVars, ['allowed_classes' => false]) : []);
			$stepVars = (is_array($stepVars) ? $stepVars : []);
			if (empty($stepVars))
			{
				$stepVars = [
					'number' => 0,
					'count' => Correction::getCountStatusClosedSessions(),
				];
			}

			if ($stepVars['count'] > 0)
			{
				$option['count'] = $stepVars['count'];

				$resultCorrectionSession = Correction::setStatusClosedSessions(true, 100);

				if (count($resultCorrectionSession) > 0)
				{
					$stepVars['number'] += count($resultCorrectionSession);

					Option::set(self::$moduleId, self::OPTION_NAME, serialize($stepVars));
					$return = Stepper::CONTINUE_EXECUTION;
				}
				else
				{
					Option::delete(self::$moduleId, ["name" => self::OPTION_NAME]);
				}

				$option['progress'] = round($stepVars['number'] * 100 / $stepVars['count']);
				$option['steps'] = $stepVars['number'];
			}
			else
			{
				Option::delete(self::$moduleId, ["name" => self::OPTION_NAME]);
			}
		}

		return $return;
	}

	public static function getTitle(): string
	{
		return Loc::getMessage("IMOL_UPDATE_REPAIR_STATUS_CLOSED_SESSIONS");
	}
}