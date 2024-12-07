import { Loc, Type, ajax, Event } from "main.core";

class Ajax
{
	constructor()
	{
		this.type = null;
		this.method = null;
		this.url = null;
		this.callback = () => {};
		this.failure_callback = () => {};
		this.progress_callback = null;
		this.loadstart_callback = null;
		this.loadend_callback = null;
		this.offline = null;
		this.processData = null;
		this.xhr = null;
		this.data = null;
		this.headers = null;
		this.aborted = null;
		this.formData = null;
	}

	static wrap(params)
	{
		const instance = new Ajax();
		return instance.instanceWrap(params);
	}

	instanceWrap(params)
	{
		this.init(params);

		this.xhr = ajax({
			timeout: 30,
			start: this.start,
			preparePost: this.preparePost,
			method: this.method,
			dataType: this.type,
			url: this.url,
			data: this.data,
			headers: this.headers,
			processData: this.processData,
			onsuccess: (response) => {
				let failed = false;

				if (this.xhr.status === 0)
				{
					this.failure_callback();
					return;
				}
				else if (this.type == 'json')
				{
					failed = (
						Type.isPlainObject(response)
						&& !Type.isNull(response)
						&& Type.isStringFilled(response.status)
						&& response.status === 'failed'
					);
				}
				else if (this.type == 'html')
				{
					failed = (response === '{"status":"failed"}');
				}

				if (failed)
				{
					if (!this.aborted)
					{
						this.repeatRequest();
					}
				}
				else
				{
					this.callback(response);
				}
			},
			onfailure: (errorCode, requestStatus) => {
				if (
					Type.isStringFilled(errorCode)
					&& errorCode === 'status'
					&& typeof requestStatus !== 'undefined'
					&& requestStatus == 401
				)
				{
					this.repeatRequest();
				}
				else
				{
					this.failure_callback();
				}
			}
		});

		this.bindHandlers();

		Event.bind(this.xhr, 'abort', () => {
			this.aborted = true;
		});

		return this.xhr;
	};

	init(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		this.type = (params.type !== 'json' ? 'html' : 'json');
		this.method = (params.method !== 'POST' ? 'GET' : 'POST');
		this.url = params.url;
		this.data = params.data;
		this.headers = (typeof params.headers !== 'undefined' ? params.headers : []);
		this.processData = (Type.isBoolean(params.processData) ? params.processData : true);
		this.start = params.start;
		this.preparePost = params.preparePost;
		this.callback = params.callback;

		if (Type.isFunction(params.callback_failure))
		{
			this.failure_callback = params.callback_failure;
		}
		if (Type.isFunction(params.callback_progress))
		{
			this.progress_callback = params.callback_progress;
		}
		if (Type.isFunction(params.callback_loadstart))
		{
			this.loadstart_callback = params.callback_loadstart;
		}
		if (Type.isFunction(params.callback_loadend))
		{
			this.loadend_callback = params.callback_loadend;
		}
		if (typeof params.formData !== 'undefined')
		{
			this.formData = params.formData;
		}
	}

	static runComponentAction(component, action, config, callbacks)
	{
		const instance = new Ajax();
		return instance.instanceRunComponentAction(component, action, config, callbacks);
	}

	instanceRunComponentAction(component, action, config, callbacks)
	{
		if (!Type.isPlainObject(callbacks))
		{
			callbacks = {};
		}

		return new Promise((resolve, reject) => {
			config.onrequeststart = (requestXhr) => {
				this.xhr = requestXhr;
			};

			ajax.runComponentAction(component, action, config).then((response) => {
				if (Type.isFunction(callbacks.success))
				{
					callbacks.success(response);
				}
				resolve(response);
			}, (response) => {
				if (this.xhr.status == 401)
				{
					this.repeatComponentAction(component, action, config, callbacks);
				}
				else
				{
					if (Type.isFunction(callbacks.failure))
					{
						callbacks.failure(response);
					}
					reject(response)
				}
			});

			this.bindHandlers();
		});
	}

