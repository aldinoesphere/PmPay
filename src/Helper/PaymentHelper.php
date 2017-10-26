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

	/**
	 * Create a credit payment when status_url triggered and no payment created before.
	 *
	 * @param array $paymentStatus
	 * @return Payment
	 */
	public function createPlentyPayment($paymentStatus)
	{

		$payment = pluginApp(Payment::class);

		$mopId = 0;
		$paymentMethod = $this->getPaymentMethodByPaymentKey($paymentStatus['paymentKey']);

		if (isset($paymentMethod))
		{
			$mopId = $paymentMethod->id;
		}

		$payment->mopId = (int) $mopId;
		$payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;

		$state = $this->mapTransactionState((string)$paymentStatus['status']);

		$payment->status = $state;
		$payment->currency = $paymentStatus['currency'];
		$payment->amount = $paymentStatus['amount'];

		if ($state == Payment::STATUS_APPROVED)
		{
			$payment->unaccountable = 0;
		} else {
			$payment->unaccountable = 1;
		}

		$paymentProperty = [];
		$paymentProperty[] = $this->getPaymentProperty(
						PaymentProperty::TYPE_TRANSACTION_ID,
						$paymentStatus['transaction_id']
		);
		$paymentProperty[] = $this->getPaymentProperty(PaymentProperty::TYPE_ORIGIN, Payment::ORIGIN_PLUGIN);
		$paymentProperty[] = $this->getPaymentProperty(
						PaymentProperty::TYPE_BOOKING_TEXT,
						$this->getPaymentBookingText($paymentStatus, true)
		);

		if (isset($paymentStatus['pay_to_email']))
		{
			$paymentProperty[] = $this->getPaymentProperty(
							PaymentProperty::TYPE_ACCOUNT_OF_RECEIVER,
							$paymentStatus['pay_to_email']
			);
		}

		if (isset($paymentStatus['failed_reason_code']))
		{
			$paymentProperty[] = $this->getPaymentProperty(
							PaymentProperty::TYPE_EXTERNAL_TRANSACTION_STATUS,
							$paymentStatus['failed_reason_code']
			);
		}

		$payment->properties = $paymentProperty;
		$payment->regenerateHash = true;

		$payment = $this->paymentRepository->createPayment($payment);

		$this->getLogger(__METHOD__)->error('PmPay:payment', $payment);

		return $payment;
	}

	/**
	 * update the payment by transaction_id when status_url triggered if payment already created before.
	 * create a payment if no payment created before.
	 *
	 * @param array $paymentStatus
	 */
	public function updatePlentyPayment($paymentStatus)
	{
		$payments = $this->paymentRepository->getPaymentsByPropertyTypeAndValue(
						PaymentProperty::TYPE_TRANSACTION_ID,
						$paymentStatus['transaction_id']
		);

		$this->getLogger(__METHOD__)->error('PmPay:updatePlentyPayment', $payments);

		if (count($payments) > 0)
		{

			$state = $this->mapTransactionState((string)$paymentStatus['status']);
			foreach ($payments as $payment)
			{
				if ($payment->status != $state)
				{
					$payment->status = $state;

					if ($state == Payment::STATUS_APPROVED)
					{
						$payment->unaccountable = 0;
						$payment->updateOrderPaymentStatus = true;
					}
				}

				$this->updatePaymentPropertyValue(
								$payment->properties,
								PaymentProperty::TYPE_BOOKING_TEXT,
								$this->getPaymentBookingText($paymentStatus, true)
				);

				$this->paymentRepository->updatePayment($payment);
			}
		} else {
			$payment = $this->createPlentyPayment($paymentStatus);

			$this->assignPlentyPaymentToPlentyOrder($payment, (int) $paymentStatus['orderId']);
		}
	}

	/**
	 * check if the mopId is PmPay mopId.
	 *
	 * @param number $mopId
	 * @return bool
	 */
	public function isPmPayPaymentMopId($mopId)
	{
		$paymentMethods = $this->paymentMethodRepository->allForPlugin('pmpay');

		if (!is_null($paymentMethods))
		{
			foreach ($paymentMethods as $paymentMethod)
			{
				if ($paymentMethod->id == $mopId)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * update payment property value.
	 *
	 * @param array $properties
	 * @param int $propertyType
	 * @param string $value
	 */
	public function updatePaymentPropertyValue($properties, $propertyType, $value)
	{
		if (count($properties) > 0)
		{
			foreach ($properties as $property)
			{
				if ($property->typeId == $propertyType)
				{
					$paymentProperty = $property;
					break;
				}
			}

			if (isset($paymentProperty))
			{
				$paymentProperty->value = $value;
				$this->paymentPropertyRepository->changeProperty($paymentProperty);
			}
		}
	}

	/**
	 * get payment property value.
	 *
	 * @param array $properties
	 * @param int $propertyType
	 * @return null|string
	 */
	public function getPaymentPropertyValue($properties, $propertyType)
	{
		if (count($properties) > 0)
		{
			foreach ($properties as $property)
			{
				if ($property instanceof PaymentProperty)
				{
					if ($property->typeId == $propertyType)
					{
						return $property->value;
					}
				}
			}
		}

		return null;
	}

	/**
	 * get order payment status by transactionId (success or error)
	 *
	 * @param string $transactionId
	 * @return array
	 */
	public function getOrderPaymentStatus($transactionId)
	{
		$payments = $this->paymentRepository->getPaymentsByPropertyTypeAndValue(
						PaymentProperty::TYPE_TRANSACTION_ID,
						$transactionId
		);

		$status = '';
		$properties = [];

		if (count($payments) > 0)
		{
			foreach ($payments as $payment)
			{
				$status = $payment->status;
				$properties = $payment->properties;
				break;
			}
		}

		$this->getLogger(__METHOD__)->error('PmPay:status', $status);

		if ($status == Payment::STATUS_REFUSED)
		{
			$failedReasonCode = $this->getPaymentPropertyValue(
							$properties,
							PaymentProperty::TYPE_EXTERNAL_TRANSACTION_STATUS
			);

			return [
				'type' => 'error',
				'value' => 'The payment has been failed : ' 
			];
		}

		return [
			'type' => 'success',
			'value' => 'The payment has been executed successfully.'
		];
	}

	/**
	 * get payment status (use for payment/refund detail information status).
	 *
	 * @param array $status
	 * @param bool $isCredentialValid
	 * @return string
	 */
	public function getPaymentStatus(string $status, $isCredentialValid = true)
	{
		if (!$isCredentialValid)
		{
			return 'Invalid Credential';
		}

		switch ($status)
		{
			case '0':
				return 'Pending';
			case '2':
				return 'Processed';
			case '-2':
				return 'Failed';
		}

		return 'null';
	}

	/**
	 * Returns the plentymarkets payment status matching the given payment response status.
	 *
	 * @param string $status
	 * @param bool $isRefund
	 * @return int
	 */
	public function mapTransactionState(string $status, $isRefund = false)
	{
		switch ($status)
		{
			case '0':
				return Payment::STATUS_AWAITING_APPROVAL;
			case '2':
				if ($isRefund)
				{
					return Payment::STATUS_REFUNDED;
				}
				return Payment::STATUS_APPROVED;
			case '-2':
				return Payment::STATUS_REFUSED;
		}

		return Payment::STATUS_AWAITING_APPROVAL;
	}

	/**
	 * Returns a PaymentProperty with the given params
	 *
	 * @param int $typeId
	 * @param string $value
	 * @return PaymentProperty
	 */
	private function getPaymentProperty($typeId, $value)
	{
		$paymentProperty = pluginApp(PaymentProperty::class);

		$paymentProperty->typeId = $typeId;
		$paymentProperty->value = (string) $value;

		return $paymentProperty;
	}


	/**
	 * Assign the payment to an order in plentymarkets.
	 *
	 * @param Payment $payment
	 * @param int $orderId
	 */
	public function assignPlentyPaymentToPlentyOrder(Payment $payment, int $orderId)
	{
		$orderRepo = pluginApp(OrderRepositoryContract::class);
		$authHelper = pluginApp(AuthHelper::class);

		$order = $authHelper->processUnguarded(
						function () use ($orderRepo, $orderId) {
							return $orderRepo->findOrderById($orderId);
						}
		);

		if (!is_null($order) && $order instanceof Order)
		{
			$this->paymentOrderRelationRepository->createOrderRelation($payment, $order);
		}
	}

	/**
	 * get payment booking text (use for show payment detail information).
	 *
	 * @param array $paymentStatus
	 * @param bool $isCredentialValid
	 * @return string
	 */
	public function getPaymentBookingText($paymentStatus, $isCredentialValid)
	{
		$paymentBookingText = [];
		$countryRepository = pluginApp(CountryRepositoryContract::class);

		if (isset($paymentStatus['transaction_id']))
		{
			$paymentBookingText[] = "Transaction ID : " . (string) $paymentStatus['transaction_id'];
		}
		if (isset($paymentStatus['payment_type']))
		{
			if ($paymentStatus['payment_type'] == 'NGP')
			{
				$paymentStatus['payment_type'] = 'OBT';
			}
			$paymentMethod = $this->getPaymentMethodByPaymentKey('PMPAY_' . $paymentStatus['payment_type']);
			if (isset($paymentMethod))
			{
				$paymentBookingText[] = "Used payment method : " . $paymentMethod->name;
			}
		}
		if (isset($paymentStatus['status']))
		{
			$paymentBookingText[] = "Payment status : " .
				$this->getPaymentStatus((string)$paymentStatus['status'], $isCredentialValid);
		}
		if (isset($paymentStatus['IP_country']))
		{
			$ipCountry = $countryRepository->getCountryByIso($paymentStatus['IP_country'], 'isoCode2');
			$paymentBookingText[] = "Order originated from : " . $ipCountry->name;
		}
		if (isset($paymentStatus['payment_instrument_country']))
		{
			$paymentInstrumentCountry = $countryRepository->getCountryByIso(
							$paymentStatus['payment_instrument_country'],
							'isoCode3'
			);
			$paymentBookingText[] = "Country (of the card-issuer) : " . $paymentInstrumentCountry->name;
		}
		if (isset($paymentStatus['pay_from_email']))
		{
			$paymentBookingText[] = "PmPay account email : " . $paymentStatus['pay_from_email'];
		}
		if (!empty($paymentBookingText))
		{
			return implode("\n", $paymentBookingText);
		}

		return '';
	}

}

?>