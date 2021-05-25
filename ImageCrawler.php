<?php

// allows other php to be used from the image crawler php page
require_once('simplehtmldom_2_0-RC2/simple_html_dom.php');
require_once('Entities\Product.class.php');
require_once('quickstart.php');

// url to image crawl from
$url = "https://www.ubereats.com/ca/vancouver/food-delivery/hon-sushi/XAAB10yNTL6wz9qbi2gXfA";

// google drive location, change this if you would want to upload to a different location
$upload = '1qXZy_ss3f7z5Uf7N6WaBJCmnSKWn_C1U';

// using simple_html_dom.php to parse the webpage and returns elements that can be searched 
// through html elements
$html = file_get_html($url);

// creates products from json data retrieved from Simple HTML DOM parser
$products = get_products($html);

// runs the function to create image files and upload to google drive
uploadImages($products, $upload);

function get_products($html) {
    // returns the json data from the uber eats page
    $ret = $html->find('script[type=application/json]')[0]->innertext;

    // cleaning the json data to get rid of all utf-8 code and 
    // others that might create problems with json_decode
    $ret = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
    }, $ret);
    $ret = rawurldecode($ret);

    // returns an php array that can be accessible
    $arr = json_decode($ret,true);

    // brute forced the data from uber eats where the product information is nested under a few arrays
    $data = $arr["stores"]["5c0001d7-4c8d-4cbe-b0cf-da9b8b68177c"]["data"]["sectionEntitiesMap"]["e8db9ac7-3349-4e00-915c-b7d048eb5080"];
    
    // looping through the product list from the json data and creating a product with the Product class
    foreach($data as $key => $value) {
        $np = new Product();
        $np->setImageUrl($value["imageUrl"]);
        $np->setTitle($value["title"]);
        $products[] = $np;
    }

    // returning all the products that were found
    return $products;
}

function uploadImages($products, $upload) {

    // getting google client so that we can access and upload files to drive
    $client = getClient();
    $service = new Google_Service_Drive($client);

    // looping through the products found from the webpage
    foreach($products as $product) {

        // checking to see if the product is a value product
        // anything without a proper URL or title will be omitted
        if (!empty($product->imageUrl) && (!empty($product->title))) {
            
            $url = $product->imageUrl;
            // spliting the image so that we can find out the extension of the image
            $split_image = pathinfo($product->imageUrl);

            // saving the filenameas the title name
            $filename = $product->title;

            // making sure the file is saved under the correct extension
            $complete_save_loc = $filename.".".$split_image['extension'];

            //creating a new image file to upload to google drive
            $file = new Google_Service_Drive_DriveFile($client);

            // setting the name of the image file
            $file->setName($complete_save_loc);

            // setting the extention type on google drive
            $file->setMimeType('image/'.$split_image['extension']);

            // setting the folder of where the image is saved to
            $file->setParents(array($upload));

            // creating the file and saving it to google drive
            $createdFile = $service->files->create($file, array('data' => file_get_contents($url), 'mimeType' => 'image/'.$split_image['extension'], 'uploadType' => 'media',));
        }
    }
}

// to test json decode error
// switch (json_last_error()) {
    //     case JSON_ERROR_NONE:
    //         echo ' - No errors';
    //         break;
    //     case JSON_ERROR_DEPTH:
    //         echo ' - Maximum stack depth exceeded';
    //         break;
    //     case JSON_ERROR_STATE_MISMATCH:
    //         echo ' - Underflow or the modes mismatch';
    //         break;
    //     case JSON_ERROR_CTRL_CHAR:
    //         echo ' - Unexpected control character found';
    //         break;
    //     case JSON_ERROR_SYNTAX:
    //         echo ' - Syntax error, malformed JSON';
    //         break;
    //     case JSON_ERROR_UTF8:
    //         echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
    //         break;
    //     default:
    //         echo ' - Unknown error';
    //         break;
    // }
?>