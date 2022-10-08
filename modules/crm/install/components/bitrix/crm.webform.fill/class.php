<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\PostDecodeFilter;
use Bitrix\Main\UserConsent\Agreement;

use Bitrix\Crm\WebForm;
use Bitrix\Crm\WebForm\ReCaptcha;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Helper;
use Bitrix\Crm\UI\Webpack;
use Bitrix\Crm\SiteButton\Guest;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmWebFormFillComponent extends \CBitrixComponent
{
	protected $errors = array();
	protected $formId = null;

	/** @var $form Form*/
	protected $form = null;

	protected function useCounterInSession()
	{
		return true;
	}

	protected function counterInSession($counter, $isCheck = true)
	{
		if(!$this->useCounterInSession())
		{
			return false;
		}

		$sessionKey = 'CRM_WEBFORM_COUNTERS';
		if(!$isCheck)
		{
			$_SESSION[$sessionKey][$this->formId][$counter] = 'Y';
		}

		return (
			$_SESSION[$sessionKey]
			&&
			$_SESSION[$sessionKey][$this->formId]
			&&
			$_SESSION[$sessionKey][$this->formId][$counter]
		);
	}

	protected function getAddResultParameters()
	{
		$placeholders = array();
		$commonFields = array();

		// prepare preset placeholders from url
		$fromUrl = $this->request->get('from');
		if ($fromUrl)
		{
			$uri = new \Bitrix\Main\Web\Uri($fromUrl);
			if ($uri->getLocator())
			{
				if ($uri->getQuery())
				{
					$queryParamList = array();
					parse_str($uri->getQuery(), $queryParamList);
					$placeholders = count($queryParamList) > 0 ? $queryParamList : $placeholders;
					foreach ($queryParamList as $queryParamKey => $queryParamVal)
					{
						if (!is_string($queryParamVal))
						{
							continue;
						}

						$placeholders[$queryParamKey] = \Bitrix\Main\Text\Encoding::convertEncoding(
							$queryParamVal, 'UTF-8', SITE_CHARSET
						);
					}
				}

				$placeholders['from_url'] = $uri->getLocator();
				$placeholders['from_domain'] = $uri->getHost();
			}
		}

		// prepare preset placeholders from loader parameters
		$presetsString = $this->request->get('presets');
		if ($presetsString)
		{
			$presets = array();
			parse_str($presetsString, $presets);

			foreach ($presets as $presetKey => $presetVal)
			{
				if (!is_string($presetVal))
				{
					continue;
				}

				$placeholders[$presetKey] = Encoding::convertEncoding(
					$presetVal, 'UTF-8', SITE_CHARSET
				);
			}
		}

		// prepare visited pages from loader parameters
		$visitedPages = array();
		$visitedPageList = $this->request->get('visited_pages');
		if (is_array($visitedPageList))
		{
			foreach ($visitedPageList as $visitedPage)
			{
				if (!isset($visitedPage['HREF']) || !$visitedPage['HREF'])
				{
					continue;
				}

				$visitedPages[] = array(
					'HREF' => $visitedPage['HREF'],
					'DATE' => is_numeric($visitedPage['DATE']) ? $visitedPage['DATE'] : null,
					'TITLE' => $visitedPage['TITLE']
				);
			}
		}


		// prepare utm fields in common fields
		$utmDictionary = \Bitrix\Crm\UtmTable::getCodeList();
		foreach ($placeholders as $placeholderCode => $placeholderValue)
		{
			$utmName = mb_strtoupper($placeholderCode);
			if (!in_array($utmName, $utmDictionary))
			{
				continue;
			}

			$commonFields[$utmName] = $placeholderValue;
		}

		$trace = $this->request->get('trace');

		return array(
			'COMMON_FIELDS' => $commonFields,
			'PLACEHOLDERS' => $placeholders,
			'STOP_CALLBACK' => $this->request->get('stopCallback') == 'Y',
			'COMMON_DATA' => array(
				'VISITED_PAGES' => $visitedPages,
				'TRACE' => $trace
			),
		);
	}

	public function processPost()
	{
		\CUtil::JSPostUnescape();
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		if($this->arParams['AJAX_POST'] == 'Y')
		{
			$request->addFilter(new PostDecodeFilter());
		}


		$resultError = false;
		$resultText = '';
		$resultRedirectUrl = '';
		$resultId = null;
		$gid = null;

		$action = $request->get('action');
		if($action == 'inc_counter')
		{

			$counter = $request->get('counter');
			switch($counter)
			{
				case 'view':
					if(!$this->counterInSession($counter))
					{
						Form::incCounterView($this->formId);
						$this->counterInSession($counter, false);
					}
					break;
				case 'start':
					if(!$this->counterInSession($counter))
					{
						Form::incCounterStartFill($this->formId);
						$this->counterInSession($counter, false);
					}

					break;
			}
		}
		else if (!$this->processPostCaptcha())
		{
			$resultError = true;
			$resultText = $this->errors[0];
			$resultRedirectUrl = $this->arResult['FORM']['RESULT_FAILURE_URL'];
		}
		else
		{

			foreach($this->arResult['FIELDS'] as $fieldKey => $field)
			{
				if($field['type'] == 'file')
				{
					$values = $request->getFile($field['name']);
					if (!empty($values['name']))
					{
						$values['name'] = Encoding::convertEncoding($values['name'], 'UTF-8', SITE_CHARSET);
					}
					if(is_array($values['tmp_name']))
					{
						$valuesTmp = array();
						foreach($values as $fileKey => $fileValue)
						{
							foreach($fileValue as $valueIndex => $value)
							{
								$valuesTmp[$valueIndex][$fileKey] = $value;
							}
						}

						$values = $valuesTmp;
					}
					else
					{
						$values = array($values);
					}
				}
				else
				{
					$values = $request->getPost($field['name']);
				}

				if(!is_array($values))
				{
					$values = array($values);
				}

				if($field['type'] == 'phone')
				{
					$valuesTmp = array();
					foreach($values as $value)
					{
						$value = preg_replace("/[^0-9+]/", '', $value);
						$valuesTmp[] = $value;
					}
					$values = $valuesTmp;
				}

				if ($field['entity_field_name'] == 'COMMENTS')
				{
					$valuesTmp = array();
					foreach($values as $value)
					{
						$valuesTmp[] = htmlspecialcharsbx($value);
					}
					$values = $valuesTmp;
				}

				$field['values'] = $values;
				$this->arResult['FIELDS'][$fieldKey] = $field;
			}

			$result = $this->form->addResult(
				$this->arResult['FIELDS'],
				$this->getAddResultParameters()
			);
			if($this->form->hasErrors())
			{
				$this->errors = $this->form->getErrors();

				$resultError = true;
				$resultText = $this->arResult['FORM']['RESULT_FAILURE_TEXT'];
				$resultRedirectUrl = $this->arResult['FORM']['RESULT_FAILURE_URL'];
			}
			else
			{
				$resultError = false;
				$resultText = $this->arResult['FORM']['RESULT_SUCCESS_TEXT'];
				$resultId = $result->getId();
				$resultRedirectUrl = $result->getUrl();
				$gid = $this->processPostGuest($result);
			}
		}

		if($this->arParams['AJAX_POST'] == 'Y')
		{
			if (isset($this->arResult['FORM']['FORM_SETTINGS']) && isset($this->arResult['FORM']['FORM_SETTINGS']['REDIRECT_DELAY']))
			{
				$redirectDelay = $this->arResult['FORM']['FORM_SETTINGS']['REDIRECT_DELAY'];
			}
			else
			{
				$redirectDelay = Form::REDIRECT_DELAY;
			}

			$this->answerJson(array(
				'error' => $resultError,
				'text' => $resultText,
				'redirect' => $resultRedirectUrl,
				'redirectDelay' => (int) $redirectDelay,
				'resultId' => $resultId,
				'gid' => $gid
			));
		}
		else
		{
			if($resultRedirectUrl)
			{
				LocalRedirect($resultRedirectUrl);
			}
		}
	}

	protected function processPostGuest(\Bitrix\Crm\WebForm\Result $result)
	{
		$data = array('ENTITIES' => array());
		$resultEntity = $result->getResultEntity();
		$entityTypeNames = array(
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::LeadName,
			\CCrmOwnerType::CompanyName,
		);
		foreach ($entityTypeNames as $entityTypeName)
		{

			$entityId = $resultEntity->getEntityIdByTypeName($entityTypeName);
			if (!$entityId)
			{
				continue;
			}
			$data['ENTITIES'][] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::resolveID($entityTypeName),
				'ENTITY_ID' => $entityId,
			);
		}

		return Guest::registerByContext($data);
	}

	protected function processPostCaptcha()
	{
		if (!$this->arResult['CAPTCHA']['USE'])
		{
			return true;
		}

		$reCaptcha = new ReCaptcha($this->arResult['CAPTCHA']['SECRET']);
		$isSuccess = $reCaptcha->verify(
			$this->request->get('g-recaptcha-response'),
			\Bitrix\Main\Context::getCurrent()->getServer()->get('REMOTE_ADDR')
		);
		$this->errors[] = $reCaptcha->getError();
		return $isSuccess;
	}

	protected function answerJson($answer = array())
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}

		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		header('Content-Type:application/json; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($answer);

		\CMain::FinalActions();
		exit;
	}

	protected function prepareResultDescription()
	{
		$bbCodeParser = new \CTextParser();
		$this->arResult['FORM']['DESCRIPTION_DISPLAY'] = $bbCodeParser->convertText($this->arResult['FORM']['DESCRIPTION']);
		$this->arResult['FORM']['DESCRIPTION_CLEAR'] = strip_tags($this->arResult['FORM']['DESCRIPTION_DISPLAY']);
	}

	public function prepareResult()
	{
		$this->formId = (int) $this->arParams['FORM_ID'];
		$this->form = new Form();

		if($this->formId <= 0 || !$this->form->loadOnlyForm($this->formId))
		{
			$this->errors[] = Loc::getMessage('CRM_WEBFORM_ERROR_NOT_FOUND');
			return false;
		}

		if(!$this->form->checkSecurityCode($this->arParams['SECURITY_CODE']))
		{
			$this->errors[] = Loc::getMessage('CRM_WEBFORM_ERROR_SECURITY');
			return false;
		}
		if (!in_array($this->arParams['VIEW_TYPE'], ['frame', 'preview'], true))
		{
			$landingUrl = $this->form->getLandingUrl();
			if ($landingUrl)
			{
				$getParameters = $this->request->getQueryList()->toArray();
				unset($getParameters['form_code'], $getParameters['sec']);
				$landingUrl = (new \Bitrix\Main\Web\Uri($landingUrl))->addParams($getParameters)->getLocator();
				LocalRedirect($landingUrl, true);
			}
		}
		if(!$this->form->isActive())
		{
			$this->errors[] = Loc::getMessage('CRM_WEBFORM_ERROR_DEACTIVATED');
			return false;
		}

		$this->form->load($this->formId);
		$this->arResult['FORM'] = $this->form->get();
		$this->arResult['IS_EMBEDDING_AVAILABLE'] = WebForm\Manager::isEmbeddingAvailable()
			&& $this->form->isEmbeddingEnabled()
			&& $this->form->isEmbeddingAvailable()
		;
		$this->arResult['FIELDS'] = $this->prepareResultFields();
		$this->arResult['HAS_PHONE_FIELD'] = false;
		foreach($this->arResult['FIELDS'] as $field)
		{
			if($field['type'] == \Bitrix\Crm\WebForm\Internals\FieldTable::TYPE_ENUM_PHONE)
			{
				$this->arResult['HAS_PHONE_FIELD'] = true;
			}
		}
		$this->prepareResultCaptcha();
		$this->prepareResultUserConsent();

		if($this->request->isPost())
		{
			$this->processPost();
		}

		$this->arResult['EXTERNAL_ANALYTICS_DATA'] = Helper::getExternalAnalyticsData(
			$this->arResult['FORM']['CAPTION'] ? $this->arResult['FORM']['CAPTION'] : '#' . $this->arResult['FORM']['ID']
		);

		/* Currency */
		$currencyFormatParams = \CCrmCurrency::GetCurrencyFormatParams($this->form->getCurrencyId());
		if(!is_array($currencyFormatParams))
		{
			$this->arResult['CURRENCY'] = array(
				'FORMAT_STRING' => '# ' . $this->form->getCurrencyId(),
				'DEC_POINT' => '.',
				'DECIMALS' => 2,
				'THOUSANDS_SEP' => ' ',
			);
		}
		else
		{
			$this->arResult['CURRENCY'] = array(
				'FORMAT_STRING' => $currencyFormatParams['FORMAT_STRING'],
				'DEC_POINT' => $currencyFormatParams['DEC_POINT'],
				'DECIMALS' => $currencyFormatParams['DECIMALS'],
				'THOUSANDS_SEP' => $currencyFormatParams['THOUSANDS_SEP'],
			);
		}

		$this->prepareResultPhoneCountryCode();
		$this->prepareResultDescription();
		$this->prepareResultCustomization();
		$this->arResult['CAN_REMOVE_COPYRIGHT'] = $this->form->canRemoveCopyright() || $this->arResult['FORM']['IS_CALLBACK_FORM'] == 'Y';

		global $APPLICATION;
		$APPLICATION->SetTitle($this->arResult['FORM']['CAPTION']);
		$APPLICATION->SetPageProperty('description', $this->arResult['FORM']['DESCRIPTION_CLEAR']);

		$this->arResult['TRACKING_GUEST_LOADER'] = Webpack\Guest::instance()->getEmbeddedBody();
		$this->arResult['ERRORS'] = $this->errors;
		return true;
	}

	protected function prepareResultCaptcha()
	{
		$this->arResult['CAPTCHA'] = array(
			'USE' => $this->form->isUsedCaptcha(),
			'JS_LINK' => ReCaptcha::getJavascriptResource(),
			'KEY' => ReCaptcha::getKey() ? ReCaptcha::getKey() : ReCaptcha::getDefaultKey(),
			'SECRET' => ReCaptcha::getSecret() ? ReCaptcha::getSecret() : ReCaptcha::getDefaultSecret(),
		);

		if (!$this->arResult['CAPTCHA']['KEY'] || !$this->arResult['CAPTCHA']['SECRET'])
		{
			$this->arResult['CAPTCHA']['USE'] = false;
		}
	}

	protected function prepareResultUserConsent()
	{
		$formData = $this->form->get();

		$isUsed = false;
		$buttonCaption = '';
		$text = '';
		if ($formData['USE_LICENCE'] == 'Y' && $formData['AGREEMENT_ID'])
		{
			$replace = array(
				'button_caption' => $this->arResult['FORM']['BUTTON_CAPTION'],
				'fields' => array()
			);
			foreach($this->arResult['FIELDS'] as $field)
			{
				$replace['fields'][] = $field['caption'];
			}

			$agreement = new Agreement($formData['AGREEMENT_ID'], $replace);
			if ($agreement->isActive() && $agreement->isExist())
			{
				$isUsed = true;
				$text = $agreement->getText();
				$buttonCaption = $agreement->getLabelText();
			}
		}
		$this->arResult['USER_CONSENT'] = array(
			'IS_USED' => $isUsed,
			'BUTTON_CAPTION' => $buttonCaption,
			'TEXT' => $text,
			'IS_CHECKED' => $formData['LICENCE_BUTTON_IS_CHECKED'] == 'Y',
		);
	}

	protected function prepareResultPhoneCountryCode()
	{
		$this->arResult['PHONE_COUNTRY_CODE'] = null;
		if (Loader::includeModule('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
			$zone = mb_strtolower($zone);
			if (in_array($zone, array('kz', 'by', 'ua')))
			{
				$this->arResult['PHONE_COUNTRY_CODE'] = $zone;
			}
		}
		if (!$this->arResult['PHONE_COUNTRY_CODE'])
		{
			$this->arResult['PHONE_COUNTRY_CODE'] = null;
		}
	}

	protected function prepareResultCustomization()
	{
		$this->arResult['CUSTOMIZATION']['NO_BORDERS'] = false;
		if(isset($this->arResult['FORM']['FORM_SETTINGS']) && isset($this->arResult['FORM']['FORM_SETTINGS']['NO_BORDERS']))
		{
			$this->arResult['CUSTOMIZATION']['NO_BORDERS'] = ($this->arResult['FORM']['FORM_SETTINGS']['NO_BORDERS'] == 'Y');
		}

		if($this->arResult['FORM']['BUTTON_COLOR_FONT'])
		{
			$this->arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] = htmlspecialcharsbx($this->arResult['FORM']['BUTTON_COLOR_FONT']);
		}
		else
		{
			$this->arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] = '#FFFFFF';
		}
		if($this->arResult['FORM']['BUTTON_COLOR_BG'])
		{
			$this->arResult['CUSTOMIZATION']['BUTTON_COLOR_BG'] = htmlspecialcharsbx($this->arResult['FORM']['BUTTON_COLOR_BG']);
		}
		else
		{
			$this->arResult['CUSTOMIZATION']['BUTTON_COLOR_BG'] = '#00AEEF';
		}

		$this->arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'] = htmlspecialcharsbx(
			CFile::GetPath($this->arResult['FORM']['BACKGROUND_IMAGE'])
		);

		$this->arResult['CUSTOMIZATION']['TEMPLATE_ID'] = htmlspecialcharsbx($this->arResult['FORM']['TEMPLATE_ID']);
		$this->arResult['CUSTOMIZATION']['CSS_TEXT'] = htmlspecialcharsbx($this->arResult['FORM']['CSS_TEXT']);

		$this->arResult['CUSTOMIZATION']['OG_CAPTION'] = htmlspecialcharsbx($this->arResult['FORM']['CAPTION']);
		$this->arResult['CUSTOMIZATION']['OG_DESCRIPTION'] = htmlspecialcharsbx($this->arResult['FORM']['DESCRIPTION_CLEAR']);

		$siteName = '//' . \Bitrix\Main\Context::getCurrent()->getServer()->getHttpHost();
		$siteNameHttp = 'http:' . $siteName;
		$siteNameHttps = 'https:' . $siteName;
		$this->arResult['CUSTOMIZATION']['OG_IMAGE'] = array(
			array(
				'PATH' => $siteNameHttp . $this->getPath() . '/templates/.default/images/rich_link_form_150_150.png',
				'PATH_HTTPS' => $siteNameHttps . $this->getPath() . '/templates/.default/images/rich_link_form_150_150.png',
				'TYPE' => 'image/png', 'WIDTH' => '150', 'HEIGHT' => '150'
			),array(
				'PATH' => $siteNameHttp . $this->getPath() . '/templates/.default/images/rich_link_form_150_100.png',
				'PATH_HTTPS' => $siteNameHttps . $this->getPath() . '/templates/.default/images/rich_link_form_150_100.png',
				'TYPE' => 'image/png', 'WIDTH' => '150', 'HEIGHT' => '100'
			),
		);

		if($this->arResult['FORM']['BACKGROUND_IMAGE'])
		{
			if(in_array($this->getLanguageId(), array('ru', 'ua', 'by')))
			{
				$sizeList = array(
					array('width' => 150, 'height' => 100),
				);
			}
			else
			{
				$sizeList = array(
					array('width' => 150, 'height' => 150),
				);
			}
			foreach($sizeList as $size)
			{
				$image = CFile::ResizeImageGet(
					$this->arResult['FORM']['BACKGROUND_IMAGE'],
					$size, BX_RESIZE_IMAGE_PROPORTIONAL, false
				);
				if(!$image['src'])
				{
					continue;
				}
				$backgroundImageSrcHttp = $image['src'];
				$backgroundImageSrcHttps = $backgroundImageSrcHttp;
				if(mb_substr($backgroundImageSrcHttp, 0, 1) == '/')
				{
					$backgroundImageSrcHttp = $siteNameHttp . $backgroundImageSrcHttp;
					$backgroundImageSrcHttps = $siteNameHttps . $backgroundImageSrcHttps;
				}
				$this->arResult['CUSTOMIZATION']['OG_IMAGE'][] = array(
					'PATH' => $backgroundImageSrcHttp,
					'PATH_HTTPS' => $backgroundImageSrcHttps,
					'WIDTH' => $size['width'], 'HEIGHT' => $size['height']
				);
			}
		}

		$ogLang = mb_strtoupper(\Bitrix\Main\Context::getCurrent()->getLanguage());
		$ogLogo = $this->arResult['CUSTOMIZATION']['OG_IMAGE'];
		if(isset($this->arResult['CUSTOMIZATION']['OG_IMAGE_' . $ogLang]))
		{
			$ogLogo = $this->arResult['CUSTOMIZATION']['OG_IMAGE_' . $ogLang];
		}
		$this->arResult['CUSTOMIZATION']['OG_IMAGE_CURRENT'] = $ogLogo;
		if(Loader::includeModule('intranet'))
		{
			$this->arResult['CUSTOMIZATION']['REF_LINK'] = CIntranetUtils::getB24Link('crm-form');
		}
	}

	public function checkParams()
	{
		$this->arParams['CACHE_TIME'] = (int) isset($this->arParams['CACHE_TIME']) ? $this->arParams['CACHE_TIME'] : 36000;

		$paramsFromRequest = array();
		$requestVariableNames = array('form_id', 'form_code', 'sec');
		foreach($requestVariableNames as $name)
		{
			$paramsFromRequest[$name] = $this->request->get($name);
		}

		if(!isset($this->arParams['FORM_ID'])  || !$this->arParams['FORM_ID'])
		{
			$this->arParams['FORM_ID'] = $paramsFromRequest['form_id'];
		}
		$this->arParams['FORM_ID'] = (int) $this->arParams['FORM_ID'];

		if(!isset($this->arParams['FORM_CODE'])  || !$this->arParams['FORM_CODE'])
		{
			$this->arParams['FORM_CODE'] = $paramsFromRequest['form_code'];
		}
		if(!$this->arParams['FORM_ID'] && $this->arParams['FORM_CODE'])
		{
			$this->arParams['FORM_ID'] = Form::getIdByCode($this->arParams['FORM_CODE']);
		}

		if(!isset($this->arParams['SECURITY_CODE'])  || !$this->arParams['SECURITY_CODE'])
		{
			$this->arParams['SECURITY_CODE'] = $paramsFromRequest['sec'];
		}

		if(!isset($this->arParams['PATH_TO_INVOICE_PAY'])  || !$this->arParams['PATH_TO_INVOICE_PAY'])
		{
			$this->arParams['PATH_TO_INVOICE_PAY'] = '/pub/invoice.php?invoice_id=#invoice_id#';
		}

		//$this->arParams['AJAX_POST'] = 'N';
		if(isset($this->arParams['AJAX_POST']) && $this->arParams['AJAX_POST'] == 'N')
		{
			$this->arParams['AJAX_POST'] = 'N';
		}
		else
		{
			$this->arParams['AJAX_POST'] = 'Y';
		}

		if(!isset($this->arParams['VIEW_TYPE']))
		{
			$viewType = $this->request->get('view');
			$this->arParams['VIEW_TYPE'] = $viewType ? $viewType : '';
		}

		if(!isset($this->arParams['SHOW_AGREEMENT']))
		{
			$showAgreement = $this->request->get('show_agreement');
			$this->arParams['SHOW_AGREEMENT'] = $showAgreement == 'Y' ? 'Y' : 'N';
		}

		if(!isset($this->arParams['SHOW_SUCCESS']))
		{
			$showAgreement = $this->request->get('show_success');
			$this->arParams['SHOW_SUCCESS'] = $showAgreement == 'Y' ? 'Y' : 'N';
		}

		if(!isset($this->arParams['PREVIEW_TYPE']))
		{
			$previewType = $this->request->get('preview');
			$this->arParams['PREVIEW_TYPE'] = $previewType ? $previewType : 'inline';
		}

		if(!isset($this->arParams['PREVIEW_ID']))
		{
			$this->arParams['PREVIEW_ID'] = $this->request->get('preview_id');
		}

		return true;
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->checkParams())
		{
			$this->showErrors();
			return;
		}

		if($this->request->isPost())
		{
			if(!$this->prepareResult())
			{
				$this->answerJson(array(
					'error' => true,
					'text' => implode("\n", $this->errors),
					'redirect' => '',
				));
				$this->showErrors();
				return;
			}
			else
			{
				$this->includeComponentTemplate();
			}
		}
		else
		{
			switch ($this->arParams['VIEW_TYPE'])
			{
				case 'frame':
					$this->includeWebFormFrameTemplate();
					break;
				case 'preview':
					$this->includeWebFormPreviewTemplate();
					break;
				default:
					$this->includeWebFormTemplate();
					break;
			}
			if($this->arParams['VIEW_TYPE'] == 'frame')
			{
				$this->includeWebFormFrameTemplate();
			}
			else
			{
				$this->includeWebFormTemplate();
			}
		}
	}

	public function includeWebFormTemplate()
	{
		$cacheId = $this->getCacheID();
		$cacheId .= '|agr' . $this->arParams['SHOW_AGREEMENT'] ;
		$cacheId .= '|succ' . $this->arParams['SHOW_SUCCESS'] ;
		$cacheId .= '|'.mb_substr($this->arParams['SECURITY_CODE'], 0, 32);
		$cacheId .= '|' . $this->getLanguageId();

		if($this->startResultCache($this->arParams['CACHE_TIME'], $cacheId))
		{
			if(!$this->prepareResult())
			{
				$this->abortResultCache();
				$this->showErrors();
				return;
			}
			else
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->RegisterTag(Form::getCacheTag($this->form->getId()));
				}

				$this->setResultCacheKeys(array(
					'CUSTOMIZATION',
				));

				if ($this->arParams['SHOW_AGREEMENT'] == 'Y')
				{
					$this->includeComponentTemplate('agreement');
				}
				elseif ($this->arParams['SHOW_SUCCESS'] == 'Y')
				{
					$this->includeComponentTemplate('success');
				}
				else
				{
					$this->includeComponentTemplate();
				}

				$this->endResultCache();
			}
		}
	}

	protected function includeWebFormFrameTemplate()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		$this->includeComponentTemplate('frame');
		CMain::FinalActions();
		exit;
	}

	protected function includeWebFormPreviewTemplate()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		global $USER;
		if (!$USER->IsAuthorized())
		{
			$this->arResult['PREVIEW']['AD_TITLE'] = Loc::getMessage('CRM_WEBFORM_PREVIEW_AD_AUTH_TITLE');
			$this->arResult['PREVIEW']['AD_SUBTITLE'] = Loc::getMessage('CRM_WEBFORM_PREVIEW_AD_AUTH_SUBTITLE');
			$this->includeComponentTemplate('preview_ad');
			CMain::FinalActions();
			exit;
		}

		if (!\Bitrix\Main\Loader::includeModule('crm') || !WebForm\Manager::checkReadPermission())
		{
			$this->arResult['PREVIEW']['AD_TITLE'] = Loc::getMessage('CRM_WEBFORM_PREVIEW_AD_NSD_TITLE');
			$this->arResult['PREVIEW']['AD_SUBTITLE'] = Loc::getMessage('CRM_WEBFORM_PREVIEW_AD_NSD_SUBTITLE');
			$this->includeComponentTemplate('preview_ad');
			CMain::FinalActions();
			exit;
		}

		$previewTypeParam = $this->arParams['PREVIEW_TYPE'];
		$this->arResult['PREVIEW']['TYPE'] = $previewTypeParam;
		$this->arResult['PREVIEW']['SCRIPT'] = '';

		switch ($previewTypeParam)
		{
			case 'button':
			case 'ol':
				$button = new \Bitrix\Crm\SiteButton\Button((int)$this->arParams['PREVIEW_ID']);
				$this->arResult['PREVIEW']['SCRIPT'] = \Bitrix\Crm\SiteButton\Script::getScript($button);
				break;
			case 'click':
			case 'auto':
			case 'inline':
			default:
				$form = new WebForm\Form((int)$this->arParams['FORM_ID']);
				$formData = $form->get();
				$this->arResult['PREVIEW']['VIEWS'] = $formData['FORM_SETTINGS']['VIEWS'] ?? [];

				$scripts = WebForm\Script::getListContext($formData, []);

				if (isset($scripts[strtoupper($previewTypeParam)]['text'])) {
					$this->arResult['PREVIEW']['SCRIPT'] = $scripts[strtoupper($previewTypeParam)]['text'];
				}
				break;
		}

		$this->includeComponentTemplate('preview');
		CMain::FinalActions();
		exit;
	}

	protected function checkModules()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!Loader::includeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!Loader::includeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if(!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if(count($this->errors) <= 0)
		{
			return;
		}

		if($this->arParams['VIEW_TYPE'] == 'frame')
		{
			$this->arResult['ERRORS'] = $this->errors;
			$this->includeWebFormFrameTemplate();
		}
		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}

	protected function prepareResultFields()
	{
		return $this->form->getFieldsMap();
	}
}
