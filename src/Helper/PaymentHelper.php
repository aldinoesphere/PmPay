<?php  
namespace PmPay\Helper;

use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentPropertyRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Plugin\Log\Loggable;

/**
* 
*/
class PaymentHelper
{
	use Loggable;

	/**
	 * @var PaymentMethodRepositoryContract
	 */
	private $paymentMethodRepository;

	/**
	 * @var PaymentOrderRelationRepositoryContract
	 */
	private $paymentOrderRelationRepository;

	/**
	 * @var PaymentRepositoryContract
	 */
	private $paymentRepository;

	/**
	 * @var PaymentPropertyRepositoryContract
	 */
	private $paymentPropertyRepository;

	/**
	 * @var OrderRepositoryContract
	 */
	private $orderRepository;
	
	public function __construct(
		PaymentMethodRepositoryContract $paymentMethodRepository,
		PaymentRepositoryContract $paymentRepository,
		PaymentPropertyRepositoryContract $paymentPropertyRepository,
		PaymentOrderRelationRepositoryContract $paymentOrderRelationRepository,
		OrderRepositoryContract $orderRepository
	)
	{
		$this->paymentMethodRepository          = $paymentMethodRepository;
		$this->paymentOrderRelationRepository   = $paymentOrderRelationRepository;
		$this->paymentRepository                = $paymentRepository;
		$this->paymentPropertyRepository        = $paymentPropertyRepository;
		$this->orderRepository                  = $orderRepository;
	}

	/**
	 * get domain from webstoreconfig.
	 *
	 * @return string
	 */
	public function getDomain()
	{
		$webstoreHelper = pluginApp(\Plenty\Modules\Helper\Services\WebstoreHelper::class);
		$webstoreConfig = $webstoreHelper->getCurrentWebstoreConfiguration();
		$domain = $webstoreConfig->domainSsl;

		return $domain;
	}

	public function getPaymentMethodByPaymentKey($paymentKey)
	{
		if (strlen($paymentKey))
		{
			// List all payment methods for the given plugin
			$paymentMethods = $this->paymentMethodRepository->allForPlugin('pmpay');

			if (!is_null($paymentMethods))
			{
				foreach ($paymentMethods as $paymentMethod)
				{
					if ($paymentMethod->paymentKey == $paymentKey)
					{
						return $paymentMethod;
					}
				}
			}
		}

		return null;
	}

	public function createPlentyPayment($paymentStatus) {
		$pmpayData = json_decode($paymentStatus);
		$paymentData = array();
		// Set the payment data
		$paymentData['mopId']           = (int)$this->getPayPalMopId();
		$paymentData['transactionType'] = 2;
		$paymentData['status']          = $this->mapStatus($pmpayData->status);
		$paymentData['currency']        = $pmpayData->currency;
		$paymentData['amount']          = $pmpayData->amount;
		$paymentData['receivedAt']       = $pmpayData->entryDate;
		$payment = $this->paymentRepository->createPayment($paymentData);
	}

}

?>