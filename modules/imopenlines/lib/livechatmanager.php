<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class LiveChatManager
{
	private $error = null;
	private $id = null;

	const TEMPLATE_COLOR = 'color';
	const TEMPLATE_COLORLESS = 'colorless';

	const TYPE_WIDGET = 'widget';
	const TYPE_BUTTON = 'button';

	const WIDGET_PATH_SCRIPT = '/bitrix/modules/imopenlines/install/js/imopenlines/widget/script.js';
	const WIDGET_PATH_EXTENTION_LIST = '/bitrix/modules/imopenlines/install/js/imopenlines/widget/extension.map';
	const WIDGET_PATH_STYLE = '/bitrix/modules/imopenlines/install/js/imopenlines/widget/styles.css';

	static $availableCount = null;

	public function __construct($configId)
	{
		$this->id = intval($configId);
		$this->config = false;
		$this->error = new BasicError(null, '', '');

		\Bitrix\Main\Loader::includeModule("im");
	}

	public function add($fields = Array())
	{
		$configData = Model\LivechatTable::getById($this->id)->fetch();
		if ($configData)
		{
			$this->id = $configData['CONFIG_ID'];
			$this->config = false;

			return true;
		}

		$add['CONFIG_ID'] = $this->id;

		if (isset($fields['ENABLE_PUBLIC_LINK']))
		{
			$specifiedName = true;
			if (!isset($fields['URL_CODE_PUBLIC']))
			{
				$configManager = new \Bitrix\ImOpenLines\Config();
				$config = $configManager->get($this->id);
				$fields['URL_CODE_PUBLIC'] = $config['LINE_NAME'];
				$specifiedName = false;
			}

			$add['URL_CODE_PUBLIC'] = self::prepareAlias($fields['URL_CODE_PUBLIC']);
			$add['URL_CODE_PUBLIC_ID'] = \Bitrix\Im\Alias::add(Array(
				'ALIAS' => $add['URL_CODE_PUBLIC'],
				'ENTITY_TYPE' => \Bitrix\Im\Alias::ENTITY_TYPE_LIVECHAT,
				'ENTITY_ID' => $this->id
			));

			if (!$add['URL_CODE_PUBLIC_ID'])
			{
				if ($specifiedName)
				{
					$this->error = new BasicError(__METHOD__, 'CODE_ERROR', Loc::getMessage('IMOL_LCM_CODE_ERROR'));
					return false;
				}
				else
				{
					$result = \Bitrix\Im\Alias::addUnique(Array(
						'ENTITY_TYPE' => \Bitrix\Im\Alias::ENTITY_TYPE_LIVECHAT,
						'ENTITY_ID' => $this->id
					));
					$add['URL_CODE_PUBLIC'] = $result['ALIAS'];
					$add['URL_CODE_PUBLIC_ID'] = $result['ID'];
				}
			}
		}

		$result = \Bitrix\Im\Alias::addUnique(Array(
			'ENTITY_TYPE' => \Bitrix\Im\Alias::ENTITY_TYPE_LIVECHAT,
			'ENTITY_ID' => $this->id
		));
		$add['URL_CODE'] = $result['ALIAS'];
		$add['URL_CODE_ID'] = $result['ID'];

		if (isset($fields['TEMPLATE_ID']) && in_array($fields['TEMPLATE_ID'], Array(self::TEMPLATE_COLOR, self::TEMPLATE_COLORLESS)))
		{
			$add['TEMPLATE_ID'] = $fields['TEMPLATE_ID'];
		}
		if (isset($fields['BACKGROUND_IMAGE']))
		{
			$add['BACKGROUND_IMAGE'] = intval($fields['BACKGROUND_IMAGE']);
		}
		if (isset($fields['CSS_ACTIVE']))
		{
			$add['CSS_ACTIVE'] = $fields['CSS_ACTIVE'] == 'Y'? 'Y': 'N';
		}
		if (isset($fields['CSS_PATH']))
		{
			$add['CSS_PATH'] = mb_substr($fields['CSS_PATH'], 0, 255);
		}
		if (isset($fields['CSS_TEXT']))
		{
			$add['CSS_TEXT'] = $fields['CSS_TEXT'];
		}
		if (isset($fields['COPYRIGHT_REMOVED']) && Limit::canRemoveCopyright())
		{
			$add['COPYRIGHT_REMOVED'] = $fields['COPYRIGHT_REMOVED'] == 'Y'? 'Y': 'N';
		}
		if (isset($fields['CACHE_WIDGET_ID']))
		{
			$add['CACHE_WIDGET_ID'] = intval($fields['CACHE_WIDGET_ID']);
		}
		if (isset($fields['CACHE_BUTTON_ID']))
		{
			$add['CACHE_BUTTON_ID'] = intval($fields['CACHE_BUTTON_ID']);
		}
		if (isset($fields['PHONE_CODE']))
		{
			$add['PHONE_CODE'] = $fields['PHONE_CODE'];
		}
		if (isset($fields['TEXT_PHRASES']))
		{
			$add['TEXT_PHRASES'] = $fields['TEXT_PHRASES'];
		}

		$result = Model\LivechatTable::add($add);
		if ($result->isSuccess())
		{
			$this->id = $result->getId();
			$this->config = false;
		}

		return $result->isSuccess();
	}

	public function update($fields, $options = [])
	{
		$prevConfig = $this->get();

		$update = Array();
		if (isset($fields['URL_CODE_PUBLIC']))
		{
			$fields['URL_CODE_PUBLIC'] = trim($fields['URL_CODE_PUBLIC']);
			if (empty($fields['URL_CODE_PUBLIC']))
			{
				if ($prevConfig['URL_CODE_PUBLIC_ID'] > 0)
				{
					\Bitrix\Im\Alias::delete($prevConfig['URL_CODE_PUBLIC_ID']);
				}
				$update['URL_CODE_PUBLIC'] = '';
				$update['URL_CODE_PUBLIC_ID'] = 0;
			}
			else
			{
				$fields['URL_CODE_PUBLIC'] = self::prepareAlias($fields['URL_CODE_PUBLIC']);
				if ($prevConfig['URL_CODE_PUBLIC_ID'] > 0)
				{
					if ($prevConfig['URL_CODE_PUBLIC'] != $fields['URL_CODE_PUBLIC'])
					{
						$result = \Bitrix\Im\Alias::update($prevConfig['URL_CODE_PUBLIC_ID'], Array('ALIAS' => $fields['URL_CODE_PUBLIC']));
						if ($result)
						{
							$update['URL_CODE_PUBLIC'] = $fields['URL_CODE_PUBLIC'];
						}
					}
				}
				else
				{
					$fields['URL_CODE_PUBLIC_ID'] = \Bitrix\Im\Alias::add(Array(
						'ALIAS' => $fields['URL_CODE_PUBLIC'],
						'ENTITY_TYPE' => \Bitrix\Im\Alias::ENTITY_TYPE_LIVECHAT,
						'ENTITY_ID' => $this->id
					));
					if ($fields['URL_CODE_PUBLIC_ID'])
					{
						$update['URL_CODE_PUBLIC'] = $fields['URL_CODE_PUBLIC'];
						$update['URL_CODE_PUBLIC_ID'] = $fields['URL_CODE_PUBLIC_ID'];
					}
				}
			}
		}

		if (isset($fields['TEMPLATE_ID']) && in_array($fields['TEMPLATE_ID'], Array(self::TEMPLATE_COLOR, self::TEMPLATE_COLORLESS)))
		{
			$update['TEMPLATE_ID'] = $fields['TEMPLATE_ID'];
		}
		if (isset($fields['CSS_ACTIVE']))
		{
			$update['CSS_ACTIVE'] = $fields['CSS_ACTIVE'] == 'Y'? 'Y': 'N';
		}
		if (isset($fields['BACKGROUND_IMAGE']))
		{
			$update['BACKGROUND_IMAGE'] = intval($fields['BACKGROUND_IMAGE']);
		}
		if (isset($fields['CSS_PATH']))
		{
			$update['CSS_PATH'] = mb_substr($fields['CSS_PATH'], 0, 255);
		}
		if (isset($fields['CSS_TEXT']))
		{
			$update['CSS_TEXT'] = $fields['CSS_TEXT'];
		}
		if (isset($fields['COPYRIGHT_REMOVED']) && Limit::canRemoveCopyright())
		{
			$update['COPYRIGHT_REMOVED'] = $fields['COPYRIGHT_REMOVED'] == 'Y'? 'Y': 'N';
		}
		if (isset($fields['SHOW_SESSION_ID']))
		{
			$update['SHOW_SESSION_ID'] = $fields['SHOW_SESSION_ID'] === 'Y'? 'Y': 'N';
		}
		if (isset($fields['CACHE_WIDGET_ID']))
		{
			$update['CACHE_WIDGET_ID'] = intval($fields['CACHE_WIDGET_ID']);
		}
		if (isset($fields['CACHE_BUTTON_ID']))
		{
			$update['CACHE_BUTTON_ID'] = intval($fields['CACHE_BUTTON_ID']);
		}
		if (isset($fields['PHONE_CODE']))
		{
			$update['PHONE_CODE'] = $fields['PHONE_CODE'];
		}
		if (isset($fields['TEXT_PHRASES']))
		{
			$update['TEXT_PHRASES'] = $fields['TEXT_PHRASES'];
		}

		$result = Model\LivechatTable::update($this->id, $update);
		if ($result->isSuccess())
		{
			if ($this->config)
			{
				foreach ($update as $key => $value)
				{
					$this->config[$key] = $value;
				}
			}
			if (isset($options['CLEAN_CACHE_CONNECTOR']) && \Bitrix\Main\Loader::includeModule('imconnector'))
			{
				\Bitrix\ImConnector\Connector::cleanCacheConnector(
					$this->id,
					\Bitrix\ImConnector\Connector::getCacheIdConnector($this->id, 'livechat')
				);
			}
		}

		return $result->isSuccess();
	}

	public function delete()
	{
		$prevConfig = $this->get();

		if ($prevConfig['URL_CODE_PUBLIC_ID'])
		{
			\Bitrix\Im\Alias::delete($prevConfig['URL_CODE_PUBLIC_ID']);
		}
		if ($prevConfig['URL_CODE_ID'])
		{
			\Bitrix\Im\Alias::delete($prevConfig['URL_CODE_ID']);
		}

		if ($prevConfig['CACHE_WIDGET_ID'])
		{
			\CFile::Delete($prevConfig['CACHE_WIDGET_ID']);
		}
		if ($prevConfig['CACHE_BUTTON_ID'])
		{
			\CFile::Delete($prevConfig['CACHE_BUTTON_ID']);
		}

		Model\LivechatTable::delete($this->id);
		$this->config = false;

		return true;
	}

	public static function prepareAlias($alias)
	{
		if (!\Bitrix\Main\Loader::includeModule("im"))
			return false;

		$alias = \CUtil::translit($alias, LANGUAGE_ID, array(
			"max_len"=>255,
			"safe_chars"=>".",
			"replace_space" => '-',
			"replace_other" => '-'
		));

		return \Bitrix\Im\Alias::prepareAlias($alias);
	}

	public function checkAvailableName($alias)
	{
		if (!\Bitrix\Main\Loader::includeModule("im"))
			return false;

		$alias = self::prepareAlias($alias);
		$orm = \Bitrix\Im\Model\AliasTable::getList(Array(
			'filter' => Array(
				'=ALIAS' => $alias,
				'=ENTITY_TYPE' => \Bitrix\Im\Alias::ENTITY_TYPE_LIVECHAT,
				'!=ENTITY_ID' => $this->id
			)
		));

		return $orm->fetch()? false: true;
	}

	public static function canRemoveCopyright()
	{
		return \Bitrix\Imopenlines\Limit::canRemoveCopyright();
	}

	public static function getFormatedUrl($alias = '')
	{
		return \Bitrix\ImOpenLines\Common::getServerAddress().'/online/'.$alias;
	}

	public function get($configId = null)
	{
		if ($configId)
		{
			$this->id = intval($configId);
		}

		if ($this->id <= 0)
		{
			return false;
		}

		$orm = Model\LivechatTable::getById($this->id);
		$this->config = $orm->fetch();
		if (!$this->config)
			return false;

		$this->config['URL'] = self::getFormatedUrl($this->config['URL_CODE']);
		$this->config['URL_PUBLIC'] = self::getFormatedUrl($this->config['URL_CODE_PUBLIC']);
		$this->config['URL_SERVER'] = self::getFormatedUrl();
		$this->config['COPYRIGHT_REMOVED'] = self::canRemoveCopyright()? $this->config['COPYRIGHT_REMOVED']: "N";
		$this->config['CAN_REMOVE_COPYRIGHT'] = self::canRemoveCopyright()? 'Y':'N';
		$this->config['BACKGROUND_IMAGE_LINK'] = $this->config['BACKGROUND_IMAGE']? \CFile::GetPath($this->config['BACKGROUND_IMAGE']): "";

		return $this->config;
	}

	/**
	 * @return array|bool
	 */
	public function getPublicLink()
	{
		$result = false;

		$orm = Model\LivechatTable::getList([
			'select' => ['BACKGROUND_IMAGE', 'CONFIG_NAME' => 'CONFIG.LINE_NAME', 'URL_CODE_PUBLIC', 'TEXT_PHRASES'],
			'filter' => ['=CONFIG_ID' => $this->id]
		]);
		$config = $orm->fetch();
		if ($config)
		{
			$picture = '';
			if ($config['BACKGROUND_IMAGE'] > 0)
			{
				$image = \CFile::ResizeImageGet(
					$config['BACKGROUND_IMAGE'],
					array('width' => 300, 'height' => 200), BX_RESIZE_IMAGE_PROPORTIONAL, false
				);
				if($image['src'])
				{
					$picture = $image['src'];
				}
			}

			$result = [
				'ID' => $this->id,
				'NAME' => Loc::getMessage('IMOL_LCM_PUBLIC_NAME'),
				'LINE_NAME' =>
					isset($config['TEXT_PHRASES']['BX_LIVECHAT_TITLE']) && $config['TEXT_PHRASES']['BX_LIVECHAT_TITLE'] !== '' ?
						$config['TEXT_PHRASES']['BX_LIVECHAT_TITLE'] :
						$config['CONFIG_NAME'],
				'PICTURE' => $picture,
				'URL' => self::getFormatedUrl($config['URL_CODE_PUBLIC']),
				'URL_IM' => self::getFormatedUrl($config['URL_CODE_PUBLIC'])
			];
		}

		return $result;
	}

	public function getWidget($type = self::TYPE_BUTTON, $lang = null, $config = array(), $force = false)
	{
		if (!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return false;
		}

		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			return false;
		}

		if (!\CPullOptions::GetQueueServerStatus())
		{
			return false;
		}

		$charset = SITE_CHARSET;

		$jsData = $this->getWidgetSource(Array('LANG' => $lang, 'CONFIG' => $config, 'FORCE' => $force ? 'Y' : 'N'));
		if (!$jsData)
		{
			return false;
		}

		$codeWidget = '<script type="text/javascript">'.$jsData."</script>";

		return $codeWidget;
	}

	/**
	 * @deprecated
	 *
	 * @param array $params
	 * @return string
	 */
	public static function updateCommonFiles($params = array())
	{
		if(\Bitrix\Main\Loader::includeModule('Crm'))
		{
			\Bitrix\Crm\SiteButton\Manager::updateScriptCacheAgent();
		}

		return "";
	}

	public static function getListForSelect()
	{
		$select = Array();
		$orm = \Bitrix\ImOpenLines\Model\LivechatTable::getList(Array(
			'select' => Array(
				'CONFIG_ID', 'LINE_NAME' => 'CONFIG.LINE_NAME'
			)
		));
		while ($row = $orm->fetch())
		{
			$select[$row['CONFIG_ID']] = $row['LINE_NAME']? $row['LINE_NAME']: $row['CONFIG_ID'];
		}
		return $select;
	}

	public static function getWidgetLocalize($languageId = null)
	{
		$resources = \Bitrix\Main\UI\Extension::getResourceList('imopenlines.component.widget', ['skip_core_js' => true]);

		$messages = [];

		foreach ($resources['lang'] as $file)
		{
			$fileMessages = \Bitrix\Main\Localization\Loc::loadLanguageFile(Application::getDocumentRoot().$file, $languageId);
			if ($fileMessages)
			{
				$messages = array_merge($messages, $fileMessages);
			}
		}

		return $messages;
	}

	/**
	 * Return message list with phrases from options
	 *
	 * @param array $phrases
	 * @param null $languageId
	 *
	 * @return array
	 */
	public static function getWidgetPhrases($phrases = array(), $languageId = null)
	{
		$messages = self::getWidgetLocalize($languageId);

		if (!empty($phrases) && is_array_assoc($phrases))
		{
			foreach ($phrases as $code => $phrase)
			{
				if ($phrase != '')
				{
					$messages[$code] = $phrase;
				}
			}
		}

		return $messages;
	}

	public static function compileWidgetAssets()
	{
		if (!defined('IMOL_WIDGET_GENERATE') || !IMOL_WIDGET_GENERATE)
		{
			return "";
		}

		define('UI_DESIGN_TOKENS_SKIP_CUSTOM_EXTENSION', true);
		// Note: temporarily remove this constant if you need check on developer version Vue
		define('VUEJS_DEBUG_DISABLE', true);

		$resources = \Bitrix\Main\UI\Extension::getResourceList([
			'main.core.minimal',
			'imopenlines.component.widget',
		], [
			'skip_extensions' => ['core', 'main.core', 'main.polyfill.core', 'ui.fonts.opensans', 'main.popup', 'im.v2.lib.parser'],
			'get_resolved_extension_list' => true,
		]);

		$scriptContent = "// widget bundle";
		foreach ($resources['js'] as $path)
		{
			$purePath = $path;

			$path = Application::getDocumentRoot().$path;
			if (!Main\IO\File::isFileExists($path))
			{
				continue;
			}

			$scriptContent .= "\n\n// file: ".$purePath."\n".Main\IO\File::getFileContents($path)."\n\n";
		}

		$scriptContent = preg_replace('/\/\/#(\s?)sourceMappingURL(\s?)=(\s?)([\w\.\-])+/mi', ' ', $scriptContent);

		// change BX.Vue => BX.WidgetVue to use the new features of the library,
		// we need to replace default export to another variable
		$scriptContent = str_replace(
			[
				'BX.BitrixVue',
				'ui_vue.BitrixVue',
				'exports.BitrixVue',
				'ui_vue_vuex.Vue',
				'ui_vue.Vue',
				'exports.Vue',
				'BX.Vue',
			],
			[
				'BX.WidgetBitrixVue',
				'ui_vue.WidgetBitrixVue',
				'exports.WidgetBitrixVue',
				'ui_vue_vuex.WidgetVue',
				'ui_vue.WidgetVue',
				'exports.WidgetVue',
				'BX.WidgetVue',
			],
			$scriptContent
		);

		Main\IO\File::putFileContents(Application::getDocumentRoot().self::WIDGET_PATH_SCRIPT, $scriptContent);
		Main\IO\File::putFileContents(Application::getDocumentRoot().self::WIDGET_PATH_EXTENTION_LIST, implode("\n", $resources['resolved_extension']));

		$stylesContent = "/* widget bundle*/";
		foreach ($resources['css'] as $path)
		{
			$purePath = $path;
			$path = Application::getDocumentRoot().$path;
			if (!Main\IO\File::isFileExists($path))
			{
				continue;
			}

			$stylesContent .= "\n\n/* file: ".$purePath." */\n".Main\IO\File::getFileContents($path)."\n\n";
		}

		$stylesContent = preg_replace('/\/\*#(\s?)sourceMappingURL(\s?)=(\s?)([\w\.\-])+(\s?\*\/)/mi', ' ', $stylesContent);

		Main\IO\File::putFileContents(Application::getDocumentRoot().self::WIDGET_PATH_STYLE, $stylesContent);

		return "";
	}

	private function getWidgetSource($params = array())
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return '';
		}

		$config = $this->get();

		$params['LANG'] = isset($params['LANG'])? $params['LANG']: \Bitrix\Main\Localization\Loc::getCurrentLang();
		$params['CONFIG'] = is_array($params['CONFIG'])? $params['CONFIG']: Array();

		$charset = SITE_CHARSET;

		$lang = $params['LANG'];
		$host = \Bitrix\Crm\SiteButton\ResourceManager::getServerAddress();
		$code = $config['URL_CODE'];
		$copyrightEnable = isset($params['CONFIG']["copyright"])? (bool)$params['CONFIG']["copyright"]: true;
		$copyrightEnable = $copyrightEnable? 'true': 'false';
		$copyrightUrl = \Bitrix\ImOpenLines\Common::getBitrixUrlByLang($lang);

		\Bitrix\ImOpenLines\LiveChatManager::compileWidgetAssets();

		$localize = \CUtil::PhpToJSObject(self::getWidgetLocalize($lang));

