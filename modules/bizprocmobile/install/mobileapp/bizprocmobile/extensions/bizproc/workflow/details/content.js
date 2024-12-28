/**
 * @module bizproc/workflow/details/content
 */
jn.define('bizproc/workflow/details/content', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');
	const { PureComponent } = require('layout/pure-component');
	const { EntityManager } = require('layout/ui/entity-editor/manager');
	const { CollapsibleText } = require('layout/ui/collapsible-text');

	class WorkflowDetailsContent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
		}

		get layout()
		{
			return this.props.layout;
		}

		get workflow()
		{
			return this.props.workflow;
		}

		get editorConfig()
		{
			return this.props.editorConfig;
		}

		render()
		{
			return View(
				{},
				View(
					{
						style: {
							paddingHorizontal: 10,
							borderRadius: 12,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					this.renderDocumentName(),
					this.renderDescription(),
				),
				this.renderEditor(),
			);
		}

		renderDocumentName()
		{
			return this.workflow.documentTitle && Text(
				{
					testId: 'WORKFLOW_DETAILS_DOCUMENT_NAME',
					style: {
						fontWeight: '600',
						fontSize: 18,
						lineHeightMultiple: 1.22,
						color: AppTheme.colors.base1,
						marginBottom: 12,
					},
					text: this.workflow.documentTitle,
				},
			);
		}

		renderDescription()
		{
			const collapsibleText = this.workflow.description && (new CollapsibleText({
				bbCodeMode: true,
				testId: 'WORKFLOW_DETAILS_DESCRIPTION',
				value: this.workflow.description,
				containerStyle: {
					flexGrow: 0,
				},
				style: {
					fontWeight: '400',
					fontSize: 14,
					lineHeightMultiple: 1.28,
					color: AppTheme.colors.base2,
					marginBottom: 12,
				},
				onLinkClick: ({ url }) => {
					inAppUrl.open(url, { parentWidget: this.layout });
				},
			}));

			if (collapsibleText && !this.canRenderEditor())
			{
				collapsibleText.toggleExpand();
			}

			return collapsibleText;
		}

		renderEditor()
		{
			if (this.canRenderEditor())
			{
				return EntityManager.create({
					uid: this.uid,
					layout: this.layout,
					editorProps: this.editorConfig,
					isEmbedded: true,
				});
			}

			return (
				!this.props.canView
				&& this.props.showRightError
				&& this.renderEmptyBlock(Loc.getMessage('M_BP_WORKFLOW_DETAILS_NO_RIGHT'))
			);
		}

		renderEmptyBlock(message)
		{
			return View(
				{
					style: {
						borderRadius: 12,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						marginTop: 12,
					},
				},
				Text({
					style: {
						marginHorizontal: 24,
						marginVertical: 16,
						color: AppTheme.colors.base5,
						fontSize: 14,
						fontWeight: '400',
						textAlign: 'center',
					},
					text: message,
				}),
			);
		}

		canRenderEditor()
		{
			return Boolean(this.props.canView && this.editorConfig);
		}
	}

	module.exports = { WorkflowDetailsContent };
});
