window.meetingMenuPopup = {};

function ShowMenuPopup(meetingId, bindElement)
{
	if (meetingMenuPopup[meetingId])
		BX.PopupMenu.show(meetingId, bindElement, meetingMenuPopup[meetingId], {events: {onPopupClose: __onMenuPopupClose}});

	BX.addClass(bindElement, "meeting-menu-button-selected");

	return false;
} 

function __onMenuPopupClose()
{
	BX.removeClass(this.bindElement, "meeting-menu-button-selected");
}