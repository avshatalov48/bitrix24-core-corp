<?php

namespace Bitrix\Disk\View;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\UI\Extension;

/**
 * @deprecated
 */
class Video extends Base
{
	// should be equal to \Bitrix\Transformer\VideoTransformer::MAX_FILESIZE
	const TRANSFORM_VIDEO_MAX_LIMIT = 3221225472;

	const PLAYER_MIN_WIDTH = 400;
	const PLAYER_MIN_HEIGHT = 300;

	public function __construct($name, $fileId, $viewId = null, $previewId = null, $isTransformationEnabledInStorage = true)
	{
		parent::__construct($name, $fileId, $viewId, $previewId, $isTransformationEnabledInStorage);
		$preview = $this->getPreviewData();
		if(!empty($preview) && !empty($preview['WIDTH']) && !empty($preview['HEIGHT']))
		{
			$sizes = $this->calculateSizes($preview,
				array('WIDTH' => $this->getJsViewerWidth(), 'HEIGHT' => $this->getJsViewerHeight()),
				array('WIDTH' => self::PLAYER_MIN_WIDTH, 'HEIGHT' => self::PLAYER_MIN_HEIGHT)
			);
			$this->jsViewerHeight = $sizes['HEIGHT'];
			$this->jsViewerWidth = $sizes['WIDTH'];
		}
	}

	/**
	 * Extension of the view.
	 *
	 * @return string
	 */
	public static function getViewExtension()
	{
		return 'mp4';
	}

	/**
	 * Is transformation allowed for this View.
	 *
	 * @return bool
	 */
	public static function isTransformationAllowedInOptions()
	{
		return true;
	}

	/**
	 * Returns maximum allowed transformation file size.
	 *
	 * @return int
	 */
	public function getMaxSizeTransformation()
	{
		return Configuration::getMaxSizeForVideoTransformation();
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
		$params = $this->normalizePaths($params);
		$preview = $this->getPreviewData();
		if($params['IFRAME'] == 'Y')
		{
			$sizeType = 'fluid';
			$params['WIDTH'] = '';
			$params['HEIGHT'] = '';
		}
		else
		{
			$sizeType = 'adjust';
			if($preview)
			{
				$sizeType = 'fluid';
			}
			if(isset($params['SIZE_TYPE']) && !empty($params['SIZE_TYPE']))
			{
				$sizeType = $params['SIZE_TYPE'];
			}
			if(!empty($preview) && !empty($preview['WIDTH']) && !empty($preview['HEIGHT']) && isset($params['WIDTH']) && isset($params['HEIGHT']))
			{
				$sizes = $this->calculateSizes($preview, $params);
				$params['WIDTH'] = $sizes['WIDTH'];
				$params['HEIGHT'] = $sizes['HEIGHT'];
			}
			if(!isset($params['WIDTH']))
			{
				$params['WIDTH'] = $this->getJsViewerWidth();
			}
			if(!isset($params['HEIGHT']))
			{
				$params['HEIGHT'] = $this->getJsViewerHeight();
			}
		}

		if ($params['WIDTH'] < 400)
		{
			$params['WIDTH'] = 400;
		}

		if ($params['HEIGHT'] < 130)
		{
			$params['HEIGHT'] = 130;
		}

		$autostart = 'Y';
		if(isset($params['AUTOSTART']) && $params['AUTOSTART'] == 'N')
		{
			$autostart = $params['AUTOSTART'];
		}
		if(isset($params['ID']))
		{
			$params['PLAYER_ID'] = $params['ID'];
		}
		ob_start();
		if($params['IS_MOBILE_APP'] === true)
		{
			$this->renderForMobileApp($params);
		}
		else
		{
			$this->renderForDesktop($params, $autostart, $sizeType);
		}
		return ob_get_clean();
	}

