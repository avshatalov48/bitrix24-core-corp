<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;

/**
 * Class Design
 * @package Bitrix\Crm\WebForm
 */
class Design
{
	/** @var array $options Options. */
	protected $options = [
		'theme' => 'business-light',
		'style' => '',
		'dark' => 'N',
		'shadow' => 'N',
		'font' => [
			'uri' => '',
			'family' => '',
		],
		'border' => [
			'top' => 'N',
			'right' => 'N',
			'bottom' => 'N',
			'left' => 'N',
		],
		'color' => [
			'primary' => '',
			'primaryText' => '',
			'text' => '',
			'background' => '',
			'fieldBorder' => '',
			'fieldBackground' => '',
			'fieldFocusBackground' => '',
		],
		'backgroundImage' => ''
	];

	/**
	 * Design constructor.
	 *
	 * @param array $options Options.
	 */
	public function __construct(array $options = [])
	{
		if (empty($options['theme']))
		{
			$def = $this->options['theme'];
			$options = self::getThemes()[$def];
			$options['theme'] = $def;
		}
		$this->setOptions($options);
	}

	/**
	 * Get options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * Set options.
	 *
	 * @param array $options Options.
	 * @return $this
	 */
	public function setOptions(array $options = [])
	{
		foreach ($options as $key => $value)
		{
			if (!isset($this->options[$key]))
			{
				continue;
			}

			$value = is_bool($value) ? ($value ? 'Y' : 'N') : $value;

			switch ($key)
			{
				case 'theme':
					$allowed = self::getThemes();
					if (!isset($allowed[$value]))
					{
						$value = '';
					}
					break;

				case 'dark':
					$allowed = self::getModes();
					if (!isset($allowed[$value]))
					{
						$value = 'N';
					}
					break;

				case 'shadow':
					$value = $value === 'Y' ? 'Y' : 'N';
					break;

				case 'font':
					if (!is_array($value))
					{
						$value = [];
					}
					$fontUri = trim(isset($value['uri']) ? (string) $value['uri'] : '');
					$fontUri = mb_strpos($fontUri, 'https://fonts.googleapis.com/css') === 0 ? $fontUri : '';
					$value = [
						'uri' => $fontUri,
						'family' => isset($value['family']) ? (string) $value['family'] : ''
					];
					break;

				case 'border':
					if (!is_array($value))
					{
						$value = [];
					}
					$value = array_intersect_key($value, $this->options['border']);
					foreach ($value as $index => $val)
					{
						$val = is_bool($val) ? ($val ? 'Y' : 'N') : $val;
						$value[$index] = $val === 'Y' ? 'Y' : 'N';
					}
					break;

				case 'color':
					if (!is_array($value))
					{
						$value = [];
					}
					$value = array_intersect_key($value, $this->options['color']);
					foreach ($value as $index => $val)
					{
						$value[$index] = (string) $val;
					}
					break;

				default:
					$value = (string) $value;
					break;
			}

			$this->options[$key] = $value;
		}

		return $this;
	}

	/**
	 * Convert to typed array
	 *
	 * @return array
	 */
	public function toTypedArray()
	{
		$data = $this->options;
		$data['shadow'] = $data['shadow'] === 'Y';
		$data['dark'] = $data['dark'] === 'Y'
			? true
			: ($data['dark'] === 'N'
				? false
				: $data['dark']
				);

		foreach ($data['border'] as $key => $value)
		{
			$data['border'][$key] = $value === 'Y';
		}

		return $data;
	}

	/**
	 * Get themes.
	 *
	 * @return array
	 */
	public static function getThemeNames()
	{
		return [
			'business' => Loc::getMessage('CRM_WEBFORM_DESIGN_THEME_BUSINESS'),
			'modern' => Loc::getMessage('CRM_WEBFORM_DESIGN_THEME_MODERN'),
			'classic' => Loc::getMessage('CRM_WEBFORM_DESIGN_THEME_CLASSIC'),
			'fun' => Loc::getMessage('CRM_WEBFORM_DESIGN_THEME_FUN'),
			'pixel' => Loc::getMessage('CRM_WEBFORM_DESIGN_THEME_PIXEL'),
		];
	}

