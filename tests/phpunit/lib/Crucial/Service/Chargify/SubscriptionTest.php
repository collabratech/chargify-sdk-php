<?php
use GuzzleHttp\Handler\MockHandler;


/**
 * Class Crucial_Service_Chargify_SubscriptionTest
 *
 */
class Crucial_Service_Chargify_SubscriptionTest extends PHPUnit_Framework_TestCase
{
    public function testCreateSuccess()
    {
        $chargify     = ClientHelper::getInstance('subscription.success');
        $subscription = $chargify->subscription()
            ->setProductId(123)
            ->setCustomerAttributes(array(
                'first_name'   => 'Darryl',
                'last_name'    => 'Strawberry',
                // don't change this email. we are making an assertion on its value below
                'email'        => 'darryl@mailinator.com',
                'organization' => 'Mets',

                // shipping address fields
                'phone'        => '555-555-1234',
                'address'      => '123 Main St',
                'address_2'    => 'Apt 123',
                'city'         => 'New York',
                'state'        => 'NY',
                'zip'          => '48433',
                'country'      => 'US',
            ))
            ->setPaymentProfileAttributes(array(
                'first_name'       => 'Darryl2',
                'last_name'        => 'Strawberry2',
                'full_number'      => '1',
                'expiration_month' => '03',
                'expiration_year'  => '16',
                'cvv'              => '123',
                'billing_address'  => '600 N',
                'billing_city'     => 'Chicago',
                'billing_state'    => 'IL',
                'billing_zip'      => '60610',
                'billing_country'  => 'US'
            ))
            ->create();

        $response = $subscription->getService()->getLastResponse();

        // check there wasn't an error
        $this->assertFalse($subscription->isError(), '$subscription has an error');
        $this->assertEquals(201, $response->getStatusCode(), 'Expected status code 201');

        // check for a couple of attributes on the $subscription object
        $this->assertNotEmpty($subscription['id'], '$subscription["id"] was empty');
        $this->assertEquals('darryl@mailinator.com', $subscription['customer']['email'], '$subscription["customer"]["email"] did not match what was given in request');
    }

    public function testNoShippingCreatesError()
    {
        $chargify     = ClientHelper::getInstance('subscription.error.no_shipping');
        $subscription = $chargify->subscription()
            ->setProductId(123)
            ->setCustomerAttributes(array(
                'first_name'   => 'Darryl',
                'last_name'    => 'Strawberry',
                'email'        => 'darryl@mailinator.com',
                'organization' => 'Mets'
                /**
                 * Note the omission of shipping fields here. They are required for this product so we should get an
                 * error from the API.
                 */
            ))
            ->setPaymentProfileAttributes(array(
                'first_name'       => 'Darryl2',
                'last_name'        => 'Strawberry2',
                'full_number'      => '1',
                'expiration_month' => '03',
                'expiration_year'  => '16',
                'cvv'              => '123',
                'billing_address'  => '600 N',
                'billing_city'     => 'Chicago',
                'billing_state'    => 'IL',
                'billing_zip'      => '60610',
                'billing_country'  => 'US'
            ))
            ->create();

        $response = $subscription->getService()->getLastResponse();

        // $subscription object should be in an error state
        $this->assertTrue($subscription->isError());
        $this->assertEquals(422, $response->getStatusCode(), 'Expected status code 422');

        // get errors from $subscription
        $errors = $subscription->getErrors();

        // check for error messages
        $this->assertContains('Shipping Address: cannot be blank.', $errors);
    }

    public function testPreviewSuccess()
    {
        $chargify = ClientHelper::getInstance('subscription.preview.success');
        $subscription = $chargify->subscription()
            ->setProductHandle( 'test-product' )
            ->setParam('credit_card_attributes', array(
                'address'   => '505 W Riverside Ave',
                'address_2' => null,
                'city'      => 'Spokane',
                'state'     => 'WA',
                'zip'       => '99201',
                'country'   => 'US'
            ))
            ->preview();

        $response = $subscription->getService()->getLastResponse();

        // check there wasn't an error
        $this->assertFalse($subscription->isError(), '$subscription has an error');
        $this->assertEquals(200, $response->getStatusCode(), 'Expected status code 200');

        // check for a couple of attributes on the $subscription object
        $this->assertNotEmpty($subscription->offsetGet('current_billing_manifest'), '$subscription["current_billing_manifest"] preview was empty');
        $this->assertCount(3, $subscription->offsetGet('current_billing_manifest')['line_items'], '$subscription["current_billing_manifest"]["line_items"] did not match what was given in request');

        $taxations = $subscription->offsetGet('current_billing_manifest')['line_items'][0]['taxations'];
        $this->assertNotEmpty($taxations, '$taxations from subscription preview was empty');
        $this->assertEquals( 'WA Tax (8.9%)', $taxations[0]['tax_name'], 'Tax name did not match what was given in request');
        $this->assertEquals( 89, $taxations[0]['tax_amount_in_cents'], 'Tax amount did not match what was given in request');
    }
}