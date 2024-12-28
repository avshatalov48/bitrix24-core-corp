/*
global Notify
 */

jn.define('tab.presets', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { chain, transition } = require('animation');
	const res = require('tab/settings/res');
	const getIcon = res.getIcon;
	const TabPresetUtils = require('tab.presets/utils');
	const Editor = require('tab/presets/editor');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Tourist } = require('tourist');

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
					showError(Loc.getMessage('TAB_PRESET_ERROR'), this.shownCache, () => {
						// widget.back()
					});
				});
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
			const { Alert } = require('alert');
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
							text: Loc.getMessage('TAB_PRESET_APPLY_ERROR'),
						});
					});
			};

			Alert.confirm(
				Loc.getMessage('TAB_PRESET_CONFIRM_TITLE'),
				Loc.getMessage('TAB_PRESET_CONFIRM_DESC').replace('#title#', title),
				[
					{
						// type: ButtonType.DESTRUCTIVE,
						text: Loc.getMessage('TAB_PRESET_CONFIRM_CLOSE'),
						onPress: () => {},
					},
					{
						text: Loc.getMessage('TAB_PRESET_CONFIRM_ACCEPT'),
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
				{ style: { backgroundColor: Color.bgContentPrimary.toHex() } },
				View(
					{ style: { padding: 10, backgroundColor: Color.bgContentPrimary.toHex() } },
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
							backgroundColor: Color.bgContentSecondary,
							alignItems: 'center',
							borderWidth: 0.5,
							borderColor: Color.base6.toHex(),
							justifyContent: 'space-between',
							flexDirection: 'row',
							paddingLeft: 18,
							paddingRight: 18,

						},
					onClick: () => this.openPresetEditor(),

				},
				Text({
					style: { color: AppTheme.colors.base2, fontWeight: '600', fontSize: 18 },
					text: Loc.getMessage('TAB_PRESET_USER_PRESET'),
				}),
				IconView({
					color: Color.base2,
					size: 24,
					icon: Icon.CHEVRON_TO_THE_RIGHT,
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
		}

		renderActiveStatus()
		{
			return View(
				{ style: res.styles.activeStatus },
				Text({
					text: Loc.getMessage('TAB_PRESET_CURRENT_LABEL'),
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
				if (!tabsDesc?.[code])
				{
					return null;
				}

				const title = tabsDesc?.[code]?.shortTitle ?? '';
				const color = index === 0 ? Color.base1 : Color.base4;
				const iconId = tabsDesc?.[code]?.iconId ?? code;
				const icon = getIcon(iconId) || getIcon(code);
				const iconNode = this.getIconView({
					icon,
					color,
					animated: index === 0 && active,
				});

				return View(
					{
						style: {
							width: 220,
							paddingTop: 12,
							zIndex: 10000,
							justifyContent: 'space-between',
							alignItems: 'center',
						},
					},
					iconNode,
					Text({
						style: {
							fontSize: 11,
							fontWeight: '400',
							color: index === 0 ? AppTheme.colors.base1 : AppTheme.colors.base4,
						},
						text: title,
					}),
				);
			});
		}

		async openPresetEditor()
		{
			const layout = await PageManager.openWidget('layout', {
				titleParams: {
					text: Loc.getMessage('TAB_PRESET_USER_PRESET'),
					useLargeTitleMode: true,
				},
			});
			layout.showComponent(new Editor(this.state.tabs, layout));
		}

		getIconView({ icon, color = Color.base4, animated = false })
		{
			return IconView({
				forwardRef: (ref) => {
					if (animated)
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
				size: 30,
				iconColor: color,
				icon,
			});
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
			}, () => {});
		}
		else
		{
			Notify.alert(text, '', 'OK', callback);
		}
	};

	module.exports = { TabPresetsComponent };
});
