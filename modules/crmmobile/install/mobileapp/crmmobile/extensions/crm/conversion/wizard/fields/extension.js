/**
 * @module crm/conversion/wizard/fields
 */
jn.define('crm/conversion/wizard/fields', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { unique } = require('utils/array');
	const { Type } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { BooleanType } = require('layout/ui/fields/boolean');
	const { EntityBoolean } = require('crm/ui/entity-boolean');

	const LIST_ICON = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.4738 5.5H5.52616C4.9829 5.5 4.5 5.95455 4.5 6.5303V10.0758C4.5 10.6212 4.95272 11.1061 5.52616 11.1061H18.4738C19.0171 11.1061 19.5 10.6515 19.5 10.0758V6.5303C19.5 5.95455 19.0473 5.5 18.4738 5.5ZM18.0211 9.2571C18.0211 9.43892 17.8702 9.59044 17.6891 9.59044H6.34103C6.15994 9.59044 6.00903 9.43892 6.00903 9.2571V7.31771C6.00903 7.13589 6.15994 6.98438 6.34103 6.98438H17.6891C17.8702 6.98438 18.0211 7.13589 18.0211 7.31771V9.2571ZM18.4738 12.8936H5.52616C4.9829 12.8936 4.5 13.3481 4.5 13.9239V17.4693C4.5 18.0148 4.95272 18.4996 5.52616 18.4996H18.4738C19.0171 18.4996 19.5 18.0451 19.5 17.4693V13.9239C19.5 13.3481 19.0473 12.8936 18.4738 12.8936ZM18.0211 16.6515C18.0211 16.8333 17.8702 16.9848 17.6891 16.9848H6.34105C6.15996 16.9848 6.00905 16.8333 6.00905 16.6515V14.7121C6.00905 14.5303 6.15996 14.3788 6.34105 14.3788H17.6891C17.8702 14.3788 18.0211 14.5303 18.0211 14.7121V16.6515Z" fill="#2FC6F6"/></svg>';

	/**
	 * @class WizardFields
	 */
	class WizardFields extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { fields } = this.props;
			this.state = {
				entityTypeIds: fields.map(({ id }) => id),
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		getFieldText(entityTypeId)
		{
			const messageCode = 'MCRM_CONVERSION_WIZARD_LAYOUT_ENTITIES_CREATION_IN';
			const message = getEntityMessage(messageCode, entityTypeId);

			return message || Loc.getMessage(messageCode);
		}

		handleOnChange(selectedId, enable)
		{
			const { onChange } = this.props;
			const { entityTypeIds } = this.state;

			const selectedIds = enable
				? [...entityTypeIds, selectedId]
				: entityTypeIds.filter((id) => selectedId !== id);

			this.setState({ entityTypeIds: unique(selectedIds) }, () => {
				if (onChange)
				{
					onChange(selectedIds);
				}
			});
		}

		renderField({ text, enable })
		{
			const size = 24;
			const color = enable ? AppTheme.colors.base1 : AppTheme.colors.base5;

			return View(
				{
					style: {
						height: 33,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					resizeMode: 'cover',
					style: {
						width: size,
						height: size,
						marginRight: 8,
					},
					svg: {
						content: LIST_ICON,
					},
				}),
				Text({
					style: {
						color,
						fontSize: 16,
						flexShrink: 2,
					},
					text,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...this.renderFields(),
			);
		}

		renderFields()
		{
			const { fields, type } = this.props;
			const { entityTypeIds } = this.state;

			if (type === BooleanType)
			{
				return fields.map((field) => {
					const entityTypeId = Type.resolveIdByName(field.entityTypeName);
					const fieldText = this.getFieldText(entityTypeId);

					return EntityBoolean({
						...field,
						entityTypeId,
						simple: true,
						enable: entityTypeIds.includes(entityTypeId),
						onChange: this.handleOnChange,
						text: fieldText,
						disabledText: fieldText,
					});
				});
			}

			return fields.map(({ description }) => this.renderField({ text: description, enable: true }));
		}
	}

	module.exports = { WizardFields };
});
