/**
 * @module settings-v2/ui/items/src/cache-info
 */

jn.define('settings-v2/ui/items/src/cache-info', (require, exports, module) => {
	const { SettingSelector, SettingMode } = require('ui-system/blocks/setting-selector');
	const { createTestIdGenerator } = require('utils/test');
	const { EventType } = require('settings-v2/const');
	const { formatFileSize } = require('utils/file');
	const { Loc } = require('loc');
	const { Color } = require('tokens');

	class CacheInfoItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-info-item',
				context: this,
			});

			this.state = {
				value: props.value,
			};
		}

		getText()
		{
			const { modeText } = this.props;
			if (modeText)
			{
				return modeText;
			}
			const GB_SIZE = 1024 * 1024 * 1024;
			const precision = this.state.value > GB_SIZE ? 2 : 0;

			return (
				this.state.value
					? Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_CACHE_INFO_CLEAR_TEXT', {
						'#SIZE#': formatFileSize(this.state.value, precision),
					})
					: formatFileSize(this.state.value, precision)
			);
		}

		componentDidMount()
		{
			BX.addCustomEvent(EventType.changeCacheSize, this.changeInfoText);
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent(EventType.changeCacheSize, this.changeInfoText);
		}

		changeInfoText = (text) => {
			this.props.controller?.get()
				.then((value) => {
					this.setState({
						value,
					});
				}).catch((e) => {
					console.error(e);
				})
			;
		};

		render()
		{
			const { id } = this.props;

			return SettingSelector({
				...this.props,
				testId: this.getTestId(id),
				mode: SettingMode.PARAMETER,
				modeParams: {
					chevron: false,
					text: this.getText(),
					color: Color.accentMainLink,
				},
			});
		}
	}

	module.exports = {
		CacheInfoItem,
	};
});
