/**
 * @module collab/create
 */
jn.define('collab/create', (require, exports, module) => {
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { Color } = require('tokens');
	const { DialogOpener } = require('im/messenger/api/dialog-opener');
	const { CollabCreateIntro } = require('collab/create/src/intro');
	const { CollabCreatePermissions } = require('collab/create/src/permissions');
	const { CollabCreateSecurity } = require('collab/create/src/security');
	const { openCollabInvite, CollabInviteAnalytics } = require('collab/invite');
	const { CollabCreateEdit, CollabSettingsItem } = require('collab/create/src/edit');
	const { clone, mergeImmutable } = require('utils/object');
	const { Haptics } = require('haptics');
	const { isNil } = require('utils/type');
	const { ajaxPublicErrorHandler, ajaxAlertErrorHandler, showInternalAlert } = require('error');
	const { Alert, ButtonType } = require('alert');

	const CollabCreateStage = {
		INTRO: 'intro',
		EDITING: 'editing',
		PERMISSIONS: 'permissions',
		SECURITY: 'security',
	};

	const CloseEditActions = {
		SAVE_AND_EXIT: 'save_and_exit',
		CONTINUE_EDIT: 'continue_edit',
		EXIT_WITHOUT_SAVE: 'exit_without_save',
	};

	class CollabCreate extends LayoutComponent
	{
		static getOpenWidgetSettings(props, parentWidget = PageManager)
		{
			const { collabId = null, stage = null } = props;
			const isEditMode = !isNil(collabId);
			const preparedStage = isNil(stage)
				? (isEditMode ? CollabCreateStage.EDITING : CollabCreateStage.INTRO)
				: stage;
			const isEditingStage = preparedStage === CollabCreateStage.EDITING;

			let openWidgetParams = {
				backgroundColor: Color.bgPrimary.toHex(),
				titleParams: CollabCreate.getWidgetTitleParams(preparedStage, isEditMode),
			};

			if (!isEditMode)
			{
				openWidgetParams = mergeImmutable(openWidgetParams, {
					backdrop: parentWidget === PageManager ? {
						mediumPositionPercent: 85,
						onlyMediumPosition: true,
						horizontalSwipeAllowed: false,
					} : undefined,
				});
			}

			return isEditMode && isEditingStage ? mergeImmutable(openWidgetParams, { modal: true }) : openWidgetParams;
		}

		/**
		 * @param {Object} props
		 * @param {number|null} props.collabId
		 * @param {function} props.onUpdate
		 * @param {CollabCreateStage|null} props.stage
		 * @param {LayoutComponent} parentWidget
		 * @returns {Promise<CollabCreate|null>}
		 */
		static async open(props = {}, parentWidget = PageManager)
		{
			const { collabId = null, stage = CollabCreateStage.INTRO } = props;
			const isEditMode = !isNil(collabId);

			try
			{
				let settings = props.settings;
				if (!props.settings)
				{
					await NotifyManager.showLoadingIndicator();
					settings = await CollabCreate.getCollabSettings(collabId);
					NotifyManager.hideLoadingIndicatorWithoutFallback();
				}

				const layoutWidget = await parentWidget.openWidget(
					'layout',
					CollabCreate.getOpenWidgetSettings(props, parentWidget),
				);
				const instance = new CollabCreate({
					collabId,
					...props,
					settings: clone(settings),
					stage,
					layoutWidget,
				});
				layoutWidget.showComponent(instance);
				if (isEditMode && stage === CollabCreateStage.EDITING)
				{
					layoutWidget.expandBottomSheet();
					layoutWidget.setRightButtons([
						{
							type: 'cross',
							callback: async () => {
								await CollabCreate.onEditScreenClose(instance, layoutWidget);
							},
						},
					]);
					layoutWidget.setLeftButtons([]);
				}

				return instance;
			}
			catch (err)
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				console.error(err);
				await showInternalAlert();
			}

			return null;
		}

		static async onEditScreenClose(controlInstance, layoutWidget)
		{
			if (controlInstance.hasSettingsChanges())
			{
				const action = await CollabCreate.showSaveEditChangesAlert();
				switch (action)
				{
					case CloseEditActions.EXIT_WITHOUT_SAVE:
						layoutWidget.close();
						break;
					case CloseEditActions.SAVE_AND_EXIT:
						{
							controlInstance.enablePendingForEditScreen();
							const success = await controlInstance.update();
							if (success)
							{
								layoutWidget.close();
							}
							else
							{
								controlInstance.disablePendingForEditScreen();
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
				layoutWidget.close();
			}
		}

		static showSaveEditChangesAlert = async () => {
			return new Promise((resolve) => {
				Alert.confirm(
					Loc.getMessage('M_COLLAB_SAVE_CHANGES_ALERT_TITLE'),
					Loc.getMessage('M_COLLAB_SAVE_CHANGES_ALERT_DESCRIPTION'),
					[
						{
							type: ButtonType.DEFAULT,
							text: Loc.getMessage('M_COLLAB_SAVE_CHANGES_ALERT_SAVE_AND_EXIT_BUTTON'),
							onPress: () => resolve(CloseEditActions.SAVE_AND_EXIT),
						},
						{
							type: ButtonType.DESTRUCTIVE,
							text: Loc.getMessage('M_COLLAB_SAVE_CHANGES_ALERT_DESTRUCTIVE_BUTTON'),
							onPress: () => resolve(CloseEditActions.EXIT_WITHOUT_SAVE),
						},
						{
							type: ButtonType.DEFAULT,
							text: Loc.getMessage('M_COLLAB_SAVE_CHANGES_ALERT_CONTINUE_BUTTON'),
							onPress: () => resolve(CloseEditActions.CONTINUE_EDIT),
						}],
				);
			});
		};

		static getCollabSettings = async (collabId) => {
			const isEditMode = !isNil(collabId);
			if (isEditMode)
			{
				const collabResponse = await CollabCreate.#getCollab(collabId);
				if (collabResponse.status === 'success')
				{
					return CollabCreate.getCollabSettingsFromResponse(collabResponse);
				}
			}
			else
			{
				const createSettingsResponse = await CollabCreate.#getCreateSettings();
				if (createSettingsResponse.status === 'success')
				{
					return createSettingsResponse.data;
				}
			}

			return null;
		};

		static getCollabSettingsFromResponse = (response) => {
			if (!response || !response.data)
			{
				return null;
			}

			const { data } = response;
			const { name, description, ownerId, moderatorMembers, options, additionalInfo } = data;
			const { whoCanInvite, manageMessages } = options;
			const { users, image } = additionalInfo;

			return {
				name,
				description,
				image: isNil(image) ? null : {
					id: image.id,
					previewUrl: encodeURI(image.src),
				},
				permissions: {
					owner: users[ownerId],
					moderators: moderatorMembers.map((id) => users[id]),
					inviters: whoCanInvite,
					messageWriters: manageMessages,
				},
			};
		};

		static #getCreateSettings = async () => {
			return BX.ajax.runAction('mobile.Collab.getCreateSettings', { json: {} })
				.catch(ajaxPublicErrorHandler);
		};

		static #getCollab = async (collabId) => {
			return BX.ajax.runAction('socialnetwork.collab.Collab.get', {
				data: {
					id: collabId,
				},
			})
				.catch(ajaxAlertErrorHandler);
		};

		constructor(props)
		{
			super(props);

			this.state = {
				stage: props.stage ?? CollabCreateStage.INTRO,
			};

			this.settings = {
				name: '',
				description: '',
				image: null,
				...props.settings,
			};

			this.currentStepInstance = null;
		}

		get testId()
		{
			return 'collab-create';
		}

		static getWidgetTitleParams = (stage = CollabCreateStage.INTRO, isEditMode = false) => {
			return {
				text: CollabCreate.getWidgetTitleTextByStage(stage, isEditMode),
				type: CollabCreate.getWidgetTitleTypeByStage(stage, isEditMode),
			};
		};

		static getWidgetTitleTypeByStage = (stage = CollabCreateStage.INTRO, isEditMode = false) => {
			switch (stage)
			{
				case CollabCreateStage.INTRO:
					return 'section';
				case CollabCreateStage.EDITING:
					return isEditMode ? 'entity' : 'section';
				case CollabCreateStage.PERMISSIONS:
				case CollabCreateStage.SECURITY:
					return isEditMode ? 'entity' : 'dialog';
				default:
					return 'common';
			}
		};

		static getWidgetTitleTextByStage = (stage = CollabCreateStage.INTRO, isEditMode = false) => {
			switch (stage)
			{
				case CollabCreateStage.PERMISSIONS:
					return Loc.getMessage('M_COLLAB_CREATE_PERMISSIONS_ITEM_TITLE');
				case CollabCreateStage.SECURITY:
					return Loc.getMessage('M_COLLAB_CREATE_SECURITY_ITEM_TITLE');
				case CollabCreateStage.EDITING:
					return isEditMode
						? Loc.getMessage('M_COLLAB_CREATE_EDITING_TITLE_EDIT_MODE')
						: Loc.getMessage('M_COLLAB_CREATE_EDITING_TITLE');
				case CollabCreateStage.INTRO:
				default:
					return Loc.getMessage('M_COLLAB_CREATE_TITLE');
			}
		};

		#close = () => {
			this.props.layoutWidget?.close();
		};

		#hideIntro = () => {
			void CollabCreate.open({
				...this.props,
				stage: CollabCreateStage.EDITING,
			}, this.props.layoutWidget);
		};

		render()
		{
			const { stage } = this.state;

			switch (stage)
			{
				case CollabCreateStage.INTRO:
					return this.#renderIntroScreen();
				case CollabCreateStage.EDITING:
					return this.#renderEditingScreen();
				case CollabCreateStage.PERMISSIONS:
					return this.#renderPermissionsScreen();
				case CollabCreateStage.SECURITY:
					return this.#renderSecurityScreen();
				default:
					return null;
			}
		}

		#renderSecurityScreen()
		{
			return CollabCreateSecurity({
				...this.settings.security,
				testId: this.testId,
				onChange: this.#onSecurityScreenSettingsChange,
				layoutWidget: this.props.layoutWidget,
			});
		}

		#onSecurityScreenSettingsChange = (newSettings) => {
			this.props.onSecurityChange?.(newSettings);
		};

		#renderPermissionsScreen()
		{
			return CollabCreatePermissions({
				...this.settings.permissions,
				testId: this.testId,
				onChange: this.#onPermissionScreenSettingsChange,
				layoutWidget: this.props.layoutWidget,
			});
		}

		#onPermissionScreenSettingsChange = (newSettings) => {
			this.props.onPermissionsChange?.(newSettings);
		};

		#renderIntroScreen()
		{
			return CollabCreateIntro({
				onContinue: this.#hideIntro,
			});
		}

		#renderEditingScreen()
		{
			this.currentStepInstance = CollabCreateEdit({
				isEditMode: !isNil(this.props.collabId),
				onSettingsItemClick: this.#onCollabSettingsItemClick,
				onChange: this.#onCollabSettingsChange,
				name: this.settings.name,
				description: this.settings.description,
				image: this.settings.image,
				onCreateButtonClick: this.#onCreateButtonClick,
			});

			return this.currentStepInstance;
		}

		enablePendingForEditScreen = () => {
			this.currentStepInstance?.enablePending?.();
		};

		disablePendingForEditScreen = () => {
			this.currentStepInstance?.disablePending?.();
		};

		#onCreateButtonClick = async (disablePendingFunction) => {
			const isEditMode = !isNil(this.props.collabId);
			let success = false;
			if (isEditMode)
			{
				success = await this.update();
			}
			else
			{
				success = await this.#create();
			}

			if (!success)
			{
				disablePendingFunction?.();
			}
		};

		#getSettingsForUpdateRequest = () => {
			const { name, description, image, permissions } = this.settings;
			const { owner, moderators, inviters, messageWriters } = permissions;

			const changedProps = {};
			if (name !== this.props.settings.name)
			{
				changedProps.name = name;
			}

			if (description !== this.props.settings.description)
			{
				changedProps.description = description;
			}

			const settingsImageIsNil = isNil(image);
			const propsImageIsNil = isNil(this.props.settings.image);
			if (settingsImageIsNil !== propsImageIsNil || image?.base64)
			{
				changedProps.avatarId = image?.base64 ?? null;
			}

			if (owner.id !== this.props.settings.permissions.owner.id)
			{
				changedProps.ownerId = owner.id;
			}

			const addModeratorMembers = moderators.filter(
				(user) => !this.props.settings.permissions.moderators.some((m) => m.id === user.id),
			);
			if (addModeratorMembers.length > 0)
			{
				changedProps.addModeratorMembers = addModeratorMembers.map((user) => ['user', user.id]);
			}

			const deleteModeratorMembers = this.props.settings.permissions.moderators.filter(
				(user) => !moderators.some((m) => m.id === user.id),
			);
			if (deleteModeratorMembers.length > 0)
			{
				changedProps.deleteModeratorMembers = deleteModeratorMembers.map((user) => ['user', user.id]);
			}

			if (inviters !== this.props.settings.permissions.inviters)
			{
				changedProps.options = {
					...changedProps.options,
					whoCanInvite: inviters,
				};
			}

			if (messageWriters !== this.props.settings.permissions.messageWriters)
			{
				changedProps.options = {
					...changedProps.options,
					manageMessages: messageWriters,
				};
			}

			return changedProps;
		};

		#isEmptyObject = (objectToCheck) => {
			return Object.keys(objectToCheck).length === 0;
		};

		#getSettingsForCreateRequest = () => {
			const { name, description, image, permissions } = this.settings;
			const { owner, moderators, inviters, messageWriters } = permissions;

			return {
				ownerId: owner.id,
				moderatorMembers: moderators.map((user) => ['user', user.id]),
				name,
				description,
				avatarId: image?.base64 ?? null,

				options: {
					whoCanInvite: inviters,
					manageMessages: messageWriters,
				},
			};
		};

		hasSettingsChanges = () => {
			return !this.#isEmptyObject(this.#getSettingsForUpdateRequest());
		};

		update = async () => {
			const data = this.#getSettingsForUpdateRequest();
			const collabId = this.props.collabId;
			if (this.#isEmptyObject(data))
			{
				this.#close();

				return true;
			}

			const response = await BX.ajax.runAction('socialnetwork.collab.Collab.update', {
				data: {
					id: collabId,
					...data,
				},
			})
				.catch(ajaxAlertErrorHandler);

			if (response.status === 'success')
			{
				Haptics.notifySuccess();
				this.#close();

				this.props?.onUpdate?.(response);

				return true;
			}

			return false;
		};

		#create = async () => {
			const data = this.#getSettingsForCreateRequest();

			const response = await BX.ajax.runAction('socialnetwork.collab.Collab.add', {
				data,
			})
				.catch(ajaxAlertErrorHandler);

			if (response.status === 'success')
			{
				const dialogId = response.data?.dialogId;
				const chatId = response.data?.chatId;
				const collabId = Number(response.data?.id);
				if (!dialogId || !collabId || !chatId)
				{
					return false;
				}

				Haptics.notifySuccess();
				this.#close();
				await DialogOpener.open({
					dialogId,
					context: DialogOpener.context.chatCreation,
				});
				await openCollabInvite({
					collabId,
					analytics: new CollabInviteAnalytics()
						.setSection('collab_create')
						.setChatId(chatId),
				});

				this.props?.onCreate?.(response);

				return true;
			}

			return false;
		};

		#onCollabSettingsChange = (newSettings) => {
			this.settings = {
				...this.settings,
				...newSettings,
			};
		};

		#onCollabSettingsItemClick = (item) => {
			switch (item.id)
			{
				case CollabSettingsItem.PERMISSIONS:
					void CollabCreate.open({
						...this.props,
						stage: CollabCreateStage.PERMISSIONS,
						settings: this.settings,
						onPermissionsChange: this.#onPermissionsChange,
					}, this.props.layoutWidget);
					break;
				case CollabSettingsItem.SECURITY:
					void CollabCreate.open({
						...this.props,
						stage: CollabCreateStage.SECURITY,
						settings: this.settings,
						onSecurityChange: this.#onSecurityChange,
					}, this.props.layoutWidget);
					break;
				default:
					break;
			}
		};

		#onPermissionsChange = (newSettings) => {
			this.settings.permissions = newSettings;
		};

		#onSecurityChange = (newSettings) => {
			this.settings.security = newSettings;
		};
	}

	/**
	 * @param {Object} props
	 * @param {function} props.onCreate
	 * @param {LayoutComponent} parentWidget
	 * @returns {Promise<CollabCreate|null>}
	 */
	const openCollabCreate = (props = {}, parentWidget = PageManager) => {
		return CollabCreate.open(props, parentWidget);
	};

	/**
	 * @param {Object} props
	 * @param {number} props.collabId
	 * @param {function} [props.onUpdate]
	 * @param {LayoutComponent} parentWidget
	 * @returns {Promise<CollabCreate|null>}
	 */
	const openCollabEdit = (props = {}, parentWidget = PageManager) => {
		return CollabCreate.open({
			...props,
			stage: CollabCreateStage.EDITING,
		}, parentWidget);
	};

	module.exports = { CollabCreate, openCollabCreate, openCollabEdit };
});
