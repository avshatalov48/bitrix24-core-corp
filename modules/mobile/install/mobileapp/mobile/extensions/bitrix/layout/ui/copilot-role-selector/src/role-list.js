/**
 * @module layout/ui/copilot-role-selector/src/role-list
 */
jn.define('layout/ui/copilot-role-selector/src/role-list', (require, exports, module) => {
	const { JnListClass } = require('layout/ui/jn-list');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { makeLibraryImagePath } = require('asset-manager');
	const { FeedbackForm } = require('layout/ui/feedback-form-opener');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { ScopeType } = require('layout/ui/copilot-role-selector/src/type');

	/**
	 * @class CopilotRoleList
	 */
	class CopilotRoleList extends JnListClass
	{
		renderEmptyState()
		{
			return View(
				{
					style: {
						flex: 1,
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick: () => {
						Keyboard.dismiss();
					},
				},
				StatusBlock({
					testId: this.getTestId('empty-state-status-block'),
					emptyScreen: true,
					image: Image({
						svg: {
							uri: this.#getEmptyStateImageUri(),
						},
						style: {
							width: 98,
							height: 83,
						},
					}),
					title: this.#getEmptyStateTitle(),
					titleColor: Color.base2,
					description: this.#getEmptyStateDescription(),
					descriptionColor: Color.base3,
					onDescriptionLinkClick: this.#onDescriptionLinkClick,
				}),
			);
		}

		#onDescriptionLinkClick = () => {
			(new FeedbackForm({
				formId: 'copilotRoles',
				senderPage: 'copilot_role_list',
			})).openInBackdrop();
		};

		#getEmptyStateDescription = () => {
			const { selectedScopeId, searchString } = this.props;
			if (selectedScopeId === ScopeType.FAVORITES && searchString === '')
			{
				return Loc.getMessage('COPILOT_ROLE_SELECTOR_EMPTY_STATE_DESCRIPTION_FAVORITES');
			}

			if (searchString !== '')
			{
				return Loc.getMessage('COPILOT_ROLE_SELECTOR_EMPTY_STATE_DESCRIPTION_SEARCH', {
					'#color#': Color.accentMainLinks.toHex(),
				});
			}

			return Loc.getMessage('COPILOT_ROLE_SELECTOR_EMPTY_STATE_DESCRIPTION');
		};

		#getEmptyStateImageUri = () => {
			const { selectedScopeId, searchString } = this.props;

			if (searchString === '')
			{
				return makeLibraryImagePath(`empty-state-${selectedScopeId}.svg`, 'copilot-role-selector');
			}

			return makeLibraryImagePath('empty-state-search.svg', 'copilot-role-selector');
		};

		#getEmptyStateTitle = () => {
			const { searchString } = this.props;

			if (searchString !== '')
			{
				return Loc.getMessage('COPILOT_ROLE_SELECTOR_EMPTY_STATE_TITLE_SEARCH');
			}

			return Loc.getMessage('COPILOT_ROLE_SELECTOR_EMPTY_STATE_TITLE');
		};
	}

	module.exports = {
		CopilotRoleList,
	};
});
