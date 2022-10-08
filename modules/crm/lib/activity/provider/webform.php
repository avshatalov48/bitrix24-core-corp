<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Result as WebFormResult;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class WebForm extends Base
{
	const PROVIDER_ID = 'CRM_WEBFORM';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	/**
	 * Get Provider Name
	 * @return string
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_WEBFORM_NAME');
	}
	
	/**
	 * Is type editable
	 * @param null|string $providerId Provider id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	/**
	 * Checks provider status.
	 * @return bool
	 */
	public static function isActive()
	{
		static $formCount = null;
		if($formCount === null)
		{
			$formCount = FormTable::getCount();
		}

		return $formCount > 0;
	}

	/**
	 * Provider status anchor (active, inactive, settings URL etc.)
	 * @return array
	 */
	public static function getStatusAnchor()
	{
		return array(
			'TEXT' => static::isActive() ? Loc::getMessage('CRM_ACTIVITY_WEBFORM_STATUS_ACT') : Loc::getMessage('CRM_ACTIVITY_WEBFORM_STATUS_INACT'),
			'URL' => Option::get('crm', 'path_to_webform_list', '/crm/webform/list/'),
		);
	}

	/**
	 * Return type list
	 * @return array
	 */
	public static function getTypes()
	{
		$types = array();
		$formDb = FormTable::getDefaultTypeList(array(
			'select' => array('ID', 'NAME'),
			'order' => array('NAME' => 'ASC', 'ID' => 'ASC'),
		));
		while($form = $formDb->fetch())
		{
			$types[] = array(
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => $form['ID'],
				'NAME' => $form['NAME'],
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Incoming => $form['NAME']
				)
			);
		}

		return $types;
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_WEBFORM_NAME')
			)
		);
	}

	/**
	 * Render View
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		$fields = $activity['PROVIDER_PARAMS']['FIELDS'];
		if (!is_array($fields))
		{
			$fields = array();
		}
		$fieldTemplate = '
			<label class="crm-task-list-form-container" for="">
				<span class="crm-task-list-form-name" title="%caption%">%caption%%required%:</span>
				<span class="crm-task-list-form-field">%values%</span>
			</label>
		';
		$fieldsString = WebFormResult::formatFieldsByTemplate($fields, $fieldTemplate, '%value%<br>', '%value%<br>');

		$add = '';
		$link = htmlspecialcharsbx($activity['PROVIDER_PARAMS']['FORM']['LINK']);
		if (isset($activity['PROVIDER_PARAMS']['FORM']['IP']))
		{
			$ip = htmlspecialcharsbx($activity['PROVIDER_PARAMS']['FORM']['IP']);
			$add .= '<label class="crm-task-list-form-container">' .
				'<span class="crm-task-list-form-name">' . Loc::getMessage('CRM_ACTIVITY_WEBFORM_IP') . ': </span>' .
				'<span class="crm-task-list-form-field"> ' . $ip . '</span>' .
			'</label>';
		}
		if (isset($activity['PROVIDER_PARAMS']['FORM']['IS_USED_USER_CONSENT']))
		{
			$isUserConsentUsed = $activity['PROVIDER_PARAMS']['FORM']['IS_USED_USER_CONSENT'];
			$isUserConsentUsed = $isUserConsentUsed ? Loc::getMessage('CRM_ACTIVITY_WEBFORM_YES') : Loc::getMessage('CRM_ACTIVITY_WEBFORM_NO');
			$add .= '<label class="crm-task-list-form-container">' .
				'<span class="crm-task-list-form-name">' . Loc::getMessage('CRM_ACTIVITY_WEBFORM_USER_CONSENT') . ': </span>' .
				'<span class="crm-task-list-form-field"> ' . $isUserConsentUsed . '</span>' .
			'</label>';
		}
		$visitedPagesString = '';
		if (isset($activity['PROVIDER_PARAMS']['VISITED_PAGES']) && is_array($activity['PROVIDER_PARAMS']['VISITED_PAGES']))
		{
			$maxPageCount = 5;
			foreach ($activity['PROVIDER_PARAMS']['VISITED_PAGES'] as $visitedPage)
			{
				$pageDate = DateTime::createFromTimestamp($visitedPage['DATE']);
				$pageLink = '<a href="' . HtmlFilter::encode($visitedPage['HREF']) . '" target="_blank">' . HtmlFilter::encode($visitedPage['TITLE']) . '</a>';
				$visitedPagesString .= '
					<label class="crm-task-list-form-container" for="">
						<span class="crm-task-list-form-pages-name">' . $pageDate . ':</span>
						<span class="crm-task-list-form-pages-field">' . $pageLink . '</span>
					</label>
				';
				$maxPageCount--;
				if ($maxPageCount <= 0)
				{
					break;
				}
			}

			if ($visitedPagesString)
			{
				$visitedPagesString = '
					<div class="crm-task-list-form-pages">
							<div class="crm-task-list-form-pages-caption">' . Loc::getMessage('CRM_ACTIVITY_WEBFORM_VISITED_PAGES') . ':</div>
							' . $visitedPagesString . '
					</div>';
			}
		}

		return '
			<div class="crm-task-list-form">
				<div class="crm-task-list-form-inner">
					' . $fieldsString . '
				</div>
				<div class="crm-task-list-form-inner">' . $add . '</div>
				<div class="crm-task-list-form-adress">
					<div class="crm-task-list-form-adress-name">' . Loc::getMessage('CRM_ACTIVITY_WEBFORM_FIELDS_LINK') . ':</div>
					<a href="' . $link . '" class="crm-task-list-form-adress-link" target="_blank">' . $link . '</a>
				</div>
				' . $visitedPagesString . '
			</div>
		';
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY,
			CommunicationStatistics::STATISTICS_MONEY
		);
	}

	public static function canCompleteOnView($providerTypeId = null)
	{
		return true;
	}

}