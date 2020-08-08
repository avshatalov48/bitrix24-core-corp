<?
namespace Bitrix\Crm\Integration\Main\UISelector;

class CrmEntity extends \Bitrix\Main\UI\Selector\EntityBase
{
	private static function getOwnerType()
	{
		return '';
	}

	private static function getOwnerTypeName()
	{
		return '';
	}

	private static function getHandlerType()
	{
		return '';
	}

	public static function getMultiKey($key, $email)
	{
		return $key.':'.mb_substr(md5($email), 0, 8);
	}

	protected static function processMultiFields(array $entityList = [], array $entityOptions = [])
	{
		$result = $entityList;

		if (
			empty($entityOptions['returnMultiEmail'])
			|| $entityOptions['returnMultiEmail'] != 'Y'
		)
		{
			return $result;
		}

		foreach ($result as $key => $entity)
		{
			if (!empty($entity['multiEmailsList']))
			{
				foreach($entity['multiEmailsList'] as $email)
				{
					$newKey = self::getMultiKey($key, $email);
					$result[$newKey] = $entity;
					$result[$newKey]['id'] = $newKey;
					$result[$newKey]['email'] = $email;

					if (
						isset($entityOptions['onlyWithEmail'])
						&& $entityOptions['onlyWithEmail'] == 'Y'
					)
					{
						$result[$newKey]['desc'] = $email;
					}

					unset($result[$newKey]['multiEmailsList']);
				}
			}

			unset($result[$key]);
		}

		return $result;
	}

	public static function prepareToken($str)
	{
		return str_rot13($str);
	}
}