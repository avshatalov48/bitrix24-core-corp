<?
abstract class CDavGroupdavHandler
{
	protected $application;
	protected $groupdav;

	protected $httpIfMatch;

	/**
	 * CDavGroupdavHandler constructor.
	 * @param CDavGroupDav $groupdav
	 * @param $application
	 */
	public function __construct($groupdav, $application)
	{
		$this->groupdav = $groupdav;
		$this->application = $application;
	}

	/* Check if user has the neccessary rights on an entry */
	abstract function CheckPrivileges($testPrivileges, $principal, $collectionId);
	abstract function CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath);

	abstract protected function GetMethodMinimumPrivilege($method);

	public function GetApplication()
	{
		return $this->application;
	}

	/**
	 * @param CDavGroupDav $groupdav
	 * @param $application
	 * @return mixed|null
	 */
	public static function &GetApplicationHandler($groupdav, $application)
	{
		static $handlerCache = array();

		if (!array_key_exists($application, $handlerCache))
		{
			$h = 'CDav' . strtoupper(substr($application, 0, 1)) . substr($application, 1) . 'Handler';
			if (!class_exists($h))
				return null;

			$handlerCache[$application] = new $h($groupdav, $application);
		}

		return $handlerCache[$application];
	}

	public function GetEntry($method, $id, $collectionId)
	{
		$entry = $this->Read($collectionId, $id);

		if (!$entry && ($method != 'PUT' || $entry === false))
			return ($entry === false) ? '403 Forbidden' : '404 Not Found';

		$minimumPrivilege = $this->GetMethodMinimumPrivilege($method);
		$request = $this->groupdav->GetRequest();
		if (!$this->CheckPrivileges($minimumPrivilege, $request->GetPrincipal(), $collectionId))
			return '403 Forbidden';

		if ($entry)
		{
			$etag = $this->GetETag($collectionId, $entry);

			// If the clients sends an "If-Match" header ($_SERVER['HTTP_IF_MATCH']) we check with the current etag
			// of the calendar --> on failure we return 412 Precondition failed, to not overwrite the modifications
			if ($request->GetParameter('HTTP_IF_MATCH') !== null)
			{
				$m = $request->GetParameter('HTTP_IF_MATCH');
				if (strstr($m, $etag) === false)
				{
					$this->httpIfMatch = $m;
					return '412 Precondition Failed';
				}
				else
				{
					$this->httpIfMatch = $etag;
					// if an IF_NONE_MATCH is given, check if we need to send a new export, or the current one is still up-to-date
					if ($method == 'GET' &&	$request->GetParameter('HTTP_IF_NONE_MATCH') !== null)
						return '304 Not Modified';
				}
			}

			// Workaround for Mac OS X Lion > 10.7.3
			// if ($request->GetParameter('HTTP_IF_NONE_MATCH') !== null)
			// {
				// $m = $request->GetParameter('HTTP_IF_NONE_MATCH');
				// if (strstr($m, $etag) !== false || $m == '*')
				// {
					// Do nothing!
					// Mac OS X Lion > 10.7.3 is sending a one more request after event creating or modifying.
					// If we will return 304, iCal will always create new events with title "New event"
					//return '304 Not Modified';
				// }
				// else
				// {
					//return '412 Precondition Failed';
				// }
			// }
		}

		return $entry;
	}
}
?>