/**
 * @module tasks/layout/checklist/preview
 */
jn.define('tasks/layout/checklist/preview', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Random } = require('utils/random');
	const { ChecklistController } = require('tasks/checklist');
	const { PropTypes } = require('utils/validation');
	const { checklistPreviewStub } = require('tasks/layout/checklist/preview/src/stub');

	/**
	 * @class ChecklistPreview
	 */
	class ChecklistPreview extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.controller = null;
			this.handleOnChange = this.handleOnChange.bind(this);

			this.initChecklistField(props);
		}

		componentWillReceiveProps(props)
		{
			this.initChecklistField(props);
		}

		initChecklistField(props)
		{
			this.controller = new ChecklistController({ ...props, onChange: this.handleOnChange });

			this.state = {
				reload: Random.getString(),
			};
		}

		handleOnChange()
		{
			this.setState({ reload: Random.getString() });
		}

		openPageManager(checklist)
		{
			const { parentWidget } = this.props;

			this.controller.openChecklist({ checklist, parentWidget });
		}

		renderProgressView(rootItem)
		{
			const completedCount = rootItem.getCompleteCount();
			const totalCount = rootItem.getDescendantsCount();

			let fontSize = (completedCount > 9 || totalCount > 9) ? 8 : 9;
			fontSize = (completedCount > 9 && totalCount > 9) ? 7 : fontSize;

			const progressText = `${completedCount}/${totalCount}`;

			return ProgressView(
				{
					testId: 'checklist_Items_count_in_task',
					params: {
						type: 'circle',
						currentPercent: this.getPercent(completedCount, totalCount),
						color: AppTheme.colors.accentExtraDarkblue,
					},
					style: {
						borderRadius: 30,
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

		getSortedChecklists()
		{
			const checklists = this.controller.getChecklists();

			return [...checklists.values()].sort((a, b) => {
				const itemA = a.getRootItem()?.getSortIndex();
				const itemB = b.getRootItem()?.getSortIndex();

				return itemB - itemA;
			});
		}

		renderChecklists()
		{
			const { isLoading } = this.props;
			const content = [];

			this.getSortedChecklists().forEach((checklist, i) => {
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
								testId: 'checklist_title_in_task',
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
			const { parentWidget, ThemeComponent } = this.props;
			const { handleOnCreateChecklist } = this.controller;

			if (ThemeComponent)
			{
				return this.props.ThemeComponent({ field: this, handleOnCreateChecklist });
			}

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...this.renderChecklists(),
				checklistPreviewStub({
					margin: true,
					onClick: handleOnCreateChecklist(parentWidget),
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
