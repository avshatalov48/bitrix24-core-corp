<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class EntityConversionMapItem
{
	protected $srcField = '';
	protected $altSrcFields = array();
	protected $dstField = '';
	protected $isLocked = false;
	protected $isRequired = false;

	public function __construct($srcField = '', $dstField = '', array $options = null)
	{
		$this->setSourceField($srcField);
		$this->setDestinationField($dstField);

		if(is_array($options))
		{
			if(isset($options['ALT_SRC_FIELD_IDS']) && is_array($options['ALT_SRC_FIELD_IDS']))
			{
				$this->setAlternativeSourceFields($options['ALT_SRC_FIELD_IDS']);
			}

			if(isset($options['IS_LOCKED']))
			{
				$this->markAsLocked($options['IS_LOCKED']);
			}

			if(isset($options['IS_REQUIRED']))
			{
				$this->markAsRequired($options['IS_REQUIRED']);
			}
		}
	}

	public function getSourceField()
	{
		return $this->srcField;
	}

	public function setSourceField($field)
	{
		$this->srcField = $field;
	}

	public function getAlternativeSourceFields()
	{
		return $this->altSrcFields;
	}

	public function setAlternativeSourceFields(array $fieldIDs)
	{
		return $this->altSrcFields = $fieldIDs;
	}

	public function getDestinationField()
	{
		return $this->dstField;
	}

	public function setDestinationField($field)
	{
		$this->dstField = $field;
	}

	public function isLocked()
	{
		return $this->isLocked;
	}

	public function markAsLocked($isLocked)
	{
		return $this->isLocked = $isLocked;
	}

	public function isRequired()
	{
		return $this->isRequired;
	}

	public function markAsRequired($isRequired)
	{
		return $this->isRequired = $isRequired;
	}

	public static function isDynamicField($fieldID)
	{
		return strpos($fieldID, 'UF_') === 0;
	}

	public function externalize()
	{
		return array(
			'srcField' => $this->srcField,
			'dstField' => $this->dstField,
			'altSrcFields' => $this->altSrcFields,
			'isLocked' => $this->isLocked,
			'isRequired' => $this->isRequired
		);
	}

	public function internalize(array $params)
	{
		$this->srcField = isset($params['srcField']) ? $params['srcField'] : '';
		$this->dstField = isset($params['dstField']) ? $params['dstField'] : '';
		$this->altSrcFields = isset($params['altSrcFields']) ? $params['altSrcFields'] : array();
		$this->isLocked = isset($params['isLocked']) ? $params['isLocked'] : false;
		$this->isRequired = isset($params['isRequired']) ? $params['isRequired'] : false;
	}
}
