<?
use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(

	"baseThemes" => array(
		"default" => array(
			"css" => array("main.css", "menu.css")
		),

		"light" => array(
			"css" => array("main.css", "menu.css")
		),

		"dark" => array(
			"css" => array("main.css", "menu.css")
		)
	),

	"subThemes" => array(

		"default" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_DEFAULT"),
			"previewColor" => "#eef2f4",
			"previewImage" => "preview.jpg"
		),

		"light:sunset" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SUNSET"),
			"prefetchImages" => array("sunset.jpg"),
			"previewImage" => "sunset-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:greenfield" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_GREENFIELD"),
			"prefetchImages" => array("greenfield.jpg"),
			"previewImage" => "greenfield-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:tulips" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_TULIPS"),
			"prefetchImages" => array("tulips.jpg"),
			"previewImage" => "tulips-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:grass" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_GRASS"),
			"prefetchImages" => array("grass.jpg"),
			"previewImage" => "grass-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:cloud-sea" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CLOUD_SEA"),
			"prefetchImages" => array("cloud-sea.jpg"),
			"previewImage" => "cloud-sea-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:pink-fencer" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PINK_FENCER"),
			"prefetchImages" => array("pink-fencer.jpg"),
			"previewImage" => "pink-fencer-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:grass-ears" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_GRASS_EARS"),
			"prefetchImages" => array("grass-ears.jpg"),
			"previewImage" => "grass-ears-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:atmosphere" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_ATMOSPHERE"),
			"prefetchImages" => array("atmosphere.jpg"),
			"previewImage" => "atmosphere-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:paradise" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PARADISE"),
			"prefetchImages" => array("paradise.jpg"),
			"previewImage" => "paradise-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:village" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VILLAGE"),
			"prefetchImages" => array("village.jpg"),
			"previewImage" => "village-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:mountains" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAINS"),
			"prefetchImages" => array("mountains.jpg"),
			"previewImage" => "mountains-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:beach" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_BEACH"),
			"prefetchImages" => array("beach.jpg"),
			"previewImage" => "beach-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:sea-sunset" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SEA_SUNSET"),
			"prefetchImages" => array("sea-sunset.jpg"),
			"previewImage" => "sea-sunset-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:snow-village" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SNOW_VILLAGE"),
			"prefetchImages" => array("snow-village.jpg"),
			"previewImage" => "snow-village-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:meditation" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MEDITATION"),
			"prefetchImages" => array("meditation.jpg"),
			"previewImage" => "meditation-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"dark:starfish" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_STARFISH"),
			"prefetchImages" => array("starfish.jpg"),
			"previewImage" => "starfish-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"dark:sea-stones" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SEA_STONES"),
			"prefetchImages" => array("sea-stones.jpg"),
			"previewImage" => "sea-stones-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"dark:seashells" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SEASHELLS"),
			"prefetchImages" => array("seashells.jpg"),
			"previewImage" => "seashells-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:architecture" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_ARCHITECTURE"),
			"prefetchImages" => array("architecture.jpg"),
			"previewImage" => "architecture-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:skyscraper" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SKYSCRAPER"),
			"prefetchImages" => array("skyscraper.jpg"),
			"previewImage" => "skyscraper-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:wall" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_WALL"),
			"prefetchImages" => array("wall.jpg"),
			"previewImage" => "wall-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:flower" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FLOWER"),
			"prefetchImages" => array("flower.jpg"),
			"previewImage" => "flower-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:metro" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_METRO"),
			"prefetchImages" => array("metro.jpg"),
			"previewImage" => "metro-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:shining" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SHINING"),
			"prefetchImages" => array("shining.jpg"),
			"previewImage" => "shining-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:stars" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_STARS"),
			"prefetchImages" => array("stars.jpg"),
			"previewImage" => "stars-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:clouds" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CLOUDS"),
			"prefetchImages" => array("clouds.jpg"),
			"previewImage" => "clouds-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:canyon" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CANYON"),
			"prefetchImages" => array("canyon.jpg"),
			"previewImage" => "canyon-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:mountains-3" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAINS"),
			"prefetchImages" => array("mountains-3.jpg"),
			"previewImage" => "mountains-3-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:valley" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VALLEY"),
			"prefetchImages" => array("valley.jpg"),
			"previewImage" => "valley-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:leafs" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_LEAFS"),
			"prefetchImages" => array("leafs.jpg"),
			"previewImage" => "leafs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:wind" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_WIND"),
			"prefetchImages" => array("wind.jpg"),
			"previewImage" => "wind-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:grass-2" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_GRASS"),
			"prefetchImages" => array("grass-2.jpg"),
			"previewImage" => "grass-2-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:tree" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_TREE"),
			"prefetchImages" => array("tree.jpg"),
			"previewImage" => "tree-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:red-field" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_RED_FIELD"),
			"prefetchImages" => array("red-field.jpg"),
			"previewImage" => "red-field-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:trees" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_TREES"),
			"prefetchImages" => array("trees.jpg"),
			"previewImage" => "trees-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:ice" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_ICE"),
			"prefetchImages" => array("ice.jpg"),
			"previewImage" => "ice-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:plant" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PLANT"),
			"prefetchImages" => array("plant.jpg"),
			"previewImage" => "plant-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:mountains-2" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAINS"),
			"prefetchImages" => array("mountains-2.jpg"),
			"previewImage" => "mountains-2-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:countryside" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_COUNTRYSIDE"),
			"prefetchImages" => array("countryside.jpg"),
			"previewImage" => "countryside-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:morning" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MORNING"),
			"prefetchImages" => array("morning.jpg"),
			"previewImage" => "morning-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:scooter" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SCOOTER"),
			"prefetchImages" => array("scooter.jpg"),
			"previewImage" => "scooter-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true
		),

		"light:air" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_AIR"),
			"prefetchImages" => array("air.jpg"),
			"previewImage" => "air-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:autumn-forest" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_AUTUMN_FOREST"),
			"prefetchImages" => array("autumn-forest.jpg"),
			"previewImage" => "autumn-forest-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:bird" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_BIRD"),
			"prefetchImages" => array("bird.jpg"),
			"previewImage" => "bird-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:city" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CITY"),
			"prefetchImages" => array("city.jpg"),
			"previewImage" => "city-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:coloured-feathers" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_COLOURED_FEATHERS"),
			"prefetchImages" => array("coloured-feathers.jpg"),
			"previewImage" => "coloured-feathers-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:desert" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_DESERT"),
			"prefetchImages" => array("desert.jpg"),
			"previewImage" => "desert-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:feathers" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FEATHERS"),
			"prefetchImages" => array("feathers.jpg"),
			"previewImage" => "feathers-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:flower-and-leafs" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FLOWER_AND_LEAFS"),
			"prefetchImages" => array("flower-and-leafs.jpg"),
			"previewImage" => "flower-and-leafs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:high-grass" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_HIGH_GRASS"),
			"prefetchImages" => array("high-grass.jpg"),
			"previewImage" => "high-grass-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:highness" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_HIGHNESS"),
			"prefetchImages" => array("highness.jpg"),
			"previewImage" => "highness-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:hills" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_HILLS"),
			"prefetchImages" => array("hills.jpg"),
			"previewImage" => "hills-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:horses" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_HORSES"),
			"prefetchImages" => array("horses.jpg"),
			"previewImage" => "horses-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:houses" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_HOUSES"),
			"prefetchImages" => array("houses.jpg"),
			"previewImage" => "houses-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:lake" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_LAKE"),
			"prefetchImages" => array("lake.jpg"),
			"previewImage" => "lake-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:lava" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_LAVA"),
			"prefetchImages" => array("lava.jpg"),
			"previewImage" => "lava-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:lion-cubs" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_LION_CUBS"),
			"prefetchImages" => array("lion-cubs.jpg"),
			"previewImage" => "lion-cubs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:mountain" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAIN"),
			"prefetchImages" => array("mountain.jpg"),
			"previewImage" => "mountain-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:mountain-air" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_MOUNTAIN_AIR"),
			"prefetchImages" => array("mountain-air.jpg"),
			"previewImage" => "mountain-air-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:offices" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_OFFICES"),
			"prefetchImages" => array("offices.jpg"),
			"previewImage" => "offices-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:perspective" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PERSPECTIVE"),
			"prefetchImages" => array("perspective.jpg"),
			"previewImage" => "perspective-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:plants" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PLANTS"),
			"prefetchImages" => array("plants.jpg"),
			"previewImage" => "plants-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:sea" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SEA"),
			"prefetchImages" => array("sea.jpg"),
			"previewImage" => "sea-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:slope" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_SLOPE"),
			"prefetchImages" => array("slope.jpg"),
			"previewImage" => "slope-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:steel-wall" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_STEEL_WALL"),
			"prefetchImages" => array("steel-wall.jpg"),
			"previewImage" => "steel-wall-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:travel" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_TRAVEL"),
			"prefetchImages" => array("travel.jpg"),
			"previewImage" => "travel-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:winter-forest" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_WINTER_FOREST"),
			"prefetchImages" => array("winter-forest.jpg"),
			"previewImage" => "winter-forest-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:winter-night" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_WINTER_NIGHT"),
			"prefetchImages" => array("winter-night.jpg"),
			"previewImage" => "winter-night-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"light:camouflage" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CAMOUFLAGE"),
			"prefetchImages" => array("camouflage.jpg"),
			"previewImage" => "camouflage-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "23 February 2018"
		),

		"light:red-star" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_RED_STAR"),
			"prefetchImages" => array("red-star.jpg"),
			"previewImage" => "red-star-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"zones" => array("ru", "by", "kz")
		),

		"light:jack-o-lantern" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_JACK_O_LANTERN"),
			"prefetchImages" => array("jack-o-lantern.jpg"),
			"previewImage" => "jack-o-lantern-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "31 October 2018"
		),

		"light:halloween" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_HALLOWEEN"),
			"prefetchImages" => array("halloween.jpg"),
			"previewImage" => "halloween-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "31 October 2018"
		),

		"light:christmas-snow" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CHRISTMAS_SNOW"),
			"prefetchImages" => array("christmas-snow.jpg"),
			"previewImage" => "christmas-snow-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		),

		"light:christmas-gift" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CHRISTMAS_GIFT"),
			"prefetchImages" => array("christmas-gift.jpg"),
			"previewImage" => "christmas-gift-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		),

		"light:christmas-ball" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_CHRISTMAS_BALL"),
			"prefetchImages" => array("christmas-ball.jpg"),
			"previewImage" => "christmas-ball-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		),

		"light:new-years-room" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_NEW_YEARS_ROOM"),
			"prefetchImages" => array("new-years-room.jpg"),
			"previewImage" => "new-years-room-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "25 December 2018"
		),

		"light:easter-eggs" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_EASTER_EGGS"),
			"prefetchImages" => array("easter-eggs.jpg"),
			"previewImage" => "easter-eggs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "1 April 2018"
		),

		"dark:easter-eggs" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_EASTER_EGGS"),
			"prefetchImages" => array("easter-eggs.jpg"),
			"previewImage" => "easter-eggs-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "1 April 2018"
		),

		"dark:lotus" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_LOTUS"),
			"prefetchImages" => array("lotus.jpg"),
			"previewImage" => "lotus-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "8 March 2018"
		),

		"light:valentines-hearts" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VALENTINES_HEARTS"),
			"prefetchImages" => array("valentines-hearts.jpg"),
			"previewImage" => "valentines-hearts-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "14 February 2018"
		),

		"dark:coloured-paper" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_COLOURED_PAPER"),
			"prefetchImages" => array("coloured-paper.jpg"),
			"previewImage" => "coloured-paper-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:dew" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_DEW"),
			"prefetchImages" => array("dew.jpg"),
			"previewImage" => "dew-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:fabric" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FABRIC"),
			"prefetchImages" => array("fabric.jpg"),
			"previewImage" => "fabric-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:flamingo" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FLAMINGO"),
			"prefetchImages" => array("flamingo.jpg"),
			"previewImage" => "flamingo-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:flowers" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FLOWERS"),
			"prefetchImages" => array("flowers.jpg"),
			"previewImage" => "flowers-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:freshness" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FRESHNESS"),
			"prefetchImages" => array("freshness.jpg"),
			"previewImage" => "freshness-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:fur" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_FUR"),
			"prefetchImages" => array("fur.jpg"),
			"previewImage" => "fur-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:light-fabric" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_LIGHT_FABRIC"),
			"prefetchImages" => array("light-fabric.jpg"),
			"previewImage" => "light-fabric-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:table" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_TABLE"),
			"prefetchImages" => array("table.jpg"),
			"previewImage" => "table-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:vibration" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIBRATION"),
			"prefetchImages" => array("vibration.jpg"),
			"previewImage" => "vibration-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:window" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_WINDOW"),
			"prefetchImages" => array("window.jpg"),
			"previewImage" => "window-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:wooden-letters" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_WOODEN_LETTERS"),
			"prefetchImages" => array("wooden-letters.jpg"),
			"previewImage" => "wooden-letters-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
		),

		"dark:pattern-tulips" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_TULIPS"),
			"prefetchImages" => array("pattern-tulips.jpg"),
			"previewImage" => "pattern-tulips-preview.jpg",
			"width" => 1920,
			"height" => 1080,
			"resizable" => true,
			"releaseDate" => "8 March 2018"
		),

		"light:pattern-hearts" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_HEARTS"),
			"previewImage" => "pattern-hearts.svg",
			"previewColor" => "#d47689",
			"releaseDate" => "14 February 2018"
		),

		"default:pattern-grey" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_DEFAULT_WITH_PATTERN"),
			"prefetchImages" => array("pattern-grey-header.svg", "pattern-grey.svg"),
			"previewImage" => "pattern-grey-preview.jpg",
			"previewColor" => "#eef2f4"
		),

		"light:pattern-bluish-green" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_BLUISH_GREEN"),
			"previewImage" => "pattern-bluish-green.svg",
			"previewColor" => "#62b7c0",
		),

		"light:pattern-blue" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_BLUE"),
			"prefetchImages" => array("pattern-blue.svg"),
			"previewImage" => "pattern-blue.svg",
			"previewColor" => "#3ea4d0",
		),

		"light:pattern-grey" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_GREY"),
			"previewImage" => "pattern-grey.svg",
			"previewColor" => "#545d6b",
		),

		"dark:pattern-sky-blue" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_SKY_BLUE"),
			"previewImage" => "pattern-sky-blue.svg",
			"previewColor" => "#ceecf9",
		),

		"dark:pattern-light-grey" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_LIGHT_GREY"),
			"previewImage" => "pattern-light-grey.svg",
			"previewColor" => "#eef2f4"
		),

		"dark:pattern-pink" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_PINK"),
			"previewImage" => "pattern-pink.svg",
			"previewColor" => "#ffcdcd",
		),

		"light:pattern-presents" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_PRESENTS"),
			"previewImage" => "pattern-presents.svg",
			"previewColor" => "#0c588d",
		),

		"light:pattern-things" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_THINGS"),
			"previewImage" => "pattern-things.svg",
			"previewColor" => "#aa6dab",
		),

		"light:pattern-checked" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_PATTERN_CHECKED"),
			"previewImage" => "pattern-checked.jpg",
		),

		"light:video-star-sky" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_STAR_SKY"),
			"previewImage" => "star-sky-preview.jpg",
			"prefetchImages" => array("star-sky-poster.jpg"),
			"video" => array(
				"poster" => "star-sky-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-star-sky/star-sky3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-star-sky/star-sky3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-waves" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_WAVES"),
			"previewImage" => "waves-preview.jpg",
			"prefetchImages" => array("waves-poster.jpg"),
			"video" => array(
				"poster" => "waves-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-waves/waves3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-waves/waves3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-jellyfishes" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_JELLYFISHES"),
			"previewImage" => "jellyfishes-preview.jpg",
			"prefetchImages" => array("jellyfishes-poster.jpg"),
			"video" => array(
				"poster" => "jellyfishes-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-jellyfishes/jellyfishes3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-jellyfishes/jellyfishes3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-sunset" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_SUNSET"),
			"previewImage" => "sunset-preview.jpg",
			"prefetchImages" => array("sunset-poster.jpg"),
			"video" => array(
				"poster" => "sunset-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-sunset/sunset3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-sunset/sunset3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-rain" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_RAIN"),
			"previewImage" => "rain-preview.jpg",
			"prefetchImages" => array("rain-poster.jpg"),
			"video" => array(
				"poster" => "rain-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-rain/rain3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-rain/rain3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-rain-drops" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_RAIN_DROPS"),
			"previewImage" => "rain-drops-preview.jpg",
			"prefetchImages" => array("rain-drops-poster.jpg"),
			"video" => array(
				"poster" => "rain-drops-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-rain-drops/rain-drops3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-rain-drops/rain-drops3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-grass" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_GRASS"),
			"previewImage" => "grass-preview.jpg",
			"prefetchImages" => array("grass-poster.jpg"),
			"video" => array(
				"poster" => "grass-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-grass/grass3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-grass/grass3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-stones" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_STONES"),
			"previewImage" => "stones-preview.jpg",
			"prefetchImages" => array("stones-poster.jpg"),
			"video" => array(
				"poster" => "stones-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-stones/stones3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-stones/stones3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-waterfall" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_WATERFALL"),
			"previewImage" => "waterfall-preview.jpg",
			"prefetchImages" => array("waterfall-poster.jpg"),
			"video" => array(
				"poster" => "waterfall-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-waterfall/waterfall3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-waterfall/waterfall3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-shining" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_SHINING"),
			"previewImage" => "shining-preview.jpg",
			"prefetchImages" => array("shining-poster.jpg"),
			"video" => array(
				"poster" => "shining-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-shining/shining3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-shining/shining3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-beach" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_BEACH"),
			"previewImage" => "beach-preview.jpg",
			"prefetchImages" => array("beach-poster.jpg"),
			"video" => array(
				"poster" => "beach-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-beach/beach3.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-beach/beach3.mp4"
				)
			),
			"resizable" => true
		),

		"light:video-river" => array(
			"title" => Loc::getMessage("BITRIX24_THEME_VIDEO_RIVER"),
			"previewImage" => "river-preview.jpg",
			"prefetchImages" => array("river-poster.jpg"),
			"video" => array(
				"poster" => "river-poster.jpg",
				"sources" => array(
					"webm" => "//video.1c-bitrix.ru/bitrix24/themes/video-river/river.webm",
					"mp4" => "//video.1c-bitrix.ru/bitrix24/themes/video-river/river.mp4"
				)
			),
			"resizable" => true
		),
	),
);
