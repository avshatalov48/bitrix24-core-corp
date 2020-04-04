<?php
namespace Bitrix\Crm\Merger;

abstract class ContactBindingMerger
{
	private $fieldName = '';
	public function __construct($fieldName)
	{
		$this->fieldName = $fieldName !== '' ? $fieldName : 'CONTACT_BINDINGS';
	}

	abstract protected function getBindings(array $entityFields);
	abstract protected function getMappedIDs(array $map);

	public function merge(array &$seeds, array &$targ, $skipEmpty = false, array $options = array())
	{
		$resultSeedContactBindings = array();
		$effectiveIDs = null;

		$map = null;
		if(isset($options['map']) && is_array($options['map']))
		{
			$map = $options['map'];
		}

		$sourceIDs = is_array($map) ? $this->getMappedIDs($map) : null;
		if(is_array($sourceIDs))
		{
			$effectiveIDs = array();
			if(isset($targ['ID']))
			{
				$effectiveIDs[] = (int)$targ['ID'];
			}

			foreach($seeds as $seed)
			{
				if(isset($seed['ID']))
				{
					$effectiveIDs[] = (int)$seed['ID'];
				}
			}

			$effectiveIDs = array_intersect($sourceIDs, $effectiveIDs);
		}

		foreach($seeds as $seed)
		{
			$seedID = isset($seed['ID']) ? (int)$seed['ID'] : 0;
			if($effectiveIDs !== null && !in_array($seedID, $effectiveIDs))
			{
				continue;
			}

			$seedContactBindings = $this->getBindings($seed);
			if($seedContactBindings !== null)
			{
				EntityMerger::mergeEntityBindings(
					\CCrmOwnerType::Contact,
					$seedContactBindings,
					$resultSeedContactBindings
				);
			}
		}

		$targID = isset($targ['ID']) ? (int)$targ['ID'] : 0;
		if($effectiveIDs !== null)
		{
			$targContactBindings = null;
			if(in_array($targID, $effectiveIDs))
			{
				$targContactBindings = $this->getBindings($targ);
			}

			if($targContactBindings === null || count($targContactBindings) === 0)
			{
				$targContactBindings = $resultSeedContactBindings;
			}
			else
			{
				EntityMerger::mergeEntityBindings(
					\CCrmOwnerType::Contact,
					$resultSeedContactBindings,
					$targContactBindings
				);
			}
			$targ[$this->fieldName] = $targContactBindings;
		}
		else if(!empty($resultSeedContactBindings))
		{
			$targContactBindings = $this->getBindings($targ);
			$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];
			if(!$skipMultipleFields)
			{
				if($targContactBindings === null || count($targContactBindings) === 0)
				{
					$targContactBindings = $resultSeedContactBindings;
				}
				else
				{
					EntityMerger::mergeEntityBindings(
						\CCrmOwnerType::Contact,
						$resultSeedContactBindings,
						$targContactBindings
					);
				}
				$targ[$this->fieldName] = $targContactBindings;
			}
			elseif($targContactBindings === null || (count($targContactBindings) === 0 && !$skipEmpty))
			{
				$targ[$this->fieldName] = $resultSeedContactBindings;
			}
		}
	}
}
