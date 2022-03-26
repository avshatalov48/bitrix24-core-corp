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
		let me = this;
		Globals.Manager.Instance.showGlobals(Globals.Manager.Instance.mode.variable, String(this.signedDocumentType))
			.then((slider) => {
				me.onAfterSliderClose(slider)
			})
		;
	}

	showGlobalConstants()
	{
		let me = this;
		Globals.Manager.Instance.showGlobals(Globals.Manager.Instance.mode.constant, String(this.signedDocumentType))
			.then((slider) => {
				me.onAfterSliderClose(slider)
			});
	}

	onAfterSliderClose(slider)
	{
		//do smt
	}
}

namespace.BizprocEditComponent = BizprocEditComponent;