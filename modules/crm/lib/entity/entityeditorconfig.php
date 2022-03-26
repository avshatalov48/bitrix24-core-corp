<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Ui\EntityForm\Scope;

class EntityEditorConfig
{
	public const CATEGORY_NAME = 'crm.entity.editor';

	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $userID = 0;
	protected $scope = EntityEditorConfigScope::UNDEFINED;
	protected $userScopeId;
	protected $configuration;
	protected $extras = null;

	public function __construct($entityTypeID, $userID, $scope, array $extras)
	{
		if (!Main\Loader::includeModule('ui'))
		{
			throw new Main\NotSupportedException('ui module is required');
		}

		$this->entityTypeID = (int)$entityTypeID;
		$this->setUserID($userID > 0 ? $userID : \CCrmSecurityHelper::GetCurrentUserID());
		$this->setScope($scope);
		$this->extras = $extras;
		$this->configuration = new \Bitrix\UI\Form\EntityEditorConfiguration(self::CATEGORY_NAME);
	}

	public static function createWithCurrentScope(int $entityTypeId, array $extras = [])
	{
		$userId = $extras['USER_ID'] ?? \CCrmSecurityHelper::GetCurrentUserID();
		$result = new self($entityTypeId, $userId, EntityEditorConfigScope::PERSONAL, $extras);
		$result->setCurrentScope();

		return $result;
	}

	public function getUserID()
	{
		return $this->userID;
	}

