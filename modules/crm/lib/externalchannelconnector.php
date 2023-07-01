<?php
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ExternalChannelConnectorTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalChannelConnector_Query query()
 * @method static EO_ExternalChannelConnector_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalChannelConnector_Result getById($id)
 * @method static EO_ExternalChannelConnector_Result getList(array $parameters = [])
 * @method static EO_ExternalChannelConnector_Entity getEntity()
 * @method static \Bitrix\Crm\EO_ExternalChannelConnector createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_ExternalChannelConnector_Collection createCollection()
 * @method static \Bitrix\Crm\EO_ExternalChannelConnector wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_ExternalChannelConnector_Collection wakeUpCollection($rows)
 */
class ExternalChannelConnectorTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_crm_ext_channel_connector';
    }

    public static function getMap()
    {
        return array(
            'TYPE_ID' => array(
                'data_type' => 'string',
                'primary' => true,
                'required' => true
            ),
            'NAME' => array(
                'data_type' => 'string'
            ),
            'APP_ID' => array(
                'data_type' => 'string'
            ),
            'CHANNEL_ID' => array(
                'data_type' => 'string'
            ),
            'ORIGINATOR_ID' => array(
                'data_type' => 'string',
                'primary' => true,
                'required' => true
            ),
            'EXTERNAL_SERVER_HOST' => array(
                'data_type' => 'string'
            ),
        );
    }

    /**
     * @return void
     */
    public static function upsert(array $data)
    {
        $fields = array(
            'NAME' => isset($data['NAME']) ? $data['NAME'] : '',
            'APP_ID' => isset($data['APP_ID']) ? $data['APP_ID'] : '',
            'CHANNEL_ID' => isset($data['CHANNEL_ID']) ? $data['CHANNEL_ID'] : '',
            'EXTERNAL_SERVER_HOST' => isset($data['EXTERNAL_SERVER_HOST']) ? $data['EXTERNAL_SERVER_HOST'] : ''
        );

        $connection = Main\Application::getConnection();
        $queries = $connection->getSqlHelper()->prepareMerge(
            'b_crm_ext_channel_connector',
            array('TYPE_ID', 'ORIGINATOR_ID'),
            array_merge(
                $fields,
                array(
                    'TYPE_ID' => isset($data['TYPE_ID']) ? $data['TYPE_ID'] : '',
                    'ORIGINATOR_ID' => isset($data['ORIGINATOR_ID']) ? $data['ORIGINATOR_ID'] : ''
                )
            ),
            $fields
        );

        foreach($queries as $query)
        {
            $connection->queryExecute($query);
        }
    }
}