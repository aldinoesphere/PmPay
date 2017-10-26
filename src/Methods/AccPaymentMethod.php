<?php

namespace PmPay\Methods;

use Plenty\Plugin\Log\Loggable;

/**
* Class AccPaymentMethod
* @package PmPay\Methods
*/
class AccPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	/**
	 * @var name
	 */
	protected $name = 'Pm Pay Credit Cards';

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'acc.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'pmpay_acc';
}
