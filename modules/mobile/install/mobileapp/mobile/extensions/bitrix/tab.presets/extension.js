jn.define('tab.presets', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { chain, transition } = require('animation');
	const res = require('tab/settings/res');
	const getSvg = res.getSvg;
	const TabPresetUtils = require('tab.presets/utils');
	const Editor = require('tab/presets/editor');

	class TabPresetsComponent extends LayoutComponent
	{
		constructor(props, widget)
		{
			super(props);
			this.refs = {};
			this.state = {};
			this.shownCache = false;
			TabPresetUtils.presetLoader()
				.setCacheHandler((result) => {
					if (result)
					{
						this.shownCache = true;
						this.updateState(result, true);
					}
				})
				.call(true)
				.then((result) => this.updateState(result.result, true))
				.catch((e) => {
					showError(BX.message('TAB_PRESET_ERROR'), this.shownCache, () => {
						// widget.back()
					});
				});
		}

		updateState(state, init = false)
		{
			if (init)
			{
				state.presets.list = TabPresetUtils.getSortedPresets(state.presets.list, state.presets.current);
			}
			this.setState(state);
		}

		onPresetSelected({ key, title })
		{
			const state = this.state;
			if (state.presets.current === key)
			{
				Haptics.notifyWarning();

				return;
			}
			Haptics.impactLight();
			const { Alert, ButtonType } = require('alert');
			const onAccept = () => {
				Haptics.impactLight();
				state.presets.aboutCurrent = key;
				this.updateState(state);

				Notify.showIndicatorLoading();
				TabPresetUtils.setCurrentPreset(key)
					.then((result) => {
						TabPresetUtils.changeCurrentPreset(key);
						const state = this.state;
						state.presets.aboutCurrent = undefined;
						state.presets.current = key;
						this.updateState(state);
						Haptics.notifySuccess();
						Notify.showIndicatorSuccess({ hideAfter: 1000 });
						setTimeout(() => Application.relogin(), 1500);
					})
					.catch((e) => {
						Haptics.notifyFailure();
						Notify.showIndicatorError({
							hideAfter: 1000,
							text: BX.message('TAB_PRESET_APPLY_ERROR'),
						});
					});
			};

			Alert.confirm(
				BX.message('TAB_PRESET_CONFIRM_TITLE'),
				BX.message('TAB_PRESET_CONFIRM_DESC').replace('#title#', title),
				[
					{
						// type: ButtonType.DESTRUCTIVE,
						text: BX.message('TAB_PRESET_CONFIRM_CLOSE'),
						onPress: () => {
						},
					},
					{
						text: BX.message('TAB_PRESET_CONFIRM_ACCEPT'),
						onPress: onAccept,
					},
				],
			);
		}

		render()
		{
			if (typeof this.state.presets === 'undefined')
			{
				return this.renderLoading();
			}

			const { presets } = this.state;

			return ScrollView(
				{ style: { backgroundColor: AppTheme.colors.bgContentPrimary } },
				View(
					{ style: { padding: 10, backgroundColor: AppTheme.colors.bgContentPrimary } },
					...this.renderList(presets),
					this.renderManualPresetButton(),
				),
			);
		}

		renderManualPresetButton()
		{
			return View(
				{
					style:
						{
							height: 66,
							marginLeft: 6,
							marginTop: 10,
							marginRight: 6,
							borderRadius: 12,
							backgroundColor: AppTheme.colors.bgNavigation,
							alignItems: 'center',
							justifyContent: 'space-between',
							flexDirection: 'row',
							paddingLeft: 18,
							paddingRight: 18,

						},
					onClick: () => this.openPresetEditor(),

				},
				Text({
					style: { color: AppTheme.colors.base2, fontWeight: '600', fontSize: 18 },
					text: BX.message('TAB_PRESET_USER_PRESET'),
				}),
				Image({
					style: { color: AppTheme.colors.base2, width: 10, height: 16 },
					svg: { content: getSvg('chevron') },
				}),
			);
		}

		renderList(presets)
		{
			const { list, current } = presets;
			const views = [];
			Object.keys(list)
				.forEach((preset) => views.push(this.renderItem(preset, list[preset], current === preset)));

			return views;
		}

		renderItem(key, data, active = false)
		{
			const blockStyle = active ? res.styles.presetBlockActive : res.styles.presetBlockNonActive;

			return View(
				{
					onClick: () => this.onPresetSelected({ key, title: data.title }),
					style: { justifyContent: 'space-around' },
				},
				(blockStyle.check ? this.renderActiveStatus() : null),
				View(
					{
						style: res.styles.presetBlockShadow,
					},
					View(
						{ style: { clickable: false, ...blockStyle } },
						this.renderTitle(data.title),
						this.renderTabPreview(data.tabs, active),
					),
				),
			);
		}

		renderTitle(text)
		{
			return View(
				{
					style: {
						paddingLeft: 12,
						height: 50,
						clickable: false,
						justifyContent: 'space-around',
					},
				},
				Text({
					style: res.styles.presetTitle,
					text,
				}),
			);
		}

		renderTabPreview(data, active = false)
		{
			const tabs = this.tabList(Object.keys(data), active);

			return View({
				style: res.styles.tabBarPreview,
			}, ...tabs);

			return View(
				{
					style: {
						clickable: false,
						height: 70,
						flexGrow: 1,
					},
				},
				View({
					style: res.styles.tabBarPreview,
				}, ...tabs),
			);
		}

		renderActiveStatus()
		{
			return View(
				{ style: res.styles.activeStatus },
				Text({
					text: BX.message('TAB_PRESET_CURRENT_LABEL'),
					style: {
						fontSize: 9,
						color: AppTheme.colors.baseWhiteFixed,
						lineHeightMultiple: 1.1,
						fontWeight: '700',
					},
				}),
			);
		}

		renderLoading()
		{
			return View({}, Loader({
				tintColor: AppTheme.colors.base4,
				animating: true,
				size: 'small',
				style: { width: '100%', height: '100%' },
			}));
		}

		tabList(tabs, active = false)
		{
			const tabsDesc = this.state.tabs.list;

			return tabs.map((code, index) => {
				const title = tabsDesc?.[code]?.shortTitle ?? '';

				return View({
					style: {
						paddingTop: 12,
						zIndex: 10000,
						justifyContent: 'space-between',
						alignItems: 'center',
					},
				}, Image({
					ref: (ref) => {
						if (index === 0 && active)
						{
							setInterval(() => {
								chain(
									transition(ref, { bottom: -4, duration: 200, option: 'easeIn' }),
									transition(ref, { bottom: 12, duration: 150, option: 'easeInOut' }),
									transition(ref, { bottom: 0, duration: 100, option: 'easeIn' }),
									transition(ref, { bottom: 6, duration: 100, option: 'easeIn' }),
									transition(ref, { bottom: 0, duration: 50, option: 'easeIn' }),
									transition(ref, { rotate: 10, duration: 100, option: 'easeInOut' }),
									transition(ref, { rotate: -10, duration: 100, option: 'easeInOut' }),
									transition(ref, { rotate: 5, duration: 100, option: 'easeInOut' }),
									transition(ref, { rotate: -5, duration: 100, option: 'easeInOut' }),
									transition(ref, { rotate: 0, duration: 100, option: 'easeInOut' }),
								)();
							}, 2500);
						}
					},
					style: {
						width: 30,
						height: 30,
					},
					svg: {
						content: getSvg(code, index === 0 ? AppTheme.colors.accentMainPrimary : AppTheme.colors.base6),
					},
				}), Text({
					style: {
						fontSize: 11,
						fontWeight: '400',
						color: index === 0 ? AppTheme.colors.base1 : AppTheme.colors.base4,
					},
					text: title,
				}));
			});
		}

		async openPresetEditor()
		{
			const layout = await PageManager.openWidget('layout', {
				titleParams: {
					text: BX.message('TAB_PRESET_USER_PRESET'),
					useLargeTitleMode: true,
				},
			});
			layout.showComponent(new Editor(this.state.tabs, layout));
		}
	}

	const showError = (text, bottom = false, callback) => {
		if (bottom === true)
		{
			dialogs.showSnackbar({
				title: text,
				id: 'error',
				backgroundColor: AppTheme.colors.accentSoftElementRed1,
				textColor: AppTheme.colors.baseWhiteFixed,
				hideOnTap: true,
				autoHide: true,
			}, () => {
			});
		}
		else
		{
			Notify.alert(text, '', 'OK', callback);
		}
	};

	module.exports = { TabPresetsComponent };
});
