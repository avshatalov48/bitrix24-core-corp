/**
 * @module im/messenger/controller/channel-creator/step/settings
 */
jn.define('im/messenger/controller/channel-creator/step/settings', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Step } = require('im/messenger/controller/channel-creator/step/base');
	const { PrivacySelector } = require('im/messenger/controller/channel-creator/components/privacy-selector');

	class SettingsStep extends Step
	{
		#mode = 'open';
		/**
		 * @return WidgetTitleParamsType
		 */
		static getTitleParams()
		{
			return {
				text: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_SETTINGS_TITLE'),
			};
		}

		render()
		{
			return View(
				{},
				new PrivacySelector({
					defaultMode: this.props.mode ?? 'private',
					firstMode: 'private',
					badge: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_SETTINGS_BADGE'),
					openModeDescription: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_SETTINGS_OPEN_CHANNEL_DESCRIPTION'),
					privateModeDescription: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_SETTINGS_PRIVATE_CHANNEL_DESCRIPTION'),
					onChangeMode: (value) => {
						this.#mode = value;
					},
				}),
			);
		}

		getStepData()
		{
			return {
				mode: this.#mode,
			};
		}
	}

	module.exports = {
		SettingsStep,
	};
});
