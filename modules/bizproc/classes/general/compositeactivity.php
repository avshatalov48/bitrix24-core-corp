<?
abstract class CBPCompositeActivity
	extends CBPActivity
{
	protected $arActivities = array();

	public function SetWorkflow(CBPWorkflow $workflow)
	{
		parent::SetWorkflow($workflow);
		foreach ($this->arActivities as $activity)
		{
			if (!method_exists($activity, 'SetWorkflow'))
			{
				throw new Exception('ActivitySetWorkflow');
			}
			$activity->SetWorkflow($workflow);
		}
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		/** @var CBPActivity $activity */
		foreach ($this->arActivities as $activity)
			$activity->ReInitialize();
	}

	public function CollectNestedActivities()
	{
		return $this->arActivities;
	}

	public function FixUpParentChildRelationship(CBPActivity $nestedActivity)
	{
		parent::FixUpParentChildRelationship($nestedActivity);

		if (!is_array($this->arActivities))
			$this->arActivities = array();

		$this->arActivities[] = $nestedActivity;
	}

	protected function ClearNestedActivities()
	{
		$this->arActivities = array();
	}

	public function Initialize()
	{
		foreach ($this->arActivities as $activity)
			$this->workflow->InitializeActivity($activity);
	}

	public function Finalize()
	{
		foreach ($this->arActivities as $activity)
			$this->workflow->FinalizeActivity($activity);
	}

	public function HandleFault(Exception $exception)
	{
		if (!$exception)
			throw new Exception("exception");

		$status = $this->Cancel();
		if ($status == CBPActivityExecutionStatus::Canceling)
			return CBPActivityExecutionStatus::Faulting;

		return $status;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		return parent::ValidateProperties($arTestProperties, $user);
	}
}