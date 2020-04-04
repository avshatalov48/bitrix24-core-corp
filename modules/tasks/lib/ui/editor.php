<?
/**
 * @access private
 */
namespace Bitrix\Tasks\UI;

use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Util\Collection;

final class Editor
{
	public static function getHTML(array $parameters)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:main.post.form',
			'',
			static::getEditorParameters($parameters),
			false,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);

		return ob_get_clean();
	}

	public static function getEditorParameters(array $parameters)
	{
		$editorParameters = array(
			"FORM_ID" => 'task-form-'.$parameters['ID'],
			"SHOW_MORE" => "N",
			"PARSER" => array("Bold", "Italic", "Underline", "Strike", "ForeColor",
				"FontList", "FontSizeList", "RemoveFormat", "Quote", "Code",
				//(($arParams["USE_CUT"] == "Y") ? "InsertCut" : ""),
				"CreateLink",
				"Image",
				"Table",
				"Justify",
				"InsertOrderedList",
				"InsertUnorderedList",
				"SmileList",
				"Source",
				"UploadImage",
				//(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
				"MentionUser",
			),
			"BUTTONS" => static::getEditorButtons($parameters),
			"BUTTONS_HTML" => static::getEditorButtonsHTML($parameters),
			"FILES" => Array(
				"VALUE" => array(),
				"DEL_LINK" => '',
				"SHOW" => "N"
			),

			"TEXT" => array(
				"INPUT_NAME" => $parameters['INPUT_PREFIX']."[DESCRIPTION]",
				"VALUE" => str_replace("\r\n", "\n", $parameters['CONTENT']), // avoid input containing double amount of <br>
				"HEIGHT" => "120px"
			),

			"PROPERTIES" => array(), //static::getEditorProperties($parameters),
			"UPLOAD_FILE" => (
			true
			),
			"UPLOAD_FILE_PARAMS" => array('width' => 400, 'height' => 400),
			/*
			"TAGS" => Array(
				"ID" => "TAGS",
				"NAME" => "TAGS",
				"VALUE" => explode(",", trim($arResult["PostToShow"]["CategoryText"])),
				"USE_SEARCH" => "Y",
				"FILTER" => "blog",
			),
			*/
			//"SMILES" => array("VALUE" => $arSmiles),
			"NAME_TEMPLATE" => $parameters['USER_NAME_FORMAT'],
			//"AT_THE_END_HTML" => $htmlAfterTextarea,
			"LHE" => array(

				"id" => $parameters['ID'],
				"documentCSS" => "body {color:#434343;}",
				"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
				"fontSize" => "12px",
				"bInitByJS" => false,
				"height" => 100,
				"lazyLoad" => 'N',
				"bbCode" => $parameters['BBCODE_MODE'], // set editor mode: bbcode or html
				"setFocusAfterShow" => !!$parameters['ENTITY_ID'], // when creating task, we should not
				"iframeCss" => "body { padding-left: 10px !important; }",
			),
			//"USE_CLIENT_DATABASE" => "Y",
			//"ALLOW_EMAIL_INVITATION" => ($arResult["ALLOW_EMAIL_INVITATION"] ? 'Y' : 'N')
		);

		if(is_array($parameters['USER_FIELDS']))
		{
			foreach($parameters['USER_FIELDS'] as $k => $uf)
			{
				$parameters['USER_FIELDS'][$k]['FIELD_NAME'] = $parameters['INPUT_PREFIX'].'['.$uf['FIELD_NAME'].']';
			}

			$fileSystemField = Disk\UserField::getMainSysUFCode();
			$diskFileField = $parameters['USER_FIELDS'][$fileSystemField];
			if(is_array($diskFileField))
			{
				if(Collection::isA($diskFileField['VALUE']))
				{
					$diskFileField['VALUE'] = $diskFileField['VALUE']->toArray();
				}

				$editorParameters['UPLOAD_WEBDAV_ELEMENT'] = $diskFileField;
			}
		}

		return $editorParameters;
	}

	private static function getEditorProperties(array $parameters)
	{
		// make pictures inserted to the text visible
		$editorProps = array();
		$fileSystemField = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
		if(!empty($parameters['ENTITY_DATA'][$fileSystemField]))
		{
			$editorProps[$fileSystemField] = $parameters['ENTITY_DATA'][$fileSystemField];
		}

		return $editorProps;
	}

	private static function getEditorButtonsHTML(array $parameters)
	{
		if(is_array($parameters['EXTRA_BUTTONS']))
		{
			return array_map(function($item){
				return $item['HTML'];
			}, $parameters['EXTRA_BUTTONS']);
		}

		return array();
	}

	private static function getEditorButtons(array $parameters)
	{
		$buttons = array(
			"UploadImage",
			"UploadFile",
			"CreateLink",
			//(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
			//"InputTag",
		);
		if($parameters['BBCODE_MODE'])
		{
			$buttons[] = "Quote";
			$buttons[] = "MentionUser";
		}

		if(is_array($parameters['EXTRA_BUTTONS']))
		{
			$buttons = array_merge($buttons, array_keys($parameters['EXTRA_BUTTONS']));
		}

		return $buttons;
	}
}
