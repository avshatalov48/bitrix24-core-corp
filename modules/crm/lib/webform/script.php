<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm\UI\Webpack;

Loc::loadMessages(__FILE__);

class Script
{
	protected static $defaultFormPathSef = '/pub/form/#form_code#/#form_sec#/';
	protected static $defaultFormPath = '/pub/form.php?form_id=#form_id#&sec=#form_sec#';
	protected $loaderPath = '/bitrix/js/crm/form_loader.js';
	protected $formPath;
	protected $isHttps = false;
	protected $domain;

	public function __construct($domain, $isHttps = false, $formPath = null, $loaderPath = null)
	{
		$this->domain = $domain;
		$this->isHttps = $isHttps;
		if($loaderPath)
		{
			$this->loaderPath = $loaderPath;
		}
		if($formPath)
		{
			$this->formPath = $formPath;
		}
	}

	protected function isB24()
	{
		//TODO: use real value
		return true;
	}

	protected function getLoader($params)
	{
		$loaderLink = ($this->isHttps ? 'https' : 'http') . '://' . $this->domain . $this->loaderPath;
		$uri = new Uri($loaderLink);
		$loaderLink = $uri->getLocator();

		if($this->formPath)
		{
			if($this->formPath != self::$defaultFormPath && $this->formPath != self::$defaultFormPathSef)
			{
				$params['page'] = $this->formPath;
			}
		}
		$paramsString = Json::encode($params);

		$isBox = $this->isB24() ? '' : 'arguments[0].isBox=1;';
		/*
		JS VARIABLE DESCRIPTION:
			w - window object
			d - document object
			u - url of form loader file
			b - bitrix form function

			r - random number
			s - SCRIPT element with source of form loader file
			h - HEAD element
		*/

		return
			"<script id=\"bx24_form_" . $params['type'] . "\" data-skip-moving=\"true\">
	(function(w,d,u,b){w['Bitrix24FormObject']=b;w[b] = w[b] || function(){arguments[0].ref=u;
		" . $isBox . "(w[b].forms=w[b].forms||[]).push(arguments[0])};
		if(w[b]['forms']) return;
		var s=d.createElement('script');s.async=1;s.src=u+'?'+(1*new Date());
		var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
	})(window,document,'" . $loaderLink . "','b24form');

	b24form(" . $paramsString . ");
</script>";

	}

	public function getInline($params)
	{
		return $this->getLoader(array(
			'id' => $params['id'],
			'lang' => $params['lang'],
			'sec' => $params['sec'],
			'type' => 'inline',
		));
	}

	public function getButton($params)
	{
		$html = $this->getLoader(array(
			'id' => $params['id'],
			'lang' => $params['lang'],
			'sec' => $params['sec'],
			'type' => 'button',
			'click' => ''
		));
		$html .= '<button class="b24-web-form-popup-btn-' . HtmlFilter::encode($params['id']) . '">';
		$html .= HtmlFilter::encode($params['button_caption']);
		$html .= '</button>';
		return $html;
	}

	public function getLink($params)
	{
		$html = $this->getLoader(array(
			'id' => $params['id'],
			'lang' => $params['lang'],
			'sec' => $params['sec'],
			'type' => 'link',
			'click' => ''
		));
		$html .= '<a class="b24-web-form-popup-btn-' . HtmlFilter::encode($params['id']) . '">';
		$html .= HtmlFilter::encode($params['button_caption']);
		$html .= '</a>';
		return $html;
	}

	public function getDelay($params)
	{
		return $this->getLoader(array(
			'id' => $params['id'],
			'lang' => $params['lang'],
			'sec' => $params['sec'],
			'type' => 'delay',
			'delay' => $params['delay'] ? $params['delay'] : 5,
		));
	}

	public static function getPublicFormPath()
	{
		return Option::get('crm', 'webform_public_form_path', '/pub/form/#form_code#/#form_sec#/');
	}

	public static function setPublicFormPath($path)
	{
		Option::set('crm', 'webform_public_form_path', $path);
	}

	public static function proxyUrl($url)
	{
		if (Loader::includeModule('bitrix24') && !\CBitrix24::isCustomDomain())
		{
			$url = new Uri($url);
			if (mb_strpos($url->getPath(), '/pub/') === 0)
			{
				$url = $url->setPath(
					'/'.$url->getHost().mb_substr($url->getPath(), 4)
				)->setHost('bitrix24public.com')->getLocator();
			}
		}

		return $url;
	}

