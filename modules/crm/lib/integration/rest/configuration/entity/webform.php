<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Rest;
use Bitrix\Crm;

/**
 * Class WebForm
 * @package Bitrix\Crm\Integration\Rest\Configuration\Entity
 */
class WebForm
{
	const ENTITY_CODE = 'CRM_FORM';

	private static $instance = null;

	private $accessManifest = [
		'crm_form',
	];


	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Export.
	 *
	 * @param array $option Option.
	 * @return array|null
	 */
	public function export($option)
	{
		if(!Rest\Configuration\Helper::checkAccessManifest($option, $this->accessManifest))
		{
			return null;
		}

		$content = ['list' => []];
		$list = Crm\WebForm\Internals\FormTable::getDefaultTypeList([
			'select' => ['ID'],
			'filter' => [
				'=ACTIVE' => 'Y',
				'=IS_SYSTEM' => 'N',
			],
		]);
		foreach ($list as $item)
		{
			$options = Crm\WebForm\Options::create($item['ID'])->getArray();
			$content['list'][] = self::cleanFormOptions($options);
		}

		return [
			'FILE_NAME' => 'list',
			'CONTENT' => $content,
		];
	}

	/**
	 * Clear.
	 *
	 * @param array $option Option.
	 * @return array|null
	 */
	public function clear(array $option)
	{
		if(!Rest\Configuration\Helper::checkAccessManifest($option, $this->accessManifest))
		{
			return null;
		}

		$result = [];
		if ($option['CLEAR_FULL'])
		{
			$list = Crm\WebForm\Internals\FormTable::getDefaultTypeList([
				'select' => ['ID'],
				'filter' => [
					'=IS_SYSTEM' => 'N',
				],
			]);
			foreach ($list as $item)
			{
				Crm\WebForm\Form::delete($item['ID']);
			}
		}

		return $result;
	}

	/**
	 * Import.
	 *
	 * @param array $import Import.
	 * @return array|null
	 */
	public function import(array $import)
	{
		if(!Rest\Configuration\Helper::checkAccessManifest($import, $this->accessManifest))
		{
			return null;
		}

		$result = [];
		if(empty($import['CONTENT']['DATA']))
		{
			return $result;
		}

		$data = $import['CONTENT']['DATA'];
		if(empty($data['list']))
		{
			return $result;
		}

		foreach ($data['list'] as $options)
		{
			$options = self::cleanFormOptions($options);
			$options = Crm\WebForm\Options::createFromArray($options);
			$options->getForm()->merge([
				'ACTIVE' => 'Y',
				'XML_ID' => 'rest/crm_form',
			]);
			$options->save();
		}

		return $result;
	}

	private static function cleanFormOptions(array $options)
	{
		$options['id'] = null;
		$options['captcha'] = [];
		$options['responsible']['userId'] = null;
		$options['callback']['from'] = null;
		$options['analytics'] = [];
		$options['integration'] = [];

		$options['data']['agreements'] = [];
		$options['data']['fields'] = array_map(
			function ($field)
			{
				$field['editing'] = $field['editing']['editable'];
				return $field;
			},
			$options['data']['fields']
		);

		return $options;
	}
}