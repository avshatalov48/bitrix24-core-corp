<?
namespace Bitrix\Intranet\Controller\Filter;

class User extends \Bitrix\Main\Controller\Filter\User
{
	public function getListAction($filterId, $componentName, $signedParameters)
	{
		$filterId = trim($filterId);
		$unsignedParameters = \Bitrix\Main\Component\ParameterSigner::unsignParameters($componentName, $signedParameters);

		return $this->getList('USER_INTRANET', [
			'ID' => $filterId != '' ? $filterId : 'INTRANET_USER_LIST',
			'WHITE_LIST' => ($unsignedParameters['USER_PROPERTY_LIST'] ?? [])
		]);
	}

	public function getFieldAction($filterId, $id, $componentName, $signedParameters)
	{
		$unsignedParameters = \Bitrix\Main\Component\ParameterSigner::unsignParameters($componentName, $signedParameters);

		$filterId = trim($filterId);
		$id = trim($id);

		return $this->getField('USER_INTRANET', [
			'ID' => $filterId != '' ? $filterId : 'INTRANET_USER_LIST',
			'WHITE_LIST' => ($unsignedParameters['USER_PROPERTY_LIST'] ?? [])
		], $id);
	}
}

