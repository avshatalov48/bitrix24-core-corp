/**
 * @module tasks/fieldsPinner
 */
jn.define('tasks/fieldsPinner', (require, exports, module) => {
	const {Loc} = require('loc');

	class FieldsPinner
	{
		constructor()
		{
			// this.storage = Application.sharedStorage('tasksFieldsPinner');
			// this.storageKey = 'pinnedFields';

			this.pinnedFieldsByDefault = ['title', 'responsible', 'deadline', 'description', 'files', 'project'];
			// this.pinnedFields = (this.storage.get(this.storageKey) || []);
			this.pinnedFields = this.pinnedFieldsByDefault;

			// this.timer = null;
			//
			// this.isPinnedFieldsLoaded = true;
		}

		// loadPinnedFieldsForUser(userId)
		// {
		// 	return new Promise((resolve) => {
		// 		if (this.isPinnedFieldsLoaded)
		// 		{
		// 			resolve();
		// 			return;
		// 		}
		// 		(new RequestExecutor('tasksmobile.FieldsPinner.getPinnedFields', {userId}))
		// 			.call()
		// 			.then((response) => {
		// 				this.pinnedFields = this.pinnedFieldsByDefault.concat(
		// 					response.result.filter(field => this.pinnedFieldsByDefault.indexOf(field) < 0)
		// 				);
		// 				this.storage.set(this.storageKey, this.pinnedFields);
		// 				this.isPinnedFieldsLoaded = true;
		// 				resolve();
		// 			})
		// 		;
		// 	});
		// }

		isFieldPinned(field)
		{
			return this.pinnedFields.includes(field);
		}

		// pinField(field)
		// {
		// 	if (!this.pinnedFields.includes(field))
		// 	{
		// 		this.pinnedFields.push(field);
		// 		this.storage.set(this.storageKey, this.pinnedFields);
		// 		this.showNotification();
		// 		this.setTimeout();
		// 	}
		// }
		//
		// unpinField(field)
		// {
		// 	if (this.pinnedFields.includes(field))
		// 	{
		// 		this.pinnedFields.splice(this.pinnedFields.indexOf(field), 1);
		// 		this.storage.set(this.storageKey, this.pinnedFields);
		// 		this.showNotification(false);
		// 		this.setTimeout();
		// 	}
		// }
		//
		// setTimeout()
		// {
		// 	if (this.timer)
		// 	{
		// 		clearTimeout(this.timer);
		// 	}
		//
		// 	this.timer = setTimeout(() => {
		// 		this.timer = null;
		// 		this.savePinnedFields();
		// 	}, 2000);
		// }
		//
		// savePinnedFields()
		// {
		//
		// }
		//
		// renderPinRightButton(fieldName, clickCallback)
		// {
		// 	const iconPinned = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.0855 1.83831C15.7115 1.83902 16.2184 2.3784 16.2177 3.04304C16.217 3.70769 15.709 4.24591 15.083 4.24519L13.9166 4.24519L15.0708 12.8199H16.2096C16.8182 12.8444 17.2997 13.3754 17.2997 14.0221C17.2997 14.6688 16.8182 15.1997 16.2096 15.2242L13.4306 15.2268L12.546 21.509C12.5522 21.6218 12.499 21.729 12.4079 21.7873C12.3167 21.8456 12.2025 21.8455 12.1115 21.7869C12.0205 21.7283 11.9676 21.6209 11.9742 21.5081L11.009 15.2251L8.2837 15.226C7.66997 15.2083 7.18117 14.6748 7.18139 14.023C7.18161 13.3711 7.67077 12.838 8.28452 12.8208H9.42329L10.5783 4.24519L9.42004 4.24519C8.80631 4.22749 8.31751 3.69404 8.31773 3.04219C8.31795 2.39035 8.80711 1.85727 9.42085 1.84004L15.0855 1.83831Z" fill="#828B95"/></svg>';
		// 	const iconUnpinned = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M20.2132 8.29982C20.6554 8.74298 20.6324 9.48284 20.162 9.95234C19.6915 10.4218 18.9517 10.4432 18.5096 10L17.6848 9.17523L12.4377 16.0547L13.2429 16.8599C13.656 17.3076 13.621 18.0235 13.1637 18.4808C12.7065 18.9381 11.9905 18.973 11.5428 18.56L9.57595 16.5968L4.50829 20.4134C4.43293 20.4976 4.31951 20.5358 4.21379 20.5126C4.10807 20.4893 4.02749 20.4085 4.00457 20.3027C3.98164 20.1969 4.02015 20.0836 4.10456 20.0084L7.86482 14.8832L5.93716 12.9568C5.5157 12.5103 5.54727 11.7874 6.00835 11.3267C6.46944 10.8659 7.19227 10.8348 7.63843 11.2566L8.44367 12.0619L15.3243 6.81474L14.5052 5.9957C14.0838 5.54921 14.1154 4.82637 14.5764 4.3656C15.0375 3.90483 15.7604 3.87377 16.2065 4.29557L20.2132 8.29982Z" fill="#D5D7DB"/></svg>';
		//
		// 	return View(
		// 		{
		// 			style: {
		// 				height: 34,
		// 				marginLeft: 5,
		// 				paddingLeft: 15,
		// 				justifyContent: 'center',
		// 			},
		// 			testId: `pinButton_${fieldName}`,
		// 			onClick: () => {
		// 				if (this.isFieldPinned(fieldName))
		// 				{
		// 					this.unpinField(fieldName);
		// 				}
		// 				else
		// 				{
		// 					this.pinField(fieldName);
		// 				}
		// 				clickCallback(this.isFieldPinned(fieldName));
		// 			},
		// 		},
		// 		Image(
		// 			{
		// 				style: {
		// 					width: 24,
		// 					height: 24,
		// 				},
		// 				svg: {
		// 					content: (this.isFieldPinned(fieldName) ? iconPinned : iconUnpinned),
		// 				},
		// 			},
		// 		),
		// 	);
		// }
		//
		// showNotification(isPinned = true)
		// {
		// 	Notify.showMessage(
		// 		Loc.getMessage('TASKSMOBILE_FIELDS_PINNER_NOTIFICATION_MESSAGE'),
		// 		Loc.getMessage(`TASKSMOBILE_FIELDS_PINNER_NOTIFICATION_TITLE_${(isPinned ? 'PIN' : 'UNPIN')}`),
		// 		{
		// 			time: 3,
		// 		}
		// 	);
		// }
	}

	const FieldsPinnerObject = new FieldsPinner();

	module.exports = {FieldsPinner, FieldsPinnerObject};
});