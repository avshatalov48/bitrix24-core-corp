var diskufMenuNumber = 0;
function DiskActionFileMenu(id, bindElement, buttons)
{
	diskufMenuNumber++;
	BX.PopupMenu.show('bx-viewer-wd-popup' + diskufMenuNumber + '_' + id, BX(bindElement), buttons,
		{
			angle: {
				position: 'top',
				offset: 25
			},
			autoHide: true
		}
	);

	return false;
}