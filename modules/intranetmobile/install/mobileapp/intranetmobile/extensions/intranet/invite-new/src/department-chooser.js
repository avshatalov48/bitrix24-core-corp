/**
 * @module intranet/invite-new/src/department-chooser
 */
jn.define('intranet/invite-new/src/department-chooser', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Link3, LinkMode, Ellipsize } = require('ui-system/blocks/link');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text3 } = require('ui-system/typography/text');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { UIMenu, UIMenuPosition } = require('layout/ui/menu');
	const { Icon } = require('ui-system/blocks/icon');
	const { Color, Component, Indent } = require('tokens');
	const { DepartmentSelector } = require('selector/widget/entity/intranet/department');
	const { CreateDepartment } = require('intranet/create-department');
	const { Loc } = require('loc');
	const { Type } = require('type');

	class DepartmentChooser extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				department: props.department ?? null,
			};
		}

		componentWillReceiveProps(props)
		{
			this.#setDepartment(props.department ?? null);
		}

		render()
		{
			const isDepartmentSelected = this.#isDepartmentSelected();

			return Card(
				{
					testId: `${this.testId}-department-card`,
					border: false,
					style: {
						paddingVertical: Component.cardPaddingB.toNumber(),
						paddingHorizontal: Component.cardPaddingLr.toNumber(),
					},
					design: this.#isDepartmentSelected() ? CardDesign.ACCENT : CardDesign.SECONDARY,
				},
				Text3({
					testId: `${this.testId}-department-card-text`,
					text: isDepartmentSelected
						? Loc.getMessage('INTRANET_DEPARTMENT_CARD_SELECTED_DEPARTMENT_TEXT')
						: Loc.getMessage('INTRANET_DEPARTMENT_CARD_TEXT'),
					color: Color.base1,
					numberOfLines: 0,
					ellipsize: 'end',
					style: {
						paddingHorizontal: Indent.L.toNumber(),
						marginBottom: Indent.S.toNumber(),
						textAlign: 'center',
					},
				}),
				View(
					{
						style: {
							height: 34,
							width: '100%',
							justifyContent: 'center',
							alignItems: 'center',
							paddingHorizontal: Indent.XL4.toNumber(),
						},
					},
					View(
						{
							ref: this.bindDepartmentChooserRef,
						},
						isDepartmentSelected && this.#renderChooseDepartmentChipButton(),
						!isDepartmentSelected && this.#renderChooseDepartmentLink(),
					),
				),
			);
		}

		bindDepartmentChooserRef = (ref) => {
			this.departmentChooser = ref;
		};

		#isDepartmentSelected()
		{
			return this.state.department !== null;
		}

		#getSelectedDepartmentName()
		{
			return this.state.department?.title ?? '';
		}

		chooseDepartmentLinkClickHandler = () => {
			this.openDepartmentContextMenu();
		};

		openDepartmentContextMenu()
		{
			this.menu = new UIMenu(this.getDepartmentMenuItems());
			this.menu.show({
				target: this.departmentChooser,
				position: UIMenuPosition.TOP,
			});
		}

		#renderChooseDepartmentChipButton()
		{
			return ChipButton({
				testId: `${this.testId}-choose-department-chip-button`,
				compact: false,
				color: Color.accentMainPrimary,
				mode: ChipButtonMode.OUTLINE,
				design: ChipButtonDesign.PRIMARY,
				text: this.#getSelectedDepartmentName(),
				dropdown: true,
				backgroundColor: Color.bgPrimary,
				onClick: () => {
					this.openDepartmentContextMenu();
				},
			});
		}

		#renderChooseDepartmentLink()
		{
			return Link3({
				testId: `${this.testId}-department-card-link`,
				text: Loc.getMessage('INTRANET_DEPARTMENT_CHOOSE_LINK_TEXT'),
				ellipsize: Ellipsize.END,
				mode: LinkMode.DASH,
				color: Color.accentMainLink,
				numberOfLines: 1,
				textDecorationLine: 'underline',
				onClick: this.chooseDepartmentLinkClickHandler,
			});
		}

		getDepartmentMenuItems = () => {
			const items = [];
			if (env.isAdmin)
			{
				items.push(
					{
						id: 'create',
						testId: `${this.testId}-department-menu-item-create`,
						title: Loc.getMessage('INTRANET_DEPARTMENT_MENU_ITEM_CREATE_TEXT'),
						iconName: Icon.PLUS,
						onItemSelected: () => {
							CreateDepartment.open({
								parentWidget: this.props.layout ?? layout,
								showToastAfterCreation: false,
								onSave: (department) => {
									if (department)
									{
										this.#setDepartment(department);
									}
								},
							});
						},
					},
				);
			}
			items.push(
				{
					id: 'choose',
					testId: `${this.testId}-department-menu-item-choose`,
					title: Loc.getMessage('INTRANET_DEPARTMENT_MENU_ITEM_CHOOSE_TEXT'),
					iconName: Icon.COMPANY,
					onItemSelected: () => {
						this.#openDepartmentSelector();
					},
				},
			);

			if (this.#isDepartmentSelected())
			{
				items.push({
					id: 'clear',
					testId: `${this.testId}-department-menu-item-clear`,
					title: Loc.getMessage('INTRANET_DEPARTMENT_MENU_ITEM_CLEAR_TEXT'),
					isCustomIconColor: true,
					iconName: Icon.CROSS,
					isDestructive: true,
					onItemSelected: () => {
						this.#setDepartment(null);
					},
				});
			}

			return items;
		};

		#setDepartment(department)
		{
			this.setState({
				department,
			}, () => {
				if (Type.isFunction(this.props.selectedDepartmentChanged))
				{
					this.props.selectedDepartmentChanged(this.state.department);
				}
			});
		}

		#openDepartmentSelector()
		{
			this.departmentSelector = DepartmentSelector.make({
				provider: {
					options: this.props.providerOptions || {},
				},
				initSelectedIds: this.state.department?.id ? [this.state.department.id] : null,
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (departments) => {
						if (departments && departments.length > 0)
						{
							this.#setDepartment(departments[0]);
						}
					},
				},
			});

			this.departmentSelector.show({}, this.props.layout ?? layout);
		}
	}

	module.exports = { DepartmentChooser };
});