$initWidget = <<<JS
	var buttonInstance = BX.SiteButton;
	BXLiveChat = new BX.LiveChatWidget({
		host: '{$host}',
		code: '{$code}',
		language: '{$lang}',
		styles: {
			backgroundColor: buttonInstance.config.bgColor || null,
			iconColor: buttonInstance.config.iconColor || null
		},
		location: buttonInstance.config.location || null,
		buttonInstance: buttonInstance,
		copyrightUrl: '{$copyrightUrl}',
		copyright: {$copyrightEnable},
		localize: {$localize}
	});
	BXLiveChat.start();
JS;

		$scriptName = 'script.js';
		$scriptPath = Application::getDocumentRoot().self::WIDGET_PATH_SCRIPT;
		$scriptPathMin = mb_substr(Application::getDocumentRoot().self::WIDGET_PATH_SCRIPT, 0, -3).'.min.js';
		if (Main\IO\File::isFileExists($scriptPathMin))
		{
			$file = new \Bitrix\Main\IO\File($scriptPath);
			$minFile = new \Bitrix\Main\IO\File($scriptPathMin);
			if ($file->getModificationTime() <= $minFile->getModificationTime())
			{
				$scriptName = 'script.min.js';
			}
		}

		$stylesName = 'styles.css';
		$stylesPath = Application::getDocumentRoot().self::WIDGET_PATH_STYLE;
		$stylesPathMin = mb_substr(Application::getDocumentRoot().self::WIDGET_PATH_STYLE, 0, -4).'.min.css';
		if (Main\IO\File::isFileExists($stylesPathMin))
		{
			$file = new \Bitrix\Main\IO\File($stylesPath);
			$minFile = new \Bitrix\Main\IO\File($stylesPathMin);
			if ($file->getModificationTime() <= $minFile->getModificationTime())
			{
				$stylesName = 'styles.min.css';
			}
		}

		$codeWidget =
			'window.addEventListener(\'onBitrixLiveChatSourceLoaded\',function() {'
				.str_replace(["\n","\t"], " ", $initWidget).
			'});'.
			'(function () {'.
				'var f = function () {'.
					'var week = function () {var d = new Date();d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1)); return Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);};'.
					'var head = (document.getElementsByTagName("head")[0] || document.documentElement);'.
					'var style = document.createElement("link"); style.type = "text/css"; style.rel = "stylesheet";  style.href = "'.$host.'/bitrix/js/imopenlines/widget/'.$stylesName.'?r='.time().'-"+week();'.
					'var script = document.createElement("script"); script.type = "text/javascript"; script.async = "true"; script.charset = "'.$charset.'"; script.src = "'.$host.'/bitrix/js/imopenlines/widget/'.$scriptName.'?r='.time().'-"+week();'.
					'head.appendChild(style); head.appendChild(script);'.
				'};'.
				'if (typeof(BX)!="undefined" && typeof(BX.ready)!="undefined") {BX.ready(f)}'.
				'else if (typeof(jQuery)!="undefined") {jQuery(f)}'.
				'else {f();}'.
			'})();'
		;

		return $codeWidget;
	}

	public function getWidgetConfigForPublicPage($params = array())
	{
		$config = $this->get();

		$params['LANG'] = isset($params['LANG'])? $params['LANG']: \Bitrix\Main\Localization\Loc::getCurrentLang();
		$params['CONFIG'] = is_array($params['CONFIG'])? $params['CONFIG']: Array();

		$lang = $params['LANG'];
		$host = \Bitrix\ImOpenLines\Common::getServerAddress();
		$code = $config['URL_CODE'];
		$copyrightEnable = isset($params['CONFIG']["copyright"])? (bool)$params['CONFIG']["copyright"]: true;
		$copyrightEnable = $copyrightEnable? 'true': 'false';
		$copyrightUrl = \Bitrix\ImOpenLines\Common::getBitrixUrlByLang($lang);

		return <<<JS
	BXLiveChat = new BX.LiveChatWidget({
		pageMode: {
			placeholder: 'imopenlines-page-placeholder',
			useBitrixLocalize: true,
		},
		host: '{$host}',
		code: '{$code}',
		language: '{$lang}',
		copyrightUrl: '{$copyrightUrl}',
		copyright: {$copyrightEnable},
	});
	BXLiveChat.start();
JS;
	}

	public static function available()
	{
		if (!is_null(static::$availableCount))
		{
			return static::$availableCount > 0;
		}
		$orm = \Bitrix\ImOpenLines\Model\LivechatTable::getList(Array(
			'select' => Array('CNT'),
			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
			),
		));
		$row = $orm->fetch();
		static::$availableCount = $row['CNT'];

		return ($row['CNT'] > 0);
	}

	public static function availableCount()
	{
		return is_null(static::$availableCount)? 0: static::$availableCount;
	}

	public function getError()
	{
		return $this->error;
	}
}