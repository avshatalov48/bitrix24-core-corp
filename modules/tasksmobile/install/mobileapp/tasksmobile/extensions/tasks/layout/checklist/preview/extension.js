/**
 * @module tasks/layout/checklist/preview
 */
jn.define('tasks/layout/checklist/preview', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PropTypes } = require('utils/validation');
	const { checklistPreviewStub } = require('tasks/layout/checklist/preview/src/stub');

	/**
	 * @class ChecklistPreview
	 */
	class ChecklistPreview extends LayoutComponent
	{
		updateChecklistsView()
		{
			this.setState({});
		}

		openPageManager(checklist)
		{
			const { checklistController } = this.props;

			checklistController.openChecklist(checklist);
		}

		renderProgressView(rootItem)
		{
			const completedCount = rootItem.getCompleteCount();
			const totalCount = rootItem.getTotalCount();

			let fontSize = (completedCount > 9 || totalCount > 9) ? 8 : 9;
			fontSize = (completedCount > 9 && totalCount > 9) ? 7 : fontSize;

			const progressText = `${completedCount}/${totalCount}`;

			return ProgressView(
				{
					params: {
						type: 'circle',
						currentPercent: this.getPercent(completedCount, totalCount),
						color: AppTheme.colors.accentExtraDarkblue,
					},
					style: {
						width: 23,
						height: 23,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: AppTheme.colors.base5,
					},
				},
				View(
					{
						style: {
							position: 'absolute',
							width: 20,
							height: 20,
							justifyContent: 'center',
							alignItems: 'center',
							borderRadius: 10,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					Text({
						style: {
							fontSize,
							textAlign: 'center',
						},
						numberOfLines: 1,
						text: progressText,
					}),
				),
			);
		}

		getPercent(completedCount, totalCount)
		{
			return parseInt(
				(completedCount > 0 ? (completedCount * 100 / totalCount) : 0).toFixed(0),
				10,
			);
		}

		renderCheckLists()
		{
			const { isLoading, checklistController } = this.props;
			const checklists = checklistController.getChecklists();
			const content = [];

			checklists.forEach((checklist, i) => {
				const needMargin = i !== 0;
				const rootItem = checklist.getRootItem();

				content.push(
					checklistPreviewStub({
						isLoading,
						margin: needMargin,
						onClick: () => {
							this.openPageManager(checklist);
						},
						content: View(
							{
								style: {
									flexDirection: 'row',
									width: '100%',
									alignItems: 'center',
								},
							},
							this.renderProgressView(rootItem),
							Text({
								text: rootItem.getTitle(),
								style: {
									marginLeft: 6,
									fontSize: 16,
									fontWeight: '400',
									color: AppTheme.colors.base4,
								},
							}),
						),
					}),
				);
			});

			return content;
		}

		render()
		{
			const { checklistController } = this.props;
			const { handleOnCreateChecklist } = checklistController;

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...this.renderCheckLists(),
				checklistPreviewStub({
					margin: true,
					onClick: () => {
						handleOnCreateChecklist();
					},
				}),
			);
		}
	}

	ChecklistPreview.propTypes = {
		checkListTree: PropTypes.object,
		parentWidget: PropTypes.object,
		isLoading: PropTypes.bool,
	};

	module.exports = { ChecklistPreview };
});
