/**
 * @module crm/document/pagenav
 */
jn.define('crm/document/pagenav', (require, exports, module) => {
	const { Loc } = require('loc');

	/**
	 * @class CrmDocumentPageNav
	 */
	class CrmDocumentPageNav extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { currentPage, totalPage } = props;

			this.state = {
				currentPage,
				totalPage,
			};
		}

		get hasData()
		{
			return this.state.currentPage && this.state.totalPage;
		}

		/**
		 * @public
		 * @param {number} currentPage
		 * @param {number} totalPage
		 */
		setData({ currentPage, totalPage })
		{
			if (currentPage !== this.state.currentPage || totalPage !== this.state.totalPage)
			{
				this.setState({ currentPage, totalPage });
			}
		}

		render()
		{
			return View(
				{},
				this.hasData && Text({
					text: Loc.getMessage('M_CRM_DOCUMENT_PAGE_NAVIGATION', {
						'#CURRENT#': this.state.currentPage,
						'#TOTAL#': this.state.totalPage,
					}),
					style: {
						color: '#A8ADB4',
						fontSize: 14,
					},
				}),
			);
		}
	}

	module.exports = { CrmDocumentPageNav };
});
