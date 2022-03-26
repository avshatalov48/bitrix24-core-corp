(() => {
	/**
	 * @class FieldsWrapper
	 */
	this.FieldsWrapper = (props) => {
		const config = BX.prop.getObject(props, 'config', {});
		const fields = BX.prop.getArray(props, 'fields', []);

		return View({
				style: BX.prop.getObject(config, 'styles', null)
			},
			...fields.map((field, index) => {
				if (field)
				{
					return View(
						{},
						View({
							style: {
								height: (index > 0 ? 0.5 : 0),
								backgroundColor: '#DBDDE0',
								...(BX.prop.getBoolean(config, 'fieldStyles', {})),
							},
						}),
						field
					);
				}
			})
		);
	};
})();
