<?php
namespace Bitrix\ImOpenLines\Crm;

use Bitrix\Crm\FieldMultiTable;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenLines\Crm;
use Bitrix\ImOpenLines\Model\SessionTable;

/**
 * Class ReplacementUserCode
 *
 * @package Bitrix\ImOpenLines\Crm
 */

Loc::loadMessages(__FILE__);

class ReplacementUserCode extends Stepper
{
	public const OPTION_NAME = 'imopenlines_crm_replacement_user_code';
	public static $moduleId = 'imopenlines';
	protected static $moduleIdCrm = 'crm';
	protected static $stepSize = 50;

	/**
	 * @inheritdoc
	 *
	 * @param array $result
	 * @return bool
	 */
	public function execute(array &$result): bool
	{
		$return = false;

		if (
			Loader::includeModule(self::$moduleId)
			&& Loader::includeModule(self::$moduleIdCrm)
		)
		{
			[$oldUserCode, $newUserCode] = $this->outerParams;

			$optionName = self::OPTION_NAME . md5($oldUserCode . $newUserCode);
			$count = 0;

			$params = Option::get(self::$moduleId, $optionName, '');
			$params = ($params !== '' ? @unserialize($params, ['allowed_classes' => false]) : []);
			$params = (is_array($params) ? $params : []);
			if (empty($params))
			{
				$params = [
					'activity' => false,
				];
			}

			if ($params['activity'] !== true)
			{
				$sessions = SessionTable::getList([
					'select' => [
						'CRM_ACTIVITY_ID',
					],
					'filter' => [
						'=USER_CODE' => $newUserCode
					]
				]);

				while ($session = $sessions->fetch())
				{
					if (!empty($session['CRM_ACTIVITY_ID']))
					{
						Crm\Activity::update($session['CRM_ACTIVITY_ID'], ['USER_CODE' => $newUserCode]);

						$communications = \CCrmActivity::GetCommunications($session['CRM_ACTIVITY_ID']);

						$isEditCommunications = false;

						foreach ($communications as $idCommunication=>$communication)
						{
							if(
								!empty($communication['VALUE'])
								&& mb_strpos($communication['VALUE'], 'imol|') === 0
							)
							{
								$communications[$idCommunication]['VALUE'] = 'imol|' . $newUserCode;
								$isEditCommunications = true;
							}
						}

						if($isEditCommunications === true)
						{
							\CAllCrmActivity::SaveCommunications($session['CRM_ACTIVITY_ID'], $communications);
						}

						$count++;
					}

					if ($count >= self::$stepSize)
					{
						break;
					}
				}
			}

			if (!($count >= self::$stepSize))
			{
				$params['activity'] = true;

				$res = FieldMultiTable::getList([
					'select' => [
						'ID',
						'VALUE_TYPE',
					],
					'filter' => [
						'=TYPE_ID' => 'IM',
						'=VALUE' => 'imol|' . $oldUserCode,
					]
				]);

				$multiFields = new \CCrmFieldMulti();
				while (
					($row = $res->fetch())
					&& $count < self::$stepSize
				)
				{
					$multiFields->Update($row['ID'], [
						'TYPE_ID' => 'IM',
						'VALUE_TYPE' => $row['VALUE_TYPE'],
						'VALUE' => 'imol|' . $newUserCode,
					]);

					$count++;
				}
			}

			if ($count > 0)
			{
				Option::set(self::$moduleId, $optionName, serialize($params));
				$return = true;
			}
			else
			{
				Option::delete(self::$moduleId, ['name' => $optionName]);
			}
		}

		return $return;
	}
}