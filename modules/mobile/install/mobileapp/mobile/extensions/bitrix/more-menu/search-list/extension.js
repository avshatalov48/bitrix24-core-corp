/**
 * @module more-menu/search-list
 */
jn.define('more-menu/search-list', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { List } = require('more-menu/ui/list');
	const { PropTypes } = require('utils/validation');
	const { Indent } = require('tokens');
	const {
		handleItemClick,
	} = require('more-menu/utils');

	const isIosPlatform = Application.getPlatform() === 'ios';

	/**
	 * @class SearchList
	 */
	class SearchList extends LayoutComponent
	{
		/**
		 * @param {object} props.layout
		 * @param {function} props.getMenuList
		 * @param {string} props.testId
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				filteredSections: null,
				searchText: '',
				searchActive: false,
			};

			this.debounceFilter = debounce(this.#handleSearch.bind(this), 500);
		}

		get layout()
		{
			return this.props.layout;
		}

		static setListeners({ layout, getMenuList, testId })
		{
			// eslint-disable-next-line no-param-reassign
			layout.search.mode = 'layout';

			layout.setRightButtons([
				{
					type: 'search',
					id: 'search',
					callback: () => {
						const searchList = new SearchList({
							layout,
							getMenuList,
							testId,
						});

						layout.search.show(searchList);

						layout.search.on('textChanged', ({ text }) => {
							searchList.debounceFilter(text);
						});
					},
				},
			]);
		}

		/**
		 * @param {string} text
		 */
		#handleSearch(text)
		{
			const searchText = text.trim().toLowerCase();

			if (searchText === '')
			{
				this.setState({
					filteredSections: null,
					searchText: '',
					searchActive: false,
				});

				return;
			}

			const filteredSections = this.#performSearch(searchText);

			this.setState({
				filteredSections,
				searchText,
				searchActive: true,
			});
		}

		/**
		 * @param {string} searchText
		 * @returns {Array}
		 */
		#performSearch(searchText)
		{
			const currentMenuList = this.props.getMenuList();

			return currentMenuList
				.map((section) => {
					const filteredItems = section.items
						.filter((item) => item.title.toLowerCase().includes(searchText))
						.map((item) => ({ ...item }));

					return filteredItems.length > 0
						? { ...section, items: filteredItems }
						: null;
				})
				.filter(Boolean);
		}

		render()
		{
			const {
				searchActive,
				filteredSections,
			} = this.state;
			const { testId } = this.props;

			const menuList = this.props.getMenuList();

			const dataToRender = searchActive ? filteredSections : menuList;

			return ScrollView(
				{},
				View(
					{
						onPan: () => Keyboard.dismiss(),
						style: {
							marginHorizontal: Indent.XL3.toNumber(),
						},
					},
					new List({
						testId: searchActive ? `${testId}-search-filled` : testId,
						structure: dataToRender,
						onItemClick: this.onItemClick,
					}),
					View(
						{
							style: {
								height: isIosPlatform ? 60 : 1,
							},
						},
					),
				),
			);
		}

		onItemClick = (item) => {
			if (this.layout)
			{
				this.layout.search.close();
			}

			handleItemClick(item);
		};
	}

	SearchList.propTypes = {
		layout: PropTypes.object.isRequired,
		getMenuList: PropTypes.func.isRequired,
		testId: PropTypes.string.isRequired,
	};

	module.exports = {
		SearchList,
	};
});
