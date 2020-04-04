<?php

namespace Bitrix\Disk\View;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\TypeFile;

class Document extends Base
{
	/**
	 * Extension of the view.
	 *
	 * @return string
	 */
	public static function getViewExtension()
	{
		return 'pdf';
	}

	/**
	 * Return html code to view file.
	 *
	 * @param array $params
	 * @return string
	 */
	public function render($params = array())
	{
		if(empty($params) || !isset($params['PATH']) || empty($params['PATH']))
		{
			return '';
		}
		if(is_array($params['PATH']))
		{
			$params['PATH'] = reset($params['PATH']);
		}
		$filename = $this->getName();

		if (!isset($params['HEIGHT']))
		{
			$params['HEIGHT'] = $this->getJsViewerHeight();
		}
		if (!isset($params['WIDTH']))
		{
			$params['WIDTH'] = $this->getJsViewerWidth();
		}

		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:pdf.viewer',
			'',
			array_merge($params, array(
				'TITLE' => $filename,
			)
		));
		return ob_get_clean();
	}

	/**
	 * Is transformation allowed for this View.
	 *
	 * @return bool
	 */
	public static function isTransformationAllowedInOptions()
	{
		return Configuration::allowDocumentTransformation();
	}

	/**
	 * Returns maximum allowed transformation file size.
	 *
	 * @return int
	 */
	public function getMaxSizeTransformation()
	{
		return Configuration::getMaxSizeForDocumentTransformation();
	}

	/**
	 * True if view should be saved in version as well.
	 *
	 * @return bool
	 */
	public function isSaveForVersion()
	{
		return true;
	}

	/**
	 * Return html-attribute for iframe viewer.
	 *
	 * @return string|null
	 */
	public function getJsViewerFallbackHtmlAttributeName()
	{
		return 'data-bx-pdfFallback';
	}

	/**
	 * Return type of viewer from core_viewer.js
	 *
	 * @return string|null
	 */
	public function getJsViewerType()
	{
		return 'ajax';
	}

	/**
	 * Returns true if view can be rendered in some way.
	 *
	 * @return bool
	 */
	public function isHtmlAvailable()
	{
		if($this->getData())
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns true if edit button should be hidden in js viewer.
	 *
	 * @return bool
	 */
	public function isJsViewerHideEditButton()
	{
		if(TypeFile::isPdf($this->name))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns array of extensions that can be viewed.
	 *
	 * @return array
	 */
	public static function getViewableExtensions()
	{
		return array(
			'pdf',
		);
	}

	/**
	 * Returns approximate time of transformation of the file.
	 * We may add here some calculations based on file size or statistic.
	 *
	 * @return int
	 */
	public function getTransformTime()
	{
		return 8;
	}
}