	public static function getDomain()
	{
		$result = null;
		$previous = $result = Option::get("crm", "portal_protocol_url", '');

		$isHttps = Context::getCurrent()->getRequest()->isHttps();
		$httpHost = Context::getCurrent()->getServer()->getHttpHost();
		if ($httpHost)
		{
			$result = ($isHttps ? 'https' : 'http') . '://' . $httpHost;
			if ($result !== $previous)
			{
				Option::set("crm", "portal_protocol_url", $result);
			}
		}

		if (!$result)
		{
			$result = $previous;
		}

		if (!$result && Loader::includeModule('intranet'))
		{
			$httpHost = \CIntranetUtils::getHostName();
			$result = ($isHttps ? 'https' : 'http') . '://' . $httpHost;
		}

		$uri = new Uri($result);
		$result = $uri->getLocator();
		if (mb_substr($result, -1) == '/')
		{
			$result = mb_substr($result, 0, -1);
		}

		return $result;
	}

	public static function getPublicUrl(array $formData)
	{
		if ($landingUrl = Internals\LandingTable::getLandingPublicUrl($formData['ID']))
		{
			return $landingUrl;
		}

		$link = self::getDomain() . self::$defaultFormPathSef;
		$link = str_replace(
			array('#id#', '#form_id#', '#form_code#', '#form_sec#'),
			array($formData['ID'], $formData['ID'], $formData['CODE'], $formData['SECURITY_CODE']),
			$link
		);

		return self::proxyUrl($link);
	}

	public static function getAgreementUrl(array $formData)
	{
		$uri = new Uri(self::getPublicUrl($formData));
		$uri->addParams(array('show_agreement' => 'Y'));
		return $uri->getLocator();
	}

	public static function getSuccessPageUrl(array $formData)
	{
		$uri = new Uri(self::getPublicUrl($formData));
		$uri->addParams(array('show_success' => 'Y'));
		return $uri->getLocator();
	}

	public static function getUrlContext($formData, $formPath = null)
	{
		if ($landingUrl = Internals\LandingTable::getLandingPublicUrl($formData['ID']))
		{
			return $landingUrl;
		}

		if(!$formPath)
		{
			$formPath = self::getPublicFormPath();
		}

		$link = self::getDomain() . $formPath;
		$link = str_replace(
			array('#id#', '#form_id#', '#form_code#', '#form_sec#'),
			array($formData['ID'], $formData['ID'], $formData['CODE'], $formData['SECURITY_CODE']),
			$link
		);

		return self::proxyUrl($link);
	}

	public static function getListContext($formData, $params, $formPath = null)
	{
		static $httpHost;
		if(!$httpHost)
		{
			$httpHost = Context::getCurrent()->getServer()->getHttpHost();
		}

		static $isHttps;
		if(!$isHttps)
		{
			$isHttps = Context::getCurrent()->getRequest()->isHttps();
		}

		$script = new static($httpHost, $isHttps, $formPath);

		if (!$formData['ID'])
		{
			return [];
		}

		$lang = Context::getCurrent()->getLanguage();
		$scriptParams = array(
			'id' => $formData['ID'],
			'lang' => $lang,
			'sec' => $formData['SECURITY_CODE']
		);

		$webpack = Webpack\Form::instance($formData['ID']);
		if (!$webpack->isBuilt())
		{
			$webpack->build();
			$webpack = Webpack\Form::instance($formData['ID']);
		}

		return array(
			'INLINE' => [
				'text' => $webpack
					->configureFormEmbeddedScript(['action' => 'inline', 'sec' => $formData['SECURITY_CODE']])
					->getEmbeddedScript(),
				'old' => $script->getInline($scriptParams)
			],
			'CLICK' => [
				'text' => $webpack
					->configureFormEmbeddedScript(['action' => 'click', 'sec' => $formData['SECURITY_CODE']])
					->getEmbeddedScript(),
				'old' => $script->getButton($scriptParams + ['button_caption' => Loc::getMessage('CRM_WEBFORM_SCRIPT_BUTTON_TEXT')])
			],
			'AUTO' => [
				'text' => $webpack
					->configureFormEmbeddedScript(['action' => 'auto', 'sec' => $formData['SECURITY_CODE']])
					->getEmbeddedScript(),
				'old' => $script->getDelay($scriptParams + ['delay' => 5])
			]
		);
	}

