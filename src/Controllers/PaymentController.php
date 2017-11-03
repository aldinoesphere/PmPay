<?php

namespace PmPay\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Basket\Contracts\BasketItemRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;

use PmPay\Services\GatewayService;
use PmPay\Helper\PaymentHelper;
use PmPay\Services\PaymentService;
use PmPay\Services\OrderService;
/**
* Class PaymentController
* @package PmPay\Controllers
*/
class PaymentController extends Controller
{
	use Loggable;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

	/**
	 * @var BasketItemRepositoryContract
	 */
	private $basketItemRepository;

	/**
	 * @var SessionStorage
	 */
	private $sessionStorage;

	/**
	 *
	 * @var gatewayService
	 */
	private $gatewayService;

	/**
	 *
	 * @var paymentHelper
	 */
	private $paymentHelper;

	/**
	 *
	 * @var orderService
	 */
	private $orderService;

	/**
	 *
	 * @var paymentService
	 */
	private $paymentService;

	/**
	 * PaymentController constructor.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param BasketItemRepositoryContract $basketItemRepository
	 * @param SessionStorageService $sessionStorage
	 */
	public function __construct(
					Request $request,
					Response $response,
					BasketItemRepositoryContract $basketItemRepository,
					FrontendSessionStorageFactoryContract $sessionStorage,
					GatewayService $gatewayService,
					PaymentHelper $paymentHelper,
					OrderService $orderService,
					PaymentService $paymentService
	) {
		$this->request = $request;
		$this->response = $response;
		$this->basketItemRepository = $basketItemRepository;
		$this->sessionStorage = $sessionStorage;
		$this->gatewayService = $gatewayService;
		$this->paymentHelper = $paymentHelper;
		$this->orderService = $orderService;
		$this->paymentService = $paymentService;
	}

	/**
	 * handle return_url from payment gateway
	 */
	public function handleReturn()
	{
		$this->getLogger(__METHOD__)->error('PmPay:return_url', $this->request->all());
		$this->sessionStorage->getPlugin()->setValue('PmPayTransactionId', $this->request->get('transaction_id'));

		$orderId = $this->request->get('orderId');

		$this->getLogger(__METHOD__)->error('PmPay:orderId', $orderId);

		$basketItems = $this->basketItemRepository->all();
		$this->getLogger(__METHOD__)->error('PmPay:basketItems', $basketItems);

		foreach ($basketItems as $basketItem)
		{
			$this->basketItemRepository->removeBasketItem($basketItem->id);
		}

		return $this->response->redirectTo('execute-payment/'.$orderId);
	}

	/**
	 * show payment widget
	 */
	public function handlePayment(Twig $twig, $checkoutId)
	{
		$paymentPageUrl = $this->paymentHelper->getDomain() . '/payment/pmpay/validate/' . $checkoutId . '/';
		$this->getLogger(__METHOD__)->error('PmPay:paymentPageUrl', $paymentPageUrl);

		$data = [
			'checkoutId' => $checkoutId,
			'paymentPageUrl' => $paymentPageUrl
		];

		return $twig->render('PmPay::Payment.PaymentWidget', $data);
	}

	/**
	 * show payment widget
	 */
	public function handleValidation($checkoutId)
	{
		$this->getLogger(__METHOD__)->error('PmPay:checkoutId', $checkoutId);
		
		$pmpaySettings = $this->paymentService->getPmPaySettings();
		$ccSettings = $this->paymentService->getCcSettings();
		$orderData = $this->orderService->placeOrder();

		$orderId = $orderData->order->id;

		$this->getLogger(__METHOD__)->error('PmPay:orderId', $orderData->order->id);

		$parameters = [
			'authentication.userId' => $pmpaySettings['userId'],
			'authentication.password' => $pmpaySettings['password'],
			'authentication.entityId' => $ccSettings['entityId']
		];

		$paymentConfirmation = $this->gatewayService->paymentConfirmation($checkoutId, $parameters);
		return $this->response->redirectTo('payment/pmpay/return?orderId=' . $orderId);
	}

}
