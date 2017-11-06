<?php

namespace PmPay\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Log\Loggable;
use PmPay\Helper\PaymentHelper;

/**
* Class PaymentNotificationController
* @package PmPay\Controllers
*/
class PaymentNotificationController extends Controller
{
	use Loggable;

	/**
	 *
	 * @var Request
	 */
	private $request;

	/**
	 *
	 * @var PaymentHelper
	 */
	private $paymentHelper;

	/**
	 * PaymentNotificationController constructor.
	 *
	 * @param Request $request
	 * @param PaymentHelper $paymentHelper
	 */
	public function __construct(Request $request, PaymentHelper $paymentHelper)
	{
		$this->request = $request;
		$this->paymentHelper = $paymentHelper;
	}

	/**
	 * handle status_url from payment gateway
	 * @return string
	 */
	public function handleStatus()
	{
		$this->getLogger(__METHOD__)->error('PmPay:status_url', $this->request->all());

		$paymentStatus = $this->request->all();
		$this->paymentHelper->updatePlentyPayment($paymentStatus);

		return 'ok';
	}
}
