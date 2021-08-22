<?php
namespace Bitrix\Imopenlines\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application,
	Bitrix\Main\Entity,
	Bitrix\Main\Error;

Loc::loadMessages(__FILE__);

/**
 * Class SessionIndexTable
 *
 * Fields:
 * <ul>
 * <li> SESSION_ID int mandatory
 * <li> SEARCH_CONTENT string optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SessionIndex_Query query()
 * @method static EO_SessionIndex_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_SessionIndex_Result getById($id)
 * @method static EO_SessionIndex_Result getList(array $parameters = array())
 * @method static EO_SessionIndex_Entity getEntity()
 * @method static \Bitrix\Imopenlines\Model\EO_SessionIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection createCollection()
 * @method static \Bitrix\Imopenlines\Model\EO_SessionIndex wakeUpObject($row)
 * @method static \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection wakeUpCollection($rows)
 */

class SessionIndexTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_session_index';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'SESSION_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'SEARCH_CONTENT' => array(
				'data_type' => 'text',
			),
		);
	}

	protected static function getMergeFields()
	{
		return array('SESSION_ID');
	}

	public static function merge(array $data)
    {
        $result = new Entity\AddResult();

        $helper = Application::getConnection()->getSqlHelper();
        $insertData = $data;
        $updateData = $data;
        $mergeFields = static::getMergeFields();

        foreach ($mergeFields as $field)
        {
            unset($updateData[$field]);
        }

        $merge = $helper->prepareMerge(
            static::getTableName(),
            static::getMergeFields(),
            $insertData,
            $updateData
        );

        if ($merge[0] != "")
        {
            Application::getConnection()->query($merge[0]);
            $id = Application::getConnection()->getInsertedId();
            $result->setId($id);
            $result->setData($data);
        }
        else
        {
            $result->addError(new Error('Error constructing query'));
        }

        return $result;
    }
}