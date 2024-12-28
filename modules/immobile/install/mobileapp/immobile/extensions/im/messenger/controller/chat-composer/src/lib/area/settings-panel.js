/**
 * @module im/messenger/controller/chat-composer/lib/area/settings-panel
 */
jn.define('im/messenger/controller/chat-composer/lib/area/settings-panel', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { StageSelector } = require('ui-system/blocks/stage-selector');

	/**
	 * @class SettingsPanel
	 * @typedef {LayoutComponent<SettingsPanelProps, {}>} SettingsPanel
	 */
	class SettingsPanel extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				Card(
					{
						testId: 'settings-panel',
						design: CardDesign.PRIMARY,
						hideCross: true,
						border: true,
						excludePaddingSide: {
							all: true,
						},
					},
					...this.getActionList(),
				),
			);
		}

		getActionList()
		{
			return this.props.actionList.map((action) => {
				return View(
					{},
					StageSelector({
						testId: action.testId,
						cardBorder: false,
						rightIcon: action.icon,
						title: action.title,
						subtitle: action.subtitle,
						onClick: () => {
							action.onClick();
						},
					}),
					action.divider
						? View({
							style: {
								backgroundColor: Color.bgSeparatorSecondary.toHex(),
								height: 1,
								width: '100%',
							},
						})
						: null
					,
				);
			});
		}
	}

	module.exports = { SettingsPanel };
});
