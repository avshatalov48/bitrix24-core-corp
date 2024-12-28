/**
 * @module tab-presets-new
 */
jn.define('tab-presets-new', (require, exports, module) => {
	const TabPresetsNewUtils = require('tab-presets-new/utils');
	const { PresetInfo } = require('tab-presets-new/preset-info');
	const { PresetManualSettings } = require('tab-presets-new/preset-manual-settings');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { CardBanner } = require('ui-system/blocks/banners/card-banner');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Text3, Text7 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { Alert } = require('alert');
	const { Notify } = require('notify');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Tourist } = require('tourist');

	const ITEM_KEY_MANUAL = 'manual';
	const ITEM_TYPE_MANUAL = 'manual';

	const ITEM_KEY_BANNER = 'banner';
	const ITEM_TYPE_BANNER = 'banner';

	class TabPresetsComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				shouldShowBanner: Tourist.firstTime('show_tab_presets_banner'),
			};

			TabPresetsNewUtils.getPresetGetDataRequestExecutor()
				.setCacheHandler((result) => {
					if (result)
					{
						this.updateState(result, true);
					}
				})
				.call(true)
				.then((result) => this.updateState(result.result, true))
				.catch(() => Notify.alert(Loc.getMessage('TAB_PRESETS_NEW_ERROR'), '', 'OK'))
			;
		}

		componentDidMount()
		{
			this.setUserVisitedTabPresets();
		}

		setUserVisitedTabPresets()
		{
			if (Tourist.firstTime('visited_tab_presets'))
			{
				Tourist.remember('visited_tab_presets')
					.then(() => {
						BX.postComponentEvent('onSetUserCounters', [
							{
								[String(env.siteId)]: { menu_tab_presets: 0 },
							},
						]);
					})
					.catch(console.error)
				;
			}
		}

		updateState(newState, init = false)
		{
			const presets = newState.presets;

			if (init)
			{
				presets.list = TabPresetsNewUtils.getSortedPresets(presets.list, presets.current);
			}

			this.setState({
				presets,
				tabs: newState.tabs,
			});
		}

		render()
		{
			if (!this.state.presets)
			{
				return new LoadingScreenComponent({
					showAirStyle: true,
					testId: `${this.testId}_loading_screen`,
				});
			}

			const { shouldShowBanner, presets, tabs } = this.state;

			return ListView({
				style: {
					flex: 1,
				},
				isRefreshing: false,
				dragInteractionEnabled: false,
				data: [
					{
						items: [
							shouldShowBanner && { key: ITEM_KEY_BANNER, type: ITEM_TYPE_BANNER },
							...Object.keys(presets.list).map((key, index) => ({
								...presets.list[key],
								key,
								type: key,
								isFirst: index === 0,
								isSelected: this.isPresetSelected(key),
							})),
							!this.isPresetSelected(ITEM_KEY_MANUAL) && {
								key: ITEM_KEY_MANUAL,
								type: ITEM_TYPE_MANUAL,
								title: Loc.getMessage('PRESET_MANUAL_SETTINGS_TITLE'),
								tabs: Object.fromEntries(
									Object.entries(tabs.current)
										.sort((a, b) => a.sort - b.sort)
										.map(([tabName, tabData]) => [tabName, tabData.sort])
									,
								),
							},
						].filter(Boolean),
					},
				],
				ref: (ref) => {
					this.listRef = ref;
				},
				renderItem: (item) => this.renderItem(item.key, item),
			});
		}

		renderItem(presetId, preset)
		{
			if (preset.type === ITEM_TYPE_BANNER)
			{
				return this.renderBanner();
			}

			const isSelected = this.isPresetSelected(presetId);
			const isManual = this.isPresetManual(presetId);

			return View(
				{
					style: {
						paddingTop: (preset.isFirst ? Indent.XL.toNumber() : 0),
						paddingBottom: Indent.XL.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				Card(
					{
						style: {
							paddingTop: Indent.XL.toNumber(),
							paddingBottom: Indent.XL3.toNumber(),
						},
						excludePaddingSide: {
							all: true,
						},
						design: CardDesign.PRIMARY,
						hideCross: true,
						border: true,
						accent: isSelected,
						testId: `${this.testId}_preset_${presetId}`,
						onClick: () => {
							if (isManual)
							{
								this.openPresetManualSettings();
							}
							else
							{
								PresetInfo.show({
									presetTitle: preset.title,
									presetImagePath: TabPresetsNewUtils.getPresetInfoImagePath(presetId),
									isPresetSelected: isSelected,
									tabsDescription: preset.tabsDescription,
									tabsIcons: Object.fromEntries(
										Object.keys(preset.tabsDescription).map((tabName) => {
											return [tabName, this.getTabItemData(tabName).iconId];
										}),
									),
									parentWidget: this.props.parentWidget,
									testId: `${this.testId}_preset_${presetId}_info`,
									onPresetSelected: () => this.setPreset(presetId),
								});
							}
						},
					},
					this.renderPresetHeader(presetId, preset),
					this.renderPresetTabs(presetId, preset),
				),
			);
		}

		renderBanner()
		{
			return View(
				{
					style: {
						paddingTop: Indent.XL.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				CardBanner({
					testId: `${this.testId}_banner`,
					title: Loc.getMessage('TAB_PRESETS_NEW_BANNER_TITLE'),
					description: Loc.getMessage('TAB_PRESETS_NEW_BANNER_DESCRIPTION'),
					image: Image({
						resizeMode: 'contain',
						style: {
							width: 64,
							height: 64,
						},
						svg: {
							uri: makeLibraryImagePath('down-menu-presets-settings.svg', 'graphic'),
						},
					}),
					onClose: () => {
						this.listRef?.deleteRowsByKeys(
							[ITEM_KEY_BANNER],
							'automatic',
							async () => {
								await Tourist.remember('show_tab_presets_banner');
								this.setState({ shouldShowBanner: false });
							},
						);
					},
				}),
			);
		}

		renderPresetHeader(presetId, { title })
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingHorizontal: Indent.XL2.toNumber(),
						paddingBottom: Indent.XS.toNumber(),
					},
					testId: `${this.testId}_preset_${presetId}_header`,
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					this.isPresetManual(presetId) && IconView({
						style: {
							marginRight: Indent.XS2.toNumber(),
						},
						icon: Icon.PERSON,
						color: Color.accentMainPrimary,
						size: 20,
					}),
					Text3({
						style: {
							flexShrink: 1,
							borderBottomWidth: 1,
							borderBottomColor: Color.base5.toHex(),
							borderStyle: 'dash',
						},
						testId: `${this.testId}_preset_${presetId}_title`,
						accent: true,
						numberOfLines: 1,
						ellipsize: 'end',
						text: title,
					}),
					!this.isPresetManual(presetId) && IconView({
						style: {
							marginLeft: Indent.XS2.toNumber(),
						},
						testId: `${this.testId}_preset_${presetId}_hint`,
						icon: Icon.QUESTION,
						color: Color.base4,
						size: 20,
					}),
				),
				this.renderPresetButton(presetId),
			);
		}

		renderPresetButton(presetId)
		{
			const isSelected = this.isPresetSelected(presetId);
			const isManual = this.isPresetManual(presetId);

			return ChipButton({
				style: {
					marginLeft: Indent.XL.toNumber(),
				},
				testId: `${this.testId}_preset_${presetId}_button`,
				compact: true,
				design: (isSelected ? ChipButtonDesign.PRIMARY : ChipButtonDesign.BLACK),
				mode: (isSelected ? ChipButtonMode.SOLID : ChipButtonMode.OUTLINE),
				text: (
					isSelected
						? Loc.getMessage(isManual ? 'TAB_PRESETS_NEW_EDIT' : 'TAB_PRESETS_NEW_SELECTED')
						: Loc.getMessage('TAB_PRESETS_NEW_SELECT')
				),
				onClick: () => this.onPresetSelected(presetId),
			});
		}

		renderPresetTabs(presetId, { tabs })
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-evenly',
						marginTop: Indent.XL3.toNumber(),
						marginHorizontal: Indent.M.toNumber(),
					},
					testId: `${this.testId}_preset_${presetId}_tabs`,
				},
				...Object.entries(tabs)
					.sort((a, b) => a[1] - b[1])
					.map((entry, index) => {
						return this.renderPresetTabsItem(entry[0], this.isPresetSelected(presetId), index === 0);
					})
				,
			);
		}

		renderPresetTabsItem(tabName, isSelected = false, isFirst = false)
		{
			const tabItem = this.getTabItemData(tabName);
			if (!tabItem)
			{
				return null;
			}

			const baseColor = (isSelected ? Color.base4 : Color.base5);
			const firstColor = (isSelected ? Color.accentMainPrimary : Color.base1);

			return View(
				{
					style: {
						flexShrink: 1,
						alignItems: 'center',
						marginLeft: (isFirst ? 0 : Indent.XS.toNumber()),
					},
					testId: `${this.testId}_tab_${tabName}`,
				},
				IconView({
					testId: `${this.testId}_tab_${tabName}_icon`,
					icon: TabPresetsNewUtils.getIcon(tabItem.iconId) || TabPresetsNewUtils.getIcon(tabName),
					color: (isFirst ? firstColor : baseColor),
					size: 32,
				}),
				Text7({
					style: {
						marginTop: Indent.XS2.toNumber(),
					},
					testId: `${this.testId}_tab_${tabName}_title`,
					color: (isFirst ? firstColor : baseColor),
					numberOfLines: 1,
					ellipsize: 'end',
					text: tabItem.shortTitle,
				}),
			);
		}

		onPresetSelected(presetId)
		{
			if (this.isPresetManual(presetId))
			{
				this.openPresetManualSettings();

				return;
			}

			if (this.isPresetSelected(presetId))
			{
				Haptics.notifyWarning();

				return;
			}

			Haptics.impactLight();
			Alert.confirm(
				Loc.getMessage('TAB_PRESETS_NEW_CONFIRM_TITLE'),
				Loc.getMessage('TAB_PRESETS_NEW_CONFIRM_DESCRIPTION'),
				[
					{
						text: Loc.getMessage('TAB_PRESETS_NEW_CONFIRM_CLOSE'),
						onPress: () => {},
					},
					{
						text: Loc.getMessage('TAB_PRESETS_NEW_CONFIRM_ACCEPT'),
						onPress: () => this.setPreset(presetId),
					},
				],
			);
		}

		openPresetManualSettings()
		{
			PresetManualSettings.show({
				parentWidget: this.props.parentWidget,
				tabs: this.state.tabs,
			});
		}

		setPreset(presetId)
		{
			Haptics.impactLight();
			void Notify.showIndicatorLoading();

			TabPresetsNewUtils.setCurrentPreset(presetId)
				.then(() => {
					TabPresetsNewUtils.changeCurrentPreset(presetId);

					this.updateState({
						...this.state,
						presets: {
							...this.state.presets,
							current: presetId,
						},
					});

					Haptics.notifySuccess();
					void Notify.showIndicatorSuccess({ hideAfter: 1000 });

					setTimeout(() => Application.relogin(), 1500);
				})
				.catch(() => {
					Haptics.notifyFailure();
					void Notify.showIndicatorError({
						hideAfter: 2000,
						text: Loc.getMessage('TAB_PRESETS_NEW_APPLY_ERROR'),
					});
				})
			;
		}

		isPresetSelected(presetId)
		{
			return presetId === this.state.presets.current;
		}

		isPresetManual(presetId)
		{
			return presetId === ITEM_KEY_MANUAL;
		}

		getTabItemData(tabName)
		{
			return this.state.tabs.list[tabName];
		}

		get testId()
		{
			return 'tab_presets';
		}
	}

	module.exports = { TabPresetsComponent };
});
