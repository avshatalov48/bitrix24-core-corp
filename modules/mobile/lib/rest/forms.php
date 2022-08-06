<?php

namespace Bitrix\Mobile\Rest;

use Bitrix\Main\Localization\Loc;

class Forms extends \IRestService
{
	public static function getMethods()
	{
		return [
			'mobile.form.profile' => ['callback' => [__CLASS__, 'userProfile'], 'options' => ['private' => false]],
		];
	}

	public static function userProfile($params, $n, \CRestServer $server)
	{
		global $USER_FIELD_MANAGER;
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		$result = [];
		$result["fields"] = [
			'PERSONAL_PHOTO' =>
				[
					'sectionCode' => 'top',
					'id' => 'PERSONAL_PHOTO',
					'type' => 'userpic',
					'title' => Loc::getMessage('PERSONAL_PHOTO')
				],
			'NAME' =>
				[
					'sectionCode' => 'main',
					'id' => 'NAME',
					'type' => 'input',
					'title' => Loc::getMessage('FIRST_NAME'),
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
					'title' => Loc::getMessage('EMAIL'),
					'params'=>["openScheme"=>"mailto"]
				],
			'PERSONAL_MOBILE' =>
				[
					'sectionCode' => 'main',
					'id' => 'PERSONAL_MOBILE',
					'type' => 'input',
					'title' => Loc::getMessage('PERSONAL_MOBILE'),
					'params'=>["openScheme"=>"tel"]
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
					'title' => Loc::getMessage('WORK_PHONE'),
					'params'=>["openScheme"=>"tel"]
				],
			'UF_PHONE_INNER' =>
				[
					'sectionCode' => 'extra',
					'id' => 'UF_PHONE_INNER',
					'type' => 'input',
					'title' => Loc::getMessage('UF_PHONE_INNER'),
					'params'=>["openScheme"=>"tel-inner"]
				],
			'UF_SKYPE' =>
				[
					'sectionCode' => 'extra',
					'id' => 'UF_SKYPE',
					'type' => 'input',
					'title' => Loc::getMessage('UF_SKYPE'),
					'params'=>["openScheme"=>"skype"]
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
					'title' => Loc::getMessage('UF_FACEBOOK'),
					'asterix' => ($region === "ru" || $region === null) ? Loc::getMessage('META_RESTRICTED_ORGANIZATION_MESSAGE'): ""
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
				]
		];

		$userFields = [
			"UF_PHONE_INNER",
			"UF_SKYPE",
			"UF_TWITTER",
			"UF_FACEBOOK",
			"UF_LINKEDIN",
			"UF_XING",
			"UF_SKILLS",
			"UF_INTERESTS",
			"UF_WEB_SITES",
			"UF_DEPARTMENT"
		];

		$userFieldList= $USER_FIELD_MANAGER->GetUserFields("USER");

		foreach ($userFields as $fieldId)
		{
			if(!array_key_exists($fieldId, $userFieldList))
			{
				unset($result["fields"][$fieldId]);
			}
		}

		$result["sections"] = [
			[
				'id' => 'top',//avatar
			],
			[
				'id' => 'actions'
			],
			[
				'id' => 'main',
				'title' => Loc::getMessage("MAIN"),
			],
			[
				'id' => 'extra',
				'title' => Loc::getMessage("EXTRA"),
			]
		];

		return $result;
	}
}