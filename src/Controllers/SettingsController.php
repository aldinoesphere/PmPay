<?php  
namespace PmPay\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Application;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Frontend\Services\SystemService;

/**
* @package PmPay\Controllers
*/
class SettingsController extends Controller
{

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

	/**
	 *
	 * @var systemService
	 */
	private $systemService;

	public function __construct(
		Request $request,
		Response $response,
		SystemService $systemService
	) {
		$this->request = $request;
		$this->response = $response;
		$this->systemService = $systemService;
	}


	/**
	 * Display PmPay backend configuration
	 *
	 * @param Twig $twig
	 * @param string $settingType
	 * @return void
	 */
	public function loadConfiguration(Twig $twig, $settingType)
	{
		$plentyId = $this->systemService->getPlentyId();

		return $twig->render(
						'PmPay::Configuration.Settings',
						array(
							'status' => $this->request->get('status'),
							'locale' => substr($_COOKIE['plentymarkets_lang_'], 0, 2),
							'plentyId' => $plentyId,
							'settingType' => $settingType
						)
		);
	}

	public function saveConfiguration() {

	}
}

?>