	/**
	 * Calculate sizes of popup with player.
	 * @param array $originalSizes
	 * @param array $maxSizes
	 * @param array $minSizes
	 * @return array
	 * @throws ArgumentNullException
	 */
	private function calculateSizes($originalSizes, $maxSizes, $minSizes = array())
	{
		if(!isset($originalSizes['WIDTH']) || !isset($originalSizes['HEIGHT']))
		{
			throw new ArgumentNullException('originalSizes');
		}
		if(!isset($maxSizes['WIDTH']) || !isset($maxSizes['HEIGHT']))
		{
			throw new ArgumentNullException('maxSizes');
		}
		if(!isset($minSizes['WIDTH']))
		{
			$minSizes['WIDTH'] = 0;
		}
		if(!isset($minSizes['HEIGHT']))
		{
			$minSizes['HEIGHT'] = 0;
		}
		if($originalSizes['WIDTH'] > $minSizes['WIDTH'] && $originalSizes['HEIGHT'] > $minSizes['HEIGHT'] &&
		 $originalSizes['WIDTH'] < $maxSizes['WIDTH'] && $originalSizes['HEIGHT'] < $maxSizes['HEIGHT'])
		{
			$newSizes = array(
				'WIDTH' => $originalSizes['WIDTH'],
				'HEIGHT' => $originalSizes['HEIGHT']
			);
			return $newSizes;
		}
		if($originalSizes['WIDTH'] < $minSizes['WIDTH'] || $originalSizes['HEIGHT'] < $minSizes['HEIGHT'])
		{
			$newSizes = array(
				'WIDTH' => $minSizes['WIDTH'],
				'HEIGHT' => $minSizes['HEIGHT']
			);
			$resultRelativeSize = $newSizes['WIDTH'] / $newSizes['HEIGHT'];
			$videoRelativeSize = $originalSizes['WIDTH'] / $originalSizes['HEIGHT'];
			if($resultRelativeSize > $videoRelativeSize)
			{
				$reduceRatio = $newSizes['WIDTH'] / $originalSizes['WIDTH'];
			}
			else
			{
				$reduceRatio = $newSizes['HEIGHT'] / $originalSizes['HEIGHT'];
			}
		}
		else
		{
			$newSizes = array(
				'WIDTH' => $maxSizes['WIDTH'],
				'HEIGHT' => $maxSizes['HEIGHT']
			);
			$resultRelativeSize = $newSizes['WIDTH'] / $newSizes['HEIGHT'];
			$videoRelativeSize = $originalSizes['WIDTH'] / $originalSizes['HEIGHT'];
			if($resultRelativeSize > $videoRelativeSize)
			{
				$reduceRatio = $newSizes['HEIGHT'] / $originalSizes['HEIGHT'];
			}
			else
			{
				$reduceRatio = $newSizes['WIDTH'] / $originalSizes['WIDTH'];
			}
		}
		$newSizes['WIDTH'] = floor($originalSizes['WIDTH'] * $reduceRatio);
		$newSizes['HEIGHT'] = floor($originalSizes['HEIGHT'] * $reduceRatio);
		return array('WIDTH' => $newSizes['WIDTH'], 'HEIGHT' => $newSizes['HEIGHT']);
	}

	/**
	 * Returns true if view can be rendered in some way.
	 *
	 * @return bool
	 */
	public function isHtmlAvailable()
	{
		if($this->getData() || $this->isTransformationAllowed())
		{
			return true;
		}

		return false;
	}

	public function renderTransformationInProcessMessage($params = [])
	{
		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:disk.file.transform.video',
			'',
			[
				'BFILE_ID' => $this->fileId,
				'ATTACHED_OBJECT' => $params['ATTACHED_OBJECT'],
				'FILE' => $params['FILE'],
			]
		);

