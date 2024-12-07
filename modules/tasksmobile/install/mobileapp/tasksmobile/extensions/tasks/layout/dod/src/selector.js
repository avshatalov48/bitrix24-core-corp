/**
 * @module tasks/layout/dod/src/selector
 */
jn.define('tasks/layout/dod/src/selector', (require, exports, module) => {
	const { UIMenu } = require('layout/ui/menu');
	const { PureComponent } = require('layout/pure-component');
	const { StageSelector, CardDesign, Icon } = require('ui-system/blocks/stage-selector');

	/**
	 * @class DodTypeSelector
	 */
	class DodTypeSelector extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.selectorRef = null;
			this.menu = null;

			this.#init(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.#init(nextProps);
		}

		#init(nextProps)
		{
			this.menu = new UIMenu(this.#getDodTypeItems(nextProps));
			this.#initState(nextProps);
		}

		#initState(props)
		{
			const { selectedTypeId, types } = props;
			const selectedType = types.find((type) => type.id === selectedTypeId);

			this.state = {
				selectedItemTitle: selectedType?.name,
			};
		}

		#handleShowUIMenu = () => {
			this.menu.show({ target: this.selectorRef });
		};

		#getDodTypeItems(props)
		{
			const { types, selectedTypeId } = props;

			return types.map(({ id, name }) => ({
				id: String(id),
				testId: `DOD_TYPE_ID_${id}`,
				title: name,
				checked: selectedTypeId === id,
				sectionCode: 'default',
				onItemSelected: this.handleOnItemSelected,
			}));
		}

		handleOnItemSelected = (event, { id, title }) => {
			this.setState(
				{ selectedItemTitle: title },
				() => {
					const { onSelected } = this.props;

					onSelected(Number(id));
				},
			);
		};

		render()
		{
			const { selectedItemTitle } = this.state;

			return StageSelector({
				testId: 'DOD_TYPE_SELECTOR',
				ref: (ref) => {
					this.selectorRef = ref;
				},
				title: selectedItemTitle,
				cardDesign: CardDesign.SECONDARY,
				icon: Icon.DROPDOWN,
				onClick: this.#handleShowUIMenu,
			});
		}
	}

	DodTypeSelector.propTypes = {
		selectedTypeId: PropTypes.number.isRequired,
		onSelected: PropTypes.func.isRequired,
		types: PropTypes.arrayOf(PropTypes.object).isRequired,
	};

	module.exports = { DodTypeSelector };
});
