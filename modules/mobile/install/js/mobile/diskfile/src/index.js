import {Type} from "main.core";
import ImageController from "./imagecontroller";
import FileController from "./filecontroller";

class DiskFile
{
	constructor(params)
	{
		this.signedParameters = (Type.isStringFilled(params.signedParameters) ? params.signedParameters : '');
		if (params.images)
		{
			new ImageController(
				Object.assign(
					{signedParameters: this.signedParameters},
					params.images
				)
			);
			new FileController(params.files);
		}
		else
		{
			new ImageController(params);
		}
	}
}
export {DiskFile};