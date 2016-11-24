<?php

class DownloadableCheckoutExtension extends Extension {

    /**
     * Check to see if the shopping cart only contains downloadable
     * products.
     *
     * @return Boolean
     */
    public function onlyDownloadable() {
        $cart = ShoppingCart::get();

        foreach($cart->getItems() as $item) {
            if(!$item->FindStockItem() InstanceOf DownloadableProduct) {
                return false;
            }
        }

        return true;
    }

    /**
     * If the shopping cart only contains downloadable products, we
     * don't need to set delivery details, so we will copy the billing
     * details automatically
     *
     */
    public function onBeforeDelivery() {
        if($this->owner->onlyDownloadable()) {
            return $this
                ->owner
                ->redirect($this->owner->Link('finish'));
        }
    }

    /**
     * If we use a
     *
     */
    public function onBeforeUseMemberAddress() {
        if($this->owner->onlyDownloadable()) {
            $id = $this->owner->request->param("ID");
            $otherid = $this->owner->request->param("OtherID");
            $data = array();
            $address = MemberAddress::get()->byID($id);

            if($address) {
                $data = array();

                $data['DeliveryFirstnames']  = $address->FirstName;
                $data['DeliverySurname']    = $address->Surname;
                $data['DeliveryAddress1']   = $address->Address1;
                $data['DeliveryAddress2']   = $address->Address2;
                $data['DeliveryCity']       = $address->City;
                $data['DeliveryPostCode']   = $address->PostCode;
                $data['DeliveryCountry']    = $address->Country;

                Session::set("Checkout.DeliveryDetailsForm.data", $data);
            }
        }
    }

    /**
     * If the shopping cart only contains downloadable products, we
     * don't need to set delivery details, so we will copy the billing
     * details automatically
     *
     */
    public function updateBillingForm($form) {
        // Change the form buttons
        if($this->owner->onlyDownloadable()) {
            $form->Actions()->removeByName("action_doSetDelivery");

            // Rename set delivery
            $set_delivery = $form
                ->Actions()
                ->dataFieldByName("action_doContinue");

            if($set_delivery)
                $set_delivery->setTitle(_t("DownloadableProduct.Continue", "Continue"));
        }
    }
}
