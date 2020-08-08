<?php
namespace Bitrix\Intranet\Component\UserProfile\StressLevel;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\UserWelltoryTable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Intranet\Component\UserProfile\StressLevel;

class Img implements \Bitrix\Main\Errorable
{
	/** @var ErrorCollection errorCollection */
	protected $errorCollection;
	protected $imagePartsPath;
	protected $factor;

	const TYPE_LIST = [ 'green', 'yellow', 'red', 'unknown' ];

	function __construct()
	{
		$this->imagePartsPath = $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/intranet/lib/component/userprofile/stresslevel/assets";
		$this->factor = 1;
	}

	public function getImageSupport($checkSSL = true)
	{
		return (
			class_exists('Imagick')
			&& (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() || !$checkSSL)
		);
	}

	public function getImagePartsPath()
	{
		return $this->imagePartsPath;
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	protected function drawImageBorder(array $params = [])
	{
		$result = false;

		$imageWidth = (
			isset($params['width'])
				? intval($params['width'])
				: 0
		);
		$imageHeight = (
			isset($params['height'])
				? intval($params['height'])
				: 0
		);
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| $imageWidth <= 0
			|| $imageHeight <= 0
		)
		{
			return $result;
		}

		$borderRectangle = new \ImagickDraw();
		$borderRectangle->setFillColor('#525C69');
		$borderRectangle->setFillOpacity(0.15);
		$borderRectangle->roundRectangle(0, 0, $factor*$imageWidth, $factor*$imageHeight, $factor*5, $factor*5);
		$canvas->drawImage($borderRectangle);

		$borderRectangleInner = new \ImagickDraw();
		$borderRectangleInner->setFillColor('#FFFFFF');
		$borderRectangleInner->roundRectangle($factor*1, $factor*1, $factor*($imageWidth-1), $factor*($imageHeight-1), $factor*4, $factor*4);
		$canvas->drawImage($borderRectangleInner);

		return true;
	}

	protected function drawImageRectangle(array $params = [])
	{
		$result = false;

		$type = (
			isset($params['type'])
				? $params['type']
				: false
			);
		$value = (
			isset($params['value'])
				? intval($params['value'])
				: 0
		);
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| !$type
			|| $type == ''
		)
		{
			return $result;
		}

		$textTypeValue = StressLevel::getTypeDescription($type, $value);

		if ($textTypeValue <> '')
		{
			$textType = new \ImagickDraw();
			$fontPath = $this->getImagePartsPath().'/OpenSans-Semibold.ttf';
			if (!file_exists($fontPath))
			{
				return false;
			}
			$textType->setFont($fontPath);
			$textType->setFillColor('white');
			$textType->setStrokeAntialias(true);
			$textType->setTextAntialias(true);
			$textType->setFontSize($factor*12);

			$error = "";
			if (LANG_CHARSET != "UTF-8")
			{
				$textTypeValue = \Bitrix\Main\Text\Encoding::convertEncoding($textTypeValue, LANG_CHARSET, "UTF-8", $error);
				if (
					!$textTypeValue
					&& !empty($error)
				)
				{
					$this->errorCollection[] = new Error('CONVERT_CHARSET_ERROR');
					return null;
				}
			}

			$textMetrics = $canvas->queryFontMetrics($textType, $textTypeValue);
			$rectangleWidth = $textMetrics['textWidth']/$factor + 40;
			if ($rectangleWidth < 100)
			{
				$rectangleWidth = 100;
			}
			elseif ($rectangleWidth > 160)
			{
				$rectangleWidth = 160;
			}
		}
		if (!$rectangleWidth)
		{
			return false;
		}

		$fillColor = '#c8cbce';

		switch($type)
		{
			case 'red':
				$fillColor = '#ff5752';
				break;
			case 'green':
				$fillColor = '#9dcf00';
				break;
			case 'yellow':
				$fillColor = '#f7a700';
				break;
			case 'unknown':
				$fillColor = '#c8cbce';
				break;
			default:
		}

		$rectangle = new \ImagickDraw();
		$rectangle->setFillColor($fillColor);
		$rectangleLeft = 148;
		$rectangleRight = $rectangleLeft + $rectangleWidth;
		$rectangle->roundRectangle($factor*$rectangleLeft, $factor*17, $factor*$rectangleRight, $factor*37, $factor*11, $factor*11);
		$canvas->drawImage($rectangle);

		if ($textType)
		{
			$textType->setTextAlignment(\Imagick::ALIGN_CENTER);
			$textType->annotation($factor*($rectangleLeft + $rectangleWidth/2), $factor*32, $textTypeValue);
			$canvas->drawImage($textType);
		}

