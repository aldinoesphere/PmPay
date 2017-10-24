<?php
 
namespace PmPay\Providers;
 
use Plenty\Plugin\ServiceProvider;
 
class PmPayServiceProvider extends ServiceProvider
{
    public function register()
    {
 		$this->getApplication()->register(PmPayRouteServiceProvider::class);
    }
}