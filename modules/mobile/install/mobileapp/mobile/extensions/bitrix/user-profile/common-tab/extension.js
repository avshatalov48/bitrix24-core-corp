/**
 * @module user-profile/common-tab
 */
jn.define('user-profile/common-tab', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { AreaList } = require('ui-system/layout/area-list');
	const { ButtonDesign, ButtonSize, Button } = require('ui-system/form/buttons/button');
	const { Indent, Color } = require('tokens');
	const { getTabsRunActionExecutor } = require('user-profile/api');
	const { TabsCacheManager } = require('user-profile/common-tab/src/cache-manager');
	const { ProfileBlockFactory } = require('user-profile/common-tab/src/block/factory');
	const { createTestIdGenerator } = require('utils/test');
	const { Animated } = require('animation/animated');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const { dispatch } = require('statemanager/redux/store');
	const { Type } = require('type');
	const { ajaxPublicErrorHandler } = require('error');
	const { TabType } = require('user-profile/const');
	const { confirmClosing } = require('alert');
	const { AvaMenu } = require('ava-menu');
	const { withCurrentDomain } = require('utils/url');
	const { FieldChangeManager } = require('user-profile/common-tab/src/field-change-manager');

	const CloseEditActions = {
		SAVE_AND_EXIT: 'save_and_exit',
		CONTINUE_EDIT: 'continue_edit',
		EXIT_WITHOUT_SAVE: 'exit_without_save',
	};

	/**
	 * @typedef {Object} CommonTabProps
	 * @property {number} ownerId
	 * @property {PageManager} parentWidget
	 *
	 * @class CommonTab
	 */
	class CommonTab extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: 'common-tab',
				context: this,
			});

			this.cacheManager = new TabsCacheManager();
			this.cacheManager.setRunActionExecutor(getTabsRunActionExecutor(this.props.ownerId));

			this.onFieldChange = this.onFieldChange.bind(this);

			/** @type {Button|null} */
			this.saveButtonRef = null;
			this.contentOpacity = Animated.newCalculatedValue(1);

			this.scrollViewRef = null;
			this.fieldsManager = new FieldChangeManager();
			this.closeButtonUpdated = false;

			this.state = {
				data: props.data ?? {},
				isEditMode: false,
			};
		}

		async componentDidMount()
		{
			const { parentWidget, canUpdate } = this.props;

			if (canUpdate)
			{
				parentWidget.setRightButtons([
					{
						type: 'edit',
						testId: this.getTestId('edit-button'),
						callback: () => this.#activateEditMode(),
					},
				]);
			}

			Keyboard.on(Keyboard.Event.Hidden, () => this.#updateSaveButtonState());
		}

		componentDidUpdate()
		{
			const { isEditMode } = this.state;
			const { parentWidget, closeIcon } = this.props;

			if (isEditMode && !this.closeButtonUpdated)
			{
				this.closeButtonUpdated = true;
				parentWidget.setLeftButtons([]);
				parentWidget.setLeftButtons([
					{
						svg: {
							content: closeIcon,
						},
						callback: this.onEditScreenClose,
					},
				]);
			}
		}

		componentWillUnmount()
		{
			Keyboard.off(Keyboard.Event.Hidden, () => this.#updateSaveButtonState());
		}

		#updateSaveButtonState()
		{
			if (!this.saveButtonRef)
			{
				return;
			}

			this.saveButtonRef.setLoading(this.isSaving);
			this.saveButtonRef.setDisabled(!this.fieldsManager.canSave());
		}

		render()
		{
			const { isEditMode } = this.state;

			return Box(
				{
					safeArea: {
						bottom: false,
					},
					scrollProps: {
						ref: this.bindScrollViewRef,
						style: {
							backgroundColor: Color.bgContentSecondary.toHex(),
						},
					},
					footer: isEditMode && BoxFooter(
						{
							style: {
								opacity: this.contentOpacity,
							},
							keyboardButton: {
								text: Loc.getMessage('M_PROFILE_EDIT_BUTTON_SUBMIT'),
								testId: this.getTestId('button-submit'),
								onClick: () => Keyboard.dismiss(),
							},
						},
						() => ([
							Button({
								testId: this.getTestId('button-save'),
								stretched: true,
								disabled: !this.fieldsManager.canSave(),
								size: ButtonSize.L,
								text: Loc.getMessage('M_PROFILE_EDIT_BUTTON_SAVE'),
								withStateDecorator: true,
								ref: (ref) => {
									this.saveButtonRef = ref;
								},
								onClick: this.#onSaveButtonClick,
							}),
							Button({
								testId: this.getTestId('button-cancel'),
								design: ButtonDesign.PLAIN_NO_ACCENT,
								stretched: true,
								size: ButtonSize.L,
								text: Loc.getMessage('M_PROFILE_EDIT_BUTTON_CANCEL'),
								onClick: () => {
									Haptics.impactLight();
									void this.#deactivateEditMode();
								},
								style: {
									paddingTop: Indent.L.toNumber(),
									paddingBottom: Indent.XL3.toNumber(),
								},
							}),
						]),
					),
					withScroll: true,
					testId: this.getTestId('box'),
				},
				AreaList(
					{
						testId: this.getTestId('area-list'),
						style: {
							backgroundColor: Color.bgContentSecondary.toHex(),
							opacity: this.contentOpacity,
						},
						withScroll: false,
					},
					...this.blocks,
				),
			);
		}

		get blocks()
		{
			const { ownerId, parentWidget } = this.props;
			const { data, isEditMode } = this.state;

			const { users } = data;
			if (Type.isArrayFilled(users))
			{
				dispatch(usersUpserted(users));
			}

			return ProfileBlockFactory.getAll({
				...data,
				isEditMode,
				ownerId,
				parentWidget,
				cacheManager: this.cacheManager,
				onChange: this.onFieldChange,
				onFocus: this.onFieldFocus,
			});
		}

		onFieldFocus = async (ref) => {
			const position = this.scrollViewRef?.getPosition(ref);
			if (position)
			{
				this.scrollViewRef?.scrollTo({ y: position.y - 20, animated: true });
			}
		};

		bindScrollViewRef = (ref) => {
			this.scrollViewRef = ref;
		};

		scrollToTop()
		{
			this.scrollViewRef?.scrollToBegin(false);
		}

		onFieldChange(key, value, isValid)
		{
			this.fieldsManager.setField(key, value, isValid);
			this.#updateSaveButtonState();
		}

		async #activateEditMode()
		{
			const { parentWidget } = this.props;
			this.isSaving = false;
			this.fieldsManager.clear();

			Haptics.impactLight();

			parentWidget.setRightButtons([]);

			await this.#fadeIn();
			this.setState({ isEditMode: true }, () => {
				this.#updateSaveButtonState();
				this.#fadeOut();
				this.updateTabsEditMode(true);
				this.updateWidgetTitle(true);
			});
		}

		async #deactivateEditMode()
		{
			const { parentWidget } = this.props;
			await this.#fadeIn();
			this.setState({ isEditMode: false }, () => {
				parentWidget.setRightButtons([
					{
						type: 'edit',
						testId: this.getTestId('edit-button'),
						callback: () => this.#activateEditMode(),
					},
				]);
				this.#fadeOut();
				this.scrollToTop();
				this.updateTabsEditMode(false);
				this.updateWidgetTitle(false);
			});
		}

		updateWidgetTitle(isEditMode)
		{
			const { tabsWidget } = this.props;
			const text = isEditMode
				? Loc.getMessage('M_PROFILE_EDIT_TITLE')
				: Loc.getMessage('M_PROFILE_TITLE');
			tabsWidget.setTitle({
				text,
			}, true);
		}

		updateTabsEditMode(isEditMode)
		{
			const { tabsWidget } = this.props;
			Object.values(TabType)
				.filter((tabId) => tabId !== TabType.COMMON)
				.forEach((tabId) => {
					tabsWidget.updateItem(tabId, {
						selectable: !isEditMode,
					});
				});
		}

		onEditScreenClose = async () => {
			const { tabsWidget } = this.props;
			if (this.fieldsManager.hasChanges())
			{
				const action = await this.showSaveEditChangesAlert(this.fieldsManager.canSave());
				switch (action)
				{
					case CloseEditActions.EXIT_WITHOUT_SAVE:
						tabsWidget.close();
						break;
					case CloseEditActions.SAVE_AND_EXIT:
						{
							const success = await this.#saveChanges();
							if (success)
							{
								tabsWidget.close();
							}
						}
						break;
					case CloseEditActions.CONTINUE_EDIT:
						break;
					default:
				}
			}
			else
			{
				tabsWidget.close();
			}
		};

		showSaveEditChangesAlert = async (hasSaveAndClose) => {
			return new Promise((resolve) => {
				confirmClosing({
					onSave: () => resolve(CloseEditActions.SAVE_AND_EXIT),
					onClose: () => resolve(CloseEditActions.EXIT_WITHOUT_SAVE),
					onCancel: () => resolve(CloseEditActions.CONTINUE_EDIT),
					hasSaveAndClose,
				});
			});
		};

		#fadeIn()
		{
			return new Promise((resolve) => {
				const config = {
					toValue: 0,
					duration: 0.2,
					type: 'easeInQuad',
				};
				Animated.timing(this.contentOpacity, config).start(() => resolve());
			});
		}

		#fadeOut()
		{
			const config = {
				toValue: 1,
				duration: 0.2,
				type: 'easeOutQuad',
			};

			Animated.timing(this.contentOpacity, config).start(null);
		}

		#onSaveButtonClick = () => {
			void this.#saveChanges();
		};

		#saveChanges = async () => {
			if (!this.fieldsManager.canSave() || this.isSaving)
			{
				return false;
			}

			this.isSaving = true;
			this.#updateSaveButtonState();
			void NotifyManager.showLoadingIndicator();
			Haptics.impactLight();

			let response = {};
			try
			{
				response = await this.fieldsManager.saveChanges(this.props.ownerId);
			}
			catch (result)
			{
				await ajaxPublicErrorHandler(result);
				NotifyManager.hideLoadingIndicator(false);
				this.isSaving = false;
				this.#updateSaveButtonState();
			}

			const { status, data } = response;
			const isSuccess = status === 'success';
			NotifyManager.hideLoadingIndicator(isSuccess);
			if (isSuccess)
			{
				this.fieldsManager.clear();
				this.#updateFields(data);
				await this.#deactivateEditMode();
				this.#updateCurrentUserInfoInAvaMenu(data);
			}
			else
			{
				await ajaxPublicErrorHandler(response);
			}

			return isSuccess;
		};

		#updateCurrentUserInfoInAvaMenu(data)
		{
			const { ownerId } = this.props;
			const { users } = data;
			if (Number(ownerId) !== Number(env.userId) || !Type.isArrayFilled(users))
			{
				return;
			}

			const userData = users.find((user) => Number(user.id) === Number(ownerId));
			if (!userData)
			{
				return;
			}

			const uri = withCurrentDomain(userData.avatarSize100);
			AvaMenu.setUserInfo({
				title: userData.fullName,
				avatar: {
					uri,
				},
			});
		}

		#updateFields(data)
		{
			const { users } = data;

			if (Type.isArrayFilled(users))
			{
				dispatch(usersUpserted(users));
			}

			this.setState({
				data,
			}, () => {
				this.cacheManager?.modifyCommonTabDataInCache(data);
				this.scrollToTop();
			});
		}
	}

	module.exports = {
		/**
		 * @param {CommonTabProps} props
		 * @returns {CommonTab}
		 */
		CommonTab: (props) => new CommonTab(props),
	};
});
