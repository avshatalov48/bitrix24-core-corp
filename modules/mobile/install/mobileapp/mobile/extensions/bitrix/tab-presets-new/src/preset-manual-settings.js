/**
 * @module tab-presets-new/preset-manual-settings
 */
jn.define('tab-presets-new/preset-manual-settings', (require, exports, module) => {
	const TabPresetsNewUtils = require('tab-presets-new/utils');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { CardBanner } = require('ui-system/blocks/banners/card-banner');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { H5 } = require('ui-system/typography/heading');
	const { Text5 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Loc } = require('loc');
	const { Notify } = require('notify');
	const { Haptics } = require('haptics');
	const { BottomSheet } = require('bottom-sheet');
	const { Tourist } = require('tourist');

	const ITEM_TYPE_BANNER = 'banner';
	const ITEM_TYPE_ACTIVE = 'active';
	const ITEM_TYPE_INACTIVE = 'inactive';
	const ITEM_TYPE_STATIC = 'static';

	const SECTION_HEADER_HEIGHT = 45;

	const isIOS = Application.getPlatform() === 'ios';

	class PresetManualSettings extends LayoutComponent
	{
		static show({ parentWidget, tabs })
		{
			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('PRESET_MANUAL_SETTINGS_TITLE'),
					type: 'dialog',
				},
				component: (layout) => new PresetManualSettings({
					tabs,
					parentWidget: layout,
				}),
			})
				.setParentWidget(parentWidget)
				.alwaysOnTop()
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.open()
			;
		}

		constructor(props)
		{
			super(props);

			this.refsMap = new Map();

			this.state = {
				shouldShowBanner: Tourist.firstTime('show_preset_manual_settings_banner'),
			};

			this.parseTabs(props.tabs);
		}

		parseTabs({ current, list })
		{
			const activeItemKeys = Object.keys(current);
			const inactiveItemKeys = Object.keys(list).filter((key) => !activeItemKeys.includes(key));

			this.initialActiveItems = activeItemKeys;
			this.activeItems = activeItemKeys.map((key) => ({
				...current[key],
				...list[key],
				key,
				type: current[key].canChangeSort ? ITEM_TYPE_ACTIVE : ITEM_TYPE_STATIC,
				showDragIcon: current[key].canChangeSort,
				showBorderBottom: current[key].canChangeSort,
			}));
			this.inactiveItems = inactiveItemKeys.map((key, index) => ({
				...list[key],
				key,
				type: ITEM_TYPE_INACTIVE,
				canBeRemoved: true,
				showBorderBottom: true,
			}));
		}

		render()
		{
			const { shouldShowBanner } = this.state;

			return Box(
				{
					safeArea: {
						bottom: true,
					},
					footer: BoxFooter(
						{},
						Button({
							testId: `${this.testId}_button_save`,
							stretched: true,
							size: ButtonSize.L,
							text: Loc.getMessage('PRESET_MANUAL_SETTINGS_BUTTON_SAVE'),
							onClick: () => this.save(),
						}),
					),
					backgroundColor: Color.bgContentPrimary,
				},
				ListView({
					style: {
						flex: 1,
					},
					isRefreshing: false,
					dragInteractionEnabled: true,
					data: [
						{
							items: [shouldShowBanner && { key: 'banner', type: ITEM_TYPE_BANNER }].filter(Boolean),
							dragInteractionEnabled: false,
							fixedHeight: 0,
						},
						{
							title: Loc.getMessage('PRESET_MANUAL_SETTINGS_SECTION_MENU_TITLE'),
							items: this.activeItems.filter((item) => item.type !== ITEM_TYPE_STATIC),
							dragInteractionEnabled: true,
							fixedHeight: SECTION_HEADER_HEIGHT,
						},
						{
							items: this.activeItems.filter((item) => item.type === ITEM_TYPE_STATIC),
							dragInteractionEnabled: false,
							fixedHeight: 0,
						},
						{
							title: Loc.getMessage('PRESET_MANUAL_SETTINGS_SECTION_MORE_TITLE'),
							items: this.inactiveItems,
							dragInteractionEnabled: false,
							fixedHeight: SECTION_HEADER_HEIGHT,
						},
					],
					ref: (ref) => {
						this.listRef = ref;
					},
					onItemDrop: ({ from, to }) => this.handleMove(from, to),
					renderSectionHeader: (section) => this.renderSectionHeader(section),
					renderItem: (item) => this.renderItem(item),
				}),
				View({
					style: {
						height: 1,
						marginTop: -2,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				}),
			);
		}

		save()
		{
			const config = Object.fromEntries(this.activeItems.map((item, index) => [item.key, index]));

			if (!this.isConfigChanged(config))
			{
				Haptics.impactLight();
				this.props.parentWidget.close();

				return;
			}

			void Notify.showIndicatorLoading();

			TabPresetsNewUtils.setUserConfig(config)
				.then((result) => {
					if (result)
					{
						TabPresetsNewUtils.changeCurrentPreset('manual', {
							tabs: config,
							title: Loc.getMessage('PRESET_MANUAL_SETTINGS_TITLE'),
						});

						void Notify.showIndicatorSuccess({ hideAfter: 1000 });
						Haptics.notifySuccess();
						setTimeout(() => Application.relogin(), 1000);
					}
				})
				.catch(() => {
					void Notify.showIndicatorError({
						hideAfter: 1000,
						text: Loc.getMessage('TAB_PRESETS_NEW_APPLY_ERROR'),
					});
					Haptics.notifyFailure();
				})
			;
		}

		isConfigChanged(config)
		{
			const currentActiveItems = Object.keys(config);

			return (
				currentActiveItems.length !== this.initialActiveItems.length
				|| !currentActiveItems.every((tab, index) => tab === this.initialActiveItems[index])
			);
		}

		renderSectionHeader(section)
		{
			if (!section.title)
			{
				return null;
			}

			return View(
				{
					style: {
						height: SECTION_HEADER_HEIGHT,
						justifyContent: 'center',
						paddingTop: Indent.L.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				H5({
					text: section.title,
				}),
			);
		}

		renderItem(item)
		{
			if (item.type === ITEM_TYPE_BANNER)
			{
				return this.renderBanner();
			}

			return View(
				{
					ref: (ref) => this.refsMap.set(item.key, ref),
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginLeft: Indent.XL.toNumber(),
							marginRight: Indent.XL3.toNumber(),
							paddingVertical: Indent.M.toNumber(),
							paddingRight: Indent.XL.toNumber(),
						},
						testId: `${this.testId}_item_${item.key}`,
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						IconView({
							style: {
								marginRight: Indent.XS2.toNumber(),
							},
							testId: `${this.testId}_item_${item.key}_drag`,
							icon: Icon.DRAG,
							size: 24,
							color: (item.showDragIcon ? Color.base4 : Color.bgContentPrimary),
						}),
						IconView({
							style: {
								marginRight: Indent.XS2.toNumber(),
							},
							testId: `${this.testId}_item_${item.key}_icon`,
							icon: TabPresetsNewUtils.getIcon(item.iconId) || TabPresetsNewUtils.getIcon(item.key),
							size: 32,
							color: Color.base1,
						}),
						Text5({
							testId: `${this.testId}_item_${item.key}_text`,
							text: item.shortTitle,
						}),
					),
					item.type !== ITEM_TYPE_STATIC && Button({
						testId: `${this.testId}_item_${item.key}_button`,
						size: ButtonSize.XS,
						disabled: !item.canBeRemoved,
						design: (
							item.type === ITEM_TYPE_ACTIVE
								? ButtonDesign.OUTLINE
								: ButtonDesign.OUTLINE_ACCENT_2
						),
						text: (
							item.type === ITEM_TYPE_ACTIVE
								? Loc.getMessage('PRESET_MANUAL_SETTINGS_BUTTON_HIDE')
								: Loc.getMessage('PRESET_MANUAL_SETTINGS_BUTTON_SHOW')
						),
						onClick: this.onButtonClick.bind(this, item),
					}),
				),
				item.showBorderBottom && View({
					style: {
						height: 1,
						marginHorizontal: Indent.XL3.toNumber(),
						backgroundColor: Color.base7.toHex(),
					},
				}),
			);
		}

		renderBanner()
		{
			return View(
				{
					style: {
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				CardBanner({
					testId: `${this.testId}_banner`,
					title: Loc.getMessage('PRESET_MANUAL_SETTINGS_BANNER_TITLE'),
					description: Loc.getMessage('PRESET_MANUAL_SETTINGS_BANNER_DESCRIPTION'),
					image: Image({
						resizeMode: 'contain',
						style: {
							width: 64,
							height: 64,
						},
						svg: {
							uri: makeLibraryImagePath('down-menu-custom-preset-settings.svg', 'graphic'),
						},
					}),
					onClose: () => {
						this.listRef?.deleteRowsByKeys(
							['banner'],
							'automatic',
							async () => {
								await Tourist.remember('show_preset_manual_settings_banner');
								this.setState({ shouldShowBanner: false });
							},
						);
					},
				}),
			);
		}

		async onButtonClick(item)
		{
			if (this.isUIBlocked)
			{
				return;
			}

			this.isUIBlocked = true;

			if (item.type === ITEM_TYPE_ACTIVE)
			{
				await this.hideItem(item);
			}
			else if (this.activeItems.length === 5)
			{
				await this.showItem(item, 3);
				await this.hideItem((this.activeItems[4].canBeRemoved ? this.activeItems[4] : this.activeItems[2]));
			}
			else
			{
				await this.showItem(item);
			}

			this.isUIBlocked = false;
		}

		async hideItem(item)
		{
			const newItem = {
				...item,
				type: ITEM_TYPE_INACTIVE,
				showDragIcon: false,
			};

			this.activeItems = this.activeItems.filter((i) => i.key !== item.key);
			this.inactiveItems = [newItem, ...this.inactiveItems];

			void this.listRef?.updateRows([newItem], (isIOS ? 'none' : 'automatic'));
			await this.listRef?.moveRow(newItem, this.inactiveSectionIndex, 0, true);
		}

		async showItem(item, indexToShow = this.activeItems.length - 1)
		{
			const newItem = {
				...item,
				type: ITEM_TYPE_ACTIVE,
				showDragIcon: true,
			};

			this.activeItems.splice(indexToShow, 0, newItem);
			this.inactiveItems = this.inactiveItems.filter((i) => i.key !== item.key);

			void this.listRef?.updateRows([newItem], (isIOS ? 'none' : 'automatic'));
			await this.listRef?.moveRow(newItem, this.activeSectionIndex, indexToShow, true, true);
		}

		handleMove(from, to)
		{
			const indexFrom = from.index;
			const indexTo = to.index;

			if (indexFrom === indexTo)
			{
				return;
			}

			const item = this.activeItems[indexFrom];

			this.activeItems.splice(indexFrom, 1);
			this.activeItems.splice(indexTo, 0, item);

			Haptics.impactMedium();
		}

		get activeSectionIndex()
		{
			return 1;
		}

		get inactiveSectionIndex()
		{
			return 3;
		}

		get testId()
		{
			return 'preset_manual_settings';
		}
	}

	module.exports = { PresetManualSettings };
});
