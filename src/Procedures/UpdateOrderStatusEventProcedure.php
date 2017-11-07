<?php
namespace PmPay\Procedures;

use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Plugin\Log\Loggable;

use PmPay\Services\PaymentService;
use PmPay\Helper\PaymentHelper;

/**
* Class UpdateOrderStatusEventProcedure
* @package PmPay\Procedures
*/
class UpdateOrderStatusEventProcedure
{
	use Loggable;

	/**
	 * @param EventProceduresTriggered $eventTriggered
	 * @param PaymentRepositoryContract $paymentRepository
	 * @param PaymentService $paymentService
	 * @param PaymentHelper $paymentHelper
	 * @throws \Exception
	 */
	public function run(
					EventProceduresTriggered $eventTriggered,
					PaymentRepositoryContract $paymentRepository,
					PaymentService $paymentService,
					PaymentHelper $paymentHelper
	) {
		/** @var Order $order */
		$order = $eventTriggered->getOrder();

		// only sales orders are allowed order types to upate order status
		if ($order->typeId == 1)
		{
			$orderId = $order->id;
		}

		if (empty($orderId))
		{
			throw new \Exception('Update order status PmPay payment failed! The given order is invalid!');
		}

		/** @var Payment[] $payment */
		$payments = $paymentRepository->getPaymentsByOrderId($orderId);

		$this->getLogger(__METHOD__)->error('PmPay:payments', $payments);

		if (count($payments) > 0)
		{
			/** @var Payment $payment */
			foreach ($payments as $payment)
			{
				if ($paymentHelper->isPmPayPaymentMopId($payment->mopId))
				{
					$transactionId = $paymentHelper->getPaymentPropertyValue(
									$payment->properties,
									PaymentProperty::TYPE_TRANSACTION_ID
					);

					$this->getLogger(__METHOD__)->error('PmPay:transactionId', $transactionId);

					if (isset($transactionId))
					{
						// update order status the payment
						$updateResult = $paymentService->updateOrderStatus($transactionId, $order);
						$this->getLogger(__METHOD__)->error('PmPay:updateResult', $updateResult);

						if ($updateResult['error'])
						{
							throw new \Exception('Update order status PmPay payment failed!');
						}

						if ($updateResult['success'])
						{
							$paymentStatus = $updateResult['response'];


							$state = $paymentHelper->mapTransactionState((string) $paymentStatus['status']);

							if ($payment->status != $state)
							{
								$payment->status = $state;

								if ($state == Payment::STATUS_APPROVED)
								{
									$payment->unaccountable = 0;
									$payment->updateOrderPaymentStatus = true;
								}
							}

							$paymentHelper->updatePaymentPropertyValue(
											$payment->properties,
											PaymentProperty::TYPE_BOOKING_TEXT,
											$paymentHelper->getPaymentBookingText($paymentStatus)
							);

							$this->getLogger(__METHOD__)->error('PmPay:update_payment', $payment);

							$paymentRepository->updatePayment($payment);
						}
					}
				}
			}
		}
		else
		{
			throw new \Exception('Update order status PmPay payment failed! The given order does not have payment!');
		}
	}
}
