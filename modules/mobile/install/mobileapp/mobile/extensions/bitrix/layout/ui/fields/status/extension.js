/**
 * @module layout/ui/fields/status
 */
jn.define('layout/ui/fields/status', (require, exports, module) => {

	const { chevronRight } = require('assets/common');
	const { BaseField } = require('layout/ui/fields/base');

	/**
	 * @class StatusField
	 */
	class StatusField extends BaseField
	{
		canFocusTitle()
		{
			return false;
		}

		renderContent()
		{
			const statuses = this.getValue();

			return View(
				{
					style: this.styles.statusList,
				},
				...(statuses || []).map((status, index) => (
					View(
						{
							style: this.styles.statusItem(
								this.isReadOnly(),
								index,
								statuses.length - 1,
								status.backgroundColor,
							),
						},
						Text(
							{
								style: this.styles.statusItemText(status.color),
								text: status.name,
							},
						),
					)
				)),
			);
		}

		getDefaultStyles()
		{
			return {
				...super.getDefaultStyles(),
				statusList: {
					flexDirection: 'row',
					flexWrap: 'wrap',
					flex: 1,
					marginTop: 3,
				},
				statusItem: (readOnly, index, lastIndex, backgroundColor) => ({
					height: 21,
					borderRadius: 10.5,
					paddingHorizontal: 8,
					paddingVertical: 1,
					justifyContent: 'center',
					marginBottom: readOnly ? 1 : 4,
					marginRight: lastIndex !== index ? 10 : 0,
					backgroundColor: backgroundColor.replace(/[^#0-9a-fA-F]/g, ''),
				}),
				statusItemText: (color) => ({
					color: color.replace(/[^#0-9a-fA-F]/g, ''),
					fontSize: 9,
					fontWeight: '700',
				}),
				wrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 9,
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 11,
				},
			};
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', true);
		}

		renderEditIcon()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						width: 24,
						height: 24,
						marginLeft: 5,
					},
				},
				Image(
					{
						style: {
							height: 15,
							width: 9,
						},
						svg: {
							content: chevronRight(),
						},
					},
				),
			);
		}
	}

	module.exports = {
		StatusType: 'status',
		StatusField: (props) => new StatusField(props),
	};

});
