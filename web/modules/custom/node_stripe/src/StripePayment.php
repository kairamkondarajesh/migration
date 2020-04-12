<?php

namespace Drupal\node_stripe;

use Exception;
use Stripe\Stripe;
use Stripe\Charge;
use Drupal\Core\Logger\LoggerChannelFactory;

class StripePayment
{
    
    /**
     * @var Psr\Log\LoggerInterface;
     */
    protected $loggerChannel;

    public function __construct(LoggerChannelFactory $logger)
    {
        $this->setApiKey('sk_test_wErEd9NiAIqV9X1hJxmU7a5P00FF54WyY4');
        $this->loggerChannel = $logger->get('node_stripe');
    }

    protected function setApiKey($key)
    {
        Stripe::setApiKey($key);
    }

    public function processPayment($amount, $currency, $source, $description, $metadata)
    {
        try {
            $charge = Charge::create(
                [
                'amount' => $amount,
                'currency' => $currency,
                'source' => $source,
                "description" => $description,
                "metadata" => $metadata
                ]
            );
            return $charge->jsonSerialize(); 
        } catch (Exception $e) {
            $this->loggerChannel->error("Stripe payment gateway eror with message {$e->getMessage()}");
            return false;
        }
        
    }
}