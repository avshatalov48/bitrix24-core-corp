<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\CustomerType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Rest\Configuration\Helper;
use CCrmOwnerType;
use Exception;

class DetailConfiguration
{

	const ENTITY_CODE = 'CRM_DETAIL_CONFIGURATION';

	private $entityTypeDetailConfiguration = [];

	private static $instance = null;

	private $accessManifest = [
		'total',
		'crm'
	];

	/**
	 * DetailConfiguration constructor.
	 */
	private function __construct()
	{
		$this->entityTypeDetailConfiguration = [
			'LEAD'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Lead,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
			'DEAL'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Deal,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
			'CONTACT'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Contact,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
			'COMPANY'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Company,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
		];

	}

	/**
	 * @return DetailConfiguration|null
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
	 * @return array
	 * @throws ArgumentException
	 */
	private function exportDetailConfigurationList()
	{
		$return = $this->entityTypeDetailConfiguration;
		unset($return['LEAD'.EntityEditorConfigScope::COMMON]);
		$return = array_keys($return);

		$return[] = 'LEAD'.EntityEditorConfigScope::COMMON.'_'.CustomerType::GENERAL;
		$return[] = 'LEAD'.EntityEditorConfigScope::COMMON.'_'.CustomerType::RETURNING;

		if(DealCategory::isCustomized())
		{
			$category = DealCategory::getAll(false);
			foreach ($category as $item)
			{
				if(!$item['IS_DEFAULT'])
				{
					$return[] = 'DEAL'.EntityEditorConfigScope::COMMON.'_'.$item['ID'];
				}
			}
		}

		return $return;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 * @throws InvalidOperationException
	 * @throws NotSupportedException
	 */
	public function export($option)
	{
		if(!Helper::checkAccessManifest($option, $this->accessManifest))
		{
			return null;
		}

		$step = false;
		if(array_key_exists('STEP', $option))
		{
			$step = $option['STEP'];
		}

		$keys = $this->exportDetailConfigurationList();
		$typeEntity = $keys[$step]?:'';
		$return = [
			'FILE_NAME' => $typeEntity,
			'CONTENT' => [],
			'NEXT' => count($keys) > $step+1 ? $step : false
		];

		if(!empty($this->entityTypeDetailConfiguration[$typeEntity]))
		{
			global $USER;
			$extras = [];
			$config = new EntityEditorConfig(
				$this->entityTypeDetailConfiguration[$typeEntity]['ID'],
				$USER->GetID(),
				$this->entityTypeDetailConfiguration[$typeEntity]['SCOPE'],
				$extras
			);
			try
			{
				$return['CONTENT'] = [
					'ENTITY' => $typeEntity,
					'DATA' => $config->get()
				];
			}
			catch (Exception $e)
			{
			}
		}
		elseif(mb_strpos($typeEntity,'DEAL') !== false || mb_strpos($typeEntity,'LEAD') !== false)
		{
			list($entity, $id) = explode('_', $typeEntity,2);
			if($this->entityTypeDetailConfiguration[$entity])
			{
				global $USER;
				$id = intVal($id);
				if(mb_strpos($typeEntity,'DEAL') !== false)
				{
					$extras = [
						'DEAL_CATEGORY_ID' => $id
					];
				}
				else
				{
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}
				$config = new EntityEditorConfig(
					$this->entityTypeDetailConfiguration[$entity]['ID'],
					$USER->GetID(),
					$this->entityTypeDetailConfiguration[$entity]['SCOPE'],
					$extras
				);
				if($id > 0)
				{
					if($extras['DEAL_CATEGORY_ID'])
					{
						$category = array_column(DealCategory::getAll(false), null, 'ID');
						if(!empty($category[$id]))
						{
							$return['CONTENT'] = [
								'ENTITY' => $typeEntity,
								'DATA' => $config->get()
							];
						}
					}
					else
					{
						$return['CONTENT'] = [
							'ENTITY' => $typeEntity,
							'DATA' => $config->get()
						];
					}
				}
			}
		}

		return $return;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 */
	public function clear($option)
	{
		if(!Helper::checkAccessManifest($option, $this->accessManifest))
		{
			return null;
		}

		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];
		if($clearFull)
		{
			global $USER;

			$configurationEntity = $this->exportDetailConfigurationList();
			foreach ($configurationEntity as $entity)
			{
				$extras = [];
				if (mb_strpos($entity, 'LEAD') !== false)
				{
					list($entity, $id) = explode('_', $entity, 2);
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}

				if($this->entityTypeDetailConfiguration[$entity])
				{
					$id = $this->entityTypeDetailConfiguration[$entity]['ID'];
					$scope = $this->entityTypeDetailConfiguration[$entity]['SCOPE'];
				}
				else
				{
					continue;
				}

				$config = new EntityEditorConfig(
					$id,
					$USER->GetID(),
					$scope,
					$extras
				);
				try
				{
					$config->reset();
					$config->forceCommonScopeForAll();
				}
				catch (\Exception $e)
				{
				}
			}
		}

		return $result;
	}

	/**
	 * @param $import
	 *
	 * @return mixed
	 * @throws ArgumentException
	 * @throws InvalidOperationException
	 * @throws NotSupportedException
	 */
	public function import($import)
	{
		if(!Helper::checkAccessManifest($import, $this->accessManifest))
		{
			return null;
		}

		$return = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $return;
		}
		$item = $import['CONTENT']['DATA'];
		if(!$item['ENTITY'] || !$item['DATA'])
		{
			return $return;
		}
		if($this->entityTypeDetailConfiguration[$item['ENTITY']])
		{
			global $USER;
			$extras = [];
			$config = new EntityEditorConfig(
				$this->entityTypeDetailConfiguration[$item['ENTITY']]['ID'],
				$USER->GetID(),
				$this->entityTypeDetailConfiguration[$item['ENTITY']]['SCOPE'],
				$extras
			);
			$data = $config->normalize($item['DATA'], ['remove_if_empty_name' => true]);
			$data = $config->sanitize($data);
			if(!empty($data))
			{
				$config->set($data);
				$config->forceCommonScopeForAll();
			}
		}
		elseif(mb_strpos($item['ENTITY'],'DEAL') !== false || mb_strpos($item['ENTITY'],'LEAD') !== false)
		{
			list($entity, $id) = explode('_', $item['ENTITY'],2);
			if($this->entityTypeDetailConfiguration[$entity])
			{
				global $USER;
				$id = intVal($id);
				if(mb_strpos($item['ENTITY'],'DEAL') !== false)
				{
					if(!empty($import['RATIO'][Status::ENTITY_CODE][$id]))
					{
						$id = $import['RATIO'][Status::ENTITY_CODE][$id];
					}

					$extras = [
						'DEAL_CATEGORY_ID' => $id
					];
				}
				else
				{
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}
				$config = new EntityEditorConfig(
					$this->entityTypeDetailConfiguration[$entity]['ID'],
					$USER->GetID(),
					$this->entityTypeDetailConfiguration[$entity]['SCOPE'],
					$extras
				);
				$errors = [];
				$data = $config->normalize($item['DATA'], ['remove_if_empty_name' => true]);
				if(!$config->check($data, $errors))
				{
					$return['ERROR_MESSAGES'][] = $errors;
				}
				else
				{
					$data = $config->sanitize($data);
					if(!empty($data))
					{
						try
						{
							$config->set($data);
							$config->forceCommonScopeForAll();
						}
						catch (\Exception $e)
						{
						}
					}
				}
			}
		}

		return $return;
	}
}