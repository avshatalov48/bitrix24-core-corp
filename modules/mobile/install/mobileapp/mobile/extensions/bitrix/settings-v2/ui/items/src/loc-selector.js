/**
 * @module settings-v2/ui/items/src/loc-selector
 */
jn.define('settings-v2/ui/items/src/loc-selector', (require, exports, module) => {
	const { SettingSelector, SettingMode } = require('ui-system/blocks/setting-selector');
	const { createTestIdGenerator } = require('utils/test');
	const { Loc } = require('loc');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { NativeLocService } = require('settings-v2/services/native');

	class LocSelectorItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-loc-selector-item',
				context: this,
			});

			this.layoutWidget = this.props.layoutWidget ?? PageManager;
			this.selectWidget = null;

			this.state = {
				currentLocOption: props.value,
				currentLocLabel: '',
			};
		}

		get controller()
		{
			return this.props.controller;
		}

		componentDidMount()
		{
			void this.loadLocOptions();
		}

		async loadLocOptions()
		{
			this.locLabels = await NativeLocService.getOptionLabels();
			this.locOptions = await NativeLocService.getOptions();

			this.setState(
				{
					currentLocLabel: this.locLabels[this.state.currentLocOption],
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
					text: this.state.currentLocLabel,
				},
				onClick: this.openSelectWidget,
			});
		}

		openSelectWidget = async () => {
			const menu = new ContextMenu({
				actions: this.getPreparedItems(),
			});
			menu.setSelectedActions([this.state.currentLocOption], true);

			const title = Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_LOC_SELECTOR_TITLE');

			this.selectWidget = await this.layoutWidget.openWidget('layout', {
				titleParams: {
					text: title,
					type: 'dialog',
				},
			});
			this.selectWidget.showComponent(menu);
		};

		getPreparedItems()
		{
			const defaultIndex = this.locOptions.indexOf('default');
			if (defaultIndex > 0)
			{
				const [defaultItem] = this.locOptions.splice(defaultIndex, 1);
				this.locOptions.unshift(defaultItem);
			}

			return this.locOptions.map((option) => {
				return {
					id: option,
					title: this.locLabels[option],
					sectionCode: 'default',
					onClickCallback: () => {
						this.setOption(option);
					},
				};
			});
		}

		setOption = (option) => {
			if (option === this.state.currentLocOption && this.selectWidget)
			{
				this.selectWidget.back();
				this.selectWidget = null;
			}
			else
			{
				this.onChange(option);
			}
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
		LocSelectorItem,
	};
});
