import { ajax as Ajax, Type } from "main.core";

declare type ModelData = {
    id: ?number,
    entityTypeId: ?number,
};

declare type getParameters = {
    analyticsLabel: ?string,
    context: ?string,
}

export type Actions = {
	get: ?string,
	add: ?string,
	update: ?string,
	delete: ?string,
};

/**
 * @abstract
 * @memberOf BX.Crm
 */
export class Model
{
    data;
    getParameters;
    deleted = false;
    progress = false;

    constructor(data: ModelData, params: ?{
        getParameters: ?{
            get: ?getParameters,
            add: ?getParameters,
            update: ?getParameters,
            delete: ?getParameters,
        }
    })
    {
        this.data = {};
        if(Type.isPlainObject(data))
        {
            this.data = data;
        }
        this.getParameters = {
            add: {},
            get: {},
            update: {},
            delete: {},
        };
        if(Type.isPlainObject(params))
        {
            this.getParameters = params.getParameters;
        }
    }

    get actions(): Actions
    {
        return {
            get: this.compileActionString('get'),
            add: this.compileActionString('add'),
            update: this.compileActionString('update'),
            delete: this.compileActionString('delete'),
        };
    }

	/**
	 * @protected
	 * @param action
	 */
	compileActionString(action: string): string
	{
		return ('crm.api.' + this.getModelName() + '.' + action);
	}

    getId(): ?number
    {
        return this.data.id;
    }

    getEntityTypeId(): ?number
    {
        return this.data.entityTypeId;
    }

    isSaved(): boolean
    {
        return (this.getId() > 0);
    }

    isDeleted(): boolean
	{
		return this.deleted;
	}

    setData(data: ModelData): this
    {
        this.data = data;

        return this;
    }

    getData(): ModelData
    {
        return this.data;
    }

    setGetParameters(action: string, parameters: getParameters)
    {
        this.getParameters[action] = parameters;
    }

    getGetParameters(action: string): getParameters
    {
        return {
            ...{
                analyticsLabel: 'crmModel' + this.getModelName() + action,
            },
            ...this.getParameters[action]
        };
    }

    /**
     * @abstract
     */
    getModelName(): string
    {
        throw new Error('Method "getModelName" should be overridden');
    }

    setDataFromResponse(response: {data: {}})
    {
        this.setData(response.data[this.getModelName()]);
    }

    load(): Promise<{data: {}},string[]>
    {
        return new Promise((resolve, reject) => {
            const errors = [];

            if(this.progress)
            {
                errors.push('Another action is in progress');
                reject(errors);
                return;
            }

            if(!this.isSaved())
            {
                errors.push('Cant load ' + this.getModelName() + ' without id');
                reject(errors);
                return;
            }

            const action = this.actions.get;
            if(!Type.isString(action) || action.length <= 0)
            {
                errors.push('Load action is not specified');
                reject(errors);
                return;
            }

            this.progress = true;
            Ajax.runAction(action, {
                data: {
                    id: this.getId(),
                },
                getParameters: this.getGetParameters('get')
            }).then((response) => {
                this.progress = false;
                this.setDataFromResponse(response);
                resolve(response);
            }).catch((response) => {
                this.progress = false;
                response.errors.forEach(({message}) => {
                    errors.push(message);
                });
                reject(errors);
            });
        });
    }

    save(): Promise<{data: {}},string[]>
    {
        return new Promise((resolve, reject) => {
            let errors = [];

            if(this.progress)
            {
                errors.push('Another action is in progress');
                reject(errors);
                return;
            }

            let action;
            let data;
            let getParameters;
            if(this.isSaved())
            {
                action = this.actions.update;
                data = {
                    id: this.getId(),
                    fields: this.getData(),
                };
                getParameters = this.getGetParameters('update');
            }
            else
            {
                action = this.actions.add;
                data = {
                    fields: this.getData(),
                };
                getParameters = this.getGetParameters('add');
            }

            if(!Type.isString(action) || action.length <= 0)
            {
                errors.push('Save action is not specified');
                reject(errors);
                return;
            }

            this.progress = true;
            Ajax.runAction(action, {
                data,
                getParameters,
            }).then((response) => {
                this.progress = false;
                this.setDataFromResponse(response);
                resolve(response);
            }).catch((response) => {
                this.progress = false;
                errors = [...errors, ...this.extractErrorMessages(response)];
                reject(errors);
            });
        });
    }

	/**
	 * @protected
	 * @param errors
	 */
	extractErrorMessages({errors}): string[]
	{
		const errorMessages: string[] = [];
		errors.forEach( ({message}) => {
			if(Type.isPlainObject(message) && message.text)
			{
				errorMessages.push(message.text);
			}
			else
			{
				errorMessages.push(message);
			}
		});

		return errorMessages;
	}

    delete(): Promise<{data: {}},string[]>
    {
        return new Promise((resolve, reject) => {
            const errors = [];

            if(this.progress)
            {
                errors.push('Another action is in progress');
                reject(errors);
                return;
            }

            if(!this.isSaved())
            {
                errors.push('Cant delete ' + this.getModelName() + ' without id');
                reject(errors);
                return;
            }

            const action = this.actions.delete;
            if(!Type.isString(action) || action.length <= 0)
            {
                errors.push('Delete action is not specified');
                reject(errors);
                return;
            }

            this.progress = true;
            Ajax.runAction(action, {
                data: {
                    id: this.getId(),
                },
                getParameters: this.getGetParameters('delete'),
            }).then((response) => {
                this.deleted = true;
                this.progress = false;
                resolve(response);
            }).catch((response) => {
                this.progress = false;
                response.errors.forEach(({message}) => {
                    errors.push(message);
                });
                reject(errors);
            });
        });
    }
}
