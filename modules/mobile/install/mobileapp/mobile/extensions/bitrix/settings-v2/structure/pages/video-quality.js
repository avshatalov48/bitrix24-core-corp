/**
 * @module settings-v2/structure/pages/video-quality
 */
jn.define('settings-v2/structure/pages/video-quality', (require, exports, module) => {
	const { createVideoQualitySwitch, createSection, createDescription, createVideoBanner } = require('settings-v2/structure/src/item-create-helper');
	const { NativeSettingController } = require('settings-v2/controller/native');
	const { SettingsPageId, EventType } = require('settings-v2/const');
	const { SettingEmitter } = require('settings-v2/emitter');
	const { Loc } = require('loc');

	/**
	 * @returns {NativeSettingController}
	 */
	const createVideoQualityController = () => {
		return (new NativeSettingController({
			settingId: 'files_video_quality',
			fallbackValue: 'LQ',
		})).setOnChange((value) => {
			SettingEmitter.emit(EventType.changeVideoQuality, value);
		});
	};

	/** @type SettingPage */
	const VideoQualityPage = {
		id: SettingsPageId.VIDEO_QUALITY,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_VIDEO_QUALITY_TITLE'),
		items: [
			createVideoBanner({
				id: 'video-quality-banner',
				controller: createVideoQualityController(),
			}),
			createSection({
				id: 'video-quality-section',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_VIDEO_QUALITY_SECTION_TITLE'),
				items: [
					createVideoQualitySwitch({
						id: 'video-quality-setting',
						controller: createVideoQualityController(),
					}),
					createDescription({
						id: 'video-quality-description',
						text: Loc.getMessage('SETTINGS_V2_STRUCTURE_VIDEO_QUALITY_DESCRIPTION'),
					}),
				],
			}),
		],
	};

	module.exports = {
		VideoQualityPage,
	};
});
