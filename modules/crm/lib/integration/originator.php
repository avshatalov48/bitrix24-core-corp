<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Main;

/**
 * Class OriginatorTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Originator_Query query()
 * @method static EO_Originator_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Originator_Result getById($id)
 * @method static EO_Originator_Result getList(array $parameters = [])
 * @method static EO_Originator_Entity getEntity()
 * @method static \Bitrix\Crm\Integration\EO_Originator createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integration\EO_Originator_Collection createCollection()
 * @method static \Bitrix\Crm\Integration\EO_Originator wakeUpObject($row)
 * @method static \Bitrix\Crm\Integration\EO_Originator_Collection wakeUpCollection($rows)
 */
class OriginatorTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_originator';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Main\ORM\Fields\StringField('ORIGINATOR_ID', ['primary' => true, 'size' => 255, 'required' => true])),
			(new Main\ORM\Fields\IntegerField('ICON_ID'))
		];
	}

	public static function onBeforeAdd(Main\ORM\Event $event)
	{
		return self::modifyFields($event);
	}

	public static function onBeforeUpdate(Main\ORM\Event $event)
	{
		return self::modifyFields($event);
	}

	private static function modifyFields(Main\ORM\Event $event)
	{
		$result = new Main\ORM\EventResult;
		$data = $event->getParameter('fields');
		if (array_key_exists('ICON', $data))
		{
			if ($str = \CFile::CheckImageFile($data['ICON']))
			{
				$result->addError(new Main\ORM\Fields\FieldError(static::getEntity()->getField('ICON_ID'), $str));
			}
			else
			{
				\CFile::ResizeImage($data['ICON'], [
					'width' => Main\Config\Option::get('crm', 'originator_icon_size', 100),
					'height' => Main\Config\Option::get('crm', 'originator_icon_size', 100)]);
				$data['ICON']['MODULE_ID'] = 'crm';
				if ($id = $event->getParameter('id'))
				{
					$id = is_array($id) ? reset($id) : $id;
					if (is_string($id) && ($originator = OriginatorTable::getById($id)->fetchObject()))
					{
						$data['ICON']['old_file'] = $originator->getIconId();
					}
				}
				if (\CFile::SaveForDB($data, 'ICON', 'crm/originator'))
				{
					$result->modifyFields(['ICON_ID' => $data['ICON']]);
				}
			}
			$result->unsetField('ICON');
		}
		return $result;
	}

	public static function onBeforeDelete(Main\Entity\Event $event)
	{
		$result = new Main\ORM\EventResult();
		$id = $event->getParameter('id');
		$id = $id['ORIGINATOR_ID'];
		if (($entity = OriginatorTable::getById($id)->fetchObject())
			&& ($entity->getIconId() > 0))
		{
			\CFile::Delete($entity->getIconId());
		}
		return $result;
	}
}

class Originator
{
	protected static $list;
	public static function set(array $fields)
	{
		$id = array_key_exists('ORIGINATOR_ID', $fields) ? trim($fields['ORIGINATOR_ID']) : null;
		unset($fields['ORIGINATOR_ID']);
		if (empty($fields))
		{
			$result = new Main\ORM\Data\AddResult();
			$result->setPrimary(['ORIGINATOR_ID' => $id]);
		}
		else
		{
			try
			{
				if ($id !== null && ($originator = OriginatorTable::getById($id)->fetch()))
				{
					$result = OriginatorTable::update($id, $fields);
				}
				else
				{
					$result = OriginatorTable::add(['ORIGINATOR_ID' => $id] + $fields);
				}
			}
			catch (\Throwable $e)
			{
				$result = new Main\Result();
				$result->addError(new Main\Error($e->getMessage(), $e->getCode()));
			}
		}
		return $result;
	}

	public static function getList(array $filter = [])
	{
		return OriginatorTable::getList([
			'select' => ['*'],
			'filter' => $filter,
			'cache' => ['ttl' => 864000]
		])->fetchAll();
	}

	public static function get(?string $id = null)
	{
		if (!is_array(static::$list))
		{
			static::$list = [];
			foreach (static::getList([]) as $item)
			{
				static::$list[$item['ORIGINATOR_ID']] = $item;
			}
		}
		if ($id === null)
		{
			return static::$list;
		}
		if (array_key_exists($id, static::$list))
		{
			return static::$list[$id];
		}
		return null;
	}

	public static function getIcon(?string $id): ?array
	{
		if (($item = static::get($id))
			&& ($icon = \CFile::GetFileArray($item['ICON_ID'])))
		{
			return [
				'FILE_NAME' => $icon['FILE_NAME'],
				'CONTENT_TYPE' => $icon['CONTENT_TYPE'],
				'WIDTH' => $icon['WIDTH'],
				'HEIGHT' => $icon['HEIGHT'],
				'SRC' => $icon['SRC']
			];
		}
		return null;
	}
}