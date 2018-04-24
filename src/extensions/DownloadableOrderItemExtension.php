<?php

class DownloadableOrderItemExtension extends DataExtension
{

    public function getDownloadLink()
    {
        $order = Order::get()->byID($this->owner->ParentID);
        $match = $this->owner->match();

        if ($match && method_exists($match, "getDownloadLink")) {
            return $match->getDownloadLink().
                '?o='.$order->ID.
                '&k='.$order->AccessKey;
        }

        return false;
    }
}
