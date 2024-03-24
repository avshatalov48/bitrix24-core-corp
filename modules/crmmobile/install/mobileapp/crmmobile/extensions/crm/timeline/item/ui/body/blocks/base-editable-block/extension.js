/**
 * @module crm/timeline/item/ui/body/blocks/base-editable-block
 */
jn.define('crm/timeline/item/ui/body/blocks/base-editable-block', (require, exports, module) => {
	const { largePen } = require('assets/common');
	const { inAppUrl } = require('in-app-url');
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineTextEditor } = require('crm/timeline/ui/text-editor');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
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

			this.textEditorLayout = null;
		}

		componentWillReceiveProps(props)
		{
			this.state.text = props.text;
		}

		render()
		{
			return View(
				{
					testId: 'TimelineItemBodyEditableDescriptionContainer',
				},
				View(
					{
						style: {
							paddingBottom: 15,
							paddingTop: this.state.text.length > 0 ? 14 : 30,
							paddingLeft: 16,
							paddingRight: 30,
							borderWidth: 1,
							borderColor: AppTheme.colors.bgSeparatorPrimary,
							borderRadius: 12,
						},
						onLongClick: () => this.openEditor(),
					},
					this.renderEditIcon(),
					this.renderText(),
				),
			);
		}

		renderEditIcon()
		{
			if (this.isReadonly || !this.state.editable)
			{
				return null;
			}

			return View(
				{
					testId: 'TimelineItemBodyEditableDescriptionEdit',
					onClick: () => this.openEditor(),
					style: {
						position: 'absolute',
						right: 0,
						top: 3,
						paddingHorizontal: 16,
						paddingVertical: 14,
					},
				},
				Image({
					tintColor: AppTheme.colors.base3,
					svg: {
						content: largePen(),
					},
					style: {
						width: 18,
						height: 18,
					},
				}),
			);
		}

		openEditor()
		{
			if (this.isReadonly || !this.state.editable)
			{
				return;
			}

			TimelineTextEditor.open({
				title: this.getEditorTitle(),
				text: this.state.text,
				required: true,
				placeholder: this.getEditorPlaceholder(),
				onSave: (text) => this.onSave(text),
				onLinkClick: ({ url }) => this.onLinkClick(url),
			}).then(({ layout }) => {
				this.textEditorLayout = layout;
			});
		}

		getEditorTitle()
		{
			return '';
		}

		getEditorPlaceholder()
		{
			return '';
		}

		renderText()
		{
			const props = this.getTextParams();
			props.value = this.state.text;

			return new CollapsibleText(props);
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

		onLinkClick(url)
		{
			inAppUrl.open(url, {
				backdrop: true,
				parentWidget: this.textEditorLayout,
			});
		}

		onSave(text)
		{
			this.textEditorLayout = null;

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
