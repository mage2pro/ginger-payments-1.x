<?php

class Ginger_Ginger_Model_Observer
{
    public function convertPayment($observer)
    {
        $orderPayment = $observer->getEvent()->getOrderPayment();
        $quotePayment = $observer->getEvent()->getQuotePayment();

        $orderPayment->setGingerOrderId($quotePayment->getGingerOrderId());
        $orderPayment->setGingerBanktransferReference($quotePayment->getGingerBanktransferReference());
        $orderPayment->setGingerIdealIssuerId($quotePayment->getGingerIdealIssuerId());

        return $this;
    }
}
