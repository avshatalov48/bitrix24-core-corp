import Form from './form.js';
import Options from './options.js';

const add = function(params)
{
	const container = BX('diskuf-selectdialog-' + params['UID']);

	if (container && BX.isNodeInDom(container))
	{
		const eventObject = container.parentNode;
		if (!container.hasAttribute("bx-disk-load-is-bound"))
		{
			container.setAttribute("bx-disk-load-is-bound", "Y");
			BX.addCustomEvent(eventObject, "DiskLoadFormController", function (status)
			{
				try {
					BX.Disk.UF.Options.set({urlUpload: params.urlUpload});
					return BX.Disk.UF.Form.getBriefInstance({
						container: container,
						eventObject: container.parentNode,
						id: params.UID,
						fieldName: params.controlName,
						input: BX.findChild(container, { className : 'diskuf-fileUploader' }, true)
					});
				}
				catch(e)
				{
					console.log('Error with compatibility', e);
				}
			});
		}
		if (!!params['values'] && params['values'].length > 0 && !params['hideSelectDialog'])
			BX.onCustomEvent(container.parentNode, 'DiskLoadFormController', ['show']);
	}
}
export {
	Form,
	Options,
	add
}
