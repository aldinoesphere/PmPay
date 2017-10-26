<?php  
namespace PmPay\Migrations;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

use PmPay\Helper\PaymentHelper;

/**
* 
*/
class CreatePaymentMethodTwo
{
	
	/**
	 * @var PaymentMethodRepositoryContract
	 */
	private $paymentMethodRepository;

	/**
	 * @var PaymentHelper
	 */
	private $paymentHelper;


	public function __construct(
		PaymentMethodRepositoryContract $paymentMethodRepository, 
		PaymentHelper $paymentHelper
	) {
		$this->paymentMethodRepository = $paymentMethodRepository;
		$this->paymentHelper = $paymentHelper;
	}

	public function run() {
		$this->createPaymentMethodByPaymentKey('PMPAY_ACC', 'Credit Card Payment Methods');
	}

	/**
	 * Create payment method with given parameters if it doesn't exist
	 *
	 * @param string $paymentKey
	 * @param string $name
	 */
	private function createPaymentMethodByPaymentKey($paymentKey, $name)
	{
		// Check whether the ID of the PmPay payment method has been created
		$paymentMethod = $this->paymentHelper->getPaymentMethodByPaymentKey($paymentKey);
		if (is_null($paymentMethod))
		{
			$this->paymentMethodRepository->createPaymentMethod(
							[
								'pluginKey' => 'pmpay',
								'paymentKey' => (string) $paymentKey,
								'name' => $name
							]
			);
		}
	}
}

?>