		return true;
	}

	/**
	 * @param $image \Imagick
	 * @param $draw
	 * @param $text
	 * @param $maxWidth
	 * @return array
	 * https://stackoverflow.com/questions/5746537/how-can-i-wrap-text-using-imagick-in-php-so-that-it-is-drawn-as-multiline-text
	 */
	protected function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth)
	{
		$factor = $this->factor;

		$words = explode(" ", $text);
		$lines = array();
		$i = 0;
		$lineHeight = 0;
		while($i < count($words) )
		{
			$currentLine = $words[$i];
			if($i+1 >= count($words))
			{
				$lines[] = $currentLine;
				break;
			}
			$metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
			while($metrics['textWidth'] <= $maxWidth)
			{
				$currentLine .= ' '.$words[++$i];
				if ($i+1 >= count($words))
				{
					break;
				}
				$metrics = $image->queryFontMetrics($draw, $currentLine.' '.$words[$i+1]);
			}

			$lines[] = $currentLine;
			$i++;
			if($metrics['textHeight'] > $lineHeight)
			{
				$lineHeight = $metrics['textHeight'] - $factor*3;
			}
		}
		return [$lines, $lineHeight];
	}

	protected function drawImageComment(array $params = [])
	{
		$text = (
			isset($params['value'])
				? $params['value']
				: false
		);
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$factor = $this->factor;

		if (
			!$text
			|| !$canvas
			|| $text == ''
		)
		{
			return false;
		}

		$textComment = new \ImagickDraw();
		$fontPath = $this->getImagePartsPath().'/OpenSans-Light.ttf';
		if (!file_exists($fontPath))
		{
			return false;
		}
		$fontSize = 16;
		$textComment->setFont($fontPath);
		$textComment->setFillColor('#333333');
		$textComment->setStrokeAntialias(true);
		$textComment->setTextAntialias(true);
		$textComment->setFontSize($factor*$fontSize);
		$textComment->setTextAlignment(\Imagick::ALIGN_LEFT);

		$error = "";
		if (LANG_CHARSET != "UTF-8")
		{
			$text = \Bitrix\Main\Text\Encoding::convertEncoding($text, LANG_CHARSET, "UTF-8", $error);
			if (
				!$text
				&& !empty($error)
			)
			{
				$this->errorCollection[] = new Error('CONVERT_CHARSET_ERROR');
				return null;
			}
		}


		list($lines, $lineHeight) = self::wordWrapAnnotation($canvas, $textComment, $text, $factor*154);
		$linesCount = count($lines);

		while($linesCount > 2)
		{
			$fontSize--;
			$textComment->setFontSize($factor*$fontSize);
			list($lines, $lineHeight) = self::wordWrapAnnotation($canvas, $textComment, $text, $factor*154);
			$linesCount = count($lines);
		}

		for($i = 0; $i < $linesCount; $i++)
		{
			$canvas->annotateImage($textComment, $factor*152, $factor*58 + $i*$lineHeight, 0, $lines[$i]);
		}

		return true;
	}

	protected function drawImageGradient(array $params = [])
	{
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$center = (
			isset($params['center'])
				? $params['center']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| !$center
		)
		{
			return false;
		}

		$imagePath = $this->getImagePartsPath().'/gradient_4x.png';
		if (!file_exists($imagePath))
		{
			return false;
		}

		$gradientImage = new \Imagick($imagePath);
/*
		if ($factor > 1)
		{
			$gradientImage->resizeImage($factor*104, $factor*57, \Imagick::FILTER_LANCZOS, 1);
		}
*/
		$canvas->compositeImage($gradientImage, \Imagick::COMPOSITE_DEFAULT, $center['x']-$factor*52, $center['y']-$factor*52);

		return true;
	}

	protected function drawImageArrow(array $params = [])
	{
		$stressValue = (
			isset($params['value'])
				? intval($params['value'])
				: false
		);
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$center = (
			isset($params['center'])
				? $params['center']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| !$center
			|| $stressValue === false
		)
		{
			return false;
		}

		$imagePath = $this->getImagePartsPath().'/arrow2_4x.png';
		if (!file_exists($imagePath))
		{
			return false;
		}

		$transparentColor = new \ImagickPixel('#00000000');
		$deltaY = $factor*6;
		$angle = $stressValue/100*180 - 90;
		$arrowLength = $factor*52 + $deltaY;
		$arrowWidth = $factor*16;

		$arrowImage = new \Imagick($imagePath);
/*
		if ($factor > 1)
		{
			$arrowImage->resizeImage($factor*16, $factor*34, \Imagick::FILTER_LANCZOS, 1);
		}
*/
		$arrowQuadrate = new \Imagick();
		$arrowQuadrate->newImage($arrowLength*2, $arrowLength*2, $transparentColor);
		$arrowQuadrate->compositeImage($arrowImage, \Imagick::COMPOSITE_DEFAULT, $arrowLength-$arrowWidth/2, 0*$factor);

		$radius = $arrowLength * sqrt(2);
		$arrowX = $center['x'] - ($radius*cos(deg2rad(45 - abs($angle))));
		$arrowY = $center['y'] - ($radius*cos(deg2rad(45 - abs($angle))));

		if ($angle !== 0)
		{
			$arrowQuadrate->rotateimage($transparentColor, $angle);
		}

		$canvas->compositeImage($arrowQuadrate, \Imagick::COMPOSITE_DEFAULT, $arrowX, $arrowY);

		return true;
	}

	protected function drawImageValue(array $params = [])
	{
		$stressValue = (
			isset($params['value'])
				? intval($params['value'])
				: false
		);
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$center = (
			isset($params['center'])
				? $params['center']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| !$center
			|| $stressValue === false
		)
		{
			return false;
		}

		$imageValue = new \ImagickDraw();
		$fontPath = $this->getImagePartsPath().'/OpenSans-Semibold.ttf';
		if (!file_exists($fontPath))
		{
			return false;
		}
		$imageValue->setFont($fontPath);
		$imageValue->setFillColor('#525c69');
		$imageValue->setStrokeAntialias(true);
		$imageValue->setTextAntialias(true);
		$imageValue->setFontSize($factor*26);
		$imageValue->setTextAlignment(\Imagick::ALIGN_CENTER);
		$imageValue->annotation($center['x'], $center['y']+$factor*10, $stressValue);
		$canvas->drawImage($imageValue);

		$metrics = $canvas->queryFontMetrics($imageValue, $stressValue);

		return $metrics;
	}

	protected function drawImagePercent(array $params = [])
	{
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$metrics = (
			isset($params['metrics'])
				? $params['metrics']
				: false
		);
		$center = (
			isset($params['center'])
				? $params['center']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| !$center
			|| !is_array($metrics)
		)
		{
			return false;
		}

		$imageValuePercent = new \ImagickDraw();
		$fontPath = $this->getImagePartsPath().'/OpenSans-Regular.ttf';
		if (!file_exists($fontPath))
		{
			return false;
		}
		$imageValuePercent->setFont($fontPath);
		$imageValuePercent->setFillColor('#000000');
		$imageValuePercent->setStrokeAntialias(true);
		$imageValuePercent->setTextAntialias(true);
		$imageValuePercent->setFontSize($factor*14);
		$imageValuePercent->setFillOpacity(0.30);
		$imageValuePercent->setTextAlignment(\Imagick::ALIGN_LEFT);

		$imageValuePercent->annotation($center['x'] + ($metrics['textWidth']/2), $center['y']+$factor*7, '%');
		$canvas->drawImage($imageValuePercent);

		return true;
	}

	protected function drawImageCaption(array $params = [])
	{
		$canvas = (
			isset($params['canvas'])
				? $params['canvas']
				: false
		);
		$center = (
			isset($params['center'])
				? $params['center']
				: false
		);
		$factor = $this->factor;

		if (
			!$canvas
			|| !$center
		)
		{
			return false;
		}

		$imageCaption = new \ImagickDraw();
		$fontPath = $this->getImagePartsPath().'/OpenSans-Semibold.ttf';
		if (!file_exists($fontPath))
		{
			return false;
		}
		$imageCaption->setFont($fontPath);
		$imageCaption->setFillColor('#525c69');
		$imageCaption->setFillOpacity(0.7);
		$imageCaption->setStrokeAntialias(true);
		$imageCaption->setTextAntialias(true);
		$imageCaption->setFontSize($factor*8);
		$imageCaption->setTextAlignment(\Imagick::ALIGN_CENTER);

		$caption = mb_strtoupper(Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_IMG_VALUE_CAPTION'));
		if ($caption == '')
		{
			return false;
		}

		$error = "";
		if (LANG_CHARSET != "UTF-8")
		{
			$caption = \Bitrix\Main\Text\Encoding::convertEncoding($caption, LANG_CHARSET, "UTF-8", $error);
			if (
				!$caption
				&& !empty($error)
			)
			{
				$this->errorCollection[] = new Error('CONVERT_CHARSET_ERROR');
				return null;
			}
		}

		$imageCaption->annotation($center['x'], $center['y']-$factor*13, $caption);
		$canvas->drawImage($imageCaption);

		return true;
	}

	protected function drawImagePowered(array $params = [])
	{
		$canvas = (
		isset($params['canvas'])
			? $params['canvas']
			: false
		);

		$factor = $this->factor;

		if (!$canvas)
		{
			return false;
		}

		$imagePath = $this->getImagePartsPath().'/logo_'.\Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID).'_4x.png';
		if (!file_exists($imagePath))
		{
			return false;
		}

		$logoImage = new \Imagick($imagePath);
		/*
				if ($factor > 1)
				{
					$gradientImage->resizeImage($factor*104, $factor*57, \Imagick::FILTER_LANCZOS, 1);
				}
		*/
		$logoGeometry = $logoImage->getImageGeometry();

		$imagePowered = new \ImagickDraw();
		$fontPath = $this->getImagePartsPath().'/OpenSans-Regular.ttf';
		if (!file_exists($fontPath))
		{
			return false;
		}
		$imagePowered->setFont($fontPath);
		$imagePowered->setFillColor('#828B95');
		$imagePowered->setFillOpacity(0.65);
		$imagePowered->setStrokeAntialias(true);
		$imagePowered->setTextAntialias(true);
		$imagePowered->setFontSize($factor*10);
		$imagePowered->setTextAlignment(\Imagick::ALIGN_LEFT);

		$poweredText = Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_IMG_POWERED2');
		if ($poweredText <> '')
		{
			$error = "";
			if (LANG_CHARSET != "UTF-8")
			{
				$poweredText = \Bitrix\Main\Text\Encoding::convertEncoding($poweredText, LANG_CHARSET, "UTF-8", $error);
				if (
					!$poweredText
					&& !empty($error)
				)
				{
					$this->errorCollection[] = new Error('CONVERT_CHARSET_ERROR');
					return null;
				}
			}

			$poweredText = ' '.$poweredText;
			$textMetrics = $canvas->queryFontMetrics($imagePowered, $poweredText);

			$blockWidth = intval($logoGeometry['width'] + $textMetrics['textWidth']);
		}
		else
		{
			$blockWidth = $logoGeometry['width'];
		}

		$canvasGeometry = $canvas->getImageGeometry();
		$canvasWidth = $canvasGeometry['width'];

		$left = ($canvasWidth-$blockWidth)/2;

		$canvas->compositeImage($logoImage, \Imagick::COMPOSITE_DEFAULT, $left, $factor*87);
		$imagePowered->annotation(($left+$logoGeometry['width']), $factor*96, $poweredText);
		$canvas->drawImage($imagePowered);

		return true;
	}

	public function getImage(array $params = [])
	{
		global $USER;

		$result = null;

		$factor = (
			isset($params['factor'])
				? intval($params['factor'])
				: 1
		);

		if (
			$factor < 1
			|| $factor > 10
		)
		{
			$factor = 1;
		}

		$this->factor = $factor;
		$checkSSL = $params["checkSSL"] ?? true;

		if (
			!$this->getImageSupport($checkSSL) ||
			!Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$res = UserWelltoryTable::getList([
			'filter' => [
				'USER_ID' => $USER->getId()
			],
			'order' => [
				'ID' => 'DESC'
			],
			'limit' => 1
		]);
		if (!($measurementFields = $res->fetch()))
		{
			return $result;
		}

		$canvas = new \Imagick();
		$transparentColor = new \ImagickPixel('#00000000');

		$imageWidth = 322;
		$imageHeight = 104;

		$canvas->newImage($factor*$imageWidth, $factor*$imageHeight, $transparentColor);

		$this->drawImageBorder([
			'canvas' => $canvas,
			'width' => $imageWidth,
			'height' => $imageHeight
		]);

		$gradientCenter = [
			'x' => $factor*71,
			'y' => $factor*67
		];

		$this->drawImageGradient([
			'canvas' => $canvas,
			'center' => $gradientCenter
		]);
		$this->drawImageArrow([
			'canvas' => $canvas,
			'value' => $measurementFields['STRESS'],
			'center' => $gradientCenter
		]);
		$this->drawImagePowered([
			'canvas' => $canvas
		]);
		if (intval($factor) > 1)
		{
			$canvas->resizeImage($imageWidth, $imageHeight, \Imagick::FILTER_LANCZOS, 1);
		}

		$factor = $this->factor = 1;
		$gradientCenter = [
			'x' => $factor*71,
			'y' => $factor*67
		];

		$this->drawImageRectangle([
			'canvas' => $canvas,
			'type' => $measurementFields['STRESS_TYPE'],
			'value' => $measurementFields['STRESS']
		]);
		$this->drawImageComment([
			'canvas' => $canvas,
			'value' => $measurementFields['STRESS_COMMENT']
		]);
		$metrics = $this->drawImageValue([
			'canvas' => $canvas,
			'value' => $measurementFields['STRESS'],
			'center' => $gradientCenter
		]);
		$this->drawImagePercent([
			'canvas' => $canvas,
			'metrics' => $metrics,
			'center' => $gradientCenter
		]);
		$this->drawImageCaption([
			'canvas' => $canvas,
			'center' => $gradientCenter
		]);

		$canvas->setImageFormat('png');
		$canvas->setCompressionQuality(1);

		$result = $canvas;

		return $result;
	}
}