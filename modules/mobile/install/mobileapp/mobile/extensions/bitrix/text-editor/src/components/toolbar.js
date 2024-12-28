/**
 * @module text-editor/components/toolbar
 */
jn.define('text-editor/components/toolbar', (require, exports, module) => {
	const { Type } = require('type');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Icon } = require('assets/icons');
	const { ToolbarButton } = require('text-editor/components/toolbar-button');
	const { Color, Indent } = require('tokens');

	const separatorSvg = '<svg width="10" height="14" viewBox="0 0 10 14" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4.5" width="1" height="14" fill="#E2E2E2"/></svg>';

	class ToolbarComponent extends LayoutComponent
	{
		/**
		 * @param props {{
		 * 		allowFiles: boolean,
		 * 		allowBBCode: boolean,
		 * 		events: {
		 * 			[eventName: string]: ({type: string}) => void,
		 * 		},
		 * 		saveButton?: {[key: string]: any},
		 * }}
		 */
		constructor(props = {})
		{
			super(props);

			if (Type.isPlainObject(props.events))
			{
				Object.entries(props.events).forEach(([eventName, handler]) => {
					this.on(eventName, handler);
				});
			}

			this.state = {
				highlight: [],
				isShown: false,
				isSaveButtonLoading: false,
				saveButton: props.saveButton,
				allowFiles: props.allowFiles,
			};
		}

		setSaveButtonLoading(value)
		{
			this.setState({
				isSaveButtonLoading: value,
			});
		}

		forceSaveButtonProps(saveButtonProps)
		{
			this.setState({
				saveButton: saveButtonProps,
			});
		}

		forceAllowFiles(value)
		{
			this.setState({
				allowFiles: value,
			});
		}

		show()
		{
			this.setState({
				isShown: true,
				isSaveButtonLoading: false,
			});
		}

		hide()
		{
			this.setState({
				isShown: false,
				isSaveButtonLoading: false,
			});
		}

		highlightButtons(activeButtons)
		{
			this.setState({
				highlight: activeButtons,
			});
		}

		render()
		{
			const { allowBBCode, testId } = this.props;
			const {
				highlight,
				isShown,
				isSaveButtonLoading,
				saveButton = {},
				allowFiles,
			} = this.state;

			return View(
				{
					style: {
						display: isShown ? 'flex' : 'none',
						flexDirection: 'row',
						justifyContent: (allowBBCode ? 'space-between' : 'flex-end'),
						alignItems: 'center',
						height: 52,
						marginHorizontal: Indent.XL3.toNumber(),
					},
				},
				allowBBCode && ToolbarButton({
					icon: Icon.BOLD,
					active: highlight.includes('bold'),
					onClick: () => {
						this.emit('onFormat', [{ type: 'bold' }]);
					},
					testId: `${testId}_BOLD`,
				}),
				allowBBCode && ToolbarButton({
					icon: Icon.ITALIC,
					active: highlight.includes('italic'),
					onClick: () => {
						this.emit('onFormat', [{ type: 'italic' }]);
					},
					testId: `${testId}_ITALIC`,
				}),
				allowBBCode && ToolbarButton({
					icon: Icon.UNDERLINE,
					active: highlight.includes('underline'),
					onClick: () => {
						this.emit('onFormat', [{ type: 'underline' }]);
					},
					testId: `${testId}_UNDERLINE`,
				}),
				allowBBCode && ToolbarButton({
					icon: Icon.STRIKETHROUGH,
					active: highlight.includes('strikethrough'),
					onClick: () => {
						this.emit('onFormat', [{ type: 'strikethrough' }]);
					},
					testId: `${testId}_STRIKE`,
				}),
				allowBBCode && ToolbarButton({
					icon: Icon.BULLETED_LIST,
					active: highlight.includes('bulletList'),
					onClick: () => {
						this.emit('onFormat', [{ type: 'markedList' }]);
					},
					testId: `${testId}_MARKED_LIST`,
				}),
				allowBBCode && ToolbarButton({
					icon: Icon.NUMBERED_LIST,
					active: highlight.includes('numberList'),
					onClick: () => {
						this.emit('onFormat', [{ type: 'numericList' }]);
					},
					testId: `${testId}_NUMBER_LIST`,
				}),
				allowBBCode && Image({
					style: {
						width: 1,
						height: 10,
						marginLeft: 12,
						marginRight: 10,
					},
					tintColor: Color.base5.toHex(),
					svg: {
						content: separatorSvg,
					},
					testId: `${testId}_SEPARATOR`,
				}),
				allowBBCode && ToolbarButton({
					icon: Icon.MENTION,
					onClick: () => {
						this.emit('onMention', [{ type: 'onMention' }]);
					},
					testId: `${testId}_MENTION`,
				}),
				allowBBCode && allowFiles && ToolbarButton({
					icon: Icon.ATTACH,
					onClick: () => {
						this.emit('onAttach', [{ type: 'onAttach' }]);
					},
					testId: `${testId}_ATTACH`,
				}),
				Button({
					leftIcon: Icon.ARROW_TOP,
					size: ButtonSize.S,
					design: ButtonDesign.FILLED,
					loading: isSaveButtonLoading,
					style: {
						alignSelf: 'center',
					},
					...saveButton,
					onClick: () => {
						this.setSaveButtonLoading(true);
						this.emit('onSave', [{}]);
					},
					testId: `${testId}_SAVE`,
				}),
			);
		}
	}

	module.exports = {
		ToolbarComponent,
	};
});