	public function setUserID($userID)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}
		$this->userID = max($userID, 0);
	}

	public function getScope()
	{
		return $this->scope;
	}

	public function setCurrentScope()
	{
		$configId = $this->getConfigId();
		$configScope = $this->configuration->getScope($configId);
		$userScopeId = 0;
		if (is_array($configScope))
		{
			$userScopeId = (int)$configScope['userScopeId'];
			$configScope = $configScope['scope'];
		}
		if (!Crm\Entity\EntityEditorConfigScope::isDefined($configScope))
		{
			$configScope = Crm\Entity\EntityEditorConfigScope::COMMON;
		}
		if (
			$configScope !== Crm\Entity\EntityEditorConfigScope::CUSTOM
			|| !$userScopeId
			|| !isset(Scope::getInstance()->getUserScopes($configId, 'crm')[$userScopeId])
		)
		{
			$userScopeId = null;
			if ($configScope === Crm\Entity\EntityEditorConfigScope::CUSTOM)
			{
				$configScope = Crm\Entity\EntityEditorConfigScope::COMMON;
			}
		}

		$this->setScope($configScope);
		if ($userScopeId > 0)
		{
			$this->setUserScopeId($userScopeId);
		}
	}

	public function setScope($scope)
	{
		if(!is_string($scope))
		{
			$scope = (string)$scope;
		}

		if(!Crm\Entity\EntityEditorConfigScope::isDefined($scope))
		{
			throw new Main\ArgumentException("Must belong to range specified by Crm\Entity\EntityEditorConfigScope");
		}
		$this->scope = $scope;
	}

	public function getUserScopeId()
	{
		return $this->userScopeId;
	}

	public function setUserScopeId(int $userScopeId)
	{
		$this->userScopeId = $userScopeId;
	}

	public static function isEntityTypeSupported($entityTypeID)
	{
		return $entityTypeID === \CCrmOwnerType::Lead
			|| $entityTypeID === \CCrmOwnerType::Deal
			|| $entityTypeID === \CCrmOwnerType::Contact
			|| $entityTypeID === \CCrmOwnerType::Company
			|| \CCrmOwnerType::isUseFactoryBasedApproach($entityTypeID);
	}

	protected function getConfigId(): string
	{
		$optionName = $this->resolveOptionName();
		if($optionName === '')
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new Main\NotSupportedException("The entity type '{$entityTypeName}' is not supported in current context.");
		}

		return $optionName;
	}

	protected function resolveOptionName()
	{
		switch($this->entityTypeID)
		{
			case \CCrmOwnerType::Lead:
				{
					$prefix = '';
					$customerType = isset($this->extras['LEAD_CUSTOMER_TYPE'])
						? (int)$this->extras['LEAD_CUSTOMER_TYPE'] : Crm\CustomerType::UNDEFINED;
					if($customerType !== Crm\CustomerType::UNDEFINED && $customerType !== Crm\CustomerType::GENERAL)
					{
						$prefix = mb_strtolower(Crm\CustomerType::resolveName($customerType));
					}
					$optionName = $prefix !== '' ? "{$prefix}_lead_details" : 'lead_details';
					break;
				}
			case \CCrmOwnerType::Deal:
				{
					$optionName = Crm\Category\DealCategory::prepareFormID(
						isset($this->extras['DEAL_CATEGORY_ID']) ? (int)$this->extras['DEAL_CATEGORY_ID'] : 0,
						'deal_details',
						false
					);
					break;
				}
			case \CCrmOwnerType::Contact:
				{
					$optionName = 'contact_details';
					break;
				}
			case \CCrmOwnerType::Company:
				{
					$optionName = 'company_details';
					break;
				}
			case \CCrmOwnerType::Quote:
			{
				$optionName = 'quote_details';
				break;
			}
			default:
			{
				$optionName = '';
			}
		}

		if (empty($optionName) && \CCrmOwnerType::isUseDynamicTypeBasedApproach($this->entityTypeID))
		{
			$componentName = Crm\Service\Container::getInstance()->getRouter()->getItemDetailComponentName($this->entityTypeID);
			if ($componentName)
			{
				$componentClassName = \CBitrixComponent::includeComponentClass($componentName);
				if ($componentClassName)
				{
					/** @var Crm\Component\EntityDetails\FactoryBased $component */
					$component = new $componentClassName;
					$component->initComponent($componentName);
					$params = [
						'ENTITY_TYPE_ID' => $this->entityTypeID,
					];
					$categoryId = $this->extras['CATEGORY_ID'] ?? null;
					if ($categoryId > 0)
					{
						$params['categoryId'] = $categoryId;
					}
					$component->arParams = $params;
					$component->init();
					$optionName = $component->getEditorConfigId();
				}
			}
		}

		return $optionName;
	}

	public function canDoOperation($operation)
	{
		if(strcasecmp($operation, EntityEditorConfigOperation::GET) === 0)
		{
			if($this->scope === Crm\Entity\EntityEditorConfigScope::PERSONAL)
			{
				return ($this->userID > 0 && $this->userID === \CCrmSecurityHelper::GetCurrentUserID())
					|| \CCrmAuthorizationHelper::CanEditOtherSettings();
			}
			elseif($this->scope === Crm\Entity\EntityEditorConfigScope::COMMON)
			{
				return true;
			}
		}
		elseif(strcasecmp($operation, EntityEditorConfigOperation::SET) === 0 ||
			strcasecmp($operation, EntityEditorConfigOperation::RESET) === 0
		)
		{
			if($this->scope === Crm\Entity\EntityEditorConfigScope::PERSONAL)
			{
				return ($this->userID > 0 && $this->userID === \CCrmSecurityHelper::GetCurrentUserID())
					|| \CCrmAuthorizationHelper::CanEditOtherSettings();
			}
			elseif($this->scope === Crm\Entity\EntityEditorConfigScope::COMMON)
			{
				return \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
			}
		}
		elseif(strcasecmp($operation, EntityEditorConfigOperation::FORCE_COMMON_SCOPE_FOR_ALL) === 0)
		{
			return \CCrmAuthorizationHelper::CanEditOtherSettings();
		}

		return false;
	}

	public function get()
	{
		if(!Crm\Entity\EntityEditorConfigScope::isDefined($this->scope) ||
			($this->scope === Crm\Entity\EntityEditorConfigScope::PERSONAL && $this->userID <= 0)
		)
		{
			throw new Main\InvalidOperationException("This operation is not permitted at current settings.");
		}

		if(
			$this->getScope() === Crm\Entity\EntityEditorConfigScope::CUSTOM
			&& $this->getUserScopeId()
		)
		{
			$result = Scope::getInstance()->getScopeById($this->getUserScopeId());
		}
		else
		{
			$result = $this->configuration->get($this->getConfigId(), $this->getScope());
		}

		return is_array($result) ? $this->normalize($result) : $result;
	}

	public function set(array $data)
	{
		if(empty($data))
		{
			throw new Main\ArgumentException("Must be not empty array.", "data");
		}

		if(
			!Crm\Entity\EntityEditorConfigScope::isDefined($this->scope)
			|| ($this->scope === Crm\Entity\EntityEditorConfigScope::PERSONAL && $this->userID <= 0)
		)
		{
			throw new Main\InvalidOperationException("This operation is not permitted at current settings.");
		}

		if ( // compatibility mode
			is_array($data) &&
			isset($data[0]['type']) &&
			$data[0]['type'] === 'section'
		)
		{
			$data = [
				[
					'name' => 'default_column',
					'type' => 'column',
					'elements' => $data
				]
			];
		}

		$this->configuration->set(
			$this->getConfigId(),
			$data,
			[
				'scope' => $this->getScope(),
				'userScopeId' => $this->getUserScopeId() ?: null,
			]
		);
	}

	public function reset()
	{
		if(!Crm\Entity\EntityEditorConfigScope::isDefined($this->scope) ||
			($this->scope === Crm\Entity\EntityEditorConfigScope::PERSONAL && $this->userID <= 0)
		)
		{
			throw new Main\InvalidOperationException("This operation is not permitted at current settings.");
		}

		$optionName = $this->resolveOptionName();
		if($optionName === '')
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new Main\NotSupportedException("The entity type '{$entityTypeName}' is not supported in current context.");
		}

		if($this->scope === Crm\Entity\EntityEditorConfigScope::COMMON)
		{
			$optionName = "{$optionName}_common";
		}

		return \CUserOptions::DeleteOption(
			self::CATEGORY_NAME,
			$optionName,
			$this->scope === Crm\Entity\EntityEditorConfigScope::COMMON,
			$this->scope === Crm\Entity\EntityEditorConfigScope::PERSONAL ? $this->userID : false
		);
	}

	public function forceCommonScopeForAll()
	{
		$optionName = $this->resolveOptionName();
		if($optionName === '')
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new Main\NotSupportedException("The entity type '{$entityTypeName}' is not supported in current context.");
		}

		\CUserOptions::DeleteOptionsByName(self::CATEGORY_NAME, $optionName);
		\CUserOptions::DeleteOptionsByName(self::CATEGORY_NAME, "{$optionName}_scope");
	}

	public function normalize(array $data, array $options = [])
	{
		if ( // compatibility mode
			isset($data[0]) &&
			isset($data[0]['type']) &&
			$data[0]['type'] === 'column'
		)
		{
			$data = (array)$data[0]['elements'];
		}

		if (isset($options['remove_if_empty_name']) && $options['remove_if_empty_name'])
		{
			for($i = 0, $sectionCount = count($data); $i < $sectionCount; $i++)
			{
				if((isset($data[$i]['elements']) && is_array($data[$i]['elements'])))
				{
					for ($j = 0, $elementCount = count($data[$i]['elements']); $j < $elementCount; $j++)
					{
						if (
							!isset($data[$i]['elements'][$j]['name']) ||
							trim($data[$i]['elements'][$j]['name']) === ''
						)
						{
							unset($data[$i]['elements'][$j]);
						}
					}
				}
			}
		}

		return $data;
	}

	public function sanitize(array $data)
	{
		if(empty($data))
		{
			return array();
		}

		if(array_values($data) != $data)
		{
			$data = array_values($data);
		}

		$effectiveData = array();
		for($i = 0, $sectionCount = count($data); $i < $sectionCount; $i++)
		{
			if(isset($data[$i]['type']) && $data[$i]['type'] !== 'section')
			{
				continue;
			}

			if(!(isset($data[$i]['name']) && trim($data[$i]['name']) !== ''))
			{
				continue;
			}

			$effectiveSection = array(
				'type' => 'section',
				'name' => $data[$i]['name'],
				'title' => isset($data[$i]['title']) ? $data[$i]['title'] : $data[$i]['name']
			);

			$effectiveElements = array();
			if(isset($data[$i]['elements']) && is_array($data[$i]['elements']))
			{
				for($j = 0, $elementCount = count($data[$i]['elements']); $j < $elementCount; $j++)
				{
					if(!(isset($data[$i]['elements'][$j]['name']) && trim($data[$i]['elements'][$j]['name']) !== ''))
					{
						continue;
					}

					$effectiveElement = array('name' => $data[$i]['elements'][$j]['name']);
					if(isset($data[$i]['elements'][$j]['optionFlags'])
						&& is_numeric($data[$i]['elements'][$j]['optionFlags'])
					)
					{
						$effectiveElement['optionFlags'] = $data[$i]['elements'][$j]['optionFlags'];
					}
					if(isset($data[$i]['elements'][$j]['options'])
						&& is_array($data[$i]['elements'][$j]['options'])
					)
					{
						$effectiveElement['options'] = $data[$i]['elements'][$j]['options'];
					}

					$effectiveElements[] = $effectiveElement;
				}
			}
			$effectiveSection['elements'] = $effectiveElements;
			$effectiveData[] = $effectiveSection;
		}
		return $effectiveData;
	}

	public function check(array &$data, array &$errors)
	{
		if(empty($data))
		{
			$errors[] = "There are no data";
			return false;
		}

		if(array_values($data) != $data)
		{
			$errors[] = "The data must be indexed array.";
			return false;
		}

		for($i = 0, $sectionCount = count($data); $i < $sectionCount; $i++)
		{
			if(!isset($data[$i]['type']) || trim($data[$i]['type']) === '')
			{
				$data[$i]['type'] = 'section';
			}

			if(!(isset($data[$i]['type']) && $data[$i]['type'] === 'section'))
			{
				$errors[] = "Section at index {$i} have type '{$data[$i]['type']}'. The expected type is 'section'.";
				return false;
			}

			if(!(isset($data[$i]['name']) && trim($data[$i]['name']) !== ''))
			{
				$errors[] = "Section at index {$i} does not have name.";
				return false;
			}

			if(!(isset($data[$i]['title']) && trim($data[$i]['title']) !== ''))
			{
				$errors[] = "Section at index {$i} does not have title.";
				return false;
			}

			if(!(isset($data[$i]['elements']) && is_array($data[$i]['elements'])))
			{
				$data[$i]['elements'] = array();
			}

			for($j = 0, $elementCount = count($data[$i]['elements']); $j < $elementCount; $j++)
			{
				if(!(isset($data[$i]['elements'][$j]['name']) && trim($data[$i]['elements'][$j]['name']) !== ''))
				{
					$errors[] = "Element at index {$j} in section at index {$i} does not have name.";
					return false;
				}
			}
		}

		return true;
	}

	public function isFormFieldVisible(string $fieldName): bool
	{
		static $data = [];
		$cacheKey = (string)$this->userID . '_' . (string)$this->scope . '_' . (string)$this->userScopeId;
		if (!array_key_exists($cacheKey, $data))
		{
			$data[$cacheKey] = [];
			$config = $this->get();
			if (is_array($config))
			{
				foreach ($config as $section)
				{
					foreach ($section['elements'] as $element)
					{
						$data[$cacheKey][] = $element['name'];
					}
				}
			}
		}

		return in_array($fieldName, $data[$cacheKey], true);
	}
}