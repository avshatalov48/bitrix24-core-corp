<?php
namespace Bitrix\Intranet\Tests;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main;
use Bitrix\Intranet\LeftMenu;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ManagerTest
 * @covers \Bitrix\Intranet\CustomSection\Manager
 * @phpUnit 8.5.8
 */
class MenuTest extends TestCase
{
	/** @var LeftMenu\Menu |MockObject */
	protected $menu;
	/** @var LeftMenu\User |MockObject */
	protected $user;
	/** @var LeftMenu\Preset\Manager |MockObject */
	protected $presetManager;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		Main\Loader::registerNamespace("Bitrix\\Intranet\\LeftMenu\\", __DIR__."/../lib");
	}

	protected function setUp(): void
	{
		parent::setUp();
		$this->user = $this->getMockBuilder(LeftMenu\User::class)
			->disableOriginalConstructor()
			->setMethodsExcept(['isAdmin'])
			->getMock()
		;
		$this->user
			->method('isAdmin')
			->will($this->returnValue(false))
		;

		$this->menu = $this->getMockBuilder(LeftMenu\Menu::class)
			->setMethods(['getSavedUserMenuItems'])
			->setConstructorArgs([$this->getDefaultMenuItems(), $this->user])
			->getMock()
		;
		$this->menu
			->method('getSavedUserMenuItems')
			->will($this->returnCallback([$this, 'getSavedUserMenuItems']))
		;
	}

//region Tests
	public function testDefaultPresetShouldBeSocial()
	{
		$manager = $this->getMockBuilder(LeftMenu\Preset\Manager::class)
			->disableOriginalConstructor()
			->setMethods(['getCurrentPresetId'])
			->getMock()
		;
		$manager
			->method('getCurrentPresetId')
			->will($this->returnValue('Unknown'))
		;
		$defaultPreset = LeftMenu\Preset\Social::CODE;
		if (!empty(\CUserOptions::GetOption('intranet', 'left_menu_preset_'.SITE_ID)))
		{
			$defaultPreset = \CUserOptions::GetOption('intranet', 'left_menu_preset_'.SITE_ID);
		}
		else if (!empty(\COption::GetOptionString('intranet', 'left_menu_preset', '')))
		{
			$defaultPreset = \COption::GetOptionString('intranet', 'left_menu_preset', '');
		}
		$this->assertEquals(
			$defaultPreset,
			$manager::getPreset()->getCode(),
			'Default preset is the Social or saved in options'
		);
	}

	public function testCollectMenu()
	{
		$preset = LeftMenu\Preset\Manager::getPreset(LeftMenu\Preset\Crm::CODE);
		$this->assertEquals(LeftMenu\Preset\Crm::CODE, $preset->getCode());
		$this->menu->applyPreset($preset);
		$this->assertEmpty($this->menu->getHiddenItems());
	}

	/**
	 * @dataProvider presets
	 */
	public function testConvertingOldToNewPreset($old, $new, $expected)
	{
		$res = array_diff_assoc(
			LeftMenu\Preset\Crm::oldToNewStructure($old, $new),
			$expected,
		);
		$this->assertCount(0, $res,
			'Presets should be the same'
		);
	}

	public function presets()
	{
		$new = [
			'show' => [
				'menu_teamwork' => [
					'menu_live_feed',
					'menu_im_messenger',
					'menu_calendar',
					'menu_conference'
				],
				'menu_tasks',
				'menu_marketplace_group' => [
					'menu_marketplace_sect',
					'12345',
					'23456',
					'34567'
				],
			],
			'hide' => [
				"menu_analytics",
				"menu_marketing",
			]
		];
		return [
			[
				[
					'show' => [
						'menu_tasks',
						'menu_conference',
						'own12',
						'menu_live_feed',
						'own34',
						'menu_marketplace_sect',
						'menu_marketing',
						'12345',
						'23456',
						'34567'
					],
					'hide' => [
						'menu_calendar',
						'menu_analytics',
					]
				],
				$new,
				[
					'show' => [
						'menu_tasks',
						'own12',
						'menu_teamwork' => [
							'menu_live_feed',
							'menu_conference',
						],
						'own34',
						'menu_marketplace_group' => [
							'menu_marketplace_sect',
							'12345',
							'23456',
							'34567'
						],
						'menu_marketing'
					],
					'hide' => [
						'menu_calendar',
						'menu_analytics',
					]
				],
			],
			[
				[
					'show' => [
						'menu_tasks',
						'menu_conference',
						'own12',
						'own34',
						'menu_marketplace_sect',
						'menu_marketing',
						'12345',
						'23456',
						'34567'
					],
					'hide' => [
						'menu_live_feed',
						'menu_calendar',
						'menu_analytics',
					]
				],
				$new,
				[
					'show' => [
						'menu_tasks',
						'menu_conference',
						'own12',
						'own34',
						'menu_marketplace_group' => [
							'menu_marketplace_sect',
							'12345',
							'23456',
							'34567'
						],
						'menu_marketing'
					],
					'hide' => [
						'menu_teamwork' => [
							'menu_live_feed',
							'menu_calendar'
						],
						'menu_analytics',
					]
				],

			],
			[
				[
					'show' => [
						'menu_tasks',
						'menu_conference',
						'own12',
						'own34',
						'menu_live_feed',
						'menu_marketing',
						'34567'
					],
					'hide' => [
						'menu_marketplace_sect',
						'12345',
						'23456',
						'menu_calendar',
						'menu_analytics',
					]
				],
				$new,
				[
					'show' => [
						'menu_tasks',
						'own12',
						'own34',
						'menu_teamwork' => [
							'menu_live_feed',
							'menu_conference',
						],
						'menu_marketing',
						'34567'
					],
					'hide' => [
						'menu_marketplace_group' => [
							'menu_marketplace_sect',
							'12345',
							'23456',
						],
						'menu_calendar',
						'menu_analytics',
					]
				]
			],
		];
	}

	public function getDefaultMenuItems(): array
	{
		return include __DIR__."/stubData/defaultMenu.php";
	}

	public function getSavedUserMenuItems(): array
	{
		return [
			LeftMenu\MenuItem\ItemUserFavorites::class =>
				include __DIR__."/stubData/favoritesMenu.php",
			LeftMenu\MenuItem\ItemUserSelf::class          =>
				include __DIR__."/stubData/manuallyAddedMenu.php",
			LeftMenu\MenuItem\ItemRestApplication::class      =>
				include __DIR__."/stubData/restMenu.php",
			LeftMenu\MenuItem\ItemAdminShared::class =>
				include __DIR__."/stubData/fromAdminToAllMenu.php"
		];
	}
//endregion
}
