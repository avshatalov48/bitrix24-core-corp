/**
 * @module tasks/layout/checklist/list/src/root-item
 */
jn.define('tasks/layout/checklist/list/src/root-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { BaseChecklistItem } = require('tasks/layout/checklist/list/src/base-item');

	const FOCUS = 'focus';
	const BLUR = 'blur';

	/**
	 * @class RootChecklistItem
	 */
	class RootChecklistItem extends BaseChecklistItem
	{
		render()
		{
			return this.renderContent([
				View(
					{
						testId: 'checklist_root_title',
						style: {
							flexDirection: 'column',
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'flex-start',
							},
							onClick: () => {
								this.textInputFocus();
							},
						},
						this.renderTextField(),
					),
					this.renderDescription(),
				),
			]);
		}

		renderDescription()
		{
			const { item } = this.props;

			const getCompleteCount = `${item.countCompletedCount(true)}/${item.getDescendantsCount(true)}`;

			return View(
				{
					testId: 'checklist_items_count',
					style: {
						marginTop: Indent.S,
					},
				},
				Text({
					style: {
						fontSize: 12,
						color: Color.base3,
					},
					text: `${getCompleteCount} ${Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_DONE')}`,
				}),
			);
		}

		getTextFieldStyle()
		{
			return {
				fontWeight: '500',
				color: Color.base1,
			};
		}

		/**
		 * @protected
		 * @param {CheckListFlatTreeItem} item
		 */
		getPlaceholder(item)
		{
			return item.getPrevTitle() || Loc.getMessage('TASKSMOBILE_LAYOUT_LIST_INPUT_PLACEHOLDER');
		}

		handleOnChangeTitle(title)
		{
			const { item } = this.props;

			item.setTitle(title);
			item.setIsNew(false);
			this.handleOnChange();
		}

		handleOnBlur()
		{
			const { item } = this.props;

			this.toggleChecklistRootTitle(item, BLUR);

			super.handleOnBlur();
		}

		handleOnFocus()
		{
			const { item, hideMenu } = this.props;

			this.toggleChecklistRootTitle(item, FOCUS);

			if (hideMenu)
			{
				hideMenu();
			}

			super.handleOnFocus(item);
		}

		handleOnSubmit()
		{
			const { item } = this.props;
			if (item.getDescendantsCount() > 0)
			{
				this.textInputBlur();

				return;
			}

			super.handleOnSubmit();
		}

		toggleChecklistRootTitle(item, action)
		{
			if (action === FOCUS && this.isDefaultChecklistTitle(item.getTitle()))
			{
				this.clearText(item);
			}
			else if (action === BLUR && !item.hasItemTitle())
			{
				item.setTitle(item.getPrevTitle());
				this.textRef.reload();
			}
		}

		isDefaultChecklistTitle(value)
		{
			const regex = new RegExp(`^${Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_STUB_TEXT').toLowerCase()}(\\s\\d+)?$`);

			return regex.test(value.trim().toLowerCase());
		}
	}

	module.exports = { RootChecklistItem };
});
