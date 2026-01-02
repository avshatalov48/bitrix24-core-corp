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
	const { showToast, showErrorToast } = require('toast');
	const { BottomSheet } = require('bottom-sheet');
	const {
		showAccessRestrictionBox,
	} = require('intranet/create-department/src/permissions-box');
	const { DepartmentSelectorCard } = require('intranet/create-department/src/selector-card/department');
	const { UserSelectorCard } = require('intranet/create-department/src/selector-card/user');
	const { Line } = require('utils/skeleton');
	const { usersSelector, usersUpserted } = require('statemanager/redux/slices/users');
	const { usersUpserted: intranetUsersUpserted } = require('intranet/statemanager/redux/slices/employees');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const { dispatch, getState } = require('statemanager/redux/store');
	const isAndroid = Application.getPlatform() === 'android';
	const { fetchDepartmentPermissions } = require('intranet/create-department/src/api');

	const NO_CREATE_PERMISSIONS_CODE = 1;

	/**
	 * @class CreateDepartment
	 */
	class CreateDepartment extends LayoutComponent
	{
		/**
		 * @param {Object} data
		 * @param {Object} [data.parentWidget]
		 * @param {string} [data.title]
		 * @param {boolean} [data.shouldCheckNeedToBeMemberOfNewDepartment = false]
		 * @param {boolean} [data.showToastAfterCreation = true]
		 * @param {boolean} [data.showToastAfterCreationError = true]
		 * @param {Function} [data.onSave]
		 * @param {Function} [data.onError]
		 * @param {Function} [data.onClose]
		 */
		static async open(data)
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
					createDepartment.setLayout(layoutWidget);
				})
				.catch(() => {});
		}

		async fetchCreateDepartmentSettings()
		{
			const results = await Promise.allSettled([
				fetchDepartmentPermissions(),
				this.fetchDefaultHeadOfDepartment(),
			]);

			const settings = {};

			if (results[0]?.status === 'fulfilled')
			{
				const response = results[0].value;
				if (response.status === 'success')
				{
					const { data } = response;
					settings.canInviteUsers = data.canInviteUsers ?? false;
					settings.needToBeMemberOfNewDepartment = data.needToBeMemberOfNewDepartment ?? true;
					settings.canCreateNewDepartment = data.canCreateNewDepartment ?? false;
					settings.defaultParentDepartment = data.firstPossibleParentForNewDepartment ?? null;
				}
			}

			if (results[1]?.status === 'fulfilled')
			{
				settings.defaultHeadOfDepartment = results[1].value;
			}

			return settings;
		}

		async fetchDefaultHeadOfDepartment(userId = env.userId)
		{
			const defaultUser = usersSelector.selectById(getState(), userId);
			if (defaultUser)
			{
				return {
					id: defaultUser.id,
					name: defaultUser.fullName,
				};
			}

			const result = await BX.ajax.runAction('intranetmobile.employees.getUsersByIds', {
				data: {
					ids: [userId],
				},
			})
				.catch(console.error);
			if (result.status !== 'success')
			{
				return null;
			}

			const { items: mainInfo, users: intranetInfo } = result.data;
			this.saveUserToRedux(mainInfo, intranetInfo);

			return {
				id: mainInfo[0].id,
				name: mainInfo[0].fullName,
			};
		}

		saveUserToRedux(mainInfo, intranetInfo)
		{
			const actions = [usersUpserted(mainInfo), intranetUsersUpserted(intranetInfo)];
			dispatch(batchActions(actions));
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
				needToBeMemberOfNewDepartment: true,
				parentDepartment: null,
				departmentName: '',
				headOfDepartment: {
					id: env.userId,
				},
				error: null,
				loading: true,
			};
		}

		get showToastAfterCreation()
		{
			return this.props.showToastAfterCreation ?? true;
		}

		get showToastAfterCreationError()
		{
			return this.props.showToastAfterCreationError ?? true;
		}

		async componentDidMount()
		{
			Keyboard.on(Keyboard.Event.WillHide, () => {
				this.layoutWidget.setBottomSheetHeight(CreateDepartment.getStartingLayoutHeight());
			});
			this.focusOnNameInput();

			const {
				canCreateNewDepartment,
				needToBeMemberOfNewDepartment,
				defaultParentDepartment,
				defaultHeadOfDepartment,
			} = await this.fetchCreateDepartmentSettings();

			if (!canCreateNewDepartment)
			{
				showAccessRestrictionBox({
					parentWidget: this.layoutWidget,
					onClose: this.close,
				});

				return;
			}

			this.setState({
				needToBeMemberOfNewDepartment,
				parentDepartment: defaultParentDepartment,
				headOfDepartment: defaultHeadOfDepartment,
				loading: false,
			});
		}

		#getTestId = (suffix) => {
			const prefix = 'create-department';

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		setLayout(layout)
		{
			this.layoutWidget = layout;

			this.layoutWidget.on('onViewHidden', () => {
				this.props.onClose?.(this);
			});
		}

		render()
		{
			const { loading } = this.state;

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
					loading && this.renderSkeletonForSelectorCards(),
					!loading && View(
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

		renderSkeletonForSelectorCards()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
				Line(100, 14, 16, 7, 4),
				Line('100%', 16, 0, 14, 4),
				Line('100%', 1, 0, 0, 0),
				Line(100, 14, 17, 7, 4),
				Line('100%', 16, 0, 0, 4),
			);
		}

		renderFooter()
		{
			const { departmentName, loading } = this.state;

			return BoxFooter(
				{
					safeArea: !isAndroid,
					keyboardButton: {
						text: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_CREATE_BUTTON_TEXT'),
						loading,
						onClick: this.save,
						disabled: departmentName === '',
					},
				},
				Button({
					testId: this.#getTestId('create-button'),
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_CREATE_BUTTON_TEXT'),
					stretched: true,
					onClick: this.save,
					loading,
					disabled: departmentName === '',
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
			const { error } = this.state;

			return View(
				{
					style: {
						flex: 1,
						marginLeft: Indent.L.toNumber(),
					},
				},
				StringInput({
					testId: this.#getTestId('name-input'),
					placeholder: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NAME_INPUT_TITLE'),
					size: InputSize.L,
					design: InputDesign.PRIMARY,
					mode: InputMode.STROKE,
					align: 'left',
					error: error || false,
					errorText: error,
					onChange: (departmentName) => {
						const errorText = Type.isStringFilled(departmentName) ? null : Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NAME_INPUT_ERROR');
						this.setState({ departmentName, error: errorText });
					},
					forwardRef: this.handleInputRef,
				}),
			);
		}

		handleInputRef = (ref) => {
			this.nameInputRef = ref;
			this.focusOnNameInput();
		};

		renderParentDepartmentSelector()
		{
			const { parentDepartment } = this.state;

			return DepartmentSelectorCard({
				testId: 'parent-department',
				parentWidget: this.layoutWidget,
				title: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_PARENT_DEPARTMENT_SELECTOR_TITLE'),
				text: parentDepartment?.name ?? '',
				selectedId: parentDepartment?.id ?? null,
				showBottomBorder: true,
				onViewHidden: this.focusOnNameInput,
				onSelected: (selectedDepartments) => {
					if (Array.isArray(selectedDepartments) && selectedDepartments.length > 0)
					{
						this.setState({
							parentDepartment: {
								id: selectedDepartments[0].id,
								name: selectedDepartments[0].title,
							},
						});
					}
				},
			});
		}

		renderHeadOfDepartmentSelector()
		{
			const { headOfDepartment } = this.state;

			return UserSelectorCard({
				testId: 'head-of-department',
				parentWidget: this.layoutWidget,
				title: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_HEAD_OF_DEPARTMENT_SELECTOR_TITLE'),
				text: headOfDepartment?.name ?? '',
				selectedId: headOfDepartment?.id ?? null,
				onViewHidden: this.focusOnNameInput,
				editable: this.isHeadOfDepartmentEditable(),
				onSelected: (selectedUsers) => {
					if (Array.isArray(selectedUsers) && selectedUsers.length > 0)
					{
						this.setState({
							headOfDepartment: {
								id: selectedUsers[0].id,
								name: selectedUsers[0].title,
							},
						});
					}
				},
			});
		}

		isHeadOfDepartmentEditable()
		{
			const { needToBeMemberOfNewDepartment } = this.state;
			const { shouldCheckNeedToBeMemberOfNewDepartment = false } = this.props;

			return !shouldCheckNeedToBeMemberOfNewDepartment || !needToBeMemberOfNewDepartment;
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
				parentId: this.state.parentDepartment?.id,
				userIds: {
					MEMBER_HEAD: [this.state.headOfDepartment?.id],
				},
			};

			this.setState({ loading: true }, () => {
				BX.ajax.runAction('humanresources.api.Structure.Department.create', { data: departmentData })
					.then((result) => {
						if (result.status === 'success')
						{
							if (this.showToastAfterCreation)
							{
								showToast({
									message: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_SAVE_TOAST_SUCCESS_MESSAGE'),
								}, this.props.parentWidget);
							}

							const { id, name: title } = result.data.node;
							this.props.onSave?.({
								id,
								title,
							});

							this.setState({ loading: false }, () => {
								this.close();
							});
						}
						else
						{
							this.#handlerCreationErrorResult(result);
						}
					}, (result) => {
						this.#handlerCreationErrorResult(result);
					})
					.catch((error) => console.error(error));
			});
		};

		focusOnNameInput = () => {
			this.nameInputRef?.focus();
		};

		close = () => {
			this.layoutWidget.close();
		};

		#handlerCreationErrorResult(result)
		{
			this.setState({
				loading: false,
			}, () => {
				if (result.errors?.length > 0)
				{
					if (result.errors[0].code === NO_CREATE_PERMISSIONS_CODE)
					{
						showAccessRestrictionBox({
							parentWidget: this.layoutWidget,
							subdepartmentCreationCase: true,
							onClose: this.focusOnNameInput,
						});
					}
					else
					{
						if (this.showToastAfterCreationError)
						{
							showErrorToast({
								message: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_SAVE_TOAST_ERROR_MESSAGE'),
							}, this.layoutWidget);
						}
						this.props.onError?.(result, this.layoutWidget, this);
					}
				}
			});
		}
	}

	module.exports = { CreateDepartment, showAccessRestrictionBox, fetchDepartmentPermissions };
});
