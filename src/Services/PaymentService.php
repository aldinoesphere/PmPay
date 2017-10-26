<?php  

namespace PmPay\Services;

use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Frontend\Services\SystemService;
use Plenty\Plugin\Log\Loggable;


use PmPay\Helper\PaymentHelper;
use PmPay\Services\Database\SettingsService;
/**
* 
*/
class PaymentService
{
	use Loggable;

	/**
	 *
	 * @var ItemRepositoryContract
	 */
	private $itemRepository;

	/**
	 *
	 * @var FrontendSessionStorageFactoryContract
	 */
	private $session;

	/**
	 *
	 * @var AddressRepositoryContract
	 */
	private $addressRepository;

	/**
	 *
	 * @var CountryRepositoryContract
	 */
	private $countryRepository;

	/**
	 *
	 * @var PaymentHelper
	 */
	private $paymentHelper;

	/**
	 *
	 * @var systemService
	 */
	private $systemService;

	/**
	 *
	 * @var settingsService
	 */
	private $settingsService;

	/**
	 *
	 * @var gatewayService
	 */
	private $gatewayService;

	/**
	 *
	 * @var orderService
	 */
	private $orderService;

	/**
	 *
	 * @var orderRepository
	 */
	private $orderRepository;

	/**
	 * @var array
	 */
	public $settings = [];

	function __construct(
		ItemRepositoryContract $itemRepository,
		FrontendSessionStorageFactoryContract $session,
		AddressRepositoryContract $addressRepository,
		CountryRepositoryContract $countryRepository,
		PaymentHelper $paymentHelper,
		SystemService $systemService
	){
		$this->itemRepository = $itemRepository;
		$this->session = $session;
		$this->addressRepository = $addressRepository;
		$this->countryRepository = $countryRepository;
		$this->paymentHelper = $paymentHelper;
		$this->systemService = $systemService;
	}

	/**
	 * Load the settings from the database for the given settings type
	 *
	 * @param $settingsType
	 * @return array|null
	 */
	public function loadCurrentSettings($settingsType = 'pmpay-general')
	{
		$setting = $this->settingsService->loadSetting($this->systemService->getPlentyId(), $settingsType);
		if (is_array($setting) && count($setting) > 0)
		{
			$this->settings = $setting;
		}
	}

	/**
	 * get the settings from the database for the given settings type is skrill_general
	 *
	 * @return array|null
	 */
	public function getPmPaySettings()
	{
		$this->loadCurrentSettings();
		return $this->settings;
	}

	/**
	 * this function will execute after we are doing a payment and show payment success or not.
	 *
	 * @param int $orderId
	 * @return array
	 */
	public function executePayment($orderId)
	{
		$transactionId = $this->session->getPlugin()->getValue('pmpayTransactionId');

		return $this->paymentHelper->getOrderPaymentStatus($transactionId);
	}

	/**
	 * Get the PayPal payment content
	 *
	 * @param Basket $basket
	 * @return string
	 */
	public function getPaymentContent(Basket $basket, PaymentMethod $paymentMethod):string
	{
	 
	    $pmpaySettings = $this->getPmPaySettings();

		$orderData = $this->orderService->placeOrder();

		if (!isset($orderData->order->id))
		{
			return [
				'type' => GetPaymentMethodContent::RETURN_TYPE_ERROR,
				'content' => 'The order can not created'
			];
		}

		$orderId = $orderData->order->id;
		$transactionId = time() . $this->getRandomNumber(4) . $orderId;

		$billingAddress = $this->getAddress($this->getBillingAddress($basket));

		$parameters = [
			'pay_to_email' => $pmpaySettings['merchantEmail'],
			'recipient_description' => $pmpaySettings['userId'],
			'transaction_id' => $transactionId,
			'return_url' => $this->paymentHelper->getDomain().
				'/payment/skrill/return?orderId='.$orderId,
			'status_url' => $this->paymentHelper->getDomain().
				'/payment/skrill/status?orderId='.$orderId.
				'&paymentKey='.$paymentMethod->paymentKey,
			'cancel_url' => $this->paymentHelper->getDomain().'/checkout',
			'logo_url' => $pmpaySettings['logoUrl'],
			'prepare_only' => 1,
			'pay_from_email' => $billingAddress['email'],
			'firstname' => $billingAddress['firstName'],
			'lastname' => $billingAddress['lastName'],
			'address' => $billingAddress['address'],
			'postal_code' => $billingAddress['postalCode'],
			'city' => $billingAddress['city'],
			'country' => $billingAddress['country'],
			'amount' => $basket->basketAmount,
			'currency' => $basket->currency,
			'detail1_description' => "Order pay from " . $billingAddress['email'],
			'merchant_fields' => 'platform',
			'platform' => '21477252',
		];
		if ($paymentMethod->paymentKey == 'PMPAY_ACC')
		{
			$parameters['payment_methods'] = 'VSA, MSC, AMX';
		}
		

		try
		{
			$sidResult = $this->gatewayService->getSidResult($parameters);
		}
		catch (\Exception $e)
		{
			return [
				'type' => GetPaymentMethodContent::RETURN_TYPE_ERROR,
				'content' => 'An error occurred while processing your transaction. Please contact our support.'
			];
		}

		// if ($pmpaySettings['display'] == 'REDIRECT')
		// {
			$paymentPageUrl = $this->gatewayService->getPaymentPageUrl($sidResult);
		// }
		// else
		// {
		// 	$paymentPageUrl = $this->paymentHelper->getDomain().'/payment/pmpay/pay/' . $sidResult;
		// }

		return [
			'type' => GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL,
			'content' => $paymentPageUrl
		];
	}


}

?>