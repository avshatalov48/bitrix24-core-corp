<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'old_id' => '1237',
	'parent' => 'ent-en',
	'code' => 'ent-en/main',
	'name' => Loc::getMessage("LANDING_DEMO_ENT_EN_MAIN_TITLE"),
	'description' => Loc::getMessage("LANDING_DEMO_ENT_EN_MAIN_DESCRIPTION"),
	'preview' => '',
	'preview2x' => '',
	'preview3x' => '',
	'preview_url' => '',
	'show_in_list' => 'N',
	'active' => false,
	'type' => ['mainpage'],
	'version' => 3,
	'fields' => [
		'TITLE' => Loc::getMessage("LANDING_DEMO_ENT_EN_TITLE"),
		'RULE' => null,
		'ADDITIONAL_FIELDS' => [
			'THEME_USE' => 'Y',
			'THEME_COLOR' => '#1e86ff',
			'BACKGROUND_USE' => 'N',
			'BACKGROUND_POSITION' => 'center',
			'BACKGROUND_COLOR' => '#ffffff',
			'METAOG_TITLE' => 'Ent en',
			// 'METAOG_IMAGE' => '//cp.local/upload/landing/661/xuc7x9x3zft73mdudiq3f8pkarejg7nj/bg_prof_vibe_1x.png',
		],
	],
	'layout' => [],
	'items' => [
		'#block3229' => [
			'old_id' => 3229,
			'code' => 'mp_widget.about',
			'access' => 'X',
			'anchor' => 'b7742',
			'nodes' => [
				'bitrix:landing.blocks.mp_widget.about' =>
					[
						'TITLE' => '#COMPANY_NAME#',
						'TEXT' => 'Welcome to your start page! Designed exclusively for our Enterprise clients, this page displays key capabilities available to you and provides quick access to the main tools and settings.',
						'BOSS_ID' => 1,
						'SHOW_EMPLOYEES' => null,
						'SHOW_SUPERVISORS' => null,
						'SHOW_DEPARTMENTS' => null,
						'COLOR_HEADERS' => null,
						'COLOR_TEXT' => null,
						'COLOR_ICON' => null,
						'COLOR_BORDER' => null,
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg-image widget-type-rounded',
					],
			],
		],
		'#block3230' => [
			'old_id' => 3230,
			'code' => 'mp_widget.ent_activate_trial',
			'access' => 'X',
			'anchor' => 'b7743',
			'nodes' => [
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg widget-type-rounded',
					],
			],
		],
		'#block3231' => [
			'old_id' => 3231,
			'code' => '70.1.cards_with_text_button_bgimg',
			'access' => 'X',
			'anchor' => 'b7744',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'Bitrix24 Enterprise: Key Features',
					],
				'.landing-block-node-item-title' =>
					[
						0 => 'Scalability',
						1 => 'Security',
						2 => 'Performance',
						3 => 'AI assistant CoPilot',
					],
				'.landing-block-node-item-text' =>
					[
						0 => '<p bxstyle="text-align: center;"><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Up to 100 TB of storage space</span><br /><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Up to 10,000 employees</span><br /><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Multiple branches</span></p><p></p>',
						1 => '<span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Two-step authorization</span><br /><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">AES-256 encryption</span><br /><span bxstyle="font-family: var(--ui-font-family-primary, var(--ui-font-family-helvetica));color: var(--theme-color-main);font-size: 1rem;">Single sign-on</span><p></p>',
						2 => '<span bxstyle="color: var(--theme-color-main);font-size: 1rem;">99.95% SLA guaranteed</span><br /><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">High-performance cluster infrastructure</span><br /><span bxstyle="font-family: var(--ui-font-family-primary, var(--ui-font-family-helvetica));color: var(--theme-color-main);font-size: 1rem;">2.5x increased REST API performance</span><p></p>',
						3 => '<span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Write texts</span><br /><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Generate ideas</span><br /><span bxstyle="color: var(--theme-color-main);font-size: 1rem;">Automate workflows</span><p></p>',
					],
				'.landing-block-node-item-button' =>
					[
						0 =>
							[
								'href' => 'javascript:BX.Helper.show(\'redirect=detail&code=18783714\')',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => 'See more',
							],
						1 =>
							[
								'href' => 'javascript:BX.Helper.show(\'redirect=detail&code=18783714\')',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => 'See more',
							],
						2 =>
							[
								'href' => 'javascript:BX.Helper.show(\'redirect=detail&code=18783714\')',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => 'See more',
							],
						3 =>
							[
								'href' => 'javascript:BX.Helper.show(\'redirect=detail&code=18783714\')',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => 'See more',
							],
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-px-30 g-py-30 g-bg-image widget-type-rounded',
					],
			],
		],
		'#block3232' => [
			'old_id' => 3232,
			'code' => '70.2.oval_cards_with_icon_text',
			'access' => 'X',
			'anchor' => 'b7745',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'HR: Company',
					],
				'.landing-block-node-text' =>
					[
						0 => 'Organize internal hierarchy and corporate knowledge',
					],
				'.landing-block-node-card-link' =>
					[
						0 =>
							[
								'href' => '/company/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
						1 =>
							[
								'href' => '/hr/config/permission/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
						2 =>
							[
								'href' => '/hr/structure/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
						3 =>
							[
								'href' => '/kb/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
					],
				'.landing-block-node-card-title' =>
					[
						0 => 'Employee directory',
						1 => 'Roles',
						2 => 'Company structure',
						3 => 'Knowledge base',
					],
				'.landing-block-node-card-icon' =>
					[
						0 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-grid-2 g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
						1 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-briefcase g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
						2 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-diagram-project g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
						3 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-book-blank g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg g-px-30 g-py-30 widget-type-rounded',
					],
			],
		],
		'#block3233' => [
			'old_id' => 3233,
			'code' => '70.3.cards_with_top_title_img',
			'access' => 'X',
			'anchor' => 'b7746',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'HR: Employees',
					],
				'.landing-block-node-text' =>
					[
						0 => 'Manage employee hours, schedule, and workflows',
					],
				'.landing-block-node-card-title' =>
					[
						0 => 'Employee worktime',
						1 => 'Work reports',
						2 => 'Employee self-service',
						3 => 'E-signature for HR',
					],
				'.landing-block-node-card-link' =>
					[
						0 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						1 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						2 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						3 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
					],
				'.landing-block-node-card-img' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
						1 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
						2 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
						3 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg g-px-30 g-py-30 widget-type-rounded',
					],
			],
		],
		'#block3234' => [
			'old_id' => 3234,
			'code' => '70.4.oval_cards_with_title_text_left_color_line',
			'access' => 'X',
			'anchor' => 'b7747',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'Tasks &amp; projects',
					],
				'.landing-block-node-text' =>
					[
						0 => 'Manage tasks and track employee workload',
					],
				'.landing-block-node-card-title' =>
					[
						0 => 'Ongoing',
						1 => 'Set by me',
						2 => 'Assisting',
						3 => 'Following',
					],
				'.landing-block-node-card-link' =>
					[
						0 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						1 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						2 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						3 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
					],
				'.landing-block-node-card-text' =>
					[
						0 => '1000 tasks',
						1 => '123 tasks',
						2 => '111 tasks',
						3 => '234 tasks',
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg g-px-30 g-py-30 widget-type-rounded',
					],
			],
		],
		'#block3235' => [
			'old_id' => 3235,
			'code' => '70.5.three_buttons_title_text_img',
			'access' => 'X',
			'anchor' => 'b7748',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'Internal communication',
					],
				'.landing-block-node-text' =>
					[
						0 => 'Stay in touch with your team',
					],
				'.landing-block-node-card-title' =>
					[
						0 => 'Secure messenger and online meetings',
						1 => 'Feed: post announcements, polls',
						2 => 'Channels',
					],
				'.landing-block-node-card-link' =>
					[
						0 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						1 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						2 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
					],
				'.landing-block-node-img' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg g-px-30 g-py-30 widget-type-rounded',
					],
			],
		],
		'#block3236' => [
			'old_id' => 3236,
			'code' => '70.3.cards_with_top_title_img',
			'access' => 'X',
			'anchor' => 'b7749',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'Automation',
					],
				'.landing-block-node-text' =>
					[
						0 => 'Streamline and automate your workflows across multiple departments',
					],
				'.landing-block-node-card-title' =>
					[
						0 => 'Sales automation',
						1 => 'Workflows',
						2 => 'Smart processes',
						3 => '.',
					],
				'.landing-block-node-card-link' =>
					[
						0 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						1 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						2 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
						3 =>
							[
								'href' => '#',
								'target' => '_self',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
								'text' => '',
							],
					],
				'.landing-block-node-card-img' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
						1 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
						2 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
						3 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg g-px-30 g-py-30 widget-type-rounded',
					],
			],
		],
		'#block3237' => [
			'old_id' => 3237,
			'code' => '70.2.oval_cards_with_icon_text',
			'access' => 'X',
			'anchor' => 'b7750',
			'nodes' => [
				'.landing-block-node-title' =>
					[
						0 => 'CRM for sales and marketing',
					],
				'.landing-block-node-text' =>
					[
						0 => 'Manage your sales and clients',
					],
				'.landing-block-node-card-link' =>
					[
						0 =>
							[
								'href' => '/company/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
						1 =>
							[
								'href' => '/hr/config/permission/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
						2 =>
							[
								'href' => '/hr/structure/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
						3 =>
							[
								'href' => '/kb/',
								'target' => '_blank',
								'attrs' =>
									[
										'data-embed' => null,
										'data-url' => null,
									],
							],
					],
				'.landing-block-node-card-title' =>
					[
						0 => 'Sales &amp; marketing automation',
						1 => 'Estimates &amp; invoices',
						2 => 'AI-assisted sales',
						3 => 'Contact Center',
					],
				'.landing-block-node-card-icon' =>
					[
						0 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-grid-2 g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
						1 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-briefcase g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
						2 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-diagram-project g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
						3 =>
							[
								'classList' =>
									[
										0 => 'landing-block-node-card-icon fa far fa-book-blank g-font-size-25 g-mr-20 g-rounded-50x g-bg text-center',
									],
							],
					],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg g-px-30 g-py-30 widget-type-rounded',
					],
			],
		],
		// '#block3238' => [
		// 	'old_id' => 3238,
		// 	'code' => '70.6.cards_with_img_bottom_title',
		// 	'access' => 'X',
		// 	'anchor' => 'b7751',
		// 	'nodes' => [
		// 		'.landing-block-node-title' =>
		// 			[
		// 				0 => 'Team collaboration',
		// 			],
		// 		'.landing-block-node-text' =>
		// 			[
		// 				0 => 'Text',
		// 			],
		// 		'.landing-block-node-card-title' =>
		// 			[
		// 				0 => 'Calendar',
		// 				1 => 'Online documents',
		// 				2 => 'Bitrix24 Collab',
		// 				3 => 'Card 4',
		// 			],
		// 		'.landing-block-node-card-link' =>
		// 			[
		// 				0 =>
		// 					[
		// 						'href' => '#',
		// 						'target' => '_blank',
		// 						'attrs' =>
		// 							[
		// 								'data-embed' => null,
		// 								'data-url' => null,
		// 							],
		// 						'text' => '',
		// 					],
		// 				1 =>
		// 					[
		// 						'href' => '#',
		// 						'target' => '_blank',
		// 						'attrs' =>
		// 							[
		// 								'data-embed' => null,
		// 								'data-url' => null,
		// 							],
		// 						'text' => '',
		// 					],
		// 				2 =>
		// 					[
		// 						'href' => '#',
		// 						'target' => '_blank',
		// 						'attrs' =>
		// 							[
		// 								'data-embed' => null,
		// 								'data-url' => null,
		// 							],
		// 						'text' => '',
		// 					],
		// 				3 =>
		// 					[
		// 						'href' => '#',
		// 						'target' => '_blank',
		// 						'attrs' =>
		// 							[
		// 								'data-embed' => null,
		// 								'data-url' => null,
		// 							],
		// 						'text' => '',
		// 					],
		// 			],
		// 		'.landing-block-node-card-img"' =>
		// 			[],
		// 		'#wrapper' =>
		// 			[
		// 				0 =>
		// 					[
		// 						'src' => '',
		// 						'src2x' => '',
		// 						'id' => null,
		// 						'id2x' => null,
		// 						'alt' => '',
		// 						'isLazy' => 'N',
		// 					],
		// 			],
		// 	],
		// 	'style' => [
		// 		'#wrapper' =>
		// 			[
		// 				0 => 'landing-block g-bg widget-type-rounded',
		// 			],
		// 	],
		// ],
		'#block3239' => [
			'old_id' => 3239,
			'code' => '70.7.two_content_part',
			'access' => 'X',
			'anchor' => 'b7752',
			'nodes' => [
				'.landing-block-node-title-left' =>
					[],
				'.landing-block-node-text-left' =>
					[],
				'.landing-block-node-list-item-text' =>
					[],
				'.landing-block-node-button-left-text' =>
					[],
				'.landing-block-node-button-left' =>
					[],
				'.landing-block-node-title-right' =>
					[],
				'.landing-block-node-text-right' =>
					[],
				'.landing-block-node-button-text' =>
					[],
				'.landing-block-node-button-link' =>
					[],
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
		],
		// '#block3240' => [
		// 	'old_id' => 3240,
		// 	'code' => '70.8.text_left_button_right_img',
		// 	'access' => 'X',
		// 	'anchor' => 'b7753',
		// 	'nodes' => [
		// 		'#wrapper' =>
		// 			[
		// 				0 =>
		// 					[
		// 						'src' => '',
		// 						'src2x' => '',
		// 						'id' => null,
		// 						'id2x' => null,
		// 						'alt' => '',
		// 						'isLazy' => 'N',
		// 					],
		// 			],
		// 	],
		// 	'style' => [
		// 		'#wrapper' =>
		// 			[
		// 				0 => 'landing-block g-bg widget-type-rounded',
		// 			],
		// 	],
		// ],
		// '#block3241' => [
		// 	'old_id' => 3241,
		// 	'code' => '70.8.text_left_button_right_img',
		// 	'access' => 'X',
		// 	'anchor' => 'b7754',
		// 	'nodes' => [
		// 		'#wrapper' =>
		// 			[
		// 				0 =>
		// 					[
		// 						'src' => '',
		// 						'src2x' => '',
		// 						'id' => null,
		// 						'id2x' => null,
		// 						'alt' => '',
		// 						'isLazy' => 'N',
		// 					],
		// 			],
		// 	],
		// 	'style' => [
		// 		'#wrapper' =>
		// 			[
		// 				0 => 'landing-block g-bg widget-type-rounded',
		// 			],
		// 	],
		// ],
		'#block3242' => [
			'old_id' => 3242,
			'code' => 'mp_widget.apps',
			'access' => 'X',
			'anchor' => 'b7755',
			'nodes' => [
				'bitrix:landing.blocks.mp_widget.apps' =>
					[
						'TITLE_MOBILE' => null,
						'TITLE_DESKTOP' => null,
						'COLOR_TITLE_MOBILE' => null,
						'COLOR_TITLE_DESKTOP' => null,
						'COLOR_TEXT_MOBILE' => null,
						'COLOR_TEXT_DESKTOP' => null,
						'COLOR_BUTTON_MOBILE' => null,
						'COLOR_BUTTON_TEXT_MOBILE' => null,
						'COLOR_BUTTON_DESKTOP' => null,
						'COLOR_BUTTON_TEXT_DESKTOP' => null,
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg widget-type-rounded',
					],
			],
		],
		// '#block3243' => [
		// 	'old_id' => 3243,
		// 	'code' => '70.9.title_text_button_on_bgimg',
		// 	'access' => 'X',
		// 	'anchor' => 'b7756',
		// 	'nodes' => [
		// 		'.landing-block-node-title' =>
		// 			[
		// 				0 => 'Rest API',
		// 			],
		// 		'.landing-block-node-text' =>
		// 			[
		// 				0 => 'Connect external apps and services to enhance your Bitrix24',
		// 			],
		// 		'.landing-block-node-link' =>
		// 			[
		// 				0 =>
		// 					[
		// 						'href' => 'https://apidocs.bitrix24.com/api-reference/index.html',
		// 						'target' => '_blank',
		// 						'attrs' =>
		// 							[
		// 								'data-embed' => null,
		// 								'data-url' => null,
		// 							],
		// 						'text' => 'Connect',
		// 					],
		// 			],
		// 		'#wrapper' =>
		// 			[
		// 				0 =>
		// 					[
		// 						'src' => '',
		// 						'src2x' => '',
		// 						'id' => null,
		// 						'id2x' => null,
		// 						'alt' => '',
		// 						'isLazy' => 'N',
		// 					],
		// 			],
		// 	],
		// 	'style' => [
		// 		'#wrapper' =>
		// 			[
		// 				0 => 'landing-block g-bg-image widget-type-rounded',
		// 			],
		// 	],
		// ],
		'#block3244' => [
			'old_id' => 3244,
			'code' => 'mp_widget.ent_demo_product',
			'access' => 'X',
			'anchor' => 'b7757',
			'nodes' => [
				'#wrapper' =>
					[
						0 =>
							[
								'src' => '',
								'src2x' => '',
								'id' => null,
								'id2x' => null,
								'alt' => '',
								'isLazy' => 'N',
							],
					],
			],
			'style' => [
				'#wrapper' =>
					[
						0 => 'landing-block g-bg widget-type-rounded',
					],
			],
		],

	],
];