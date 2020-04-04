<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
$result = [];
$result["fields"] = [
	'PERSONAL_PHOTO' =>
		[
			'sectionCode' => 'userpic',
			'id' => 'PERSONAL_PHOTO',
			'type' => 'userpic',
			'title' => Loc::getMessage('PERSONAL_PHOTO')
		],
	'NAME' =>
		[
			'sectionCode' => 'main',
			'id' => 'NAME',
			'type' => 'input',
			'title' => Loc::getMessage('NAME'),
			'styles' =>
				[
					'title' =>
						[
							'font' =>
								[
									'color' => '#777777',
									'fontStyle' => 'semibold',
								],
						],
				],
		],
	'LAST_NAME' =>
		[
			'sectionCode' => 'main',
			'id' => 'LAST_NAME',
			'type' => 'input',
			'title' => Loc::getMessage('LAST_NAME')
		],
	'SECOND_NAME' =>
		[
			'sectionCode' => 'main',
			'id' => 'SECOND_NAME',
			'type' => 'input',
			'title' => Loc::getMessage('SECOND_NAME')
		],
	'EMAIL' =>
		[
			'sectionCode' => 'main',
			'id' => 'EMAIL',
			'type' => 'input',
			'title' => Loc::getMessage('EMAIL')
		],
	'PERSONAL_MOBILE' =>
		[
			'sectionCode' => 'main',
			'id' => 'PERSONAL_MOBILE',
			'type' => 'input',
			'title' => Loc::getMessage('PERSONAL_MOBILE')
		],
	'PERSONAL_BIRTHDAY' =>
		[
			'sectionCode' => 'extra',
			'id' => 'PERSONAL_BIRTHDAY',
			'type' => 'date',
			'params'=>['type'=>"date", 'format'=>"dd.MM.yyyy"],
			'title' => Loc::getMessage('PERSONAL_BIRTHDAY')
		],
	'WORK_POSITION' =>
		[
			'sectionCode' => 'extra',
			'id' => 'WORK_POSITION',
			'type' => 'input',
			'title' => Loc::getMessage('WORK_POSITION')
		],
	'PERSONAL_GENDER' =>
		[
			'sectionCode' => 'extra',
			'id' => 'PERSONAL_GENDER',
			'type' => 'selector',
			'title' => Loc::getMessage('PERSONAL_GENDER'),
			'params' =>
				[
					'items' =>
						[
								[
									'value' => 'M',
									'name' => Loc::getMessage("MALE"),
								],
								[
									'value' => 'F',
									'name' => Loc::getMessage("FEMALE"),
								],
						],
				],
		],
	'PERSONAL_WWW' =>
		[
			'sectionCode' => 'extra',
			'id' => 'PERSONAL_WWW',
			'type' => 'input',
			'title' => Loc::getMessage('PERSONAL_WWW')
		],
	'WORK_PHONE' =>
		[
			'sectionCode' => 'extra',
			'id' => 'WORK_PHONE',
			'type' => 'input',
			'title' => Loc::getMessage('WORK_PHONE')
		],
	'UF_PHONE_INNER' =>
		[
			'sectionCode' => 'extra',
			'id' => 'UF_PHONE_INNER',
			'type' => 'input',
			'title' => Loc::getMessage('UF_PHONE_INNER')
		],
	'UF_SKYPE' =>
		[
			'sectionCode' => 'extra',
			'id' => 'UF_SKYPE',
			'type' => 'input',
			'title' => Loc::getMessage('UF_SKYPE')
		],
	'UF_TWITTER' =>
		[
			'sectionCode' => 'extra',
			'id' => 'UF_TWITTER',
			'type' => 'input',
			'title' => Loc::getMessage('UF_TWITTER')
		],
	'UF_FACEBOOK' =>
		[
			'sectionCode' => 'extra',
			'id' => 'UF_FACEBOOK',
			'type' => 'input',
			'title' => Loc::getMessage('UF_FACEBOOK')
		],
	'UF_LINKEDIN' =>
		[
			'sectionCode' => 'extra',
			'id' => 'UF_LINKEDIN',
			'type' => 'input',
			'title' => Loc::getMessage('UF_LINKEDIN')
		],
	'UF_XING' =>
		[
			'sectionCode' => 'extra',
			'id' => 'UF_XING',
			'type' => 'input',
			'title' => Loc::getMessage('UF_XING')
		],
	'PASSWORD' =>
		[
			'sectionCode' => 'account',
			'id' => 'PASSWORD',
			'type' => 'input',
			'title' => Loc::getMessage('PASSWORD')
		],
];

$result["sections"] = [
	[
		'id' => 'userpic',
		'backgroundColor' => '#f0f0f0',
	],
	[
		'id' => 'main',
		'title' => Loc::getMessage("MAIN"),
		'styles' =>
			[
				'title' =>
					[
						'font' =>
							[
								'color' => '#777777',
								'fontStyle' => 'semibold',
							],
					],
			],
	],
	[
		'id' => 'extra',
		'title' => Loc::getMessage("EXTRA"),
		'styles' =>
			[
				'title' =>
					[
						'font' =>
							[
								'color' => '#777777',
								'fontStyle' => 'semibold',
							],
					],
			],
	],
	[
		'id' => 'account',
		'title' => Loc::getMessage("ACCOUNT"),
		'footer' => '',
		'styles' =>
			[
				'title' =>
					[
						'font' =>
							[
								'color' => '#777777',
								'fontStyle' => 'semibold',
							],
					],
			],
	],
];

return $result;