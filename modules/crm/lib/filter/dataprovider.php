<?php
namespace Bitrix\Crm\Filter;

abstract class DataProvider
{
	/**
	 * Get Settings
	 * @return Settings
	 */
	abstract public function getSettings();

	/**
	 * Get ID.
	 * @return string
	 */
	public function getID()
	{
		return $this->getSettings()->getID();
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public abstract function prepareFields();
	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public abstract function prepareFieldData($fieldID);

	/**
	 * Prepare Field additional HTML.
	 * @param Field $field Field.
	 * @return string
	 */
	public function prepareFieldHtml(Field $field)
	{
		$info = $field->getDataItem('selector');
		if(!is_array($info))
		{
			return '';
		}

		$type = isset($info['TYPE']) ? $info['TYPE'] : '';
		if($type === 'user')
		{
			return $this->getUserSelectorHtml($field);
		}
		elseif($type === 'crm_entity')
		{
			return $this->getCrmSelectorHtml($field);
		}
		return '';
	}

	/**
	 * Render User selector.
	 * @param Field $field Target Field.
	 * @return string
	 */
	protected function getUserSelectorHtml(Field $field)
	{
		global $APPLICATION;

		$info = $field->getDataItem('selector');
		if(!is_array($info))
		{
			return '';
		}

		if(!(isset($info['TYPE']) && $info['TYPE'] === 'user' && isset($info['DATA']) && is_array($info['DATA'])))
		{
			return '';
		}

		$fieldID = isset($info['DATA']['FIELD_ID']) ? $info['DATA']['FIELD_ID'] : '';
		$selectorID = isset($info['DATA']['ID']) ? $info['DATA']['ID'] : '';
		if($fieldID === '' || $selectorID === '')
		{
			return '';
		}

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:main.ui.selector',
			'.default',
			array(
				'ID' => $selectorID,
				'ITEMS_SELECTED' =>  array(),
				'CALLBACK' => array(
					'select' => 'BX.CrmUIFilterUserSelector.processSelection',
					'unSelect' => '',
					'openDialog' => 'BX.CrmUIFilterUserSelector.processDialogOpen',
					'closeDialog' => 'BX.CrmUIFilterUserSelector.processDialogClose',
					'openSearch' => ''
				),
				'OPTIONS' => array(
					'eventInit' => 'BX.Crm.FilterUserSelector:openInit',
					'eventOpen' => 'BX.Crm.FilterUserSelector:open',
					'context' => 'FEED_FILTER_CREATED_BY',
					'contextCode' => 'U',
					'useSearch' => 'N',
					'userNameTemplate' => \CSite::GetNameFormat(false),
					'useClientDatabase' => 'Y',
					'allowEmailInvitation' => 'N',
					'enableDepartments' => 'Y',
					'enableSonetgroups' => 'N',
					'departmentSelectDisable' => 'Y',
					'allowAddUser' => 'N',
					'allowAddCrmContact' => 'N',
					'allowAddSocNetGroup' => 'N',
					'allowSearchEmailUsers' => 'N',
					'allowSearchCrmEmailUsers' => 'N',
					'allowSearchNetworkUsers' => 'N',
					'allowSonetGroupsAjaxSearchFeatures' => 'N'
				)
			),
			false,
			array('HIDE_ICONS' => 'Y')
		);
		$html = ob_get_contents();
		ob_end_clean();

		//Initialize filter user selector
		$jsID = \CUtil::JSEscape($this->getID());
		$jsField = \CUtil::JSEscape($fieldID);
		$jsSelector = \CUtil::JSEscape($selectorID);

		$html .= "<script type='text/javascript'>
			BX.ready(function(){
				var selectorId = '{$jsSelector}', fieldId = '{$jsField}', filterId = '{$jsID}';
				BX.CrmUIFilterUserSelector.remove(selectorId);
				BX.CrmUIFilterUserSelector.create(
					selectorId, 
					{ 
						filterId: filterId, 
						fieldId: fieldId
					}
				); 
		});
		</script>";

		return $html;
	}

	protected function getCrmSelectorHtml(Field $field)
	{
		$info = $field->getDataItem('selector');
		if(!is_array($info))
		{
			return '';
		}

		if(!(isset($info['TYPE']) && $info['TYPE'] === 'crm_entity' && isset($info['DATA']) && is_array($info['DATA'])))
		{
			return '';
		}

		$fieldID = isset($info['DATA']['FIELD_ID']) ? $info['DATA']['FIELD_ID'] : '';
		$selectorID = isset($info['DATA']['ID']) ? $info['DATA']['ID'] : '';
		if($fieldID === '' || $selectorID === '')
		{
			return '';
		}

		$title = isset($info['DATA']['TITLE']) ? $info['DATA']['TITLE'] : '';
		$entityTypeNames = isset($info['DATA']['ENTITY_TYPE_NAMES']) && is_array($info['DATA']['ENTITY_TYPE_NAMES'])
			? $info['DATA']['ENTITY_TYPE_NAMES'] : array();
		$isMultiple = isset($info['DATA']['IS_MULTIPLE']) && $info['DATA']['IS_MULTIPLE'] == true;

		//Initialize filter user selector
		$jsTitle = \CUtil::JSEscape($title);
		$jsField = \CUtil::JSEscape($fieldID);
		$jsSelector = \CUtil::JSEscape($selectorID);
		$jsEntityTypeNames = \CUtil::PhpToJSObject($entityTypeNames);
		$jsIsMultiple = \CUtil::PhpToJSObject($isMultiple);

		$html = "<script type='text/javascript'>
			BX.ready(function(){
				var selectorId = '{$jsSelector}', fieldId = '{$jsField}', title = '{$jsTitle}';
				BX.CrmUIFilterEntitySelector.remove(selectorId);
				BX.CrmUIFilterEntitySelector.create(
					selectorId,
					{
						fieldId: fieldId,
						title: title,
						isMultiple: {$jsIsMultiple},
						entityTypeNames: {$jsEntityTypeNames}
					}
				);
		});
		</script>";

		return $html;
	}


	/**
	 * Prepare field parameter for specified field.
	 * @param array $filter Filter params.
	 * @param string $fieldID Field ID.
	 * @return void
	 */
	public function prepareListFilterParam(array &$filter, $fieldID)
	{
	}
}