	/**
	 * Get theme list.
	 *
	 * @return array
	 */
	public static function getThemes()
	{
		return [
			'business-light' => [
				'style' => '',
				'dark' => 'N',
				'shadow' => 'Y',
				'font' => [
					'uri' => '',
					'family' => '',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#0F58D0',
					'primaryText' => '#FFFFFF',
					'text' => '#000000',
					'background' => '#FFFFFF',
					'fieldBorder' => '#00000019',
					'fieldBackground' => '#00000014',
					'fieldFocusBackground' => '#ffffffff',
				]
			],
			'business-dark' => [
				'style' => '',
				'dark' => 'Y',
				'shadow' => 'Y',
				'font' => [
					'uri' => '',
					'family' => '',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#0F58D0',
					'primaryText' => '#FFFFFF',
					'text' => '#FFFFFF',
					'background' => '#282D30',
					'fieldBorder' => '#ffffff19',
					'fieldBackground' => '#ffffff14',
					'fieldFocusBackground' => '#0000002b',
				]
			],
			'modern-light' => [
				'style' => 'modern',
				'dark' => 'N',
				'shadow' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap&subset=cyrillic',
					'family' => 'Open Sans',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#FFD110',
					'primaryText' => '#000000',
					'text' => '#000000',
					'background' => '#FFFFFF',
					'fieldBorder' => '#00000014',
					'fieldBackground' => '#00000000',
					'fieldFocusBackground' => '#00000000',
				]
			],
			'modern-dark' => [
				'style' => 'modern',
				'dark' => 'Y',
				'shadow' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap&subset=cyrillic',
					'family' => 'Open Sans',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#ffd110',
					'primaryText' => '#000000',
					'text' => '#FFFFFF',
					'background' => '#282d30',
					'fieldBorder' => '#ffffff14',
					'fieldBackground' => '#00000000',
					'fieldFocusBackground' => '#00000000',
				]
			],
			'classic-light' => [
				'style' => '',
				'dark' => 'N',
				'shadow' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&display=swap&subset=cyrillic',
					'family' => 'PT Serif',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#000000',
					'primaryText' => '#FFFFFF',
					'text' => '#000000',
					'background' => '#FFFFFF',
					'fieldBorder' => '#00000014',
					'fieldBackground' => '#00000014',
					'fieldFocusBackground' => '#0000000c',
				]
			],
			'classic-dark' => [
				'style' => '',
				'dark' => 'Y',
				'shadow' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&display=swap&subset=cyrillic',
					'family' => 'PT Serif',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#FFFFFF',
					'primaryText' => '#000000',
					'text' => '#FFFFFF',
					'background' => '#000000',
					'fieldBorder' => '#ffffff14',
					'fieldBackground' => '#ffffff14',
					'fieldFocusBackground' => '#ffffff0c',
				]
			],
			'fun-light' => [
				'style' => '',
				'dark' => 'N',
				'shadow' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=Pangolin&display=swap&subset=cyrillic',
					'family' => 'Pangolin',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#F09B22',
					'primaryText' => '#000000',
					'text' => '#000000',
					'background' => '#FFFFFF',
					'fieldBorder' => '#00000014',
					'fieldBackground' => '#f09b2214',
					'fieldFocusBackground' => '#0000000c',
				]
			],
			'fun-dark' => [
				'style' => '',
				'dark' => 'Y',
				'shadow' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=Pangolin&display=swap&subset=cyrillic',
					'family' => 'Pangolin',
				],
				'border' => [
					'bottom' => 'Y',
				],
				'color' => [
					'primary' => '#F09B22',
					'primaryText' => '#000000',
					'text' => '#FFFFFF',
					'background' => '#221400ff',
					'fieldBorder' => '#f09b220c',
					'fieldBackground' => '#f09b2214',
					'fieldFocusBackground' => '#ffffff0c',
				]
			],
			'pixel-light' => [
				'style' => '',
				'dark' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=Press+Start+2P&display=swap&subset=cyrillic',
					'family' => 'Press Start 2P',
				],
				'color' => [
					'primary' => '#00a74c',
					'primaryText' => '#FFFFFF',
					'text' => '#90EE90',
					'background' => '#282D30',
					'fieldBorder' => '#ffffff19',
					'fieldBackground' => '#ffffff14',
					'fieldFocusBackground' => '#0000002b',
				]
			],
			'pixel-dark' => [
				'style' => '',
				'dark' => 'Y',
				'font' => [
					'uri' => 'https://fonts.googleapis.com/css?family=Press+Start+2P&display=swap&subset=cyrillic',
					'family' => 'Press Start 2P',
				],
				'color' => [
					'primary' => '#00a74c',
					'primaryText' => '#FFFFFF',
					'text' => '#90EE90',
					'background' => '#282D30',
					'fieldBorder' => '#ffffff19',
					'fieldBackground' => '#ffffff14',
					'fieldFocusBackground' => '#0000002b',
				]
			],
		];
	}

	/**
	 * Get styles.
	 *
	 * @return array
	 */
	public static function getStyles()
	{
		return [
			//'' => Loc::getMessage('CRM_WEBFORM_DESIGN_STYLE_STANDARD'),
			'modern' => Loc::getMessage('CRM_WEBFORM_DESIGN_STYLE_MODERN'),
		];
	}

	/**
	 * Get modes.
	 *
	 * @return array
	 */
	public static function getModes()
	{
		return [
			'N' => Loc::getMessage('CRM_WEBFORM_DESIGN_MODE_N'),
			'Y' => Loc::getMessage('CRM_WEBFORM_DESIGN_MODE_Y'),
			//'auto' => Loc::getMessage('CRM_WEBFORM_DESIGN_MODE_AUTO'),
		];
	}
}
