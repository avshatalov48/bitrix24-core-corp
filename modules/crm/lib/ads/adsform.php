<?php

namespace Bitrix\Crm\Ads;

use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\Ads\Form\FieldMapper;
use Bitrix\Main\EventManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

use Bitrix\Seo\LeadAds;

Loc::loadMessages(__FILE__);

/**
 * Class AdsForm.
 * @package Bitrix\Crm\Ads
 */
class AdsForm extends AdsService
{
	/**
	 * Can use.
	 *
	 * @return bool
	 */
	public static function canUse()
	{
		return parent::canUse();
	}

	/**
	 * Get service types.
	 *
	 * @return array
	 */
	public static function getServiceTypes()
	{
		$types = [];
		foreach (parent::getServiceTypes() as $type)
		{
			if ($type === LeadAds\Service::TYPE_VKONTAKTE && self::isDisabled())
			{
				continue;
			}

			$types[] = $type;
		}

		return $types;
	}

	/**
	 * Get Service.
	 *
	 * @return LeadAds\Service
	 */
	public static function getService()
	{
		return LeadAds\Service::getInstance();
	}

	/**
	 * Remove group auth.
	 *
	 * @param string $type Type.
	 * @return void
	 */
	public static function removeGroupAuth($type)
	{
		static::getService()->getGroupAuth($type)->removeAuth();
	}

	/**
	 * Register group.
	 *
	 * @param string $type Type.
	 * @param string $groupId Group ID.
	 * @return bool
	 */
	public static function registerGroup($type, $groupId)
	{
		return static::getService()->registerGroup($type, $groupId);
	}

	/**
	 * Remove group auth.
	 *
	 * @param string $type Type.
	 * @param string $groupId Group ID.
	 * @return bool
	 */
	public static function unRegisterGroup($type, $groupId)
	{
		$result = static::getService()->unRegisterGroup($type, $groupId);
		static::removeGroupAuth($type);

		return $result;
	}

	/**
	 * Get service type name.
	 *
	 * @param string $type Type.
	 * @return string
	 */
	public static function getServiceTypeName($type)
	{
		return Loc::getMessage('CRM_ADS_FORM_TYPE_NAME_' . strtoupper($type));
	}

	/**
	 * Can user edit ads form.
	 *
	 * @param integer $userId User ID.
	 * @return bool
	 */
	public static function canUserEdit($userId)
	{
		$crmPerms = new \CCrmPerms($userId);
		return !$crmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE');
	}

	/**
	 * Get forms.
	 *
	 * @param string $type Type.
	 * @param string|null $accountId Account ID.
	 * @return array
	 */
	public static function getForms($type, $accountId = null)
	{
		$result = array();

		$form = LeadAds\Service::getForm($type);

		$form->setAccountId($accountId);
		$formResult = $form->getList();
		if ($formResult->isSuccess())
		{
			while ($formData = $formResult->fetch())
			{
				$formData = $form->normalizeListRow($formData);
				if ($formData['ID'])
				{
					$result[] = array(
						'id' => $formData['ID'],
						'name' => $formData['NAME'] ? $formData['NAME'] : $formData['ID']
					);
				}
			}
		}
		else
		{
			self::$errors = $formResult->getErrorMessages();
		}

		return $result;
	}

	/**
	 * Get providers.
	 *
	 * @param array|null $types Types.
	 * @return array
	 */
	public static function getProviders(array $types = null)
	{
		$providers = static::getServiceProviders($types);
		foreach ($providers as $type => $provider)
		{
			$form = LeadAds\Service::getForm($type);
			$account = LeadAds\Service::getAccount($type);
			$provider['URL_INFO'] =  $account->getUrlInfo();
			$provider['URL_ACCOUNT_LIST'] =  $account->getUrlAccountList();
			$provider['URL_FORM_LIST'] =  $form->getUrlFormList();
			$provider['IS_SUPPORT_ACCOUNT'] =  $form->isSupportAccount();

			$groupAuthAdapter = $form->getGroupAuthAdapter();
			$provider['GROUP'] = [
				'IS_AUTH_USED' => $form->isGroupAuthUsed(),
				'HAS_AUTH' => $groupAuthAdapter ? $groupAuthAdapter->hasAuth() : false,
				'AUTH_URL' => $groupAuthAdapter ? $groupAuthAdapter->getAuthUrl() : null,
				'GROUP_ID' => []
			];
			if ($provider['GROUP']['HAS_AUTH'])
			{
				$provider['GROUP']['GROUP_ID'] = current($form->getRegisteredGroups());
			}

			$providers[$type] = $provider;
		}

		return $providers;
	}

