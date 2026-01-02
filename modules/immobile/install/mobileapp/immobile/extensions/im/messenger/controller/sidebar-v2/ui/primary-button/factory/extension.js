/**
 * @module im/messenger/controller/sidebar-v2/ui/primary-button/factory
 */
jn.define('im/messenger/controller/sidebar-v2/ui/primary-button/factory', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { Feature } = require('im/messenger/lib/feature');
	const { SidebarPrimaryActionButtonId } = require('im/messenger/controller/sidebar-v2/const');

	const createSearchButton = ({ onClick, ...rest }) => ({
		id: SidebarPrimaryActionButtonId.SEARCH,
		icon: Icon.SEARCH,
		title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_SEARCH'),
		onClick,
		...rest,
	});

	const createMuteButton = ({ onClick, muted, ...rest }) => ({
		id: SidebarPrimaryActionButtonId.MUTE,
		testIdSuffix: muted ? 'muted' : 'unmuted',
		icon: muted ? Icon.NOTIFICATION_OFF : Icon.NOTIFICATION,
		title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_MUTE'),
		onClick,
		...rest,
	});

	const createVideoCallButton = ({ onClick, disabled, ...rest }) => ({
		id: SidebarPrimaryActionButtonId.VIDEO_CALL,
		icon: Icon.RECORD_VIDEO,
		title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_VIDEO_CALL'),
		onClick,
		disabled,
		...rest,
	});

	const createAudioCallButton = ({ onClick, disabled, ...rest }) => ({
		id: SidebarPrimaryActionButtonId.AUDIO_CALL,
		icon: Icon.PHONE_UP,
		title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_AUDIO_CALL'),
		onClick,
		disabled,
		...rest,
	});

	const createAutoDeleteButton = ({ onClick, selected, ...rest }) => {
		return {
			id: SidebarPrimaryActionButtonId.MESSAGE_AUTO_DELETE,
			icon: Icon.TIMER_DOT,
			title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_AUTO_DELETE'),
			onClick,
			selected,
			...rest,
		};
	};

	module.exports = {
		createSearchButton,
		createMuteButton,
		createVideoCallButton,
		createAudioCallButton,
		createAutoDeleteButton,
	};
});
