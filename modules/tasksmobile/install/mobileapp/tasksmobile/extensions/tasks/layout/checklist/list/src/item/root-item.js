/**
 * @module tasks/layout/checklist/list/src/root-item
 */
jn.define('tasks/layout/checklist/list/src/root-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const { BaseChecklistItem } = require('tasks/layout/checklist/list/src/base-item');

	const FOCUS = 'focus';
	const BLUR = 'blur';

	/**
	 * @class RootChecklistItem
	 */
	class RootChecklistItem extends BaseChecklistItem
	{
		constructor(props)
		{
			super(props);

			const { item } = props;

			this.prevTitle = item.getTitle();
		}

		render()
		{
			return this.renderContent({
				children: [
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
				],
			});
		}

		renderDescription()
		{
			const { item } = this.props;
			const completedCount = item.getCompletedCount();
			const totalCount = item.getTotalCount();

			return View(
				{
					testId: 'checklist_items_count',
					style: {
						marginTop: Indent.S.toNumber(),
					},
				},
				Text5({
					style: {
						color: Color.base3.toHex(),
					},
					text: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_DONE_MSGVER_1', {
						'#COMPLETED#': completedCount,
						'#TOTAL#': totalCount,
					}),
				}),
			);
		}

		getTextFieldStyle()
		{
			return {
				textSize: 2,
				header: true,
			};
		}

		/**
		 * @protected
		 * @param {CheckListFlatTreeItem} item
		 */
		getPlaceholder(item)
		{
			return this.#getPrevTitle() || Loc.getMessage('TASKSMOBILE_LAYOUT_LIST_INPUT_PLACEHOLDER');
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
			if (item.getTotalCount() > 0)
			{
				this.textInputBlur();

				return;
			}

			super.handleOnSubmit();
		}

		handleOnChangeTitle(title)
		{
			if (this.isDefaultChecklistTitle(this.#getPrevTitle()) && !title)
			{
				return;
			}

			super.handleOnChangeTitle(title);
		}

		toggleChecklistRootTitle(item, action)
		{
			const title = item.getTitle();
			if (action === FOCUS && this.isDefaultChecklistTitle(item.getTitle()))
			{
				this.#setPrevTitle(title);
				this.#clearText(item);
			}
			else if (action === BLUR && !item.hasItemTitle())
			{
				item.setTitle(this.#getPrevTitle());
				this.toggleCompleteText();
			}
		}

		isDefaultChecklistTitle(value)
		{
			const regex = new RegExp(`^${Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_STUB_TEXT').toLowerCase()}(\\s\\d+)?$`);

			return regex.test(value.trim().toLowerCase());
		}

		#clearText(item)
		{
			if (item.getTitle())
			{
				item.setTitle('');
				this.textRef.clear();
			}
		}

		#getPrevTitle()
		{
			return this.prevTitle;
		}

		#setPrevTitle(title)
		{
			this.prevTitle = title;
		}
	}

	module.exports = { RootChecklistItem };
});
