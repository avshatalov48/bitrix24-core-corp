BX.namespace("BX.Disk");
BX.Disk.HelpNetworkDriveClass = (function ()
{
	var HelpNetworkDriveClass = function (parameters){};
	HelpNetworkDriveClass.prototype.showContent = function (el)
	{
		el.style.display = (el.style.display == 'none') ? 'block' : 'none';
	};

	return HelpNetworkDriveClass;
})();
