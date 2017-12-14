<?php

class DownloadableOrderItemExtension extends DataExtension
{

    public function getDownloadLink()
    {
        $order = Order::get()->byID($this->owner->ParentID);

        if ($match = $this->owner->match()) {
            return $match->getDownloadLink().
                '?o='.$order->ID.
                '&k='.$order->AccessKey;
        }

        return false;
    }
}