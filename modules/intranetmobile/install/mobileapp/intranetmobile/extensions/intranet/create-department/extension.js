/**
 * @module intranet/create-department
 */

jn.define('intranet/create-department', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Corner, Indent, Component } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { InputSize, InputDesign, InputMode, StringInput } = require('ui-system/form/inputs/string');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { UserSelectorCard } = require('intranet/layout/user-selector-card');
	const { DepartmentSelectorCard } = require('intranet/layout/department-selector-card');
	const { showToast, showErrorToast } = require('toast');
	const { BottomSheet } = require('bottom-sheet');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class CreateDepartment
	 */
	class CreateDepartment extends LayoutComponent
	{
		/**
		 * @param {Object} data
		 * @param {Object} [data.parentWidget]
		 * @param {string} [data.title]
		 * @param {boolean} [data.showToastAfterCreation]
		 */
		static open(data)
		{
			const parentWidget = data.parentWidget || PageManager;
			const createDepartment = new CreateDepartment(data);

			const bottomSheet = new BottomSheet({
				component: createDepartment,
				titleParams: {
					type: 'dialog',
					text: data.title || Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_TITLE'),
					largeMode: true,
				},
			});
			bottomSheet
				.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.disableShowOnTop()
				.disableOnlyMediumPosition()
				.setMediumPositionHeight(CreateDepartment.getStartingLayoutHeight())
				.enableBounce()
				.enableSwipe()
				.disableHorizontalSwipe()
				.enableResizeContent()
				.enableAdoptHeightByKeyboard()
				.open()
				.then((layoutWidget) => {
					createDepartment.layoutWidget = layoutWidget;
				})
				.catch(() => {});
		}

		static getStartingLayoutHeight()
		{
			const TITLE_HEIGHT = 44;
			const AREA_PADDING = Component.areaPaddingB.toNumber() + Component.areaPaddingTFirst.toNumber();
			const DEPARTMENT_INPUT_SECTION_HEIGHT = 48;
			const SELECTORS_SECTION_HEIGHT = 42 * 2 + 2 * Indent.XL2.toNumber();
			const BUTTON_HEIGHT = 42 + Indent.XL2.toNumber();

			return TITLE_HEIGHT
				+ AREA_PADDING
				+ DEPARTMENT_INPUT_SECTION_HEIGHT
				+ SELECTORS_SECTION_HEIGHT
				+ BUTTON_HEIGHT
				+ (isAndroid ? Indent.XL2.toNumber() : 0);
		}

		constructor(props)
		{
			super(props);

			this.nameInputRef = null;
			this.layoutWidget = null;
			this.state = {
				parentDepartmentId: null,
				headOfDepartmentId: env.userId,
				departmentName: '',
				error: null,
				loading: false,
			};
		}

		get showToastAfterCreation()
		{
			return this.props.showToastAfterCreation ?? true;
		}

		componentDidMount()
		{
			Keyboard.on(Keyboard.Event.WillHide, () => {
				this.layoutWidget.setBottomSheetHeight(CreateDepartment.getStartingLayoutHeight());
			});
		}

		render()
		{
			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
				},
				Area(
					{
						isFirst: true,
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						this.renderDepartmentIcon(),
						this.renderDepartmentNameInput(),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								flexWrap: 'wrap',
							},
						},
						this.renderParentDepartmentSelector(),
						this.renderHeadOfDepartmentSelector(),
					),
				),
			);
		}

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: !isAndroid,
					keyboardButton: {
						text: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_CREATE_BUTTON_TEXT'),
						loading: this.state.loading,
						onClick: this.save,
					},
				},
				Button({
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_CREATE_BUTTON_TEXT'),
					stretched: true,
					onClick: this.save,
					loading: this.state.loading,
				}),
			);
		}

		renderDepartmentIcon()
		{
			return View(
				{
					style: {
						backgroundColor: Color.accentSoftBlue2.toHex(),
						flexDirection: 'row',
						justifyContent: 'center',
						alignContent: 'center',
						alignItems: 'center',
						width: 48,
						height: 48,
						borderRadius: Corner.M.toNumber(),
					},
				},
				IconView({
					icon: Icon.COMPANY,
					size: 31,
					color: Color.accentMainPrimaryalt,
				}),
			);
		}

		renderDepartmentNameInput()
		{
			return View(
				{
					style: {
						flex: 1,
						marginLeft: Indent.L.toNumber(),
					},
				},
				StringInput({
					placeholder: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NAME_INPUT_TITLE'),
					size: InputSize.L,
					design: InputDesign.PRIMARY,
					mode: InputMode.STROKE,
					align: 'left',
					focus: true,
					error: this.state.error || false,
					errorText: this.state.error,
					onChange: (departmentName) => {
						const error = Type.isStringFilled(departmentName) ? null : Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NAME_INPUT_ERROR');
						this.setState({ departmentName, error });
					},
					forwardRef: this.handleInputRef,
				}),
			);
		}

		handleInputRef = (ref) => {
			this.nameInputRef = ref;
			this.nameInputRef.focus();
		};

		renderParentDepartmentSelector()
		{
			return View(
				{
					style: {
						width: '100%',
						marginTop: Indent.XL2.toNumber(),
						borderBottomWidth: 1,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
					},
				},
				new DepartmentSelectorCard({
					title: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_PARENT_DEPARTMENT_SELECTOR_TITLE'),
					parentWidget: this.layoutWidget || PageManager,
					onViewHidden: () => this.nameInputRef.focus(),
					onChange: (departmentId) => this.setState({ parentDepartmentId: departmentId }),
					onLoadRootDepartment: (departmentId) => this.setState({ parentDepartmentId: departmentId }),
				}),
			);
		}

		renderHeadOfDepartmentSelector()
		{
			return View(
				{
					style: {
						width: '100%',
						marginTop: Indent.XL2.toNumber(),
					},
				},
				new UserSelectorCard({
					title: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_HEAD_OF_DEPARTMENT_SELECTOR_TITLE'),
					parentWidget: this.layoutWidget || PageManager,
					onViewHidden: () => this.nameInputRef.focus(),
					onChange: (userId) => this.setState({ headOfDepartmentId: userId }),
				}),
			);
		}

		save = () => {
			if (this.state.loading)
			{
				return;
			}

			if (!Type.isStringFilled(this.state.departmentName))
			{
				this.setState({ error: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NAME_INPUT_ERROR') });

				return;
			}

			const departmentData = {
				name: this.state.departmentName,
				parentDepartmentId: this.state.parentDepartmentId,
				headOfDepartmentId: this.state.headOfDepartmentId,
			};

			this.setState({ loading: true }, () => {
				BX.ajax.runAction('intranetmobile.departments.createDepartment', { data: departmentData })
					.then((result) => {
						if (result.status === 'success')
						{
							if (this.showToastAfterCreation)
							{
								showToast({
									message: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_SAVE_TOAST_SUCCESS_MESSAGE'),
								}, this.props.parentWidget);
							}

							if (this.props.onSave)
							{
								this.props.onSave({
									id: result.data.id,
									title: this.state.departmentName,
								});
							}

							this.setState({ loading: false }, () => {
								this.layoutWidget.close();
							});
						}
						else
						{
							showErrorToast({
								message: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_SAVE_TOAST_ERROR_MESSAGE'),
							}, this.props.parentWidget);

							this.setState({ loading: false });
						}
					})
					.catch((error) => console.error(error));
			});
		};
	}

	module.exports = { CreateDepartment };
});
