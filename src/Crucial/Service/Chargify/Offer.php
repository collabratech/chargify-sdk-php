<?php

namespace Crucial\Service\Chargify;

class Offer extends AbstractEntity
{
    /**
     * List all offers for your site
     *
     * @return Offers
     */
    public function listOffers()
    {
        $service = $this->getService();

        $response      = $service->request('offers', 'GET');
        $responseArray = $this->getResponseArray($response);

        if (!$this->isError()) {
            $this->_data = $this->_normalizeResponseArray($responseArray);
        } else {
            $this->_data = array();
        }
        return $this;
    }

    /**
     * This normalizes the array for us so we can rely on a consistent structure.
     *
     * @param array $responseArray
     *
     * @return array
     */
    protected function _normalizeResponseArray($responseArray)
    {
        $return = array();
        foreach ($responseArray['offers'] as $offer) {
            $return[] = $offer; 
        }
        return $return;
    }
}