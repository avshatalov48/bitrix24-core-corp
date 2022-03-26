(() => {
	/**
	 * @class Fields.StatusField
	 */
	class StatusField extends Fields.BaseField
	{
		renderContent()
		{
			const statuses = this.props.value;

			return View(
				{
					style: this.styles.statusList
				},
				...(statuses ||  []).map((status, index) => (
					View(
						{
							style: this.styles.statusItem(
								this.isReadOnly(),
								index,
								statuses.length - 1,
								status.backgroundColor
							)
						},
						Text(
							{
								style: this.styles.statusItemText(status.color),
								text: status.name
							}
						)
					)
				))
			)
		}

		getDefaultStyles()
		{
			return {
				...super.getDefaultStyles(),
				statusList: {
					flexDirection: 'row',
					flexWrap: 'wrap',
					flex: 1
				},
				statusItem: (readOnly, index, lastIndex, backgroundColor) => ({
					marginBottom: readOnly ? 1 : 4,
					paddingLeft: 10,
					paddingRight: 10,
					paddingTop: 3,
					paddingBottom: 3,
					borderRadius: 10,
					marginRight: lastIndex !== index ? 10 : 0,
					backgroundColor: backgroundColor.replace(/[^#0-9a-fA-F]/g, '')
				}),
				statusItemText: (color) => ({
					color: color.replace(/[^#0-9a-fA-F]/g, ''),
					fontSize: 13,
					fontWeight: '500'
				}),
				title: {
					color: '#A8ADB4',
					fontSize: 10,
					fontWeight: '500',
					marginBottom: 5
				},
				wrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 9
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 11
				},
			}
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.StatusField = StatusField;
})();
