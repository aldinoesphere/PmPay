<?php
 
namespace PmPay\Providers;
 
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendUpdateInvoiceAddress;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Log\Loggable;

use PmPay\Helper\PaymentHelper;
use PmPay\Services\PaymentService;
use PmPay\Methods\AccPaymentMethod;
 
class PmPayServiceProvider extends ServiceProvider
{
	use Loggable;


    public function register()
    {
 		$this->getApplication()->register(PmPayRouteServiceProvider::class);
    }

    public function boot(
    	Dispatcher $eventDispatcher,
		PaymentHelper $paymentHelper,
		PaymentService $paymentService,
		BasketRepositoryContract $basket,
		PaymentMethodContainer $payContainer,
		PaymentMethodRepositoryContract $paymentMethodService,
		EventProceduresService $eventProceduresService
    ) {
    	$this->registerPaymentMethod($payContainer, 'PMPAY_ACC', AccPaymentMethod::class);

    	// Listen for the event that gets the payment method content
		$eventDispatcher->listen(
						GetPaymentMethodContent::class,
						function(GetPaymentMethodContent $event) use( $paymentHelper, $basket, $paymentService)
                   {
                        if($event->getMop())
                        {
                              $basket = $basket->load();
                              $event->setValue($paymentService->getPaymentContent($basket));
                              $event->setType( $paymentService->getReturnType());
                        }
                   });

		// Listen for the event that executes the payment
		$eventDispatcher->listen(
						ExecutePayment::class,
						function (ExecutePayment $event) use ($paymentHelper, $paymentService) {
							if ($paymentHelper->isPmPayPaymentMopId($event->getMop()))
							{
								$result = $paymentService->executePayment($event->getOrderId());

								$event->setType($result['type']);
								$event->setValue($result['value']);
							}
						}
		);
    }

    /**
	 * register payment method.
	 *
	 * @param PaymentMethodContainer $payContainer
	 * @param string $paymentKey
	 * @param PaymentMethodService $class
	 */
	private function registerPaymentMethod($payContainer, $paymentKey, $class)
	{
		$payContainer->register(
			'PlentyPmpay::' . $paymentKey,
			$class,
			[
				AfterBasketChanged::class,
				AfterBasketItemAdd::class,
				AfterBasketCreate::class,
				FrontendLanguageChanged::class,
				FrontendUpdateInvoiceAddress::class
			]
		);
	}
}