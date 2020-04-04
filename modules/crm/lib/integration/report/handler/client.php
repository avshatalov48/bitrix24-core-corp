<?php
namespace Bitrix\Crm\Integration\Report\Handler;


class Client extends Base
{
	const WHAT_WILL_CALCULATE_COUNT = 'COUNT';

	const GROUPING_BY_RESPONSIBLE = 'RESPONSIBLE';
	const GROUPING_BY_DATE = 'DATE';


	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Client');
		$this->setCategoryKey('crm');
	}

	protected function collectFormElements()
	{
		parent::collectFormElements();
	}

	/**
	 * Called every time when calculate some report result before passing some concrete handler, such us getMultipleData or getSingleData.
	 * Here you can get result of configuration fields of report, if report in widget you can get configurations of widget.
	 *
	 * @return mixed
	 */
	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();



	}
}