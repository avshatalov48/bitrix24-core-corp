/**
 * @module calendar/event-list-view/layout/search-header
 */
jn.define('calendar/event-list-view/layout/search-header', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Component, Indent } = require('tokens');
	const { Text3 } = require('ui-system/typography/text');

	const { observeState } = require('calendar/event-list-view/state');

	/**
	 * @class SearchHeader
	 */
	class SearchHeader extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						display: this.props.isVisible ? 'flex' : 'none',
						borderBottomWidth: this.props.isVisible ? 1 : 0,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						paddingHorizontal: Component.areaPaddingLr.toNumber(),
						paddingVertical: Indent.XL.toNumber(),
					},
				},
				Text3({
					text: this.getSearchTitle(),
					color: Color.base1,
				}),
			);
		}

		getSearchTitle()
		{
			if (this.props.isInvitationPresetEnabled)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_LIST_INVITATION');
			}

			if (this.props.searchQuery !== '')
			{
				return Loc.getMessage('M_CALENDAR_EVENT_LIST_SEARCH_RESULT_BY_QUERY', {
					'#QUERY#': this.props.searchQuery,
				});
			}

			return Loc.getMessage('M_CALENDAR_EVENT_LIST_SEARCH_RESULT');
		}
	}

	const mapStateToProps = (state) => ({
		isVisible: state.isSearchMode && !state.invitesSelected,
		isInvitationPresetEnabled: state.isInvitationPresetEnabled,
		searchQuery: state.searchString,
	});

	module.exports = { SearchHeader: observeState(SearchHeader, mapStateToProps) };
});
