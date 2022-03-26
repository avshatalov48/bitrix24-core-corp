(() => {
	BX.UI = BX.UI || {};
	if(typeof BX.UI.EntityConfigScope === "undefined")
	{
		BX.UI.EntityConfigScope =
			{
				undefined: '',
				personal:  'P',
				common: 'C',
				custom: 'CUSTOM'
			};
	}
})();