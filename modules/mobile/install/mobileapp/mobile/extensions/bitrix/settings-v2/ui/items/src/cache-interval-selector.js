/**
 * @module settings-v2/ui/items/src/cache-interval-selector
 */
jn.define('settings-v2/ui/items/src/cache-interval-selector', (require, exports, module) => {
	const { SettingSelector, SettingMode } = require('ui-system/blocks/setting-selector');
	const { createTestIdGenerator } = require('utils/test');
	const { NativeCacheService } = require('settings-v2/services/native');
	const { UIMenu } = require('layout/ui/menu');

	class CacheIntervalSelectorItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-cache-interval-selector-item',
				context: this,
			});

			this.selectorRef = null;

			this.labels = null;
			this.options = null;

			this.state = {
				currentOption: null,
			};
		}

		get controller()
		{
			return this.props.controller;
		}

		getCurrentLabel()
		{
			return this.state.currentOption ? this.labels[this.state.currentOption] ?? '' : '';
		}

		componentDidMount()
		{
			void this.loadOptions();
		}

		async loadOptions()
		{
			this.labels = await NativeCacheService.getCacheIntervalOptionLabels();
			this.options = await NativeCacheService.getCacheIntervalOptions();

			this.setState(
				{
					currentOption: this.props.value,
				},
			);
		}

		render()
		{
			const { id } = this.props;

			return SettingSelector({
				...this.props,
				testId: this.getTestId(id),
				mode: SettingMode.PARAMETER,
				modeParams: {
					chevron: true,
					text: this.getCurrentLabel(),
					iconRef: (ref) => {
						this.selectorRef = ref;
					},
				},
				onClick: this.openSelectWidget,
			});
		}

		openSelectWidget = async () => {
			this.menu = new UIMenu(this.getPreparedItems());
			this.menu.show({ target: this.selectorRef });
		};

		getPreparedItems()
		{
			return this.options.map((option) => {
				if (option === 'hour' || option === 'day')
				{
					return null;
				}

				return {
					id: option,
					title: this.labels[option],
					sectionCode: 'code',
					onItemSelected: () => {
						this.setOption(option);
					},
				};
			}).filter(Boolean);
		}

		setOption = (option) => {
			this.setState(
				{
					currentOption: option,
				},
				() => this.onChange(option),
			);
		};

		onChange = (value) => {
			const { id, controller, onChange } = this.props;

			if (onChange)
			{
				onChange(id, controller, value);
			}
		};
	}

	module.exports = {
		CacheIntervalSelectorItem,
	};
});
