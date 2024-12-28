/**
 * @module crm/timeline/item/ui/body/blocks/base-editable-block
 */
jn.define('crm/timeline/item/ui/body/blocks/base-editable-block', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { EditableTextBlock } = require('layout/ui/editable-text-block');
	const AppTheme = require('apptheme');

	/**
	 * @abstract
	 * @class TimelineItemBodyBaseEditableBlock
	 */
	class TimelineItemBodyBaseEditableBlock extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			this.state = {
				text: this.props.text,
				editable: this.props.hasOwnProperty('editable') && this.props.editable,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state.text = props.text;
		}

		render()
		{
			const textProps = this.getTextParams();
			const editorProps = this.getEditorParams();

			return View(
				{
					testId: 'TimelineItemBodyEditableDescriptionContainer',
				},
				new EditableTextBlock(
					{
						value: this.state.text,
						showEditIcon: this.state.editable,
						onSave: (text) => this.onSave(text),
						textProps,
						editorProps,
						editIconTestId: 'TimelineItemBodyEditableDescriptionEdit',
					},
				),
			);
		}

		getEditorParams()
		{
			return {
				title: this.getEditorTitle(),
				placeholder: this.getEditorPlaceholder(),
				readOnly: !this.state.editable,
				bbCodeEditorParams: {
					closeOnSave: true,
				},
				useBBCodeEditor: this.props.useBBCodeEditor,
			};
		}

		getEditorTitle()
		{
			return '';
		}

		getEditorPlaceholder()
		{
			return '';
		}

		getTextParams()
		{
			return {
				testId: 'TimelineItemBodyEditableDescriptionText',
				style: {
					fontSize: 14,
					fontWeight: '400',
					color: AppTheme.colors.base1,
				},
				maxLettersCount: this.getMaxLettersCount(),
				maxEntersCount: this.getMaxEntersCount(),
				bbCodeMode: true,
			};
		}

		onSave(text)
		{
			text = text.trim();

			this.setState({ text }, () => {
				if (this.props.saveAction)
				{
					this.emitAction({
						...this.props.saveAction,
						actionParams: this.getPreparedActionParams(),
					});
				}
			});
		}

		getPreparedActionParams()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		getMaxLettersCount()
		{
			if (this.model.hasLowPriority)
			{
				return 30;
			}

			return 330;
		}

		getMaxEntersCount()
		{
			if (this.model.hasLowPriority)
			{
				return 1;
			}

			return 4;
		}
	}

	module.exports = { TimelineItemBodyBaseEditableBlock };
});
