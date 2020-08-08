<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main;
use Bitrix\Crm\WebForm;

/**
 * Class BPDocument
 * @package Bitrix\Crm\WebForm\Internals
 */
class BPDocument
{
	public static function getFields(int $entityTypeId = null): array
	{
		$fields = [];

		if (!WebForm\Manager::isEmbeddingAvailable())
		{
			return $fields;
		}

		$namePrefix = Main\Localization\Loc::getMessage('CRM_WEBFORM_BP_DOCUMENT_SECTION1');
		$namePrefix .=  ': ';

		$fields['FORMS.HASH.PARAMETER'] = [
			'Name' => $namePrefix . Main\Localization\Loc::getMessage('CRM_WEBFORM_BP_DOCUMENT_PARAMETER'),
			'Type' => 'string',
			'Editable' => false
		];

		foreach (self::getForms($entityTypeId) as $form)
		{
			$code = 'FORMS.FORM.' . $form['ID'];
			$fields[$code] = [
				'Name' => $namePrefix . $form['NAME'],
				'Type' => 'string',
				'Editable' => false
			];
		}

		return $fields;
	}

	public static function fill(int $entityTypeId, int $entityId, array &$document): void
	{
		if (!WebForm\Manager::isEmbeddingAvailable())
		{
			return;
		}

		$hash = (new WebForm\Embed\Sign())
			->addEntity($entityTypeId, $entityId)
			->pack();

		$document['FORMS.HASH.PARAMETER'] = WebForm\Embed\Sign::uriParameterName . '=' . urldecode($hash);
		foreach (self::getForms($entityTypeId) as $form)
		{
			$uri = new Main\Web\Uri(WebForm\Script::getPublicUrl($form));
			$uri->addParams([
				WebForm\Embed\Sign::uriParameterName => $hash
			]);
			$code = 'FORMS.FORM.' . $form['ID'];
			$document[$code] = $uri->getLocator();
		}
	}

	protected static function getForms(int $entityTypeId = null): array
	{
		$forms = WebForm\Manager::getActiveForms([
			'select' => ['ID', 'NAME', 'CODE', 'SECURITY_CODE', 'ENTITY_SCHEME'],
			'order' => ['ID' => 'DESC'],
			'cache' => ['ttl' => 36000]
		]);

		if (!$entityTypeId)
		{
			return $forms;
		}

		$filtered = [];
		foreach ($forms as $form)
		{
			if (!WebForm\Entity::isSchemeSupportEntity($form['ENTITY_SCHEME'], $entityTypeId))
			{
				continue;
			}

			$filtered[] = $form;
		}

		return $filtered;
	}
}
