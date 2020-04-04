<?
namespace Bitrix\Tasks\Util;

final class Error extends \Bitrix\Main\Error
{
	const TYPE_FATAL = 		'FATAL';
	const TYPE_WARNING = 	'WARNING';

	protected $type = 'FATAL';
	protected $data = null;

	/**
	 * Creates a new Error.
	 * @param string $message
	 * @param int $code
	 * @param bool $type
	 * @param null $data
	 */
	public function __construct($message, $code = 0, $type = false, $data = null)
	{
		parent::__construct($message, $code);

		if(!$type)
		{
			$type = static::TYPE_FATAL;
		}
		$this->type = $type;

		if($data !== null)
		{
			$this->data = $data;
		}
	}

	public function toArray()
	{
		return array(
			'CODE' =>       $this->getCode(),
			'MESSAGE' => 	$this->getMessage(),
			'TYPE' => 		$this->getType(),
			'DATA' =>       $this->getData()
		);
	}

	public function toArrayMeta()
	{
		return array(
			'CODE' =>       $this->getCode(),
			'MESSAGE' => 	$this->getMessage(),
			'TYPE' => 		$this->getType()
		);
	}

	public static function makeFromArray(array $error)
	{
		return new static($error['MESSAGE'], $error['CODE'], $error['TYPE'], $error['DATA']);
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function setMessage($message)
	{
		$this->message = $message;
	}

	public function isFatal()
	{
		return $this->type == static::TYPE_FATAL;
	}

	public function matchFilter($filter = array())
	{
		if(!is_array($filter))
		{
			$filter = array();
		}

		$match = true;

		// by code
		if(array_key_exists('CODE', $filter))
		{
			$fCode = trim((string) $filter['CODE']);

			$code = $this->getCode();
			if($code != $fCode)
			{
				$subCodes = explode('.', $code);
				$found = false;
				foreach($subCodes as $sCode)
				{
					if($sCode == $fCode)
					{
						$found = true;
						break;
					}
				}

				if(!$found)
				{
					$match = false;
				}
			}
		}

		// by type
		if(array_key_exists('TYPE', $filter))
		{
			if($this->getType() != trim((string) $filter['TYPE']))
			{
				$match = false;
			}
		}

		return $match;
	}
}