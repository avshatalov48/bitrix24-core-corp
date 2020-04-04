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

	public static function getDomain()
	{
		$result = null;
		$isHttps = Context::getCurrent()->getRequest()->isHttps();
		$httpHost = Context::getCurrent()->getServer()->getHttpHost();
		if ($httpHost)
		{
			$result = ($isHttps ? 'https' : 'http') . '://' . $httpHost;
			Option::set("crm", "portal_protocol_url", $result);
		}

		if (!$result)
		{
			$result = Option::get("crm", "portal_protocol_url", '');
		}

		if (!$result && Loader::includeModule('intranet'))
		{
			$httpHost = \CIntranetUtils::getHostName();
			$result = ($isHttps ? 'https' : 'http') . '://' . $httpHost;
		}

		$uri = new Uri($result);
		$result = $uri->getLocator();
		if (substr($result, -1) == '/')
		{
			$result = substr($result, 0, -1);
		}

		return $result;
	}

	protected static function getPublicUrl(array $formData)
	{
		$link = self::getDomain() . self::$defaultFormPath;
		$link = str_replace(
			array('#id#', '#form_id#', '#form_code#', '#form_sec#'),
			array($formData['ID'], $formData['ID'], $formData['CODE'], $formData['SECURITY_CODE']),
			$link
		);

		return $link;
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

		$uri = new Uri($link);
		return $uri->getLocator();
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

		$lang = Context::getCurrent()->getLanguage();
		$scriptParams = array(
			'id' => $formData['ID'],
			'lang' => $lang,
			'sec' => $formData['SECURITY_CODE']
		);

		return array(
			'INLINE' => $script->getInline($scriptParams),
			'BUTTON' => $script->getButton($scriptParams + array('button_caption' => Loc::getMessage('CRM_WEBFORM_SCRIPT_BUTTON_TEXT'))),
			'LINK' => $script->getLink($scriptParams + array('button_caption' => Loc::getMessage('CRM_WEBFORM_SCRIPT_BUTTON_TEXT'))),
			'DELAY' => $script->getDelay($scriptParams + array('delay' => 5))
		);
	}

	public static function getCrmButtonWidget($formId, $params = array())
	{
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

	public static function getCrmButtonWidgetShower($formId, $lang = null, array $options = [])
	{
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
					}
				};
				
				if(w[\'Bitrix24FormLoader\'] && !Bitrix24FormLoader.isFormExisted(params)) 
				{
					params.ref = "' . $url . '";
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