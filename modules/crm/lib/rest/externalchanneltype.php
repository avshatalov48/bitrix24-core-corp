<?php
namespace Bitrix\Crm\Rest;


class CCrmExternalChannelType
{
    const Undefined = 0;
    const Custom = 1;
    const Bitrix = 2;
    const OneC = 3;
    const Wordpress = 4;
    const Joomla = 5;
    const Drupal = 6;
    const Magento = 7;

    const First = 1;
    const Last = 7;

    const CustomName = 'CUSTOM';
    const BitrixName = 'BITRIX';
    const OneCName = 'ONE_C';
    const WordpressName = 'WORDPRESS';
    const JoomlaName = 'JOOMLA';
    const DrupalName = 'DRUPAL';
    const MagentoName = 'MAGENTO';

    private static $ALL_DESCRIPTIONS = array();

    public static function isDefined($typeID)
    {
        if(!is_int($typeID))
        {
            $typeID = (int)$typeID;
        }
        return $typeID >= self::First && $typeID <= self::Last;
    }

    public static function resolveID($name)
    {
        $name = strtoupper(trim(strval($name)));
        if($name == '')
        {
            return self::Undefined;
        }

        switch($name)
        {
            case self::CustomName:
                return self::Custom;

            case self::BitrixName:
                return self::Bitrix;

            case self::OneCName:
                return self::OneC;

            case self::WordpressName:
                return self::Wordpress;

            case self::JoomlaName:
                return self::Joomla;

            case self::DrupalName:
                return self::Drupal;

            case self::MagentoName:
                return self::Magento;

            default:
                return self::Undefined;
        }
    }

    public static function resolveName($typeID)
    {
        if(!is_numeric($typeID))
        {
            return '';
        }

        $typeID = intval($typeID);
        if($typeID <= 0)
        {
            return '';
        }

        switch($typeID)
        {
            case self::Custom:
                return self::CustomName;

            case self::Bitrix:
                return self::BitrixName;

            case self::OneC:
                return self::OneCName;

            case self::Wordpress:
                return self::WordpressName;

            case self::Joomla:
                return self::JoomlaName;

            case self::Drupal:
                return self::DrupalName;

            case self::Magento:
                return self::MagentoName;

            default:
                return '';
        }
    }

    public static function getAllDescriptions()
    {
        if(!self::$ALL_DESCRIPTIONS[LANGUAGE_ID])
        {
            IncludeModuleLangFile(__FILE__);

            self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
                self::Custom => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_CUSTOM'),
                self::Bitrix => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_BITRIX'),
                self::OneC => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_ONE_C'),
                self::Wordpress => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_WORDPRESS'),
                self::Joomla => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_JOOMLA'),
                self::Drupal => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_DRUPAL'),
                self::Magento => GetMessage('CRM_EXTERNAL_CHANNEL_TYPE_MAGENTO')
            );
        }

        return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
    }

    public static function getDescription($typeID)
    {
        $typeID = intval($typeID);
        $all = self::getAllDescriptions();
        return isset($all[$typeID]) ? $all[$typeID] : '';
    }

    public static function getDescriptions($types)
    {
        $result = array();
        if(is_array($types))
        {
            foreach($types as $typeID)
            {
                $typeID = intval($typeID);
                $descr = self::getDescription($typeID);
                if($descr !== '')
                {
                    $result[$typeID] = $descr;
                }
            }
        }
        return $result;
    }
}