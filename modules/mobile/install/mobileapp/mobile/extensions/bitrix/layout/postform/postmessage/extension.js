(() => {
	class ExpandedTextInputComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				height: null,
			};
		}

		componentWillReceiveProps(newProps)
		{
			if (!newProps.autoExpand)
			{
				this.setState({ height: null });
			}
		}

		onContentSizeChange({ width, height })
		{
			if (!this.props.autoExpand)
			{
				return;
			}

			this.setState({ height }, () => {
				if (typeof this.props.onPostMessageChange === 'function')
				{
					setTimeout(() => {
						this.props.onPostMessageChange({ height });
					}, 1);
				}
			});
		}

		render()
		{
			const { height } = this.state;
			const shouldOverrideHeight = !this.props.style.height;
			const style = shouldOverrideHeight ? {
				style: {

					...this.props.style,
					...(height ? {
						height,
					} : {}),
				},
			} : this.props.style;

			const props = {

				...this.props,
				...style,
				onContentSizeChange: this.onContentSizeChange.bind(this),
			};

			return TextInput(props);
		}
	}

	this.ExpandedTextInput = (props) => new ExpandedTextInputComponent(props);

	this.PostMessage = ({
		actionSheetShown,
		postText,
		backgroundImage,
		coloredMessageBackgroundData,
		deviceHeight,
		deviceRatio,
		moduleVoteInstalled,
		inputTextColor,
		placeholderTextColor,
		checkColoredText,
		rootHeightWithKeyboard,
		marginTop,
		marginBottom,
		onFocus,
		onBlur,
		onChangeText,
		onSelectionChange,
		onInput,
		onRef,
		onPostMessageChange,
		onCursorPositionChange,
		onScrollViewClick,
	}) => {
		const actionSheetLinesCount = (moduleVoteInstalled ? 8 : 7);
		const rootHeightWithActionSheet = parseInt(deviceHeight / deviceRatio, 10)
			- (60 * actionSheetLinesCount)
			- (device.screen.safeArea.bottom)
			- 50;
		const coloredTextHeight = Math.min(
			rootHeightWithActionSheet,
			(rootHeightWithKeyboard || 1_000_000),
		);

		const coloredMessage = (
			backgroundImage
			&& checkColoredText(postText)
		);

		const textInput = ExpandedTextInput({
			testId: 'postMessage',
			ref: onRef,
			autoExpand: !coloredMessage,
			value: postText,
			placeholder: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_TEXT_PLACEHOLDER_MSGVER_2'),
			placeholderTextColor,
			style: {
				color: inputTextColor,
				marginLeft: 10,
				marginRight: 10,
				marginBottom,
				marginTop,
				fontSize: (coloredMessage ? 26 : 18),
				textAlign: (coloredMessage ? 'center' : 'left'),
				textAlignVertical: (coloredMessage ? 'center' : 'top'),
				...(coloredMessage ? { height: coloredTextHeight } : {}),
			},
			onFocus,
			onBlur,
			onChangeText,
			onSelectionChange,
			onInput,
			onPostMessageChange,
			onCursorPositionChange,
			focus: false,
			autoCapitalize: 'sentences',
		});

		const mentionPlaceholder = Text(
			{
				style: {
					textAlignVertical: 'top',
					display: (postText.length > 0 ? 'none' : 'flex'),
					marginLeft: 10,
					marginRight: 10,
					color: placeholderTextColor,
					fontSize: 14,
				},
				text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_MENTION_PLACEHOLDER_MSGVER_1'),
				onTouchesEnded: onScrollViewClick,
			},
		);

		return (
			coloredMessage
				? View(
					{
						style: {
							height: coloredTextHeight + 20,
							...coloredMessageBackgroundData,
						},
					},
					textInput,
				)
				: View(
					{},
					textInput,
					mentionPlaceholder,
				)
		);
	};
})();
