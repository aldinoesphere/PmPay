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
	public function getPaymentContent(Basket $basket, $mode = 'pmpay'):string
	{
	 
	    $paymentContent = '';
	    $links = $resultJson->links;
	    if(is_array($links))
	    {
	        foreach($links as $key => $value)
	        {
	            // Get the redirect URLs for the content
	            if($value->method == 'REDIRECT')
	            {
	                $paymentContent = $value->href;
	                $this->returnType = 'redirectUrl';
	            }
	        }
	    }
	    // Check whether the content is set. Else, return an error code.
	    if(!strlen($paymentContent))
	    {
	        $this->returnType = 'errorCode';
	        return 'An unknown error occurred, please try again.';
	    }
	    return $paymentContent;
	}


}

?>