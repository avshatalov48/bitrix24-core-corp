export class Options
{
	static gridId: String = 'diskDocumentsGrid'; // $arParams['GRID_ID']
	static filterId: String = 'diskDocumentsFilter'; // $arParams['GRID_ID']
	static editableExt = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'xodt'];

	static getGridId()
	{
		return Options.gridId;
	}

	static setGridId(gridId: ?String)
	{
		Options.gridId = gridId;
	}

	static getFilterId()
	{
		return Options.filterId;
	}

	static getEditableExt()
	{
		return Options.editableExt;
	}

	static setEditableExt(extensions: ?Array)
	{
		Options.editableExt = extensions;
	}

	static setViewList()
	{
		BX.userOptions.save('disk', 'documents', 'viewMode', 'list');
		BX.userOptions.save('disk', 'documents', 'viewSize', '');
		window.location.reload();
	}

	static setViewSmallTile()
	{
		BX.userOptions.save('disk', 'documents', 'viewMode', 'tile');
		BX.userOptions.save('disk', 'documents', 'viewSize', 'm');
		window.location.reload();
	}

	static setViewBigTile()
	{
		BX.userOptions.save('disk', 'documents', 'viewMode', 'tile');
		BX.userOptions.save('disk', 'documents', 'viewSize', 'xl');
		window.location.reload();
	}
}