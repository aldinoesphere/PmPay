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
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
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
	 * gateway payment confirmation
	 *
	 * @param string $confirmationUrl
	 * @throws \Exception
	 * @return string
	 */
	private function getGatewayPaymentConfirmation($confirmationUrl)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $confirmationUrl);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
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
		$checkoutUrl = $this->oppwaUrl . 'checkouts';
		$response = $this->getGatewayResponse($checkoutUrl, $parameters);

		if (!$response)
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		$responseId = json_decode($response, true);
		return $responseId["id"];
	}

	/**
	 * Get Sid from gateway to use at payment page url
	 *
	 * @param array $parameters
	 * @throws \Exception
	 * @return string
	 */
	public function paymentConfirmation($checkoutId, $parameters)
	{
		$confirmationUrl = $this->oppwaUrl . 'checkouts/' . $checkoutId . '/payment';
		$confirmationUrl .= '?' . http_build_query($parameters, '', '&');

		$response = $this->getGatewayPaymentConfirmation($confirmationUrl);

		if (!$response)
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		$responseId = json_decode($response, true);
		return $responseId;
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
		
	}

	/**
	 * send request and get refund status from gateway
	 *
	 * @param $parameters
	 * @throws \Exception
	 * @return xml
	 */
	public function doRefund($parameters)
	{
		$parameters['action'] = 'prepare';
		$response = $this->getGatewayResponse($this->skrillRefundUrl, $parameters);

		$this->getLogger(__METHOD__)->error('Skrill:prepare_response', $response);

		$xmlResponse = simplexml_load_string($response);
		$sid = (string) $xmlResponse->sid;

		if (!$this->isMd5Valid($sid))
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		unset($parameters);
		$parameters['action'] = 'refund';
		$parameters['sid'] = $sid;

		$response = $this->getGatewayResponse($this->skrillRefundUrl, $parameters);

		$this->getLogger(__METHOD__)->error('Skrill:refund_response', $response);

		return simplexml_load_string($response);
	}

}