		return ob_get_clean();
	}

	/**
	 * Returns true if edit button should be hidden in js viewer.
	 *
	 * @return bool
	 */
	public function isJsViewerHideEditButton()
	{
		return true;
	}

	/**
	 * Get type attribute for bb-code in html-editor
	 *
	 * @return string
	 */
	public function getEditorTypeFile()
	{
		if($this->isHtmlAvailable())
		{
			return 'player';
		}

		return null;
	}

	/**
	 * Returns array of extensions that can be viewed.
	 *
	 * @return array
	 */
	public static function getViewableExtensions()
	{
		return array_merge(
			array(
				self::getViewExtension()
			),
			self::getAdditionalViewableExtensions(),
			self::getAlternativeExtensions()
		);
	}

	/**
	 * Returns array of alternative extensions, that has the same mime type as main extension
	 *
	 * @return array
	 */
	public static function getAlternativeExtensions()
	{
		return array('mp4v', 'mpg4');
	}

	/**
	 * @return array
	 */
	private static function getAdditionalViewableExtensions()
	{
		return array('flv', 'webm', 'ogv', 'mov');
	}

	/**
	 * Returns additional json array parameters for core_viewer.js
	 *
	 * @return array
	 */
	public function getJsViewerAdditionalJsonParams()
	{
		return array('wrapClassName' => 'bx-viewer-video');
	}

	/**
	 * Returns true if file should be transformed into view regardless of origin extension.
	 *
	 * @return bool
	 */
	public static function isAlwaysTransformToViewFormat()
	{
		return true;
	}

	/**
	 * Returns true if attached object with this file should have limited rights while transform in progress.
	 *
	 * @param bool $isCheckLastTransformationStatus
	 * @return bool
	 */
	public function isNeededLimitRightsOnTransformTime(bool $isCheckLastTransformationStatus = true): bool
	{
		if($this->id > 0)
		{
			return false;
		}

		$mp4Formats = array_merge(
			array(
				self::getViewExtension()
			),
			self::getAlternativeExtensions()
		);
		if(in_array(mb_strtolower($this->fileExtension), $mp4Formats))
		{
			return false;
		}

		if($isCheckLastTransformationStatus && $this->isLastTransformationFailed())
		{
			return false;
		}

		return $this->isTransformationAllowed();
	}

	/**
	 * Check $params['PATH'] and fills $params['TRACKS'] from it with mime-types.
	 *
	 * @param array $params
	 * @return mixed
	 */
	protected function normalizePaths($params)
	{
		$mimeTypes = TypeFile::getMimeTypeExtensionList();
		if(is_array($params['PATH']))
		{
			foreach($params['PATH'] as $key => $source)
			{
				if($key == 0)
				{
					$type = $mimeTypes[$this->getExtension()];
				}
				else
				{
					$type = TypeFile::getMimeTypeByFilename($this->name);
				}
				$params['TRACKS'][] = array(
					'src' => $source,
					'type' => $type,
				);
			}
			$params['USE_PLAYLIST_AS_SOURCES'] = 'Y';
			$params['USE_PLAYLIST'] = 'Y';
			unset($params['PATH']);
		}
		else
		{
			$params['TYPE'] = $mimeTypes[$this->getExtension()];
		}
		return $params;
	}

	/**
	 * Include component to render player in mobile application.
	 *
	 * @param array $params
	 */
	protected function renderForMobileApp($params)
	{
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.player',
			'',
			$params
		);
	}

	/**
	 * Include component to render player with custom skin in browser.
	 *
	 * @param array $params
	 * @param string $autostart
	 * @param string $sizeType
	 */
	protected function renderForDesktop($params, $autostart, $sizeType)
	{
		static $seeds = [];

		$videoPlayerParams = [
			'autoplay' => $autostart === 'Y',
			'autostart' => $autostart !== 'Y' && ($params['AUTOSTART_ON_SCROLL'] ?? null) === 'Y',
			'preload' => false,
			'controls' => true,
			'height' => $params['WIDTH'],
			'width' => $params['HEIGHT'],
			'fluid' => $sizeType === 'fluid',
			'skin' => 'vjs-disk_player-skin',
			'lazyload' => ($params['LAZYLOAD'] ?? null) === 'Y',
			'sources' => [],
		];

		if (isset($params['TRACKS']) && is_array($params['TRACKS']))
		{
			foreach ($params['TRACKS'] as $track)
			{
				if ($track['type'] == 'video/quicktime')
				{
					$track['type'] = 'video/mp4';
				}

				$videoPlayerParams['sources'][] = $track;
			}
		}

		$playerId = isset($params['PLAYER_ID']) && is_string($params['PLAYER_ID']) ? $params['PLAYER_ID'] : '';
		if (strlen($playerId) === 0)
		{
			if (!array_key_exists($this->fileId, $seeds))
			{
				$seeds[$this->fileId] = 0;
			}

			$seeds[$this->fileId]++;
			$seed = $seeds[$this->fileId];

			$randomSequence = new \Bitrix\Main\Type\RandomSequence($seed);
			$id = mb_substr(md5(serialize($videoPlayerParams)), 10) . $randomSequence->randString(6);

			$playerId = 'bx_videojs_player_' . $id;
		}

		?><div id="<?=$playerId . '_container'?>" class="disk-player-container<?if($sizeType == 'adjust')
		{
			?> player-adjust<?
		}
		?>"<?
		if($sizeType == 'fluid')
		{
			?> style="width: <?=$params['WIDTH'];?>px; height: <?=$params['HEIGHT'];?>px;"<?
		}
		?>>
			<div class="main-ui-loader">
				<svg class="main-ui-loader-svg" viewBox="25 25 50 50">
					<circle class="main-ui-loader-svg-circle" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				</svg>
			</div>
			<script>
			(function() {
				const params = <?=\Bitrix\Main\Web\Json::encode($videoPlayerParams)?>;
				const init = () => {
					const player = new BX.UI.VideoPlayer.Player('<?=$playerId?>', params);
					const container = document.getElementById('<?=$playerId?>_container');
					BX.Dom.append(player.createElement(), container);

					if(!player.lazyload)
					{
						player.init();
					}
				};

				if (BX.Reflection.getClass('BX.Disk.Player') !== null)
				{
					init();
				}
				else
				{
					BX.Runtime.loadExtension('disk.video').then(() => {
						init();
					});
				}
			})();
			</script>
		</div><?
	}

	/**
	 * Returns true if we should display message about transformation status.
	 *
	 * @return bool
	 */
	public function isShowTransformationInfo()
	{
		if(!self::isTransformationAllowedInOptions())
		{
			return false;
		}

		if($this->id > 0)
		{
			return false;
		}

		if($this->getSize() < self::TRANSFORM_VIDEO_MAX_LIMIT)
		{
			return true;
		}

		return false;
	}
}
