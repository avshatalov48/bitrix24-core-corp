(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	/**
	 * @class FieldsWrapper
	 */
	this.FieldsWrapper = (props) => {
		const config = BX.prop.getObject(props, 'config', {});
		const fields = BX.prop.getArray(props, 'fields', []);

		return View(
			{
				style: BX.prop.getObject(config, 'styles', null),
			},
			...fields.map((field, index) => {
				if (!field)
				{
					return null;
				}

				const fieldWrapperConfig = BX.prop.getObject(field.props, 'wrapperConfig', {});
				const showBorderCurrent = BX.prop.getBoolean(fieldWrapperConfig, 'showWrapperBorder', true);
				const fieldWrapperConfigPrevious = index > 0 ? BX.prop.getObject(
					fields[index - 1].props,
					'wrapperConfig',
					{},
				) : {};
				const showBorderPrevious = BX.prop.getBoolean(fieldWrapperConfigPrevious, 'showWrapperBorder', true);
				const showBorder = index > 0 && showBorderCurrent && showBorderPrevious;

				return View(
					{
						style: {
							...BX.prop.getObject(config, 'fieldStyles', {}),
							...BX.prop.getObject(fieldWrapperConfig, 'style', {}),
						},
					},
					View({
						style: {
							height: showBorder ? 0.5 : 0,
							backgroundColor: AppTheme.colors.bgSeparatorPrimary,
						},
					}),
					field,
				);
			}),
		);
	};
})();
