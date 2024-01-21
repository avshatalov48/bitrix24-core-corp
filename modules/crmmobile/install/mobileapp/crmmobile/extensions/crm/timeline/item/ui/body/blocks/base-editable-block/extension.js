/**
 * @module crm/timeline/item/ui/body/blocks/base-editable-block
 */
jn.define('crm/timeline/item/ui/body/blocks/base-editable-block', (require, exports, module) => {
	const { largePen } = require('assets/common');
	const { inAppUrl } = require('in-app-url');
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineTextEditor } = require('crm/timeline/ui/text-editor');
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');

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
				expanded: false,
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
						onClick: () => this.toggleExpanded(),
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
			props.value = this.prepareTextToRender(this.state.text);

			return BBCodeText(props);
		}

		prepareTextToRender(text)
		{
			const maxLettersCount = this.getMaxLettersCount();
			if (this.state.expanded || text.length <= maxLettersCount)
			{
				return text;
			}

			return `${text.slice(0, maxLettersCount).trim()}... [color=${AppTheme.colors.base3}]${Loc.getMessage(
				'M_CRM_TIMELINE_VIEW_MORE')}[/color]`;
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
			};
		}

		toggleExpanded()
		{
			if (this.state.text.length > this.getMaxLettersCount())
			{
				this.setState({ expanded: !this.state.expanded });
			}
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
				return 35;
			}

			return 330;
		}
	}

	module.exports = { TimelineItemBodyBaseEditableBlock };
});