	/**
	 * @private
	 */
	repeatComponentAction(component, action, config, callbacks)
	{
		if (!Type.isPlainObject(callbacks))
		{
			callbacks = {};
		}

		return new Promise((resolve, reject) => {
			app.BasicAuth({
				success: (auth_data) => {
					ajax.runComponentAction(component, action, config).then((response) => {
						if (Type.isFunction(callbacks.success))
						{
							callbacks.success(response);
						}
						resolve(response);
					}, (response) => {
						if (Type.isFunction(callbacks.failure))
						{
							callbacks.failure(response);
						}
						reject(response);
					});
				},
				failture: () => {
					if (Type.isFunction(callbacks.failure))
					{
						callbacks.failure();
					}
					reject();
				}
			});
		});
	}

	static runAction(action, config, callbacks)
	{
		const instance = new Ajax();
		return instance.instanceRunAction(action, config, callbacks);
	}

	instanceRunAction(action, config, callbacks)
	{
		if (!Type.isPlainObject(callbacks))
		{
			callbacks = {};
		}

		return new Promise((resolve, reject) => {
			config.onrequeststart = (requestXhr) => {
				this.xhr = requestXhr;
			};

			ajax.runAction(action, config).then((response) => {
				if (Type.isFunction(callbacks.success))
				{
					callbacks.success(response);
				}
				resolve(response);
			}, (response) => {
				if (this.xhr.status == 401)
				{
					return this.repeatAction(action, config, callbacks);
				}
				else
				{
					if (Type.isFunction(callbacks.failure))
					{
						callbacks.failure(response);
					}
					reject(response)
				}
			});

			this.bindHandlers();
		});
	}

	repeatAction(action, config, callbacks)
	{
		if (!Type.isPlainObject(callbacks))
		{
			callbacks = {};
		}

		return new Promise((resolve, reject) => {
			app.BasicAuth({
				success: (auth_data) => {
					ajax.runAction(action, config).then((response) => {
						if (Type.isFunction(callbacks.success))
						{
							callbacks.success(response);
						}
						resolve(response);
					}, (response) => {
						if (Type.isFunction(callbacks.failure))
						{
							callbacks.failure(response);
						}
						reject(response);
					});
				},
				failture: () => {
					if (Type.isFunction(callbacks.failure))
					{
						callbacks.failure();
					}
					reject();
				}
			});
		});
	}

	repeatRequest()
	{
		app.BasicAuth({
			success: (auth_data) => {
				this.data.sessid = auth_data.sessid_md5;

				if (
					this.formData !== null
					&& this.formData.get('sessid') !== null
				)
				{
					this.formData.set('sessid', auth_data.sessid_md5);
				}

				this.xhr = ajax({
					timeout: 30,
					preparePost: this.preparePost,
					start: this.start,
					method: this.method,
					dataType: this.type,
					url: this.url,
					data: this.data,
					onsuccess: (response_ii) => {
						let failed = false;

						if (this.xhr.status === 0)
						{
							failed = true;
						}
						else if (this.type === 'json')
						{
							failed = (
								Type.isPlainObject(response_ii)
								&& Type.isStringFilled(response_ii.status)
								&& response_ii.status === 'failed'
							);
						}
						else if (this.type === 'html')
						{
							failed = (response_ii === '{"status":"failed"}');
						}

						if (failed)
						{
							this.failure_callback();
						}
						else
						{
							this.callback(response_ii);
						}
					},
					onfailure: (response) => {
						this.failure_callback();
					}
				});

				if (
					!this.start
					&& this.formData !== null
				)
				{
					this.xhr.send(this.formData);
				}
			},
			failture: () => {
				this.failure_callback();
			}
		});
	}

	/**
	 * @private
	 */
	bindHandlers()
	{
		if (Type.isFunction(this.progress_callback))
		{
			Event.bind(this.xhr, 'progress', this.progress_callback);
		}

		if (Type.isFunction(this.load_callback))
		{
			Event.bind(this.xhr, 'load', this.load_callback);
		}

		if (Type.isFunction(this.loadstart_callback))
		{
			Event.bind(this.xhr, 'loadstart', this.loadstart_callback);
		}

		if (Type.isFunction(this.loadend_callback))
		{
			Event.bind(this.xhr, 'loadend', this.loadend_callback);
		}

		if (Type.isFunction(this.error_callback))
		{
			Event.bind(this.xhr, 'error', this.error_callback);
		}

		if (Type.isFunction(this.abort_callback))
		{
			Event.bind(this.xhr, 'abort', this.abort_callback);
		}
	}
}

export {Ajax};
