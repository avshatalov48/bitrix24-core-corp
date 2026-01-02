/**
 * @module intranet/invite-new/src/department-chooser
 */
jn.define('intranet/invite-new/src/department-chooser', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { Color } = require('tokens');
	const { DepartmentSelector } = require('selector/widget/entity/intranet/department');
	const { Icon } = require('ui-system/blocks/icon');
	const { UIMenu, UIMenuPosition } = require('layout/ui/menu');
	const { Loc } = require('loc');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} DepartmentChooserProps
	 * @property {Object} department
	 * @property {Object} providerOptions
	 * @property {Object} layout
	 * @property {Function} selectedDepartmentChanged
	 * @property {string} testId

	 * @class DepartmentChooser
	 */
	class DepartmentChooser extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				context: this,
			});
		}

		render()
		{
			return ChipButton({
				testId: this.getTestId('choose-department-chip-button'),
				forwardRef: this.bindDepartmentChipButtonRef,
				compact: false,
				color: Color.accentMainPrimary,
				mode: ChipButtonMode.OUTLINE,
				design: this.#getChipButtonDesign(),
				text: this.#getSelectedDepartmentName(),
				dropdown: true,
				rounded: false,
				backgroundColor: Color.bgPrimary,
				onClick: this.#onClick,
				style: {
					maxWidth: '100%',
				},
			});
		}

		bindDepartmentChipButtonRef = (ref) => {
			this.departmentChipButton = ref;
		};

		#onClick = () => {
			if (this.props.department)
			{
				this.#openDepartmentContextMenu();

				return;
			}
			this.#openDepartmentSelector();
		};

		#getChipButtonDesign()
		{
			return this.props.department ? ChipButtonDesign.PRIMARY : ChipButtonDesign.GREY;
		}

		#getSelectedDepartmentName()
		{
			if (this.props.department)
			{
				return this.props.department?.title ?? '';
			}

			return Loc.getMessage('INTRANET_INVITE_EMPTY_DEPARTMENT_CHIP_BUTTON_TEXT');
		}

		#openDepartmentSelector()
		{
			const { department, layout } = this.props;

			this.departmentSelector = DepartmentSelector.make({
				provider: {
					options: {
						selectMode: 'departmentsOnly',
						restricted: 'inviteUser',
					},
				},
				createOptions: {
					enableCreation: true,
					shouldCheckNeedToBeMemberOfNewDepartment: true,
					getParentLayout: () => this.departmentSelector.widget,
				},
				initSelectedIds: department?.id ? [department.id] : null,
				widgetParams: {
					title: Loc.getMessage('INTRANET_INVITE_DEPARTMENT_SELECTOR_TITLE'),
					backdrop: {
						mediumPositionHeight: 460,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (departments) => {
						this.props.selectedDepartmentChanged?.(departments?.length > 0 ? departments[0] : null);
					},
				},
			});

			this.departmentSelector.show({}, layout);
		}

		#openDepartmentContextMenu()
		{
			this.menu = new UIMenu(this.#getDepartmentMenuItems());
			this.menu.show({
				target: this.departmentChipButton,
				position: UIMenuPosition.TOP,
			});
		}

		#getDepartmentMenuItems = () => {
			return [
				{
					id: 'choose',
					testId: this.getTestId('department-menu-item-choose'),
					title: Loc.getMessage('INTRANET_DEPARTMENT_MENU_ITEM_CHOOSE_TEXT_MSGVER_1'),
					iconName: Icon.EDIT,
					onItemSelected: () => {
						this.#openDepartmentSelector();
					},
				},
				{
					id: 'clear',
					testId: this.getTestId('department-menu-item-clear'),
					title: Loc.getMessage('INTRANET_DEPARTMENT_MENU_ITEM_CLEAR_TEXT'),
					iconName: Icon.CROSS,
					onItemSelected: () => {
						this.props.selectedDepartmentChanged?.(null);
					},
				},
			];
		};
	}

	module.exports = {
		/**
		 * @param {DepartmentChooserProps} props
		 * @returns {DepartmentChooser}
		 */
		DepartmentChooser: (props) => new DepartmentChooser(props),
	};
});
