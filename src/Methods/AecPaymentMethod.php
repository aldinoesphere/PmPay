<?php

namespace PmPay\Methods;

use Plenty\Plugin\Log\Loggable;

/**
* Class AecPaymentMethod
* @package PmPay\Methods
*/
class AecPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	/**
	 * @var name
	 */
	protected $name = 'Easy Credit';

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'aec.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'easy-credit';
}
