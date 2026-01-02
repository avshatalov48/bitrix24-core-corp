<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePickerVideo;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$new = time() < strtotime('21 October 2025');
$videoDomain = (new ThemePickerVideo())->getDomain();

return [
	"baseThemes" => [
		"light" => [
			"css" => ["base.css"],
		],

		"dark" => [
			"css" => ["base.css"],
		]
	],

	"subThemes" => [
		"light:lightness" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LIGHTNESS"),
			"previewImage" => "lightness-preview.jpg",
			"prefetchImages" => ["lightness.jpg", "lightness-blurred.webp"],
			"resizable" => true,
			"width" => 1920,
			"height" => 1080,
			"new" => $new,
		],

		"light:gravity" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GRAVITY"),
			"previewImage" => "gravity-preview.jpg",
			"prefetchImages" => ["gravity.jpg", "gravity-blurred.webp"],
			"resizable" => true,
			"width" => 1920,
			"height" => 1080,
		],

		"light:video-orion" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_ORION"),
			"previewImage" => "orion-preview.jpg",
			"prefetchImages" => ["orion-poster.jpg"],
			"video" => [
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-orion/orion.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-orion/orion.mp4"
				]
			],
			"resizable" => true,
		],

		"light:orion" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ORION"),
			"previewImage" => "orion-preview.jpg",
			"prefetchImages" => ["orion.jpg", "orion-blurred.webp"],
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:video-shining-intelligence" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_SHINING_INTELLIGENCE"),
			"previewImage" => "shining-intelligence-preview.jpg",
			"prefetchImages" => ["shining-intelligence-poster.jpg"],
			"video" => [
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-shining-intelligence/shining-intelligence.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-shining-intelligence/shining-intelligence.mp4"
				]
			],
			"resizable" => true,
		],

		"light:shining-intelligence" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SHINING_INTELLIGENCE"),
			"prefetchImages" => ["shining-intelligence.jpg", "shining-intelligence-blurred.webp"],
			"previewImage" => "shining-intelligence-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:contrast-horizon" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CONTRAST_HORIZON"),
			"prefetchImages" => ["contrast-horizon.jpg", "contrast-horizon-blurred.webp"],
			"previewImage" => "contrast-horizon-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:amethyst-inspiration" => [
			"title" => Loc::getMessage("BITRIX24_THEME_AMETHYST_INSPIRATION"),
			"prefetchImages" => ["amethyst-inspiration.jpg", "amethyst-inspiration-blurred.webp"],
			"previewImage" => "amethyst-inspiration-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:astronomical-watercolor" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ASTRONOMICAL_WATERCOLOR"),
			"prefetchImages" => ["astronomical-watercolor.jpg", "astronomical-watercolor-blurred.webp"],
			"previewImage" => "astronomical-watercolor-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:galactic-dream" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GALACTIC_DREAM"),
			"prefetchImages" => ["galactic-dream.jpg", "galactic-dream-blurred.webp"],
			"previewImage" => "galactic-dream-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:cosmic-dreams" => [
			"title" => Loc::getMessage("BITRIX24_THEME_COSMIC_DREAMS"),
			"prefetchImages" => ["cosmic-dreams.jpg", "cosmic-dreams-blurred.webp"],
			"previewImage" => "cosmic-dreams-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:sunset-magic" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SUNSET_MAGIC"),
			"prefetchImages" => ["sunset-magic.jpg", "sunset-magic-blurred.webp"],
			"previewImage" => "sunset-magic-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:dawn-harmony" => [
			"title" => Loc::getMessage("BITRIX24_THEME_DAWN_HARMONY"),
			"prefetchImages" => ["dawn-harmony.jpg", "dawn-harmony-blurred.webp"],
			"previewImage" => "dawn-harmony-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:northern-lights" => [
			"title" => Loc::getMessage("BITRIX24_THEME_NORTHERN_LIGHTS"),
			"prefetchImages" => ["northern-lights.jpg", "northern-lights-blurred.webp"],
			"previewImage" => "northern-lights-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:jupiter" => [
			"title" => Loc::getMessage("BITRIX24_THEME_JUPITER"),
			"prefetchImages" => ["jupiter.jpg", "jupiter-blurred.webp"],
			"previewImage" => "jupiter-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:pancakes-cat" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PANCAKES_CAT"),
			"prefetchImages" => ["pancakes-cat.jpg", "pancakes-cat-blurred.webp"],
			"previewImage" => "pancakes-cat-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"zones" => ["ru", "by"],
		],

		"light:pancakes" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PANCAKES"),
			"prefetchImages" => ["pancakes.jpg", "pancakes-blurred.webp"],
			"previewImage" => "pancakes-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"zones" => ["ru", "by"],
		],

		"light:video-jupiter" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_JUPITER"),
			"previewImage" => "jupiter-preview.jpg",
			"prefetchImages" => ["jupiter-poster.jpg"],
			"video" => [
				// "poster" => "jupiter-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-jupiter/jupiter.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-jupiter/jupiter.mp4"
				]
			],
			"resizable" => true,
		],

		"light:orbital-symphony" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ORBITAL_SYMPHONY"),
			"prefetchImages" => ["orbital-symphony.jpg", "orbital-symphony-blurred.webp"],
			"previewImage" => "orbital-symphony-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:flickering-way" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FLICKERING_WAY"),
			"prefetchImages" => ["flickering-way.jpg", "flickering-way-blurred.webp"],
			"previewImage" => "flickering-way-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:mysterious-vega" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MYSTERIOUS_VEGA"),
			"prefetchImages" => ["mysterious-vega.jpg", "mysterious-vega-blurred.webp"],
			"previewImage" => "mysterious-vega-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:saturn" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SATURN"),
			"prefetchImages" => ["saturn.jpg", "saturn-blurred.webp"],
			"previewImage" => "saturn-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:video-saturn" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_SATURN"),
			"previewImage" => 'saturn-preview.jpg',
			"prefetchImages" => ["saturn-poster.jpg"],
			"video" => [
				// "poster" => "saturn-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-saturn/saturn.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-saturn/saturn.mp4"
				]
			],
			"resizable" => true,
		],

		"light:sapphire-whirlwind" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SAPPHIRE_WHIRLWIND"),
			"prefetchImages" => ["sapphire-whirlwind.jpg", "sapphire-whirlwind-blurred.webp"],
			"previewImage" => "sapphire-whirlwind-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:orion-nebula" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ORION_NEBULA"),
			"prefetchImages" => ["orion-nebula.jpg", "orion-nebula-blurred.webp"],
			"previewImage" => "orion-nebula-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:cosmic-string" => [
			"title" => Loc::getMessage("BITRIX24_THEME_COSMIC_STRING"),
			"prefetchImages" => ["cosmic-string.jpg", "cosmic-string-blurred.webp"],
			"previewImage" => "cosmic-string-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],
		"light:neptune" => [
			"title" => Loc::getMessage("BITRIX24_THEME_NEPTUNE"),
			"prefetchImages" => ["neptune.jpg", "neptune-blurred.webp"],
			"previewImage" => "neptune-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:video-neptune" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_NEPTUNE"),
			"previewImage" => "neptune-preview.jpg",
			"prefetchImages" => ["neptune-poster.jpg"],
			"video" => [
				// "poster" => "neptune-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-neptune/neptune.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-neptune/neptune.mp4"
				]
			],
			"resizable" => true,
		],

		"light:pluto" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PLUTO"),
			"prefetchImages" => ["pluto.jpg", "pluto-blurred.webp"],
			"previewImage" => "pluto-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:andromeda-galaxy" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ANDROMEDA_GALAXY"),
			"prefetchImages" => ["andromeda-galaxy.jpg", "andromeda-galaxy-blurred.webp"],
			"previewImage" => "andromeda-galaxy-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:galactic-harmony" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GALACTIC_HARMONY"),
			"prefetchImages" => ["galactic-harmony.jpg", "galactic-harmony-blurred.webp"],
			"previewImage" => "galactic-harmony-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:foggy-horizon" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FOGGY_HORIZON"),
			"prefetchImages" => ["foggy-horizon.jpg", "foggy-horizon-blurred.webp"],
			"previewImage" => "foggy-horizon-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:milky-way" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MILKY_WAY"),
			"prefetchImages" => ["milky-way.jpg", "milky-way-blurred.webp"],
			"previewImage" => "milky-way-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:magic-spheres" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MAGIC_SPHERES"),
			"prefetchImages" => ["magic-spheres.jpg", "magic-spheres-blurred.webp"],
			"previewImage" => "magic-spheres-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:raspberry-daiquiri" => [
			"title" => Loc::getMessage("BITRIX24_THEME_RASPBERRY_DAIQUIRI"),
			"prefetchImages" => ["raspberry-daiquiri.jpg", "raspberry-daiquiri-blurred.webp"],
			"previewImage" => "raspberry-daiquiri-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:tropical-sunset" => [
			"title" => Loc::getMessage("BITRIX24_THEME_TROPICAL_SUNSET"),
			"prefetchImages" => ["tropical-sunset.jpg", "tropical-sunset-blurred.webp"],
			"previewImage" => "tropical-sunset-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:delicate-silk" => [
			"title" => Loc::getMessage("BITRIX24_THEME_DELICATE_SILK"),
			"prefetchImages" => ["delicate-silk.jpg", "delicate-silk-blurred.webp"],
			"previewImage" => "delicate-silk-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:dark-silk" => [
			"title" => Loc::getMessage("BITRIX24_THEME_DARK_SILK"),
			"prefetchImages" => ["dark-silk.jpg", "dark-silk-blurred.webp"],
			"previewImage" => "dark-silk-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:coastal-dunes" => [
			"title" => Loc::getMessage("BITRIX24_THEME_COASTAL_DUNES"),
			"prefetchImages" => ["coastal-dunes.jpg", "coastal-dunes-blurred.webp"],
			"previewImage" => "coastal-dunes-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:sunset" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SUNSET"),
			"prefetchImages" => ["sunset.jpg", "sunset-blurred.webp"],
			"previewImage" => "sunset-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:greenfield" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GREENFIELD"),
			"prefetchImages" => ["greenfield.jpg", "greenfield-blurred.webp"],
			"previewImage" => "greenfield-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:tulips" => [
			"title" => Loc::getMessage("BITRIX24_THEME_TULIPS"),
			"prefetchImages" => ["tulips.jpg", "tulips-blurred.webp"],
			"previewImage" => "tulips-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:grass" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GRASS"),
			"prefetchImages" => ["grass.jpg", "grass-blurred.webp"],
			"previewImage" => "grass-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:cloud-sea" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CLOUD_SEA"),
			"prefetchImages" => ["cloud-sea.jpg", "cloud-sea-blurred.webp"],
			"previewImage" => "cloud-sea-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:pink-fencer" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PINK_FENCER"),
			"prefetchImages" => ["pink-fencer.jpg", "pink-fencer-blurred.webp"],
			"previewImage" => "pink-fencer-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:grass-ears" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GRASS_EARS"),
			"prefetchImages" => ["grass-ears.jpg", "grass-ears-blurred.webp"],
			"previewImage" => "grass-ears-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:atmosphere" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ATMOSPHERE"),
			"prefetchImages" => ["atmosphere2.jpg", "atmosphere2-blurred.webp"],
			"previewImage" => "atmosphere-preview2.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:paradise" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PARADISE"),
			"prefetchImages" => ["paradise.jpg", "paradise-blurred.webp"],
			"previewImage" => "paradise-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:village" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VILLAGE"),
			"prefetchImages" => ["village.jpg", "village-blurred.webp"],
			"previewImage" => "village-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:mountains" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAINS"),
			"prefetchImages" => ["mountains.jpg", "mountains-blurred.webp"],
			"previewImage" => "mountains-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:beach" => [
			"title" => Loc::getMessage("BITRIX24_THEME_BEACH"),
			"prefetchImages" => ["beach.jpg", "beach-blurred.webp"],
			"previewImage" => "beach-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:sea-sunset" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SEA_SUNSET"),
			"prefetchImages" => ["sea-sunset.jpg", "sea-sunset-blurred.webp"],
			"previewImage" => "sea-sunset-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:snow-village" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SNOW_VILLAGE"),
			"prefetchImages" => ["snow-village.jpg", "snow-village-blurred.webp"],
			"previewImage" => "snow-village-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:meditation" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MEDITATION"),
			"prefetchImages" => ["meditation.jpg", "meditation-blurred.webp"],
			"previewImage" => "meditation-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"dark:starfish" => [
			"title" => Loc::getMessage("BITRIX24_THEME_STARFISH"),
			"prefetchImages" => ["starfish.jpg", "starfish-blurred.webp"],
			"previewImage" => "starfish-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"dark:sea-stones" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SEA_STONES"),
			"prefetchImages" => ["sea-stones.jpg", "sea-stones-blurred.webp"],
			"previewImage" => "sea-stones-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"dark:seashells" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SEASHELLS"),
			"prefetchImages" => ["seashells.jpg", "seashells-blurred.webp"],
			"previewImage" => "seashells-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:architecture" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ARCHITECTURE"),
			"prefetchImages" => ["architecture.jpg", "architecture-blurred.webp"],
			"previewImage" => "architecture-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:skyscraper" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SKYSCRAPER"),
			"prefetchImages" => ["skyscraper.jpg", "skyscraper-blurred.webp"],
			"previewImage" => "skyscraper-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:wall" => [
			"title" => Loc::getMessage("BITRIX24_THEME_WALL"),
			"prefetchImages" => ["wall.jpg", "wall-blurred.webp"],
			"previewImage" => "wall-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:flower" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FLOWER"),
			"prefetchImages" => ["flower.jpg", "flower-blurred.webp"],
			"previewImage" => "flower-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:metro" => [
			"title" => Loc::getMessage("BITRIX24_THEME_METRO"),
			"prefetchImages" => ["metro.jpg", "metro-blurred.webp"],
			"previewImage" => "metro-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:shining" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SHINING"),
			"prefetchImages" => ["shining.jpg", "shining-blurred.webp"],
			"previewImage" => "shining-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:stars" => [
			"title" => Loc::getMessage("BITRIX24_THEME_STARS"),
			"prefetchImages" => ["stars.jpg", "stars-blurred.webp"],
			"previewImage" => "stars-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:clouds" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CLOUDS"),
			"prefetchImages" => ["clouds.jpg", "clouds-blurred.webp"],
			"previewImage" => "clouds-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:canyon" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CANYON"),
			"prefetchImages" => ["canyon.jpg", "canyon-blurred.webp"],
			"previewImage" => "canyon-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:mountains-3" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAINS"),
			"prefetchImages" => ["mountains-3.jpg", "mountains-3-blurred.webp"],
			"previewImage" => "mountains-3-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:valley" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VALLEY"),
			"prefetchImages" => ["valley.jpg", "valley-blurred.webp"],
			"previewImage" => "valley-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:leafs" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LEAFS"),
			"prefetchImages" => ["leafs.jpg", "leafs-blurred.webp"],
			"previewImage" => "leafs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:wind" => [
			"title" => Loc::getMessage("BITRIX24_THEME_WIND"),
			"prefetchImages" => ["wind.jpg", "wind-blurred.webp"],
			"previewImage" => "wind-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:grass-2" => [
			"title" => Loc::getMessage("BITRIX24_THEME_GRASS"),
			"prefetchImages" => ["grass-2.jpg", "grass-2-blurred.webp"],
			"previewImage" => "grass-2-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:tree" => [
			"title" => Loc::getMessage("BITRIX24_THEME_TREE"),
			"prefetchImages" => ["tree.jpg", "tree-blurred.webp"],
			"previewImage" => "tree-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:red-field" => [
			"title" => Loc::getMessage("BITRIX24_THEME_RED_FIELD"),
			"prefetchImages" => ["red-field.jpg", "red-field-blurred.webp"],
			"previewImage" => "red-field-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:trees" => [
			"title" => Loc::getMessage("BITRIX24_THEME_TREES"),
			"prefetchImages" => ["trees.jpg", "trees-blurred.webp"],
			"previewImage" => "trees-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:ice" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ICE"),
			"prefetchImages" => ["ice.jpg", "ice-blurred.webp"],
			"previewImage" => "ice-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:plant" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PLANT"),
			"prefetchImages" => ["plant.jpg", "plant-blurred.webp"],
			"previewImage" => "plant-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:mountains-2" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAINS"),
			"prefetchImages" => ["mountains-2.jpg", "mountains-2-blurred.webp"],
			"previewImage" => "mountains-2-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:countryside" => [
			"title" => Loc::getMessage("BITRIX24_THEME_COUNTRYSIDE"),
			"prefetchImages" => ["countryside.jpg", "countryside-blurred.webp"],
			"previewImage" => "countryside-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:morning" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MORNING"),
			"prefetchImages" => ["morning.jpg", "morning-blurred.webp"],
			"previewImage" => "morning-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:scooter" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SCOOTER"),
			"prefetchImages" => ["scooter.jpg", "scooter-blurred.webp"],
			"previewImage" => "scooter-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		],

		"light:air" => [
			"title" => Loc::getMessage("BITRIX24_THEME_AIR"),
			"prefetchImages" => ["air.jpg", "air-blurred.webp"],
			"previewImage" => "air-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:autumn-forest" => [
			"title" => Loc::getMessage("BITRIX24_THEME_AUTUMN_FOREST"),
			"prefetchImages" => ["autumn-forest.jpg", "autumn-forest-blurred.webp"],
			"previewImage" => "autumn-forest-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:bird" => [
			"title" => Loc::getMessage("BITRIX24_THEME_BIRD"),
			"prefetchImages" => ["bird.jpg", "bird-blurred.webp"],
			"previewImage" => "bird-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:city" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CITY"),
			"prefetchImages" => ["city.jpg", "city-blurred.webp"],
			"previewImage" => "city-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:coloured-feathers" => [
			"title" => Loc::getMessage("BITRIX24_THEME_COLOURED_FEATHERS"),
			"prefetchImages" => ["coloured-feathers.jpg", "coloured-feathers-blurred.webp"],
			"previewImage" => "coloured-feathers-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:desert" => [
			"title" => Loc::getMessage("BITRIX24_THEME_DESERT"),
			"prefetchImages" => ["desert.jpg", "desert-blurred.webp"],
			"previewImage" => "desert-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:feathers" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FEATHERS"),
			"prefetchImages" => ["feathers.jpg", "feathers-blurred.webp"],
			"previewImage" => "feathers-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:flower-and-leafs" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FLOWER_AND_LEAFS"),
			"prefetchImages" => ["flower-and-leafs.jpg", "flower-and-leafs-blurred.webp"],
			"previewImage" => "flower-and-leafs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:high-grass" => [
			"title" => Loc::getMessage("BITRIX24_THEME_HIGH_GRASS"),
			"prefetchImages" => ["high-grass.jpg", "high-grass-blurred.webp"],
			"previewImage" => "high-grass-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:highness" => [
			"title" => Loc::getMessage("BITRIX24_THEME_HIGHNESS"),
			"prefetchImages" => ["highness.jpg", "highness-blurred.webp"],
			"previewImage" => "highness-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:hills" => [
			"title" => Loc::getMessage("BITRIX24_THEME_HILLS"),
			"prefetchImages" => ["hills.jpg", "hills-blurred.webp"],
			"previewImage" => "hills-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:horses" => [
			"title" => Loc::getMessage("BITRIX24_THEME_HORSES"),
			"prefetchImages" => ["horses.jpg", "horses-blurred.webp"],
			"previewImage" => "horses-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:houses" => [
			"title" => Loc::getMessage("BITRIX24_THEME_HOUSES"),
			"prefetchImages" => ["houses.jpg", "houses-blurred.webp"],
			"previewImage" => "houses-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:lake" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LAKE"),
			"prefetchImages" => ["lake.jpg", "lake-blurred.webp"],
			"previewImage" => "lake-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:lava" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LAVA"),
			"prefetchImages" => ["lava.jpg", "lava-blurred.webp"],
			"previewImage" => "lava-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:lion-cubs" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LION_CUBS"),
			"prefetchImages" => ["lion-cubs.jpg", "lion-cubs-blurred.webp"],
			"previewImage" => "lion-cubs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:mountain" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAIN"),
			"prefetchImages" => ["mountain.jpg", "mountain-blurred.webp"],
			"previewImage" => "mountain-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:mountain-air" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAIN_AIR"),
			"prefetchImages" => ["mountain-air.jpg", "mountain-air-blurred.webp"],
			"previewImage" => "mountain-air-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:offices" => [
			"title" => Loc::getMessage("BITRIX24_THEME_OFFICES"),
			"prefetchImages" => ["offices.jpg", "offices-blurred.webp"],
			"previewImage" => "offices-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:perspective" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PERSPECTIVE"),
			"prefetchImages" => ["perspective.jpg", "perspective-blurred.webp"],
			"previewImage" => "perspective-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:plants" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PLANTS"),
			"prefetchImages" => ["plants.jpg", "plants-blurred.webp"],
			"previewImage" => "plants-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:sea" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SEA"),
			"prefetchImages" => ["sea.jpg", "sea-blurred.webp"],
			"previewImage" => "sea-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:slope" => [
			"title" => Loc::getMessage("BITRIX24_THEME_SLOPE"),
			"prefetchImages" => ["slope.jpg", "slope-blurred.webp"],
			"previewImage" => "slope-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:steel-wall" => [
			"title" => Loc::getMessage("BITRIX24_THEME_STEEL_WALL"),
			"prefetchImages" => ["steel-wall.jpg", "steel-wall-blurred.webp"],
			"previewImage" => "steel-wall-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:travel" => [
			"title" => Loc::getMessage("BITRIX24_THEME_TRAVEL"),
			"prefetchImages" => ["travel.jpg", "travel-blurred.webp"],
			"previewImage" => "travel-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:winter-forest" => [
			"title" => Loc::getMessage("BITRIX24_THEME_WINTER_FOREST"),
			"prefetchImages" => ["winter-forest.jpg", "winter-forest-blurred.webp"],
			"previewImage" => "winter-forest-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:winter-night" => [
			"title" => Loc::getMessage("BITRIX24_THEME_WINTER_NIGHT"),
			"prefetchImages" => ["winter-night.jpg", "winter-night-blurred.webp"],
			"previewImage" => "winter-night-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"light:camouflage" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CAMOUFLAGE"),
			"prefetchImages" => ["camouflage.jpg", "camouflage-blurred.webp"],
			"previewImage" => "camouflage-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "23 February 2018"
		],

		"light:jack-o-lantern" => [
			"title" => Loc::getMessage("BITRIX24_THEME_JACK_O_LANTERN"),
			"prefetchImages" => ["jack-o-lantern.jpg", "jack-o-lantern-blurred.webp"],
			"previewImage" => "jack-o-lantern-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "31 October 2018"
		],

		"light:halloween" => [
			"title" => Loc::getMessage("BITRIX24_THEME_HALLOWEEN"),
			"prefetchImages" => ["halloween.jpg", "halloween-blurred.webp"],
			"previewImage" => "halloween-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "31 October 2018"
		],

		"light:christmas-snow" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CHRISTMAS_SNOW"),
			"prefetchImages" => ["christmas-snow.jpg", "christmas-snow-blurred.webp"],
			"previewImage" => "christmas-snow-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		],

		"light:christmas-gift" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CHRISTMAS_GIFT"),
			"prefetchImages" => ["christmas-gift.jpg", "christmas-gift-blurred.webp"],
			"previewImage" => "christmas-gift-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		],

		"light:christmas-ball" => [
			"title" => Loc::getMessage("BITRIX24_THEME_CHRISTMAS_BALL"),
			"prefetchImages" => ["christmas-ball.jpg", "christmas-ball-blurred.webp"],
			"previewImage" => "christmas-ball-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		],

		"light:new-years-room" => [
			"title" => Loc::getMessage("BITRIX24_THEME_NEW_YEARS_ROOM"),
			"prefetchImages" => ["new-years-room.jpg", "new-years-room-blurred.webp"],
			"previewImage" => "new-years-room-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		],

		"light:easter-eggs" => [
			"title" => Loc::getMessage("BITRIX24_THEME_EASTER_EGGS"),
			"prefetchImages" => ["easter-eggs.jpg", "easter-eggs-blurred.webp"],
			"previewImage" => "easter-eggs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "1 April 2018"
		],

		"dark:easter-eggs" => [
			"title" => Loc::getMessage("BITRIX24_THEME_EASTER_EGGS"),
			"prefetchImages" => ["easter-eggs.jpg", "easter-eggs-blurred.webp"],
			"previewImage" => "easter-eggs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "1 April 2018"
		],

		"dark:lotus" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LOTUS"),
			"prefetchImages" => ["lotus.jpg", "lotus-blurred.webp"],
			"previewImage" => "lotus-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "8 March 2018"
		],

		"light:valentines-hearts" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VALENTINES_HEARTS"),
			"prefetchImages" => ["valentines-hearts.jpg", "valentines-hearts-blurred.webp"],
			"previewImage" => "valentines-hearts-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "14 February 2018"
		],

		"dark:coloured-paper" => [
			"title" => Loc::getMessage("BITRIX24_THEME_COLOURED_PAPER"),
			"prefetchImages" => ["coloured-paper.jpg", "coloured-paper-blurred.webp"],
			"previewImage" => "coloured-paper-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:dew" => [
			"title" => Loc::getMessage("BITRIX24_THEME_DEW"),
			"prefetchImages" => ["dew.jpg", "dew-blurred.webp"],
			"previewImage" => "dew-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:fabric" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FABRIC"),
			"prefetchImages" => ["fabric.jpg", "fabric-blurred.webp"],
			"previewImage" => "fabric-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:flamingo" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FLAMINGO"),
			"prefetchImages" => ["flamingo.jpg", "flamingo-blurred.webp"],
			"previewImage" => "flamingo-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:flowers" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FLOWERS"),
			"prefetchImages" => ["flowers.jpg", "flowers-blurred.webp"],
			"previewImage" => "flowers-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:freshness" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FRESHNESS"),
			"prefetchImages" => ["freshness.jpg", "freshness-blurred.webp"],
			"previewImage" => "freshness-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:fur" => [
			"title" => Loc::getMessage("BITRIX24_THEME_FUR"),
			"prefetchImages" => ["fur.jpg", "fur-blurred.webp"],
			"previewImage" => "fur-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:light-fabric" => [
			"title" => Loc::getMessage("BITRIX24_THEME_LIGHT_FABRIC"),
			"prefetchImages" => ["light-fabric.jpg", "light-fabric-blurred.webp"],
			"previewImage" => "light-fabric-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:table" => [
			"title" => Loc::getMessage("BITRIX24_THEME_TABLE"),
			"prefetchImages" => ["table.jpg", "table-blurred.webp"],
			"previewImage" => "table-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:vibration" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIBRATION"),
			"prefetchImages" => ["vibration.jpg", "vibration-blurred.webp"],
			"previewImage" => "vibration-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:window" => [
			"title" => Loc::getMessage("BITRIX24_THEME_WINDOW"),
			"prefetchImages" => ["window.jpg", "window-blurred.webp"],
			"previewImage" => "window-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:wooden-letters" => [
			"title" => Loc::getMessage("BITRIX24_THEME_WOODEN_LETTERS"),
			"prefetchImages" => ["wooden-letters.jpg", "wooden-letters-blurred.webp"],
			"previewImage" => "wooden-letters-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		],

		"dark:pattern-tulips" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_TULIPS"),
			"prefetchImages" => ["pattern-tulips.jpg", "pattern-tulips-blurred.webp"],
			"previewImage" => "pattern-tulips-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "8 March 2018"
		],

		"light:mail" => [
			"title" => Loc::getMessage("BITRIX24_THEME_MAIL"),
			"prefetchImages" => ["mail.png", "mail-blurred.webp"],
			"previewImage" => "mail-preview.jpg",
		],

		"light:robots" => [
			"title" => Loc::getMessage("BITRIX24_THEME_ROBOTS"),
			"prefetchImages" => ["robots.png", "robots-blurred.webp"],
			"previewImage" => "robots-preview.png",
		],

		"light:pattern-hearts" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_HEARTS"),
			"previewImage" => "pattern-hearts.svg",
			"previewColor" => "#d47689",
			"releaseDate" => "14 February 2018"
		],

		"light:pattern-bluish-green" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_BLUISH_GREEN"),
			"previewImage" => "pattern-bluish-green.svg",
			"previewColor" => "#62b7c0",
		],

		"light:pattern-blue" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_BLUE"),
			"prefetchImages" => ["pattern-blue.svg", "pattern-blue-blurred.webp"],
			"previewImage" => "pattern-blue.svg",
			"previewColor" => "#3ea4d0",
		],

		"light:pattern-grey" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_GREY"),
			"previewImage" => "pattern-grey.svg",
			"previewColor" => "#545d6b",
		],

		"dark:pattern-sky-blue" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_SKY_BLUE"),
			"previewImage" => "pattern-sky-blue.svg",
			"previewColor" => "#ceecf9",
		],

		"dark:pattern-light-grey" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_LIGHT_GREY"),
			"previewImage" => "pattern-light-grey.svg",
			"previewColor" => "#eef2f4"
		],

		"dark:pattern-pink" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_PINK"),
			"previewImage" => "pattern-pink.svg",
			"previewColor" => "#ffcdcd",
		],

		"light:pattern-presents" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_PRESENTS"),
			"previewImage" => "pattern-presents.svg",
			"previewColor" => "#0c588d",
		],

		"light:pattern-things" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_THINGS"),
			"previewImage" => "pattern-things.svg",
			"previewColor" => "#aa6dab",
		],

		"light:pattern-checked" => [
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_CHECKED"),
			"previewImage" => "pattern-checked.jpg",
		],

		"light:video-star-sky" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_STAR_SKY"),
			"previewImage" => "star-sky-preview.jpg",
			"prefetchImages" => ["star-sky-poster.jpg"],
			"video" => [
				"poster" => "star-sky-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-star-sky/star-sky3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-star-sky/star-sky3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-waves" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_WAVES"),
			"previewImage" => "waves-preview.jpg",
			"prefetchImages" => ["waves-poster.jpg"],
			"video" => [
				"poster" => "waves-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-waves/waves3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-waves/waves3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-jellyfishes" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_JELLYFISHES"),
			"previewImage" => "jellyfishes-preview.jpg",
			"prefetchImages" => ["jellyfishes-poster.jpg"],
			"video" => [
				"poster" => "jellyfishes-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-jellyfishes/jellyfishes3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-jellyfishes/jellyfishes3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-sunset" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_SUNSET"),
			"previewImage" => "sunset-preview.jpg",
			"prefetchImages" => ["sunset-poster.jpg"],
			"video" => [
				"poster" => "sunset-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-sunset/sunset3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-sunset/sunset3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-rain" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_RAIN"),
			"previewImage" => "rain-preview.jpg",
			"prefetchImages" => ["rain-poster.jpg"],
			"video" => [
				"poster" => "rain-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-rain/rain3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-rain/rain3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-rain-drops" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_RAIN_DROPS"),
			"previewImage" => "rain-drops-preview.jpg",
			"prefetchImages" => ["rain-drops-poster.jpg"],
			"video" => [
				"poster" => "rain-drops-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-rain-drops/rain-drops3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-rain-drops/rain-drops3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-grass" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_GRASS"),
			"previewImage" => "grass-preview.jpg",
			"prefetchImages" => ["grass-poster.jpg"],
			"video" => [
				"poster" => "grass-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-grass/grass3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-grass/grass3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-stones" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_STONES"),
			"previewImage" => "stones-preview.jpg",
			"prefetchImages" => ["stones-poster.jpg"],
			"video" => [
				"poster" => "stones-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-stones/stones3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-stones/stones3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-waterfall" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_WATERFALL"),
			"previewImage" => "waterfall-preview.jpg",
			"prefetchImages" => ["waterfall-poster.jpg"],
			"video" => [
				"poster" => "waterfall-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-waterfall/waterfall3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-waterfall/waterfall3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-shining" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_SHINING"),
			"previewImage" => "shining-preview.jpg",
			"prefetchImages" => ["shining-poster.jpg"],
			"video" => [
				"poster" => "shining-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-shining/shining3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-shining/shining3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-beach" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_BEACH"),
			"previewImage" => "beach-preview.jpg",
			"prefetchImages" => ["beach-poster.jpg"],
			"video" => [
				"poster" => "beach-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-beach/beach3.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-beach/beach3.mp4"
				]
			],
			"resizable" => true
		],

		"light:video-river" => [
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_RIVER"),
			"previewImage" => "river-preview.jpg",
			"prefetchImages" => ["river-poster.jpg"],
			"video" => [
				"poster" => "river-poster.jpg",
				"sources" => [
					"webm" => "https://$videoDomain/bitrix24/themes/video-river/river.webm",
					"mp4" => "https://$videoDomain/bitrix24/themes/video-river/river.mp4"
				]
			],
			"resizable" => true
		],
	],
];
