/**
 * @module intranet/invite-new/src/content-status-block
 */
jn.define('intranet/invite-new/src/content-status-block', (require, exports, module) => {
	const { StatusBlock, VerticalAlign } = require('ui-system/blocks/status-block');
	const { Loc } = require('loc');
	const { DepartmentChooser } = require('intranet/invite-new/src/department-chooser');
	const { Indent } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} ContentStatusBlockProps
	 * @property {string} department
	 * @property {function} selectedDepartmentChanged
	 * @property {string} graphicsUri
	 * @property {Object} layout
	 * @property {string} testId

	 * @class ContentStatusBlock
	 */
	class ContentStatusBlock extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'status-block',
			});
			this.#initState(props);
		}

		#initState(props)
		{
			this.state = {
				department: props.department ?? null,
			};
		}

		render()
		{
			const { department, graphicsUri } = this.props;
			const isDepartmentSelected = department !== null;

			return StatusBlock({
				title: '',
				description: isDepartmentSelected
					? Loc.getMessage('INTRANET_DEPARTMENT_CARD_SELECTED_DEPARTMENT_TEXT_MSGVER_1')
					: Loc.getMessage('INTRANET_DEPARTMENT_CARD_TEXT_MSGVER_1'),
				buttons: [this.#renderDepartmentChipButton()],
				emptyScreen: false,
				verticalAlign: VerticalAlign.TOP,
				image: Image({
					style: {
						width: 178,
						height: 122,
					},
					svg: {
						resizeMode: 'contain',
						uri: graphicsUri,
					},
				}),
				style: {
					marginVertical: Indent.XL4.toNumber(),
				},
				testId: this.getTestId(),
			});
		}

		#renderDepartmentChipButton()
		{
			const { department, layout, selectedDepartmentChanged, testId } = this.props;

			return DepartmentChooser({
				testId,
				layout,
				department,
				selectedDepartmentChanged,
			});
		}
	}

	module.exports = {
		/**
		 * @param {ContentStatusBlockProps} props
		 * @returns {ContentStatusBlock}
		 */
		ContentStatusBlock: (props) => new ContentStatusBlock(props),
	};
});
