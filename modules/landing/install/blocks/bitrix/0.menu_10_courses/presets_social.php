<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'facebook' => [
		'name' => '<i class="fa fa-facebook"></i> Facebook',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="facebook">
				<a class="landing-block-card-social-icon-link u-icon-v3 g-width-35 g-height-35 g-font-size-default g-theme-color-gray-light-v2 g-color-white--hover g-theme-bg-gray-light-v3 g-bg-primary--hover g-rounded-50x g-transition-0_2 g-transition--ease-in"
				   href="https://facebook.com">
					<i class="landing-block-card-social-icon fa fa-facebook"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://facebook.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-facebook'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'instagram' => [
		'name' => '<i class="fa fa-instagram"></i> Instagram',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="instagram">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://instagram.com">
					<i class="landing-block-card-social-icon fa fa-instagram"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://instagram.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-instagram'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'twitter' => [
		'name' => '<i class="fa fa-twitter"></i> Twitter',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="twitter">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://twitter.com">
					<i class="landing-block-card-social-icon fa fa-twitter"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://twitter.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-twitter'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'youtube' => [
		'name' => '<i class="fa fa-youtube"></i> Youtube',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="youtube">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://youtube.com">
					<i class="landing-block-card-social-icon fa fa-youtube"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://youtube.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-youtube'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'telegram' => [
		'name' => '<i class="fa fa-telegram"></i> Telegram',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="telegram">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://telegram.com">
					<i class="landing-block-card-social-icon fa fa-telegram"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://telegram.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-telegram'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'pinterest' => [
		'name' => '<i class="fa fa-pinterest"></i> Pinterest',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="pinterest">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://pinterest.com">
					<i class="landing-block-card-social-icon fa fa-pinterest"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://pinterest.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-pinterest'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'skype' => [
		'name' => '<i class="fa fa-skype"></i> Skype',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="skype">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://skype.com">
					<i class="landing-block-card-social-icon fa fa-skype"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://skype.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-skype'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'dribbble' => [
		'name' => '<i class="fa fa-dribbble"></i> Dribbble',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="dribbble">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://dribbble.com">
					<i class="landing-block-card-social-icon fa fa-dribbble"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://dribbble.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-dribbble'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'linkedin' => [
		'name' => '<i class="fa fa-linkedin"></i> Linkedin',
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="linkedin">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://linkedin.com">
					<i class="landing-block-card-social-icon fa fa-linkedin"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://linkedin.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-linkedin'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'vk' => [
		'name' => '<i class="fa fa-vk"></i> '.Loc::getMessage('LANDING_BLOCK_MENU10__SOCIALS__PRESET_VK'),
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="vk">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://vk.com">
					<i class="landing-block-card-social-icon fa fa-vk"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://vk.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-vk'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],

	'odnoklassniki' => [
		'name' => '<i class="fa fa-odnoklassniki"></i> '.Loc::getMessage('LANDING_BLOCK_MENU10__SOCIALS__PRESET_OK'),
		'html' => '
			<li class="landing-block-card-social list-inline-item g-mr-10 g-mr-0--last"
				data-card-preset="odnoklassniki">
				<a class="landing-block-card-social-icon-link d-block u-icon-v3 u-icon-size--sm g-rounded-50x g-bg-gray-light-v4 g-color-gray-light-v1 g-bg-primary--hover g-color-white--hover g-font-size-14"
				   href="https://odnoklassniki.com">
					<i class="landing-block-card-social-icon fa fa-odnoklassniki"></i>
				</a>
			</li>',
		'values' => [
			'.landing-block-card-social-icon-link' => [
				'href' => 'https://odnoklassniki.com',
			],
			'.landing-block-card-social-icon' => [
				'type' => 'icon',
				'classList' => ['fa','fa-odnoklassniki'],
			],
		],
		'disallow' => ['.landing-block-card-social-icon'],
	],
];