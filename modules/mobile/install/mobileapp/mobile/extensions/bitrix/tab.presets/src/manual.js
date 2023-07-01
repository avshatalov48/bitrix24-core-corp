/**
 * @bxjs_lang_path extension.php
 */
jn.define("tab/presets/editor", (require, exports, module) => {

	const {colors, styles, svg, getSvg} = jn.require('tab/settings/res');
	const {Haptics} = jn.require('haptics');
	const CellType = {
		SECTION: "section",
		ELEMENT: "element",
		ELEMENT_INACTIVE: "inactive_element",
		ELEMENT_UNCHANGEABLE: "unchangeable",
		ELEMENT_DRAG_PLACEHOLDER: "drag_holder",
	}

	const MAX_COUNT_ITEMS = 5
	const CELL_HEIGHT = 60;
	const SECTION_HEADER_HEIGHT = 38;
	const SECTION_FOOTER_HEIGHT = 50;
	const CELL_DRAG_HOLDER_HEIGHT = CELL_HEIGHT + 16

	class PresetEditor extends LayoutComponent
	{
		constructor(props, object)
		{
			super(props);
			this.parseState(props)
			this.lastSectionFailureDrop = null
			object.setRightButtons([{name: BX.message("SETTINGS_TAB_BUTTON_DONE"), callback: () => this.save()}]);
		}

		render()
		{
			return View(
				{style: {backgroundColor: colors.mainBackground}},
				ListView({
					dragInteractionEnabled: true,
					isRefreshing: false,
					ref: ref => this.listRef = ref,
					style: {
						height: "100%",
						paddingTop: 100,
						borderRadius: 12,
						marginBottom: 20,
						backgroundColor: colors.listViewBackground
					},

					data: this.getData(),
					isRefreshing: false,
					onItemDrop: ({from, to}) => this.handleMove(from, to),
					canItemDrop: data => this.canDropItem(data),
					renderSectionHeader: (data, index) => this.renderSection({data, index}),
					renderItem: item => this.renderItem(item),
				})
			)
		}

		renderFooterSection()
		{

			return View({}, View(
					{
						style: {
							backgroundColor: colors.mainBackground,
							height: SECTION_FOOTER_HEIGHT,
						}
					},
					View(
						{
							style: styles.sectionFooter
						}
					)
				)
			)
		}

		renderSection({data, index})
		{
			const renderableSections = ['active', 'inactive', 'bottom', 'desc']
			if (renderableSections.includes(data.id) === false)
			{
				return null
			}

			if (data.id === "bottom")
			{
				return this.renderFooterSection()
			}
			else if (data.id === 'desc')
			{
				return this.renderDesc()
			}

			return View({
					style: {
						backgroundColor: colors.mainBackground,
						height: SECTION_HEADER_HEIGHT,
					}
				},
				View({
						style: {
							height: SECTION_HEADER_HEIGHT,
							flex: 1,
							flexDirection: "row",
							alignItems: "flex-end"
						}
					},
					View(
						{
							style: styles.sectionHeader,
						},
						Text({
							text: data.title,
							style: {color: colors.sectionText, fontWeight: '400', fontSize: 14}
						}),
					))
			)
		}

		renderDesc() {
			return View({
					style: {
						padding: 16,
						justifyContent: "center",
						backgroundColor: colors.mainBackground,
					},
				},

				Text({
					text: BX.message('TAB_PRESET_USER_DESCRIPTION'),
					style: { color: colors.descriptionText, fontWeight: '400', fontSize: 14}
				}),
			);
		}

		renderDragPlaceholder()
		{
			return View({
					style: {
						flexDirection: 'row',
						justifyContent: 'center',

					},
				},
				View({
						style: {
							// opacity: this.inactiveItems.length === 0 ? 0.5 : 0.0,
							justifyContent: 'center',
							alignItems: 'center',
							flexDirection: 'row',
							height: CELL_DRAG_HOLDER_HEIGHT,
							paddingLeft: 50,
							paddingRight: 50,
						}
					},
					Text(
						{
							style: {
								textAlign: "center",
								marginLeft: 10,
								color: '#30000000',
								fontWeight: '400',
								fontSize: 14
							},
							text: BX.message("TAB_PRESET_DROP_INACTIVE")
						})
				),
			);
		}

		renderItem({title, type, id, canBeRemoved})
		{
			if (type === CellType.ELEMENT_DRAG_PLACEHOLDER)
			{
				return this.renderDragPlaceholder()
			}

			const iconTintColor = type === CellType.ELEMENT_UNCHANGEABLE ? colors.unreachableIcon : colors.icon;

			return View(
				{
					onClick: () => {
						if (type === CellType.ELEMENT_UNCHANGEABLE)
						{
							this.notifyCanMoveItem(title)
						}
					},
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
						backgroundColor: "#ffffff",
					},
				},
				View({
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							height: CELL_HEIGHT,
							width: 36,
						}
					},
					type === CellType.ELEMENT_UNCHANGEABLE
						? null
						: Image({
							style: {width: 6, height: 14},
							svg: {
								content: getSvg('drag', '#999999')
							}
						})
				),
				View({
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							height: '100%',
							width: 24
						}
					},
					Image({
						style: {width: 24, height: 24},
						svg: {
							content: getSvg(id, iconTintColor)
						}
					})
				),
				View(
					{
						style:
							{
								flex: 1,
								justifyContent: 'space-between',
								flexDirection: 'row',
								borderBottomWidth: type === CellType.ELEMENT_UNCHANGEABLE ? 0.0 : 0.5,
								borderBottomColor: colors.cellBorder
							},
					},
					Text(
						{
							style:
								{
									marginLeft: 10,
									color: type === CellType.ELEMENT_UNCHANGEABLE ? colors.cellTextUnreachable : colors.cellText,
									fontWeight: '400',
									fontSize: 18
								},
							text: title

						}
					),
				),
			);
		}

		parseState(props)
		{
			this.activeItems = [];
			this.activeItemsUnchangeable = [];
			const activeKeys = Object.keys(props.current)
			this.inactiveItems = Object.keys(props.list)
				.filter(key => activeKeys.includes(key) === false)
				.map(key => {
					return {
						type: CellType.ELEMENT_INACTIVE,
						id: key,
						key,
						title: props.list[key].title,
						canBeRemoved: true,
					}
				})

			Object.keys(props.current)
				.forEach(key => {
					const isUnchangeable = props.current[key]["canChangeSort"] === false
					const canBeRemoved = props.current[key]["canBeRemoved"]
					const data = {
						id: key,
						key,
						title: props.current[key].title,
						canBeRemoved: canBeRemoved,
					}
					if (!isUnchangeable)
					{
						this.activeItems.push({...data, type: CellType.ELEMENT})
					}
					else
					{
						this.activeItemsUnchangeable.push({...data, type: CellType.ELEMENT_UNCHANGEABLE})
					}
				})
		}

		listHeight()
		{
			let items = [] //holder
			items = items.concat(this.activeItems, this.activeItemsUnchangeable, this.inactiveItems)
			return items.length * CELL_HEIGHT + SECTION_FOOTER_HEIGHT + SECTION_HEADER_HEIGHT * 2 + CELL_DRAG_HOLDER_HEIGHT

		}

		getData()
		{
			const bottomBorderSection = {items: [], title: "", dragInteractionEnabled: false, id: "bottom"}
			const sections = [
				{ items: [], id:"desc", dragInteractionEnabled: false },
				{
					items: this.activeItems,
					title: BX.message('TAB_PRESET_ACTIVE_TITLE'),
					id: "active",
					dragInteractionEnabled: true
				},
				{items: this.activeItemsUnchangeable, dragInteractionEnabled: false},
				bottomBorderSection,
				{
					items: this.inactiveItems,
					dragInteractionEnabled: true,
					title: BX.message('TAB_PRESET_INACTIVE_TITLE'),
					id: "inactive"
				},
				bottomBorderSection,
				// {items: [{type: CellType.ELEMENT_DRAG_PLACEHOLDER}], dragInteractionEnabled: false},
			]

			return sections;
		}

		handleMove(from, to)
		{
			this.lastSectionFailureDrop = null
			const sections = this.getData().map(section => section.items)
			sections[to.section].splice(to.index, 0, ...sections[from.section].splice(from.index, 1))
			if (this.listRef && this.activeItems.length >= MAX_COUNT_ITEMS)
			{
				for (let i = this.activeItems.length - 1; i >= 0; i--)
				{
					const item = this.activeItems[i];
					if (item["canBeRemoved"] && to.index !== i)
					{
						this.listRef.moveRow(item, this.inactiveIndex, 0, true).then(() => {
							this.activeItems.splice(i, 1);
							this.inactiveItems.unshift(item)
							Haptics.impactMedium();
						}).catch(e => console.error(e))
						break;
					}
				}

				return;
			}

			Haptics.impactMedium();
		}

		notifyCanMoveItem(title)
		{
			Haptics.notifyFailure();
			setTimeout(() => {
				dialogs.showSnackbar({
					title: BX.message("SETTINGS_TAB_CANT_MOVE").replace("#title#", title),
					id: "cantmove",
					backgroundColor: "#AA333333",
					textColor: "#ffffff",
					hideOnTap: true,
					autoHide: true
				}, () => {
				});
			}, 100)
		}

		canDropItem({item, from, to})
		{
			if (from.section !== to.section)
			{
				if (item.canBeRemoved === false)
				{
					const sectionData = this.getData()[to.section];
					if (this.lastSectionFailureDrop !== to.section && sectionData["dragInteractionEnabled"] !== false)
					{
						this.lastSectionFailureDrop = to.section;
						Haptics.notifyFailure()
						setTimeout(() => {
							dialogs.showSnackbar({
								title: BX.message("SETTINGS_TAB_CANT_BE_HIDDEN").replace("#title#", item.title),
								id: "cantmove",
								backgroundColor: "#AA333333",
								textColor: "#ffffff",
								hideOnTap: true,
								autoHide: true
							}, () => {
							});
						}, 100)
					}

					return false
				}
				else
				{
					const sectionData = this.getData()[to.section];

					if (sectionData.id === "active")
					{
						const activeItemsCount = this.activeItems.length + this.activeItemsUnchangeable.length;
						if (activeItemsCount > MAX_COUNT_ITEMS)
						{
							return false
						}
					}
				}
			}

			return true;
		}

		save()
		{
			const config = {};
			this.activeItems
				.concat(this.activeItemsUnchangeable)
				.forEach((item, index) => config[item.id] = index)

			const TabPresetUtils = jn.require("tab.presets/utils")
			Notify.showIndicatorLoading();
			TabPresetUtils.setUserConfig(config)
				.catch(e => {
					Haptics.notifyFailure()
					Notify.showIndicatorError({hideAfter: 1000, text: BX.message('TAB_PRESET_APPLY_ERROR')});
				})
				.then(result => {
					if (result)
					{
						Haptics.notifySuccess();
						Notify.showIndicatorSuccess({hideAfter: 1000, text: BX.message("SETTINGS_TAB_APPLIED")});
						setTimeout(() => Application.relogin(), 1500);
					}

				})
		}

		get activeIndex() {
			return 0
		}

		get inactiveIndex() {
			return 4
		}
	}

	module.exports = PresetEditor;
})