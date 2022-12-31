/**
 * @module layout/ui/fields/requisite
 */
jn.define('layout/ui/fields/requisite', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { RequisiteDetails } = require('layout/ui/fields/requisite/requisite-details');
	const { get } = require('utils/object');

	/**
	 * @class RequisiteField
	 */
	class RequisiteField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.handleClick = this.handleClick.bind(this);
		}

		isReadOnly()
		{
			return true;
		}

		isDisabled()
		{
			return true;
		}

		getSelectedRequisite()
		{
			return this.getValue().find((requisite) => requisite && requisite.selected);
		}

		getSelectedRequisiteTitle()
		{
			const requisite = this.getSelectedRequisite();

			return get(requisite, ['requisiteData', 'viewData', 'title'], '');
		}

		renderEditableContent()
		{
			return this.renderReadOnlyContent();
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return this.renderRequisite();
		}

		renderRequisite()
		{
			return View(
				{
					style: {
						borderBottomWidth: 1,
						borderBottomColor: '#a8adb4',
						borderStyle: 'dash',
						borderDashSegmentLength: 3,
						borderDashGapLength: 3,
					},
					onClick: this.handleClick,
				},
				Text({
					style: {
						fontSize: 14,
						color: '#a8adb4',
					},
					text: this.getSelectedRequisiteTitle(),
				}),
			);
		}

		handleClick()
		{
			this.getPageManager()
				.openWidget(
					'layout',
					{
						title: this.props.title,
						useLargeTitleMode: false,
						modal: false,
						backdrop: {
							mediumPositionPercent: 75,
							horizontalSwipeAllowed: false,
						},
					},
				)
				.then((layoutWidget) => {
					layoutWidget.showComponent(new RequisiteDetails({
						layout: layoutWidget,
						items: this.getValue(),
					}));
				});
		}
	}

	module.exports = {
		RequisiteType: 'requisite',
		RequisiteField: (props) => new RequisiteField(props),
	};

});
