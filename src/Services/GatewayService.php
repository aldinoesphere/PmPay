<?php
namespace PmPay\Services;

use Plenty\Plugin\Log\Loggable;

/**
* Class GatewayService
* @package PmPay\Services
*/
class GatewayService
{
	use Loggable;

	/**
	 * @var string
	 */
	protected $oppwaUrl = 'https://test.oppwa.com/v1/';

	/**
	 * Get gateway response
	 *
	 * @param string $url
	 * @param array $parameters
	 * @throws \Exception
	 * @return string
	 */
	private function getGatewayResponse($url, $parameters)
	{
		$postFields = http_build_query($parameters, '', '&');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responseData = curl_exec($ch);
		if(curl_errno($ch)) {
			return curl_error($ch);
		}
		curl_close($ch);
		return $responseData;
	}

	/**
	 * Get Sid from gateway to use at payment page url
	 *
	 * @param array $parameters
	 * @throws \Exception
	 * @return string
	 */
	public function getCheckoutId($parameters)
	{
		$checkOutUrl = $this->oppwaUrl . 'checkouts'
		$response = $this->getGatewayResponse($checkOutUrl, $parameters);

		if (!$response)
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		return $response;
	}

	/**
	 * Get Sid from gateway to use at payment page url
	 *
	 * @param array $parameters
	 * @throws \Exception
	 * @return string
	 */
	public function getSidResult($parameters)
	{
		$response = $this->getGatewayResponse($this->skrillPayUrl, $parameters);

		if (!$this->isMd5Valid($response))
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		return $response;
	}

	/**
	 * get currenty payment status from gateway
	 *
	 * @param $parameters
	 * @throws \Exception
	 * @return array
	 */
	public function getPaymentStatus($parameters)
	{
		$parameters['action'] = 'status_trn';
		$response = $this->getGatewayResponse($this->skrillQueryUrl, $parameters);

		$this->getLogger(__METHOD__)->error('Skrill:response', $response);

		$responseCode = (int) substr($response, 0, 3);
		if ($responseCode == 401)
		{
			if (strpos($response, 'Cannot login') !== false)
			{
				throw new \Exception('Please check MQI/API password');
			}
			elseif (strpos($response, 'Your account is currently locked') !== false)
			{
				$message = "Your account is currently locked. Please contact our Merchant Team: merchantservices@skrill.com";
				throw new \Exception($message);
			}
			throw new \Exception('Get payment status failed!');
		}

		$responseInArray = $this->setResponseToArray($response);

		if (!$responseInArray)
		{
			throw new \Exception('Get payment status failed!');
		}
		return $responseInArray;
	}

}