	public static function getCrmButtonWidget($formId, $params = [])
	{
		if (Manager::isEmbeddingEnabled($formId))
		{
			$options = [
				'usedBySiteButton' => true,
				'lang' => $params['LANGUAGE_ID'] ?: LANGUAGE_ID,
			];
			$formOptions = [
				'id' => 'b24-site-button-form-' . $formId,
				'visible' => false,
				'useSign' => !$params['REMOVE_COPYRIGHT'],
			];
			return Webpack\Form::instance($formId)
				->setAdditionalOptions($options)
				->setAdditionalFormOptions($formOptions)
				->getContent();
		}

		ob_start();

		/*@var $APPLICATION CMain*/
		global $APPLICATION;
		$APPLICATION->IncludeComponent("bitrix:crm.button.webform", ".default", array(
			'FORM_ID' => $formId,
			'REMOVE_COPYRIGHT' => $params['REMOVE_COPYRIGHT']  ? 'Y' : 'N',
			'TITLE' => (
				$params['IS_CALLBACK']
				?
				Loc::getMessage('CRM_WEBFORM_SCRIPT_WIDGET_FORM_CALLBACK_TITLE')
				:
				Loc::getMessage('CRM_WEBFORM_SCRIPT_WIDGET_FORM_DEFAULT_TITLE')
			)
		));

		return ob_get_clean();
	}

	public static function getCrmButtonWidgetHider($formId)
	{
		if (Manager::isEmbeddingEnabled($formId))
		{

			$id = 'b24-site-button-form-' . $formId;
			return "b24form.App.get('$id').hide();";
		}

		return 'BX.SiteButton.classes.remove(document.getElementById(\'bx24_form_container_' . $formId . '\'), \'open-sidebar\'); BX.SiteButton.onWidgetClose();';
	}

	public static function getCrmButtonWidgetShower($formId, $lang = null, array $options = [])
	{
		if (Manager::isEmbeddingEnabled($formId))
		{

			$id = 'b24-site-button-form-' . $formId;
			return "b24form.App.get('$id').show();";
		}

		$formData = FormTable::getRowById($formId);
		$sec = $formData['SECURITY_CODE'];
		$isCallbackForm = $formData['IS_CALLBACK_FORM'] == 'Y';
		if (!$lang)
		{
			$lang = Application::getInstance()->getContext()->getLanguage();
		}

		$url = self::getDomain() . '/bitrix/js/crm/form_loader.js';
		$options += [
			"borders" => false,
			"logo" => false
		];
		$options = Json::encode($options);

		return '
			(function(w,d,u,b){w[\'Bitrix24FormObject\']=b;w[b] = w[b] || function(){arguments[0].ref=u;
				(w[b].forms=w[b].forms||[]).push(arguments[0])};
				if(w[b][\'forms\']) return;
				s=d.createElement(\'script\');r=1*new Date();s.async=1;s.src=u+\'?\'+r;
				h=d.getElementsByTagName(\'script\')[0];h.parentNode.insertBefore(s,h);
			})(window,document,\'' . $url . '\',\'b24form\');
			
			(function(w,b){				
				params = {
					"id":"' . $formId . '","lang":"' . $lang .'","sec":"' . $sec . '","type":"inline_widget", 
					"node": document.getElementById("bx24_form_inline_loader_container_' . $formId . '"),
					"isCallbackForm": ' . ($isCallbackForm ? 'true' : 'false') . ',
					"options": ' . $options . ',
					"handlers": {
						"init": function (form){
							BX.SiteButton.onWidgetFormInit(form);
						},
						"keyboard": function (form, keyCode){
							if (keyCode == 27) BX.SiteButton.wm.hide();
						}
					},
					"ref": "' . $url . '" 
				};
				
				if(w[\'Bitrix24FormLoader\'] && !Bitrix24FormLoader.isFormExisted(params)) 
				{
					Bitrix24FormLoader.preLoad(params);
				}
				else
				{
					w[b](params);
				}
				
			})(window,\'b24form\');			
			
			bx24FormCont=document.getElementById("bx24_form_container_' . $formId . '");
			if (bx24FormCont) 
			{
				BX.SiteButton.classes.add(bx24FormCont, "open-sidebar");
			}
		';
	}
}