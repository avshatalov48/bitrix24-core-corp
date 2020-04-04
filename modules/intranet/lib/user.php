<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity\ExpressionField;

class UserTable extends \Bitrix\Main\UserTable
{
	public static function postInitialize(\Bitrix\Main\ORM\Entity $entity)
	{
		parent::postInitialize($entity);

		// add intranet user type expression
		$conditionList = [];
		$externalUserTypesUsed = [];

		if (ModuleManager::isModuleInstalled('replica'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'replica' THEN 'network'"
			];
			$externalUserTypesUsed[] = 'replica';
		}
		if (ModuleManager::isModuleInstalled('sale'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s IN ('sale', 'saleanonymous', 'shop') THEN 'sale'"
			];
			$externalUserTypesUsed[] = 'sale';
			$externalUserTypesUsed[] = 'saleanonymous';
			$externalUserTypesUsed[] = 'shop';
		}
		if (ModuleManager::isModuleInstalled('imconnector'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'imconnector' THEN 'imconnector'"
			];
			$externalUserTypesUsed[] = 'imconnector';
		}
		if (ModuleManager::isModuleInstalled('im'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'bot' THEN 'bot'"
			];
			$externalUserTypesUsed[] = 'bot';
		}
		if (ModuleManager::isModuleInstalled('mail'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'email' THEN 'email'"
			];
			$externalUserTypesUsed[] = 'email';
		}

		$externalUserTypes = \Bitrix\Main\UserTable::getExternalUserTypes();
		$externalUserTypesAdditional = array_diff($externalUserTypes, $externalUserTypesUsed);
		if (!empty($externalUserTypesAdditional))
		{
			$sqlHelper = \Bitrix\Main\Application::getInstance()->getConnection()->getSqlHelper();
			foreach($externalUserTypesAdditional as $externalAuthId)
			{
				$value = $sqlHelper->convertToDbText($externalAuthId);
				$conditionList[] = [
					'PATTERN' => 'EXTERNAL_AUTH_ID',
					'VALUE' => "WHEN %s = ".$value." THEN ".$value.""
				];
			}
		}

		// duplicate for inner join
		$conditionListInner = $conditionList;

		if (ModuleManager::isModuleInstalled('extranet'))
		{
			$conditionList[] = [
				'PATTERN' => 'UF_DEPARTMENT',
				'VALUE' => "WHEN %s = 'a:0:{}' THEN 'extranet'"
			];
			$conditionList[] = [
				'PATTERN' => 'UF_DEPARTMENT',
				'VALUE' => "WHEN %s IS NULL THEN 'extranet'"
			];
			$conditionListInner[] = [
				'PATTERN' => 'UTS_OBJECT_INNER.UF_DEPARTMENT',
				'VALUE' => "WHEN %s = 'a:0:{}' THEN 'extranet'"
			];
			$conditionListInner[] = [
				'PATTERN' => 'UTS_OBJECT_INNER.UF_DEPARTMENT',
				'VALUE' => "WHEN %s IS NULL THEN 'extranet'"
			];
		}

		// add USER_TYPE with left join
		$condition = "CASE ";
		$patternList = [];

		foreach($conditionList as $conditionFields)
		{
			$condition .= ' '.$conditionFields['VALUE'].' ';
			$patternList[] = $conditionFields['PATTERN'];
		}
		$condition .= "ELSE 'employee' END";

		$entity->addField(new ExpressionField('USER_TYPE',
			$condition,
			$patternList
		));

		if (Loader::includeModule('socialnetwork'))
		{
			$entity->addField(new \Bitrix\Main\ORM\Fields\Relations\OneToMany('TAGS', \Bitrix\Socialnetwork\UserTagTable::class, 'USER'));
		}

		// add USER_TYPE with inner join
		$condition = "CASE ";
		$patternList = [];

		foreach($conditionListInner as $conditionFields)
		{
			$condition .= ' '.$conditionFields['VALUE'].' ';
			$patternList[] = $conditionFields['PATTERN'];
		}
		$condition .= "ELSE 'employee' END";

		$entity->addField(new ExpressionField('USER_TYPE_INNER',
			$condition,
			$patternList
		));

		// add other fields
		$entity->addField(new ExpressionField('USER_TYPE_IS_EMPLOYEE',
			"CASE WHEN %s = 'employee' THEN 1 ELSE 0 END",
			'USER_TYPE_INNER'
		));
	}
}
