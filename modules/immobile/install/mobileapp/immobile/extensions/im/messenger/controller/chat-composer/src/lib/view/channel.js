/**
 * @module im/messenger/controller/chat-composer/lib/view/channel
 */
jn.define('im/messenger/controller/chat-composer/lib/view/channel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { DialogType } = require('im/messenger/const');
	const { Icon } = require('ui-system/blocks/icon');
	const { Box } = require('ui-system/layout/box');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { DialogInfo } = require('im/messenger/controller/chat-composer/lib/area/dialog-info');
	const { SettingsPanel } = require('im/messenger/controller/chat-composer/lib/area/settings-panel');
	const { ComposerDialogType } = require('im/messenger/controller/chat-composer/lib/const');
	const {
		dialogTypeAction,
		participantsAction,
		managersAction,
		rulesAction,
	} = require('im/messenger/controller/chat-composer/lib/actions');

	/**
	 * @class ChannelView
	 * @typedef {LayoutComponent<ChannelViewProps, ChannelViewState>} ChannelView
	 */
	class ChannelView extends LayoutComponent
	{
		static openToCreate(props)
		{
			return new this({
				...props,
				isCreate: true,
			});
		}

		static openToEdit(props)
		{
			return new this({
				...props,
				isCreate: false,
			});
		}

		constructor(props)
		{
			super(props);
			this.state = {
				name: props.title,
				description: props.description,
				avatar: props.avatar,
				type: props.type,
				userCounter: props.userCounter,
				managerCounter: props.managerCounter,
				permissions: props.permissions,
				isInputChanged: false,
				updateDialogInfoState: false,
			};
			this.stateDialogInfo = {
				title: props.title,
				description: props.description,
				inputRef: null,
			};
		}

		componentWillUnmount()
		{
			this.props.callbacks.onDestroy();
		}

		render()
		{
			return Box(
				{
					withScroll: false,
					footer: this.renderCreateFooter() || this.renderDoneFooter(),
					resizableByKeyboard: true,
					onClick: this.onClickBox.bind(this),
				},
				this.renderEntityInfo(),
				this.renderSettingsPanel(),
			);
		}

		renderCreateFooter()
		{
			if (!this.props.isCreate)
			{
				return null;
			}

			return this.renderBoxFooter(
				{
					locKeyboardButton: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_FOOTER_CREATE_BUTTON'),
					locBottomButton: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_FOOTER_CREATE_BUTTON'),
					onClick: this.onClickCreateButton.bind(this),
				},
			);
		}

		renderDoneFooter()
		{
			if (this.state.isInputChanged === false)
			{
				return null;
			}

			return this.renderBoxFooter(
				{
					locKeyboardButton: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_FOOTER_DONE_BUTTON'),
					locBottomButton: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_FOOTER_DONE_BUTTON'),
					isShowKeyboard: true,
					onClick: this.onClickDoneButton.bind(this),
				},
			);
		}

		renderBoxFooter({ locKeyboardButton, locBottomButton, onClick, isShowKeyboard = false })
		{
			const { safeArea } = this.props;

			return BoxFooter(
				{
					safeArea,
					isShowKeyboard,
					keyboardButton: {
						text: locKeyboardButton,
						color: Color.baseWhiteFixed,
						onClick,
					},
				},
				Button({
					testId: 'BUTTON_SAVE',
					text: locBottomButton,
					stretched: true,
					size: ButtonSize.L,
					onClick,
				}),
			);
		}

		renderEntityInfo()
		{
			const dialogInfoProps = {
				title: {
					placeholder: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_INFO_TITLE_INPUT_PLACEHOLDER'),
					value: this.props.isCreate ? '' : this.state.name,
					onChange: this.onChangeTextInput.bind(this),
					onFocusTitle: this.onFocusTextInput.bind(this),
				},
				avatar: {
					type: ComposerDialogType.channel,
					preview: this.props.isCreate ? '' : this.state.avatar,
					onChange: this.onChangeAvatar.bind(this),
				},
				description: {
					placeholder: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_INFO_DESCRIPTION_INPUT_PLACEHOLDER_CHANNEL'),
					value: this.props.isCreate ? '' : this.state.description,
					onChange: this.onChangeTextInput.bind(this),
					onFocusDesc: this.onFocusTextInput.bind(this),
				},
				shouldForceUpdateState: this.state.updateDialogInfoState,
			};

			if (!this.props.isCreate && !this.state.permissions.update)
			{
				delete dialogInfoProps.description;
			}

			return new DialogInfo(dialogInfoProps);
		}

		renderSettingsPanel()
		{
			if (!this.props.isCreate && !this.state.permissions.update)
			{
				return null;
			}

			const actionList = this.createActionList();

			return View(
				{
					style: {
						marginTop: Indent.XL4.toNumber(),
						paddingRight: Indent.XL3.toNumber(),
						paddingLeft: Indent.XL3.toNumber(),
					},
				},
				new SettingsPanel({
					actionList,
				}),
			);
		}

		createActionList()
		{
			const actions = [
				dialogTypeAction({
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_TYPE_TITLE_CHANNEL'),
					subtitle: this.getDialogTypeSubtitle(this.state.type),
					icon: this.getActionIcon(),
					onClick: this.props.callbacks.onClickDialogTypeAction,
				}),
				participantsAction(
					{
						title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_SUBSCRIBERS_TITLE'),
						subtitle: this.getParticipantCountSubtitle(this.state.userCounter),
						icon: this.getActionIcon(),
						onClick: this.props.callbacks.onClickParticipantAction,
					},
				),
			];

			if (!this.props.isCreate)
			{
				actions.push(
					managersAction(
						{
							subtitle: this.getManagerCountSubtitle(this.state.managerCounter),
							icon: this.getActionIcon(),
							onClick: this.props.callbacks.onClickManagersAction,
						},
					),
					rulesAction(
						{
							icon: this.getActionIcon(),
							divider: false,
							onClick: this.props.callbacks.onClickRulesAction,
						},
					),
				);
			}

			return actions;
		}

		/**
		 * @return {Icon}
		 */
		getActionIcon()
		{
			return Icon.CHEVRON_TO_THE_RIGHT;
		}

		/**
		 * @param {string} dialogType
		 * @return {string}
		 */
		getDialogTypeSubtitle(dialogType)
		{
			return dialogType === DialogType.openChannel
				? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_OPEN_TITLE')
				: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_CLOSE_TITLE');
		}

		/**
		 * @param {number} participantCounter
		 * @return {string}
		 */
		getParticipantCountSubtitle(participantCounter)
		{
			if (this.props.isCreate && this.state.userCounter === 0)
			{
				return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_SUBSCRIBERS_SUBTITLE');
			}

			return Loc.getMessagePlural(
				'IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_SUBSCRIBERS_SUBTITLE',
				participantCounter,
				{
					'#COUNT#': participantCounter,
				},
			);
		}

		/**
		 * @param {number} managerCounter
		 * @return {string}
		 */
		getManagerCountSubtitle(managerCounter)
		{
			return Loc.getMessagePlural(
				'IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_MANAGERS_SUBTITLE',
				managerCounter,
				{
					'#COUNT#': managerCounter,
				},
			);
		}

		/**
		 * @param {EntityInfoState} inputState
		 */
		onChangeTextInput(inputState)
		{
			if (inputState?.isInputChanged !== this.state.isInputChanged)
			{
				this.setState({ ...this.state, isInputChanged: inputState.isInputChanged, updateDialogInfoState: false });
			}
			this.stateDialogInfo = inputState; // this state is not set in this.setState() to optimize the amount of rendering
		}

		/**
		 * @param {LayoutComponent} inputRef
		 */
		onFocusTextInput({ inputRef })
		{
			this.stateDialogInfo.inputRef = inputRef;
		}

		onClickDoneButton()
		{
			const changedFields = {};
			const newState = {};
			if (this.stateDialogInfo.title !== this.state.name)
			{
				changedFields.title = this.stateDialogInfo.title.trim();
				newState.name = this.stateDialogInfo.title.trim();
			}

			if (!Type.isNil(this.stateDialogInfo?.description) && this.stateDialogInfo.description !== this.state.description)
			{
				changedFields.description = this.stateDialogInfo.description.trim();
				newState.description = this.stateDialogInfo.description.trim();
			}

			this.stateDialogInfo.inputRef?.blur({ hideKeyboard: true });
			this.setState({
				...this.state,
				...newState,
				isInputChanged: false,
				updateDialogInfoState: true,
			});
			this.props.callbacks?.onClickDoneButton(changedFields);
		}

		onClickCreateButton()
		{
			this.props.callbacks?.onClickCreateButton(this.stateDialogInfo);
		}

		onClickBox()
		{
			this.stateDialogInfo?.inputRef?.blur?.({ hideKeyboard: true });
		}

		/**
		 * @param {object} event
		 * @param {string} event.avatar
		 * @param {string} event.preview
		 */
		onChangeAvatar(event)
		{
			this.props.callbacks?.onChangeAvatar(event.avatar, event.preview);
		}
	}

	module.exports = { ChannelView };
});