	/**
	 * Return true if it has form links.
	 *
	 * @param integer $crmFormId Crm form ID.
	 * @param string $type Type.
	 * @return bool
	 */
	public static function hasFormLinks($crmFormId, $type = null)
	{
		if ($type)
		{
			$types = array($type);
		}
		else
		{
			$types = static::getServiceTypes();
		}

		foreach ($types as $type)
		{
			$links = static::getFormLinks($crmFormId, $type);
			if (count($links) > 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get linked forms.
	 *
	 * @param string $type Type.
	 * @return array
	 */
	public static function getLinkedForms($type)
	{
		$result = array();
		$linkDb = Internals\AdsFormLinkTable::getList(array(
			'select' => array('WEBFORM_ID'),
			'filter' => array('=ADS_TYPE' => $type),
			'cache' => array('ttl' => 300),
			'order' => array('DATE_INSERT' => 'DESC'),
		));
		while ($link = $linkDb->fetch())
		{
			$result[] = $link['WEBFORM_ID'];
		}

		return $result;
	}

	/**
	 * Get form links.
	 *
	 * @param integer $crmFormId Crm form ID.
	 * @param string $type Type.
	 * @return array
	 */
	public static function getFormLinks($crmFormId, $type)
	{
		$linkDb = Internals\AdsFormLinkTable::getList(array(
			'select' => array(
				'ADS_FORM_NAME', 'ADS_FORM_ID',
				'ADS_ACCOUNT_NAME', 'ADS_ACCOUNT_ID',
				'DATE_INSERT'
			),
			'filter' => array(
				'=WEBFORM_ID' => $crmFormId,
				'=ADS_TYPE' => $type
			),
			'limit' => 3,
			'cache' => array('ttl' => 300),
			'order' => array('DATE_INSERT' => 'DESC'),
		));
		$linkDb->addFetchDataModifier(function ($raw) {
			$raw['ADS_FORM_NAME'] = $raw['ADS_FORM_NAME'] ? $raw['ADS_FORM_NAME'] : $raw['ADS_FORM_ID'];
			$raw['ADS_ACCOUNT_NAME'] = $raw['ADS_ACCOUNT_NAME'] ? $raw['ADS_ACCOUNT_NAME'] : $raw['ADS_ACCOUNT_ID'];

			/** @var DateTime $dateInsert */
			$dateInsert = $raw['DATE_INSERT'];
			$timestamp = $dateInsert ? $dateInsert->getTimestamp() : time() + \CTimeZone::getOffset();
			$raw['DATE_INSERT_DISPLAY'] = \FormatDate('x', $timestamp);
			return $raw;
		});

		return $linkDb->fetchAll();
	}

	/**
	 * Export form.
	 *
	 * @param string $type Type.
	 * @param string $accountId Account ID.
	 * @param integer $crmFormId Crm form ID.
	 * @param array $parameters Parameters.
	 * @return bool
	 */
	public static function exportForm($type, $accountId, $crmFormId, array $parameters = array())
	{
		static::resetErrors();

		// 0. Prepare fields.
		$crmForm = new Form;
		if (!$crmForm->load($crmFormId))
		{
			static::$errors[] = "Form #{$crmFormId} not found.";
			return false;
		}

		$fields = FieldMapper::toAdsForm($crmForm);
		if (empty($fields))
		{
			static::$errors[] = "Can not map Form #{$crmFormId} fields.";
			return false;
		}

		$crmFormData = $crmForm->get();
		$formName = (isset($parameters['ADS_FORM_NAME']) && $parameters['ADS_FORM_NAME']) ? $parameters['ADS_FORM_NAME'] : $crmFormData['NAME'];
		$formSuccessUrl = (isset($parameters['ADS_FORM_SUCCESS_URL']) && $parameters['ADS_FORM_SUCCESS_URL']) ? $parameters['ADS_FORM_SUCCESS_URL'] : '';
		$locale = (isset($parameters['LOCALE']) && $parameters['LOCALE']) ? $parameters['LOCALE'] : null;
		$formTitle = (isset($crmFormData['CAPTION']) && $crmFormData['CAPTION']) ? $crmFormData['CAPTION'] : $formName;
		$description = (isset($crmFormData['DESCRIPTION']) && $crmFormData['DESCRIPTION']) ? $crmFormData['DESCRIPTION'] : '';
		if ($description)
		{
			$bbCodeParser = new \CTextParser();
			$description = $bbCodeParser->convertText($description);
			$description = str_replace(array('<br>', '<br/>', '<br />'), "\n", $description);
			$description = \CTextParser::clearAllTags($description);
		}

		$privacyPolicyUrl = $crmForm->getAgreementUrl();
		if (!$formSuccessUrl)
		{
			$formSuccessUrl = $crmForm->getSuccessPageUrl();
		}

		// 1. Send add query to Facebook.
		$form = LeadAds\Service::getForm($type);
		$form->setAccountId($accountId);
		$addResult = $form->add(array(
			'NAME' => $formName,
			'TITLE' => $formTitle,
			'DESCRIPTION' => $description,
			'SUCCESS_URL' => $formSuccessUrl,
			'PRIVACY_POLICY_URL' => $privacyPolicyUrl,
			'BUTTON_CAPTION' => $crmForm->getButtonCaption(),
			'FIELDS' => $fields,
			'LOCALE' => $locale,
		));
		if (!$addResult->isSuccess() || !$addResult->getId())
		{
			static::$errors = $addResult->getErrorMessages();
			return false;
		}
		$adsFormId = $addResult->getId();

		// 2. Save link "Facebook GroupId"-with-"Portal url" in table b_crm_ads_form.
		$addLinkResult = Internals\AdsFormLinkTable::add(array(
			'LINK_DIRECTION' => Internals\AdsFormLinkTable::LINK_DIRECTION_EXPORT,
			'WEBFORM_ID' => $crmFormId,
			'ADS_TYPE' => $type,
			'ADS_ACCOUNT_ID' => $accountId,
			'ADS_FORM_ID' => $adsFormId,
			'ADS_ACCOUNT_NAME' => isset($parameters['ADS_ACCOUNT_NAME']) ? $parameters['ADS_ACCOUNT_NAME'] : '',
			'ADS_FORM_NAME' => $formName,
		));
		if (!$addLinkResult->isSuccess())
		{
			static::$errors = $addLinkResult->getErrorMessages();
			return false;
		}

		// 3. Register web hook handler.
		EventManager::getInstance()->registerEventHandler(
			'seo',
			'OnWebHook',
			'crm',
			'\Bitrix\Crm\Ads\Form\WebHookFormFillHandler',
			'handleEvent'
		);

		return true;
	}

	/**
	 * Unlink form.
	 *
	 * @param integer $crmFormId Crm form ID.
	 * @param string|null $type Type.
	 * @return bool
	 */
	public static function unlinkForm($crmFormId, $type = null)
	{
		// Check
		$crmForm = new Form;
		if (!$crmForm->load($crmFormId))
		{
			static::$errors[] = "Form #{$crmFormId} not found.";
			return false;
		}

		$filter = array(
			'=WEBFORM_ID' => $crmFormId,
			'=LINK_DIRECTION' => Internals\AdsFormLinkTable::LINK_DIRECTION_EXPORT
		);
		if ($type)
		{
			$filter['=ADS_TYPE'] = $type;
		}

		// Remove link "Facebook GroupId"-with-"Portal url" in table b_crm_ads_form.
		$links = Internals\AdsFormLinkTable::getList(array(
			'select' => array('ID', 'ADS_TYPE', 'ADS_ACCOUNT_ID', 'ADS_FORM_ID'),
			'filter' => $filter
		));
		$result = true;
		while ($link = $links->fetch())
		{
			$form = LeadAds\Service::getForm($link['ADS_TYPE']);
			$form->setAccountId($link['ADS_ACCOUNT_ID']);
			if (!$form->unlink($link['ADS_FORM_ID']))
			{
				static::$errors[] = "Can't unlink form.";
				return false;
			}
			$deleteResult = Internals\AdsFormLinkTable::delete($link['ID']);
			if (!$deleteResult->isSuccess())
			{
				static::$errors = $deleteResult->getErrorMessages();
				return false;
			}
		}

		return $result;
	}

	/**
	 * Import form.
	 *
	 * @param string $type Type.
	 * @param string $accountId Account ID.
	 * @param string $serviceFormId Service form ID.
	 * @throws NotImplementedException
	 */
	public static function importForm($type, $accountId, $serviceFormId)
	{
		throw new NotImplementedException();
	}

	/**
	 * Get temporary disabled message.
	 *
	 * @param string $type Type.
	 * @return string
	 */
	public static function getTemporaryDisabledMessage($type)
	{
		if (!self::isDisabled())
		{
			return null;
		}

		return Loc::getMessage('CRM_ADS_FORM_TYPE_ERR_DISABLED_' . strtoupper($type));
	}

	protected static function isDisabled()
	{
		return false;
	}

	/**
	 * Return map "connector id" - "icon name" for UI-lib icon classes
	 *
	 * @return array
	 */
	public static function getAdsIconMap()
	{
		return array(
			LeadAds\Service::TYPE_FACEBOOK => 'fb-adds',
			LeadAds\Service::TYPE_VKONTAKTE => 'vk-adds',
		);
	}

	/**
	 * @deprecated
	 *
	 * @return string
	 */
	public static function getServicesBackgroundColorCss()
	{
		$style = '';
		$cssFile = (file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/ui/icons/service/ui.icons.service.css') ?
			'/bitrix/js/ui/icons/service/ui.icons.service.css' : '/bitrix/js/ui/icons/ui.icons.css');
		$cssFilePath = $_SERVER["DOCUMENT_ROOT"] . $cssFile;
		$cssFile = file_get_contents($cssFilePath);

		if (!empty($cssFile))
		{
			$cssList = Main\Web\DOM\CssParser::parse($cssFile);

			if (!empty($cssList))
			{
				$column = array_column($cssList, 'SELECTOR');
				$adsList = self::getAdsIconMap();

				foreach ($adsList as $key => $ad)
				{
					$position = array_search('.ui-icon-service-' . $ad . ' > i', $column);

					if ($position !== false)
					{
						$style .= '.crm-' . $key . '-background-color { background-color: ' . $cssList[$position]['STYLE']['background-color'] . '; }' . PHP_EOL;
						$style .= '.intranet-' . $key . '-background-color { background-color: ' . $cssList[$position]['STYLE']['background-color'] . '; }' . PHP_EOL;
					}
				}
			}
		}

		return $style;
	}
}