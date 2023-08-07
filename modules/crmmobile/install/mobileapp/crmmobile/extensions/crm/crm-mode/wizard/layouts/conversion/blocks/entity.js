/**
 * @module crm/crm-mode/wizard/layouts/conversion/blocks/entity
 */
jn.define('crm/crm-mode/wizard/layouts/conversion/blocks/entity', (require, exports, module) => {
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { EntityBoolean } = require('crm/ui/entity-boolean');

	/**
	 * @class EntityBlock
	 */
	class EntityBlock extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { selectedEntities } = this.props;

			this.entities = [TypeId.Company, TypeId.Contact];
			this.state = {
				selectedIds: selectedEntities,
			};

			this.onChange = this.onChange.bind(this);
		}

		onChange(selectedId, enable)
		{
			const { onChange } = this.props;
			const { selectedIds: stateSelectedIds } = this.state;

			let selectedIds = [];
			if (enable)
			{
				selectedIds = [...stateSelectedIds, selectedId];
			}
			else
			{
				selectedIds = selectedIds.filter((id) => selectedId !== id);
				if (selectedIds.length === 0)
				{
					selectedIds = this.entities.filter((id) => selectedId !== id);
				}
			}

			this.setState(
				{ selectedIds },
				() => {
					onChange({ name: 'selectedEntities', value: selectedIds });
				},
			);
		}

		render()
		{
			const { selectedIds } = this.state;

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...this.entities.map((entityTypeId, i) => View(
					{
						style: {
							marginTop: i === 0 ? 0 : 8,
						},
					},
					EntityBoolean({
						onChange: this.onChange,
						enable: selectedIds.includes(entityTypeId),
						text: getEntityMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION', entityTypeId),
						entityTypeId,
					}),
				)),
			);
		}
	}

	module.exports = {
		entityBlock: (props) => new EntityBlock(props),
	};
});
