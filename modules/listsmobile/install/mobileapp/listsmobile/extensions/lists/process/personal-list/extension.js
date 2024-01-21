/**
 * @module lists/process/personal-list
 */
jn.define('lists/process/personal-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { ProcessList } = require('lists/process/list');
	const { ListItemType } = require('lists/process/simple-list/items');

	class PersonalProcessList extends ProcessList
	{
		get actions() {
			return {
				loadItems: 'listsmobile.Process.loadPersonalList',
			};
		}

		get actionParams() {
			return {
				loadItems: {},
			};
		}

		get itemType()
		{
			return ListItemType.PERSONAL_PROCESS;
		}

		renderEmptyListComponent()
		{
			return new EmptyScreen({
				title: Loc.getMessage('LISTSMOBILE_PROCESS_PERSONAL_LIST_EMPTY_TITLE'),
				description: Loc.getMessage('LISTSMOBILE_PROCESS_PERSONAL_LIST_EMPTY_SUBTITLE'),
				styles: {},
			});
		}
	}

	module.exports = { PersonalProcessList };
});
