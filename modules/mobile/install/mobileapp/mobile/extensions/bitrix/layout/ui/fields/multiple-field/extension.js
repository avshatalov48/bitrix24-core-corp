/**
 * @module layout/ui/fields/multiple-field
 */
jn.define('layout/ui/fields/multiple-field', (require, exports, module) => {

	const { BaseMultipleField } = require('layout/ui/fields/base-multiple');
	const { stringify } = require('utils/string');

	/**
	 * @class MultipleField
	 */
	class MultipleField extends BaseMultipleField
	{
		prepareSingleValue(value)
		{
			if (!BX.type.isPlainObject(value) || !value.hasOwnProperty('value'))
			{
				value = { value };
			}

			return {
				...value,
				id: value.id || this.generateNextIndex(),
			};
		}

		isEmptyValue({ value })
		{
			return stringify(value) === '';
		}

		render()
		{
			this.styles = this.getStyles();

			const fields = this.getValue().map((item, index) => this.renderField(item, index));

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...fields,
			);
		}

		renderAddOrDeleteFieldButton(index, isNew)
		{
			if (this.isReadOnly())
			{
				return null;
			}

			const isFirst = !index;

			return View(
				{
					style: this.styles.addOrDeleteFieldButtonWrapper,
					onClick: () => {
						const handleAction = isFirst ? this.onAddField : this.onDeleteField;
						handleAction.call(this, index);
					},
				},
				Image({
					style: this.styles.buttonContainer,
					resizeMode: 'center',
					svg: isFirst ? svgImages.addField : svgImages.deleteField,
				}),
			);
		}
	}

	const svgImages = {
		addField: {
			content: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect opacity="0.3" x="0.25" y="0.25" width="23.5" height="23.5" rx="11.75" stroke="#767C87" stroke-width="0.5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13 6H11V11H6V13H11V18H13V13H18V11H13V6Z" fill="#A8ADB4"/></svg>`,
		},
		deleteField: {
			content: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect opacity="0.3" x="0.25" y="0.25" width="23.5" height="23.5" rx="11.75" stroke="#767C87" stroke-width="0.5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.9497 8.46537L15.5355 7.05116L12 10.5867L8.46447 7.05116L7.05025 8.46537L10.5858 12.0009L7.05025 15.5364L8.46447 16.9507L12 13.4151L15.5355 16.9507L16.9497 15.5364L13.4142 12.0009L16.9497 8.46537Z" fill="#A8ADB4"/></svg>`,
		},
	};

	module.exports = {
		MultipleField: (props) => new MultipleField(props),
	};

});
