/**
 * @module stafftrack/check-in/message
 */
jn.define('stafftrack/check-in/message', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { showToast } = require('toast');
	const { Color, Indent, Component, Corner } = require('tokens');
	const { outline: { check } } = require('assets/icons');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { PureComponent } = require('layout/pure-component');
	const { Haptics } = require('haptics');

	const { Text4 } = require('ui-system/typography/text');
	const { Link4, LinkDesign, LinkMode } = require('ui-system/blocks/link');
	const { Switcher, SwitcherMode } = require('ui-system/blocks/switcher');

	const { ImageSelector } = require('stafftrack/check-in/image-selector');
	const { Analytics } = require('stafftrack/analytics');
	const { ScrollViewWithMaxHeight, TextInputWithMaxHeight } = require('stafftrack/ui');
	/**
	 * @class Message
	 */
	class Message extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				keyboardShow: false,
				sendMessage: Type.isNil(this.props.sendMessage) ? true : this.props.sendMessage,
				rawValue: this.props.defaultValue || '',
				dialogId: this.props.dialogId || null,
				dialogName: this.props.dialogName || null,
			};

			this.layoutWidget = this.props.layoutWidget || PageManager;
			this.textFieldRef = null;
			this.imageSelectorRef = null;

			this.openChatSelector = this.openChatSelector.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnBlur = this.handleOnBlur.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.onSwitcherClick = this.onSwitcherClick.bind(this);
			this.showAlreadyCheckInToast = this.showAlreadyCheckInToast.bind(this);

			Keyboard.on(Keyboard.Event.WillHide, () => this.blur());
		}

		get userAvatar()
		{
			return this.props.userInfo?.avatar;
		}

		get userName()
		{
			return this.props.userInfo?.name;
		}

		get userId()
		{
			return this.props.userInfo?.id;
		}

		get isCancelReason()
		{
			return this.props.isCancelReason;
		}

		render()
		{
			return View(
				{
					style: {
						paddingBottom: Indent.L.toNumber(),
					},
				},
				this.renderHeader(),
				this.renderContent(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingHorizontal: this.isCancelReason ? 0 : Component.paddingLr.toNumber(),
					},
					onClick: this.onSwitcherClick,
				},
				Switcher({
					onClick: this.onSwitcherClick,
					useState: false,
					mode: SwitcherMode.SOLID,
					checked: this.state.sendMessage,
					testId: `stafftrack-message${this.isCancelReason ? '-cancel' : ''}-switcher`,
					style: {
						marginRight: Indent.L.toNumber(),
					},
				}),
				Text4({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_MESSAGE'),
					color: this.state.sendMessage ? Color.base3 : Color.base4,
				}),
				this.props.readOnly && this.renderSelectedChatText(),
				!this.props.readOnly && this.renderChatSelectorLink(),
			);
		}

		renderChatSelectorLink()
		{
			return Link4({
				onClick: this.openChatSelector,
				text: this.state.dialogName || Loc.getMessage('M_STAFFTRACK_CHECK_IN_SELECT_CHAT'),
				design: LinkDesign.BLACK,
				mode: LinkMode.DASH,
				color: this.state.sendMessage ? Color.base1 : Color.base4,
				useInAppLink: false,
				numberOfLines: 1,
				style: {
					flex: 1,
					marginLeft: Indent.XS.toNumber(),
				},
				testId: `stafftrack-message${this.isCancelReason ? '-cancel' : ''}-chat-selector`,
			});
		}

		renderSelectedChatText()
		{
			return Text4({
				numberOfLines: 1,
				ellipsize: 'end',
				text: this.state.dialogName || Loc.getMessage('M_STAFFTRACK_CHECK_IN_SELECT_CHAT'),
				color: this.state.sendMessage ? Color.base1 : Color.base4,
				style: {
					flex: 1,
					marginLeft: Indent.XS.toNumber(),
				},
				testId: `stafftrack-message${this.isCancelReason ? '-cancel' : ''}-selected-chat-text`,
			});
		}

		renderContent()
		{
			return View(
				{
					style: {
						paddingLeft: this.isCancelReason ? 0 : Component.paddingLr.toNumber(),
						flexDirection: 'row',
						opacity: this.isSendDisabled()
							? 0.35
							: 1
						,
					},
				},
				this.renderAvatar(),
				this.renderMessageInput(),
				!this.isCancelReason && this.renderImageSelector(),
			);
		}

		openChatSelector()
		{
			if (!this.state.sendMessage)
			{
				return;
			}

			void requireLazy('im:messenger/api/dialog-selector').then(({ DialogSelector }) => {
				if (DialogSelector)
				{
					const selector = new DialogSelector();
					selector.show({
						title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CHOOSE_CHAT'),
						layout: this.layoutWidget,
					})
						// eslint-disable-next-line promise/no-nesting
						.then((result) => this.onChatSelect(result))
						// eslint-disable-next-line promise/no-nesting
						.catch((error) => console.error(error));
				}
			});
		}

		onChatSelect(data)
		{
			if (!Type.isNil(data.dialogId) && !Type.isNil(data.name))
			{
				this.setState({
					dialogId: data.dialogId,
					dialogName: data.name,
				});
			}
		}

		renderAvatar()
		{
			const size = 36;

			return View(
				{
					style: {
						marginRight: Indent.M.toNumber(),
						marginTop: Indent.XL2.toNumber(),
						minWidth: size + 1,
					},
				},
				Avatar({
					testId: `stafftrack-message${this.isCancelReason ? '-cancel' : ''}-avatar`,
					size,
					id: this.userId,
					uri: this.userAvatar,
					name: this.userName,
				}),
			);
		}

		renderMessageInput()
		{
			return View(
				{
					style: {
						borderColor: this.state.keyboardShow
							? Color.accentMainPrimary.toHex()
							: Color.bgSeparatorPrimary.toHex(),
						borderWidth: 1,
						borderRadius: Corner.M.toNumber(),
						flex: 1,
						maxHeight: this.props.readOnly ? 76 : null,
						marginTop: Indent.XL2.toNumber(),
						paddingHorizontal: Indent.L.toNumber(),
						paddingVertical: Indent.M.toNumber(),
					},
				},
				!this.props.readOnly && this.renderTextField(),
				this.props.readOnly && this.renderDefaultMessageText(),
			);
		}

		renderTextField()
		{
			return new TextInputWithMaxHeight({
				testId: `stafftrack-message${this.isCancelReason ? '-cancel' : ''}-input`,
				value: this.state.rawValue,
				enable: this.isInputEnabled(),
				placeholder: this.props.placeholder,
				placeholderTextColor: Color.base4.toHex(),
				onChangeText: this.handleOnChange,
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				style: {
					color: Color.base1.toHex(),
					maxHeight: 60,
				},
				ref: (ref) => {
					this.textFieldRef = ref;
				},
			});
		}

		renderDefaultMessageText()
		{
			return new ScrollViewWithMaxHeight({
				testId: `stafftrack-message${this.isCancelReason ? '-cancel' : ''}-text`,
				onClick: this.showAlreadyCheckInToast,
				style: {
					minHeight: 20,
					maxHeight: 60,
				},
				renderContent: () => Text4({
					text: this.state.rawValue,
					color: Color.base1,
				}),
			});
		}

		renderImageSelector()
		{
			return new ImageSelector({
				sendMessage: this.state.sendMessage,
				readOnly: this.props.readOnly,
				diskFolderId: this.props.diskFolderId,
				showAlreadyCheckInToast: this.showAlreadyCheckInToast,
				ref: (ref) => {
					this.imageSelectorRef = ref;
				},
			});
		}

		handleOnChange(value)
		{
			this.state.rawValue = value;
		}

		blur()
		{
			this.textFieldRef?.blur();
		}

		focus()
		{
			this.textFieldRef?.focus();
		}

		handleOnBlur()
		{
			this.setState({ keyboardShow: false });

			if (this.props.onBlurText)
			{
				this.props.onBlurText();
			}
		}

		handleOnFocus()
		{
			this.setState({ keyboardShow: true });

			if (this.props.onFocusText)
			{
				this.props.onFocusText();
			}
		}

		// eslint-disable-next-line consistent-return
		onSwitcherClick()
		{
			if (this.props.readOnly)
			{
				return this.showAlreadyCheckInToast();
			}

			const sendMessage = !this.state.sendMessage;

			if (!this.isCancelReason)
			{
				Analytics.sendSetupChat(sendMessage);
			}

			this.setState({ sendMessage });

			Haptics.impactLight();

			if (this.props.onSwitcherClick)
			{
				this.props.onSwitcherClick(sendMessage);
			}
		}

		getCurrentDialogId()
		{
			return this.state.dialogId;
		}

		getCurrentDialogName()
		{
			return this.state.dialogName;
		}

		getMessage()
		{
			if (this.isCancelReason)
			{
				return this.state.rawValue;
			}

			return this.state.sendMessage
				? this.state.rawValue || Loc.getMessage('M_STAFFTRACK_CHECK_IN_DEFAULT_MESSAGE')
				: null
			;
		}

		getDialogId()
		{
			return this.state.sendMessage
				? this.state.dialogId
				: null
			;
		}

		getFileId()
		{
			return this.imageSelectorRef?.getFileId();
		}

		canTypeMessage()
		{
			return !this.props.readOnly && this.state.sendMessage;
		}

		isSendDisabled()
		{
			return !this.props.readOnly && !this.state.sendMessage && !this.isCancelReason;
		}

		isInputEnabled()
		{
			if (this.isCancelReason)
			{
				return true;
			}

			return this.state.sendMessage;
		}

		showAlreadyCheckInToast()
		{
			if (this.props.readOnly)
			{
				this.previousToast?.close();
				this.previousToast = showToast({
					message: Loc.getMessage('M_STAFFTRACK_CHECK_IN_ALREADY_SENT'),
					svg: {
						content: check(),
					},
					backgroundColor: Color.bgContentInapp.toHex(),
				});

				Haptics.notifyWarning();
			}
		}

		changeMessageContent(rawValue)
		{
			this.setState({ rawValue });
		}
	}

	module.exports = { Message };
});
