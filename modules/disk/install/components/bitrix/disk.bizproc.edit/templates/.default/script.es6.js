import { Reflection, Type } from 'main.core';
import { Globals } from 'bizproc.globals';

const namespace = Reflection.namespace('BX.Disk.Component');

class BizprocEditComponent
{
	signedDocumentType: string;

	constructor(options)
	{
		if (Type.isPlainObject(options)) {
			this.signedDocumentType = String(options.signedDocumentType);
		}
	}

	showGlobalVariables()
	{
		Globals.Manager.Instance.showGlobals(Globals.Manager.Instance.mode.variable, String(this.signedDocumentType))
			.then((slider) => {
				this.onAfterSliderClose(slider, window.arWorkflowGlobalVariables)
			})
		;
	}

	showGlobalConstants()
	{
		Globals.Manager.Instance.showGlobals(Globals.Manager.Instance.mode.constant, String(this.signedDocumentType))
			.then((slider) => {
				this.onAfterSliderClose(slider, window.arWorkflowGlobalConstants)
			});
	}

	onAfterSliderClose(slider, target)
	{
		const sliderInfo = slider.getData();
		if (sliderInfo.get('upsert'))
		{
			const newGFields = sliderInfo.get('upsert');
			for (const fieldId in newGFields)
			{
				target[fieldId] = newGFields[fieldId];
			}
		}
		if (sliderInfo.get('delete'))
		{
			const deletedGFields = sliderInfo.get('delete');
			for (const i in deletedGFields)
			{
				delete target[deletedGFields[i]];
			}
		}
	}
}

namespace.BizprocEditComponent = BizprocEditComponent;