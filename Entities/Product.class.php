<?php

class Product {

    // can create more fields for the image if needed, but since only the URL and title is needed here
    public $imageUrl;
    public $title;


    function getImageUrl() : string {
        return $this->imageUrl;
    }


    function getTitle() : string {
        return $this->title;
    }

    function setImageUrl(string $url) {
        $this->imageUrl = $url;
    }

    function setTitle(string $t) {
        $this->title = $t;
    }

    function jsonSerialize() {
        $obj = new StdClass;
        $obj->imageUrl = $this->getImageUrl();
        $obj->title = $this->getTitle();

        return $obj;
    }
}