"use strict";
/**
 * Parameters of component
 * @type {{init: BX.componentParameters.init}}
 * @type {{set: BX.componentParameters.set}}
 * @type {{get: BX.componentParameters.get}}
 */

BX.componentParameters =
{
    init:() => {
    	return new Promise((resolve, reject) => {

			BXMobileApp.UI.Page.params.get({callback: (data) => {
				for (let name in data)
				{
					if (data.hasOwnProperty(name))
					{
						BX.componentParameters.set(name, data[name]);
					}
				}

				console.info("BX.componentParameters: page params inited", data);
				resolve(data);
			}});

		});
	},

    get:(name, defaultValue) =>
	{
        if(
        	typeof window.__componentParameters !== "undefined"
			&& typeof window.__componentParameters[name] !== "undefined"
		)
		{
            return window.__componentParameters[name];
		}
        else if (typeof defaultValue !== "undefined")
        {
        	return defaultValue;
        }

        return null;
    },

    set: (name, value) =>
	{
        if(typeof window.__componentParameters === "undefined")
        {
            window.__componentParameters = {};
        }

        window.__componentParameters[name] = value;

        return true;
    }
};
