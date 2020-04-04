<?php

use Bitrix\Landing\Manager;

$pathJS = '/bitrix/js/landing';
$pathTemplate24 = '/bitrix/templates/';
$pathTemplate24 .= Manager::getTemplateId(
	Manager::getMainSiteId()
);
$pathCSS = '/bitrix/js/landing/css';
$pathLang = BX_ROOT . '/modules/landing/lang/' . LANGUAGE_ID;

$jsConfig = array(
	'landing_master' => array(
		'js' => array(
			$pathJS . '/bxdom.js',
			$pathJS . '/page_object.js',
			$pathJS . '/typedef.js',
			$pathJS . '/ui/editor_config.js',
			$pathJS . '/ui/style_factory.js',
			$pathJS . '/ui/field_factory.js',
			$pathJS . '/ui/property_map.js',
			$pathJS . '/collection/base_collection.js',
			$pathJS . '/collection/card_collection.js',
			$pathJS . '/collection/node_collection.js',
			$pathJS . '/ui/card/base_card.js',
			$pathJS . '/ui/card/block_preview_card.js',
			$pathJS . '/ui/card/field_group.js',
			$pathJS . '/ui/card/image_preview_card.js',
			$pathJS . '/ui/card/add_your_first_block.js',
			$pathJS . '/ui/card/base_image_library_card.js',
			$pathJS . '/ui/card/unsplash_card.js',
			$pathJS . '/ui/card/google_images_card.js',
			$pathJS . '/ui/card/uploader_card.js',
			$pathJS . '/ui/card/loader.js',
			$pathJS . '/ui/card/link_card.js',
			$pathJS . '/ui/card/landing_preview.js',
			$pathJS . '/ui/card/block_html_preview.js',
			$pathJS . '/ui/card/icon_preview.js',
			$pathJS . '/ui/card/tab_card.js',
			$pathJS . '/ui/card/dynamic_fields_group.js',
			$pathJS . '/ui/collection/button_collection.js',
			$pathJS . '/ui/collection/panel_collection.js',
			$pathJS . '/ui/collection/form_collection.js',
			$pathJS . '/collection/block_collection.js',
			$pathJS . '/ui/tool/color_picker.js',
			$pathJS . '/ui/tool/auto-font-scale.js',
			$pathJS . '/ui/tool/auto-font-scale-entry.js',
			$pathJS . '/ui/tool/popup.js',
			$pathJS . '/ui/tool/menu.js',
			$pathJS . '/ui/tool/suggests.js',
			$pathJS . '/ui/tool/font-manager.js',
			$pathJS . '/ui/adapter/css-property.js',
			$pathJS . '/ui/button/base_button.js',
			$pathJS . '/ui/button/action_button.js',
			$pathJS . '/ui/button/plus_button.js',
			$pathJS . '/ui/button/editor_action_button.js',
			$pathJS . '/ui/button/design_button.js',
			$pathJS . '/ui/button/color_button.js',
			$pathJS . '/ui/button/block_card_action.js',
			$pathJS . '/ui/button/sidebar_button.js',
			$pathJS . '/ui/button/create_link.js',
			$pathJS . '/ui/button/font_action.js',
			$pathJS . '/ui/button/change_tag.js',
			$pathJS . '/ui/button/text_background_color.js',
			$pathJS . '/ui/panel/base_panel.js',
			$pathJS . '/ui/panel/base_button_panel.js',
			$pathJS . '/ui/panel/editor_panel.js',
			$pathJS . '/ui/panel/small_editor_panel.js',
			$pathJS . '/ui/panel/content_panel.js',
			$pathJS . '/ui/panel/edit_content_panel.js',
			$pathJS . '/ui/panel/style_panel.js',
			$pathJS . '/ui/panel/preview_panel.js',
			$pathJS . '/ui/panel/unsplash_panel.js',
			$pathJS . '/ui/panel/image_panel.js',
			$pathJS . '/ui/panel/url_list.js',
			$pathJS . '/ui/panel/top_panel.js',
			$pathJS . '/ui/panel/card_action.js',
			$pathJS . '/ui/panel/link_panel.js',
			$pathJS . '/ui/panel/icon_panel.js',
			$pathJS . '/ui/panel/alert_panel.js',
			$pathJS . '/ui/panel/google_fonts_panel.js',
			$pathJS . '/ui/panel/google_images_settings_panel.js',
			$pathJS . '/ui/panel/catalog_panel.js',
			$pathJS . '/ui/panel/status_panel.js',
			$pathJS . '/ui/panel/detail_page_panel.js',
			$pathJS . '/ui/form/base_form.js',
			$pathJS . '/ui/form/card_form.js',
			$pathJS . '/ui/form/cards_form.js',
			$pathJS . '/ui/form/style_form.js',
			$pathJS . '/ui/form/balloon_form.js',
			$pathJS . '/ui/form/dynamic_cards_form.js',
			$pathJS . '/ui/form/dynamic_block_form.js',
			$pathJS . '/ui/field/base_field.js',
			$pathJS . '/ui/field/text_field.js',
			$pathJS . '/ui/field/image_field.js',
			$pathJS . '/ui/field/icon_field.js',
			$pathJS . '/ui/field/link_field.js',
			$pathJS . '/ui/field/dropdown_field.js',
			$pathJS . '/ui/field/dropdown_preview_field.js',
			$pathJS . '/ui/field/unit_field.js',
			$pathJS . '/ui/field/range_field.js',
			$pathJS . '/ui/field/button_group_field.js',
			$pathJS . '/ui/field/color_field.js',
			$pathJS . '/ui/field/link_url_field.js',
			$pathJS . '/ui/field/dropdown_inline.js',
			$pathJS . '/ui/field/dnd_list.js',
			$pathJS . '/ui/field/sortable_list.js',
			$pathJS . '/ui/field/position_field.js',
			$pathJS . '/ui/field/checkbox_field.js',
			$pathJS . '/ui/field/radio_field.js',
			$pathJS . '/ui/field/multiselect_field.js',
			$pathJS . '/ui/field/filter_field.js',
			$pathJS . '/ui/field/font_field.js',
			$pathJS . '/ui/field/html_field.js',
			$pathJS . '/ui/field/switch_field.js',
			$pathJS . '/ui/field/embed_field.js',
			$pathJS . '/ui/field/date_field.js',
			$pathJS . '/ui/field/source_field.js',
			$pathJS . '/ui/field/block_source_field.js',
			$pathJS . '/ui/field/dynamic_image_field.js',
			$pathJS . '/ui/field/pages_field.js',
			$pathJS . '/ui/field/click_action_field.js',
			$pathJS . '/ui/style_node.js',
			$pathJS . '/ui/highlight_node.js',
			$pathJS . '/events/block_event.js',
			$pathJS . '/group.js',
			$pathJS . '/block.js',
			$pathJS . '/card.js',
			$pathJS . '/node.js',
			$pathJS . '/landing.js',
			$pathJS . '/node/text.js',
			$pathJS . '/node/link.js',
			$pathJS . '/node/img.js',
			$pathJS . '/node/ul.js',
			$pathJS . '/node/map.js',
			$pathJS . '/node/component.js',
			$pathJS . '/node/icon.js',
			$pathJS . '/node/embed.js',
			$pathJS . '/client/unsplash.js',
			$pathJS . '/client/google_images.js',
			$pathJS . '/client/google_fonts.js',
			$pathJS . '/history/history.js',
			$pathJS . '/history/history_entry.js',
			$pathJS . '/history/history_command.js',
			$pathJS . '/history/history_highlight.js',
			$pathJS . '/history/action/history_action_add_block.js',
			$pathJS . '/history/action/history_action_add_card.js',
			$pathJS . '/history/action/history_action_edit_image.js',
			$pathJS . '/history/action/history_action_edit_link.js',
			$pathJS . '/history/action/history_action_edit_style.js',
			$pathJS . '/history/action/history_action_edit_text.js',
			$pathJS . '/history/action/history_action_edit_embed.js',
			$pathJS . '/history/action/history_action_edit_map.js',
			$pathJS . '/history/action/history_action_remove_block.js',
			$pathJS . '/history/action/history_action_remove_card.js',
			$pathJS . '/history/action/history_action_sort_block.js',
			$pathJS . '/history/action/history_action_update_icon.js',
			$pathJS . '/history/action/history_action_update_block_state.js',
			$pathJS . '/icons/fontawesome.js',
			$pathJS . '/icons/simple-line-icon.js',
			$pathJS . '/icons/simple-line-icon-pro1.js',
			$pathJS . '/icons/simple-line-icon-pro2.js',
			$pathJS . '/icons/et-line-icon.js',
			$pathJS . '/icons/hs-icon.js',
			$pathJS . '/mediaservice/base_mediaservice.js',
			$pathJS . '/mediaservice/youtube_mediaservice.js',
			$pathJS . '/mediaservice/vimeo_mediaservice.js',
			$pathJS . '/mediaservice/vine_mediaservice.js',
			$pathJS . '/mediaservice/instagram_mediaservice.js',
			$pathJS . '/mediaservice/google_maps_search_mediaservice.js',
			$pathJS . '/mediaservice/google_maps_place_mediaservice.js',
			$pathJS . '/mediaservice/facebook_page_plugin_service.js',
			$pathJS . '/mediaservice/facebook_post_embed_service.js',
			$pathJS . '/mediaservice/facebook_video_embed_service.js',
			$pathJS . '/mediaservice/service_factory.js',
			$pathJS . '/error_manager.js',
			$pathJS . '/external/webfontloader/webfontloader.js',
		),
		'css' => array(
			$pathCSS . '/landing_master.css',
			$pathCSS . '/ui/button/base_button.css',
			$pathCSS . '/ui/button/action_button.css',
			$pathCSS . '/ui/button/plus_button.css',
			$pathCSS . '/ui/button/color_button.css',
			$pathCSS . '/ui/button/editor_action_button.css',
			$pathCSS . '/ui/button/block_card_action.css',
			$pathCSS . '/ui/button/sidebar_button.css',
			$pathCSS . '/ui/button/font_action.css',
			$pathCSS . '/ui/panel/base_panel.css',
			$pathCSS . '/ui/panel/editor_panel.css',
			$pathCSS . '/ui/panel/small_editor_panel.css',
			$pathCSS . '/ui/panel/content_panel.css',
			$pathCSS . '/ui/panel/block_card_action.css',
			$pathCSS . '/ui/panel/edit_content_panel.css',
			$pathCSS . '/ui/panel/style_panel.css',
			$pathCSS . '/ui/panel/preview_panel.css',
			$pathCSS . '/ui/panel/block_list_panel.css',
			$pathCSS . '/ui/panel/unsplash_panel.css',
			$pathCSS . '/ui/panel/image_panel.css',
			$pathCSS . '/ui/panel/url_list.css',
			$pathCSS . '/ui/panel/card_action.css',
			$pathCSS . '/ui/panel/alert_panel.css',
			$pathCSS . '/ui/panel/google_fonts_panel.css',
			$pathCSS . '/ui/panel/catalog_panel.css',
			$pathCSS . '/ui/panel/status_panel.css',
			$pathCSS . '/ui/panel/detail_page_panel.css',
			$pathCSS . '/ui/form/base_form.css',
			$pathCSS . '/ui/form/card_form.css',
			$pathCSS . '/ui/form/cards_form.css',
			$pathCSS . '/ui/form/style_form.css',
			$pathCSS . '/ui/form/balloon_form.css',
			$pathCSS . '/ui/form/dynamic_cards_form.css',
			$pathCSS . '/ui/field/base_field.css',
			$pathCSS . '/ui/field/image_field.css',
			$pathCSS . '/ui/field/link_field.css',
			$pathCSS . '/ui/field/dropdown_field.css',
			$pathCSS . '/ui/field/dropdown_preview_field.css',
			$pathCSS . '/ui/field/unit_field.css',
			$pathCSS . '/ui/field/range_field.css',
			$pathCSS . '/ui/field/button_group_field.css',
			$pathCSS . '/ui/field/color_field.css',
			$pathCSS . '/ui/field/link_url_field.css',
			$pathCSS . '/ui/field/dropdown_inline.css',
			$pathCSS . '/ui/field/dnd_list.css',
			$pathCSS . '/ui/field/sortable_list.css',
			$pathCSS . '/ui/field/position_field.css',
			$pathCSS . '/ui/field/checkbox_field.css',
			$pathCSS . '/ui/field/multiselect_field.css',
			$pathCSS . '/ui/field/filter_field.css',
			$pathCSS . '/ui/field/font_field.css',
			$pathCSS . '/ui/field/html_field.css',
			$pathCSS . '/ui/field/switch_field.css',
			$pathCSS . '/ui/field/source_field.css',
			$pathCSS . '/ui/field/block_source_field.css',
			$pathCSS . '/ui/field/pages_field.css',
			$pathCSS . '/ui/card/base_card.css',
			$pathCSS . '/ui/card/block_preview_card.css',
			$pathCSS . '/ui/card/field_group.css',
			$pathCSS . '/ui/card/add_your_first_block.css',
			$pathCSS . '/ui/card/unsplash_card.css',
			$pathCSS . '/ui/card/base_image_library_card.css',
			$pathCSS . '/ui/card/uploader_card.css',
			$pathCSS . '/ui/card/loader.css',
			$pathCSS . '/ui/card/link_card.css',
			$pathCSS . '/ui/card/landing_preview.css',
			$pathCSS . '/ui/card/block_html_preview.css',
			$pathCSS . '/ui/card/icons_section.css',
			$pathCSS . '/ui/card/icon_preview.css',
			$pathCSS . '/ui/card/tab_card.css',
			$pathCSS . '/ui/card/dynamic_fields_group.css',
			$pathCSS . '/mediaservice/base_mediaservice.css',
			$pathCSS . '/ui/tool/suggests.css',
			$pathCSS . '/ui/tool/popup.css',
		),
		'rel' => array(
			'main.core',
			'main.polyfill.intersectionobserver',
			'main.polyfill.customevent',
			'popup',
			'color_picker',
			'dnd',
			'fx',
			'ajax',
			'action_dialog',
			'loader',
			'mediaplayer',
			'date',
			'main.imageeditor',
			'landing.backend',
			'ui.alerts',
			'landing.imagecompressor',
			'landing.imageeditor',
			'landing.imageuploader',
			'landing.loc',
			'landing.env',
			'landing.utils',
		),
		'lang' => $pathLang . '/js/landing_master.php',
		'bundle_js' => 'landing_master',
	),

	'mediaplayer' => array(
		'js' => array(
			'https://www.youtube.com/iframe_api',
			$pathJS . '/mediaplayer/base_mediaplayer.js',
			$pathJS . '/mediaplayer/youtube_mediaplayer.js',
			$pathJS . '/mediaplayer/mediaplayer_factory.js'
		),
		'rel' => [
			'landing.utils',
		]
	),

	'landing_inline_video' => array(
		'js' => array(
			$pathTemplate24 . '/assets/js/helpers/inline-video.js',
		)
	),

	'map_provider' => array(
		'js' => array(
			$pathJS . '/collection/base_collection.js',
			$pathJS . '/provider/map/base-map-provider.js',
			$pathJS . '/provider/map/google-map.js',
			$pathJS . '/provider/map/google-map/theme/silver.theme.js',
			$pathJS . '/provider/map/google-map/theme/retro.theme.js',
			$pathJS . '/provider/map/google-map/theme/dark.theme.js',
			$pathJS . '/provider/map/google-map/theme/night.theme.js',
			$pathJS . '/provider/map/google-map/theme/aubergine.theme.js'
		),
		'css' => array(
			$pathCSS . '/provider/map/google-map.css',
		),
		'rel' => [
			'landing.utils',
		],
	),

	'polyfill' => array(
		'js' => array(
			$pathJS . '/polyfill.js',
		)
	),

	'action_dialog' => array(
		'js' => array(
			$pathJS . '/ui/tool/action_dialog.js'
		),
		'css' => array(
			$pathCSS . '/ui/tool/action_dialog.css',
		),
		'rel' => array(
			'polyfill',
			'popup'
		),
		'lang' => $pathLang . '/js/action_dialog.php'
	),
	
	'landing_public' => [
		'js' => [
			$pathJS . '/events/block_event.js',
			$pathJS . '/public.js',
			$pathTemplate24 . '/assets/js/helpers/popup_init.js',
		],
		'css' => [
			$pathCSS . '/landing_public.css',
		],
		'rel' => [
			'landing_event_tracker',
			'mediaplayer',
			'polyfill',
			'landing.utils',
		],
	],
	
	'landing_event_tracker' => array(
		'js' => array(
			$pathJS . '/event-tracker/event-tracker.js',
			$pathJS . '/event-tracker/services/base-service.js',
			$pathJS . '/event-tracker/services/google-analytics-service.js'
		),
		'rel' => [
			'landing.utils',
		],
	),

	// vendors scripts for ALL blocks, included always
	'landing_core' => array(
		'js' => array(
			$pathTemplate24 . '/assets/vendor/jquery/jquery-3.2.1.js',
			$pathTemplate24 . '/assets/vendor/jquery.easing/js/jquery.easing.js',
			$pathTemplate24 . '/assets/vendor/bootstrap/js/dist/util.js',
			$pathTemplate24 . '/assets/vendor/bootstrap/js/dist/collapse.js',
			$pathTemplate24 . '/assets/vendor/fancybox/jquery.fancybox.js',
			$pathTemplate24 . '/assets/js/components/hs.core_landing.js',
			$pathTemplate24 . '/assets/js/helpers/onscroll-animation_init.js',
			$pathTemplate24 . '/assets/js/components/hs.go-to.js',
			$pathTemplate24 . '/assets/js/helpers/go_to_init.js',
		),
		'css' => array(
			$pathTemplate24 . '/assets/vendor/bootstrap/bootstrap.css',
			$pathTemplate24 . '/assets/vendor/fancybox/jquery.fancybox.css',
			$pathTemplate24 . '/themes/themes_core.css',
			$pathTemplate24 . '/assets/css/custom.css',
			$pathTemplate24 . '/assets/css/themes_custom.css',
			$pathTemplate24 . '/assets/vendor/animate.css',
		),
		'rel' => [
			'mediaplayer',
			'landing_icon_fonts',
			'main.polyfill.intersectionobserver',
			'landing.utils',
		],
	),
	
	'landing_critical_grid' => [
		'css' => [
			$pathTemplate24 . '/assets/vendor/bootstrap/bootstrap.css',
			$pathTemplate24 . '/assets/css/custom-grid.css',
			$pathTemplate24 . '/themes/themes_core.css',
		],
		'js' => [
			$pathTemplate24 . '/assets/vendor/jquery/jquery-3.2.1.js',
		]
	],
	
	'landing_icon_fonts' => array(
		'css' => array(
			$pathTemplate24 . '/assets/vendor/icon-awesome/css/font-awesome.css',
			$pathTemplate24 . '/assets/vendor/icon-etlinefont/style.css',
			$pathTemplate24 . '/assets/vendor/icon-hs/style.css',
			$pathTemplate24 . '/assets/vendor/icon-line/css/simple-line-icons.css',
			$pathTemplate24 . '/assets/vendor/icon-line-pro/style.css'
		),
	),

	'landing_menu' => array(
		'js' => [
			$pathTemplate24 . '/assets/js/components/hs.header.js',
			$pathTemplate24 . '/assets/js/components/hs.scroll-nav.js',
			$pathTemplate24 . '/assets/js/components/hs.hamburgers.js',
			$pathTemplate24 . '/assets/js/helpers/hamburgers_init.js',
			$pathTemplate24 . '/assets/js/helpers/header_menu_init.js',
		],
		'css' => [
			$pathTemplate24 . '/assets/vendor/hamburgers/hamburgers.css',
		],
		'rel' => ['landing_core'],
	),

	'landing_form' => array(
		'js' => array(
			$pathTemplate24 . '/assets/js/helpers/form_init.js',
		),
		'lang' => $pathLang . '/js/webform_alerts.php',
	),

	'landing_gallery_cards' => array(
		'js' => array(
			$pathTemplate24 . '/assets/js/helpers/gallery_cards_init.js',
		),
		'rel' => array('landing_core'),
	),

	'landing_carousel' => array(
		'js' => array(
			$pathTemplate24 . '/assets/vendor/slick-carousel/slick/slick.js',
			$pathTemplate24 . '/assets/js/components/hs.carousel.js',
			$pathTemplate24 . '/assets/js/helpers/carousel/carousel_helper.js',
			$pathTemplate24 . '/assets/js/helpers/carousel/base_carousel_init.js',
		),
		'css' => array(
			$pathTemplate24 . '/assets/vendor/slick-carousel/slick/slick.css',
		),
		'rel' => array('landing_core'),
	),

	'landing_countdown' => array(
		'js' => array(
			$pathTemplate24 . '/assets/vendor/jquery.countdown/jquery.countdown.js',
			$pathTemplate24 . '/assets/js/components/hs.countdown.js',
			$pathTemplate24 . '/assets/js/helpers/countdown_init.js',
		),
		'rel' => array('landing_core'),
	),

	'landing_google_maps_new' => array(
		'js' => array(
			$pathTemplate24 . '/assets/js/helpers/google_maps_new.js'
		),
		'rel' => array(
			'map_provider'
		)
	),
	
	'landing_lazyload' => [
		'js' => [
			$pathTemplate24 . '/assets/js/helpers/lazy_load.js',
		],
	],
	
	'landing_auto_font_scale' => [
		'js' => [
			$pathJS . '/ui/tool/auto-font-scale.js',
			$pathJS . '/ui/tool/auto-font-scale-entry.js',
			$pathTemplate24 . '/assets/js/helpers/auto_font_scale_init.js',
		],
	],
);


foreach ($jsConfig as $code => $ext)
{
	\CJSCore::registerExt($code, $ext);
}