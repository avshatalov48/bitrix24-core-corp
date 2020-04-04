(function() {

var BX = window.BX;

if (!!BX.IntranetUISelector)
{
	return;
}

BX.IntranetUISelector = {

	onGetEntityTypes: function(params)
	{
		if (
			!BX.type.isNotEmptyObject(params)
			|| !BX.type.isNotEmptyObject(params.selector)
		)
		{
			return;
		}

		var
			selectorInstance = params.selector;

		if (selectorInstance.getOption('enableDepartments') == 'Y')
		{
			selectorInstance.entityTypes.DEPARTMENTS = {
				options: {
					allowSelect: (selectorInstance.getOption('departmentSelectDisable') != 'Y' ? 'Y' : 'N'), // !departmentSelectDisable
					siteDepartmentId: (BX.type.isNotEmptyString(selectorInstance.getOption('siteDepartmentId')) ? selectorInstance.getOption('siteDepartmentId') : ''), // !departmentSelectDisable
					enableFlat: (selectorInstance.getOption('departmentFlatEnable') == 'Y' ? 'Y' : 'N')
				}
			};
		}
	}
};

BX.addCustomEvent('BX.Main.SelectorV2:onGetEntityTypes', BX.IntranetUISelector.onGetEntityTypes);

})();
