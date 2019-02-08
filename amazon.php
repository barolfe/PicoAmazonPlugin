<?php

include('amazon-config.php');

/**
 * Pico dummy plugin - a template for plugins
 *
 * You're a plugin developer? This template may be helpful :-)
 * Simply remove the events you don't need and add your own logic.
 *
 * @author  Bryan Rolfe
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 */
final class Amazon extends AbstractPicoPlugin
{
    /**
     * This plugin is enabled by default?
     *
     * @see AbstractPicoPlugin::$enabled
     * @var boolean
     */
    protected $enabled = true;
    protected $conn = false; // Mysqli object later

    /**
     * This plugin depends on ...
     *
     * @see AbstractPicoPlugin::$dependsOn
     * @var string[]
     */
    protected $dependsOn = array();

    /**
     * Triggered after Pico has loaded all available plugins
     *
     * This event is triggered nevertheless the plugin is enabled or not.
     * It is NOT guaranteed that plugin dependencies are fulfilled!
     *
     * @see    Pico::getPlugin()
     * @see    Pico::getPlugins()
     * @param  object[] &$plugins loaded plugin instances
     * @return void
     */


    // Usage:
    // $response = getAmazonPrice("com", "B013U0F6EQ");
    
    private function getAmazonPrice($region, $asin) {

        $xml = $this->aws_signed_request($region, array(
            "Operation" => "ItemLookup",
            "ItemId" => $asin,
            "IncludeReviewsSummary" => False,
            "ResponseGroup" => "Medium,OfferSummary",
        ));

        if (isset($xml->Items->Request->Errors)) {
            //echo "Error is message request";
            return False;
        }

        $item = $xml->Items->Item;
        $title = htmlentities((string) $item->ItemAttributes->Title);
        $url = htmlentities((string) $item->DetailPageURL);
        $image = htmlentities((string) $item->LargeImage->URL);

        if (count($item->OfferSummary) !== 0) {
            $price = htmlentities((string) $item->OfferSummary->LowestNewPrice->Amount);
            $code = htmlentities((string) $item->OfferSummary->LowestNewPrice->CurrencyCode);
            $qty = htmlentities((string) $item->OfferSummary->TotalNew);
        } else {
            $price = 0;
            $code = 0;
            $qty = 1;
        }

        if ($qty !== "0") {
            $response = array(
                "code" => $code,
                "price" => number_format((float) floor($price / 100), 0, '.', ''),
                "image" => $image,
                "url" => $url,
                "title" => $title
            );
        }

        return $response;
    }

    private function getPage($url) {

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $html = curl_exec($curl);
        curl_close($curl);
        return $html;
    }

    private function aws_signed_request($region, $params) {

        $public_key = AZPUBLICKEY;
        $private_key = AZPRIVATEKEY;

        $method = "GET";
        $host = "ecs.amazonaws." . $region;
        $host = "webservices.amazon." . $region;
        $uri = "/onca/xml";

        $params["Service"] = "AWSECommerceService";
        $params["AssociateTag"] = "rolfev-20"; // Put your Affiliate Code here
        $params["AWSAccessKeyId"] = $public_key;
        $params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
        $params["Version"] = "2011-08-01";

        ksort($params);

        $canonicalized_query = array();
        foreach ($params as $param => $value) {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));
            $canonicalized_query[] = $param . "=" . $value;
        }

        $canonicalized_query = implode("&", $canonicalized_query);

        $string_to_sign = $method . "\n" . $host . "\n" . $uri . "\n" . $canonicalized_query;
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
        $signature = str_replace("%7E", "~", rawurlencode($signature));

        $request = "http://" . $host . $uri . "?" . $canonicalized_query . "&Signature=" . $signature;
        $response = $this->getPage($request);

        //var_dump($response);

        $pxml = @simplexml_load_string($response);
        if ($pxml === False) {
            return False;// no xml
        } else {
            return $pxml;
        }
    }
    

    public function onPluginsLoaded(array &$plugins)
    {
        // your code
    }

    /**
     * Triggered after Pico has read its configuration
     *
     * @see    Pico::getConfig()
     * @param  array &$config array of config variables
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
        // your code
    }

    /**
     * Triggered after Pico has evaluated the request URL
     *
     * @see    Pico::getRequestUrl()
     * @param  string &$url part of the URL describing the requested contents
     * @return void
     */
    public function onRequestUrl(&$url)
    {
        // your code
    }

    /**
     * Triggered after Pico has discovered the content file to serve
     *
     * @see    Pico::getBaseUrl()
     * @see    Pico::getRequestFile()
     * @param  string &$file absolute path to the content file to serve
     * @return void
     */
    public function onRequestFile(&$file)
    {
        // your code
    }

    /**
     * Triggered before Pico reads the contents of the file to serve
     *
     * @see    Pico::loadFileContent()
     * @see    DummyPlugin::onContentLoaded()
     * @param  string &$file path to the file which contents will be read
     * @return void
     */
    public function onContentLoading(&$file)
    {
        // your code
    }

    /**
     * Triggered after Pico has read the contents of the file to serve
     *
     * @see    Pico::getRawContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function onContentLoaded(&$rawContent)
    {
        
    }

    /**
     * Triggered before Pico reads the contents of a 404 file
     *
     * @see    Pico::load404Content()
     * @see    DummyPlugin::on404ContentLoaded()
     * @param  string &$file path to the file which contents were requested
     * @return void
     */
    public function on404ContentLoading(&$file)
    {
        // your code
    }

    /**
     * Triggered after Pico has read the contents of the 404 file
     *
     * @see    Pico::getRawContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function on404ContentLoaded(&$rawContent)
    {
        // your code
    }

    /**
     * Triggered when Pico reads its known meta header fields
     *
     * @see    Pico::getMetaHeaders()
     * @param  string[] &$headers list of known meta header
     *     fields; the array value specifies the YAML key to search for, the
     *     array key is later used to access the found value
     * @return void
     */
    public function onMetaHeaders(array &$headers)
    {
        // your code
    }

    /**
     * Triggered before Pico parses the meta header
     *
     * @see    Pico::parseFileMeta()
     * @see    DummyPlugin::onMetaParsed()
     * @param  string   &$rawContent raw file contents
     * @param  string[] &$headers    known meta header fields
     * @return void
     */
    public function onMetaParsing(&$rawContent, array &$headers)
    {
        // your code
    }

    /**
     * Triggered after Pico has parsed the meta header
     *
     * @see    Pico::getFileMeta()
     * @param  string[] &$meta parsed meta data
     * @return void
     */
    public function onMetaParsed(array &$meta)
    {
        // your code
    }

    /**
     * Triggered before Pico parses the pages content
     *
     * @see    Pico::prepareFileContent()
     * @see    DummyPlugin::prepareFileContent()
     * @see    DummyPlugin::onContentParsed()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function onContentParsing(&$rawContent)
    {
        // your code
    }

    /**
     * Triggered after Pico has prepared the raw file contents for parsing
     *
     * @see    Pico::parseFileContent()
     * @see    DummyPlugin::onContentParsed()
     * @param  string &$content prepared file contents for parsing
     * @return void
     */
    public function onContentPrepared(&$content)
    {

    }

    /**
     * Triggered after Pico has parsed the contents of the file to serve
     *
     * @see    Pico::getFileContent()
     * @param  string &$content parsed contents
     * @return void
     */
    public function onContentParsed(&$content)
    {
        // search through the content, find instances of of [[ AZ ASIN ]]
        // where ASIN is the product ID number, and replace these with price

        // debug: just replace with dummy value for now
        $amazonTags = $this->getAmazonTags($content);
        //$amazonInfo = $this->getPricesFromTags($amazonTags);
        $amazonInfo = $this->getPricesFromDB($amazonTags);

        foreach ($amazonTags as $tag) {

            $info = $amazonInfo[$tag];

            if (($info['price'] == '0') | ($info['price'] == '0.0')) {
                $content = preg_replace('/\[\[ AZ ' . $tag . ' \]\]/', 'See', $content);
            } else {
                $content = preg_replace('/\[\[ AZ ' . $tag . ' \]\]/', '\$' . $info['price'], $content);
            }

            if ($info['image'] !== '') {
                //echo $info['image'];
                $content = preg_replace('/\%5B\%5B\%20AZIMG\%20' . $tag . '\%20\%5D\%5D/', $info['image'], $content);
                //$content = preg_replace('/\[\[ AZIMG ' . $tag . ' \]\]/', $info['image'], $content);
            }
            if ($info['title'] !== '') {
                //echo $info['image'];
                if (strlen($info['title']) >= 128) {
                    $info['title'] = $info['title'] . '...';
                }
                $content = preg_replace('/\[\[ AZTITLE ' . $tag . ' \]\]/', $info['title'], $content);
                //$content = preg_replace('/\[\[ AZIMG ' . $tag . ' \]\]/', $info['image'], $content);
            }

            if ($info['link'] !== '') {
                $content = preg_replace('/\%5B\%5B\%20AZLINK\%20' . $tag . '\%20\%5D\%5D/', $info['link'], $content);
                $content = preg_replace('/\[\[ AZLINK ' . $tag . ' \]\]/', $info['link'], $content);
            }
        }

    }

    private function getAmazonTags($content) {

        $result = preg_match_all("/(?:\[\[ AZ )(\w*)(?: \]\])/", $content, $matches);
        $tags = $matches[1];

        return $tags;
    }

    // DEPRICATED ?
    private function getPricesFromTags($tags) {

        foreach ($tags as $tag) {
            $tagInfo = $this->getAmazonPrice("com", $tag);
            $info[$tag] = $tagInfo;
        }

        return $info;

    }

    private function connectToDB() {
        $servername = AZDBHOST;
        $username = AZDBUSER;
        //$username = "bryanrol_pfv";
        $password = AZDBPWD;
        $dbname   = AZDBNAME;

        // Create connection
        if (!$this->conn) { 
            $this->conn = new mysqli($servername, $username, $password);
            // Check connection
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            } 
            $this->conn->select_db($dbname);
            //$this->conn->select_db('bryanrol_prepforvoyage');
        }
        
    }

    // Look-up amazon prices from DB and update DB if needed 
    private function getPricesFromDB($tags) {
        if ($this->conn) {
            if (!$this->conn->ping()) {
                $this->connectToDB();
            }
        } else {
            $this->connectToDB();
        }
        // Fetch $tags from database, if entries are present and up-to-date, do nothing
        // If not present, look up the prices, and add them
        $productInfo = [];
        foreach($tags as $tag) {
            $query = "SELECT * FROM AmazonProducts WHERE prod_id='$tag'";
            $result = mysqli_query($this->conn, $query);
            if ($result) {
                if (mysqli_num_rows($result) > 0) {
                    $product = mysqli_fetch_assoc($result);
                    $productInfo[$tag] = $product;
                    // Check if product prices was last updated more than 24 hours ago
                    if ((time() - strtotime($product['date_updated'])) > 60 * 60 * 24) {
                       // echo time() - strtotime($product['date_updated']);
                       // echo "Updating Product";
                        $this->updateProductPriceInDB($tag, $productInfo); 
                    }

                } else {
                    // update product price, add it to DB, and modify the productInfo array
                    $this->updateProductPriceInDB($tag, $productInfo); 

                }
            } else {
                $productInfo[$tag] = array('prod_id'=>$tag,title=>'','price'=>0,'link'=>'','image'=>'default.jpg','date_updated'=>0);
            }
        }

        return $productInfo;

        // SQL to make AmazonProducts table:
       // $sql = "TABLE `PreppingForVoyage`.`AmazonProducts` ( `prod_id` VARCHAR(12) NOT NULL , `price` FLOAT NOT NULL , `date_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() , PRIMARY KEY (`prod_id`)) ENGINE = InnoDB";

    }

    private function updateProductPriceInDB($tag, &$productInfo) {
        // Look-up price with an AmazonAPI request
        $prodInfo = $this->getAmazonPrice("com", $tag);
        
        if ($prodInfo == False) {
            error_log("Product request failed for ASIN: " . $tag);
            return False;
        }

        $timeNow =  date("Y-m-d H:i:s");
        $productInfo[$tag] = array('prod_id'=>$tag, 'title'=>$prodInfo['title'],'price'=>$prodInfo['price'], 'link'=>$prodInfo['url'], 'image'=> $prodInfo['image'],'date_updated'=>$timeNow);

        $query = "INSERT INTO AmazonProducts (prod_id,title,price,link,image,date_updated)
                  VALUES ('$tag','{$prodInfo['title']}','{$prodInfo['price']}','{$prodInfo['url']}','{$prodInfo['image']}','$timeNow')
                  ON DUPLICATE KEY UPDATE price=VALUES(price),title=VALUES(title),link=VALUES(link),image=VALUES(image),date_updated=VALUES(date_updated)";

        $result = mysqli_query($this->conn, $query);


        return $result;
    }
    /**
     * Triggered before Pico reads all known pages
     *
     * @see    Pico::readPages()
     * @see    DummyPlugin::onSinglePageLoaded()
     * @see    DummyPlugin::onPagesLoaded()
     * @return void
     */
    public function onPagesLoading()
    {
        // your code
    }

    /**
     * Triggered when Pico reads a single page from the list of all known pages
     *
     * The `$pageData` parameter consists of the following values:
     *
     * | Array key      | Type   | Description                              |
     * | -------------- | ------ | ---------------------------------------- |
     * | id             | string | relative path to the content file        |
     * | url            | string | URL to the page                          |
     * | title          | string | title of the page (YAML header)          |
     * | description    | string | description of the page (YAML header)    |
     * | author         | string | author of the page (YAML header)         |
     * | time           | string | timestamp derived from the Date header   |
     * | date           | string | date of the page (YAML header)           |
     * | date_formatted | string | formatted date of the page               |
     * | raw_content    | string | raw, not yet parsed contents of the page |
     * | meta           | string | parsed meta data of the page             |
     *
     * @see    DummyPlugin::onPagesLoaded()
     * @param  array &$pageData data of the loaded page
     * @return void
     */
    public function onSinglePageLoaded(array &$pageData)
    {
        // your code
    }

    /**
     * Triggered after Pico has read all known pages
     *
     * See {@link DummyPlugin::onSinglePageLoaded()} for details about the
     * structure of the page data.
     *
     * @see    Pico::getPages()
     * @see    Pico::getCurrentPage()
     * @see    Pico::getPreviousPage()
     * @see    Pico::getNextPage()
     * @param  array[]    &$pages        data of all known pages
     * @param  array|null &$currentPage  data of the page being served
     * @param  array|null &$previousPage data of the previous page
     * @param  array|null &$nextPage     data of the next page
     * @return void
     */
    public function onPagesLoaded(
        array &$pages,
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        // your code
    }

    /**
     * Triggered before Pico registers the twig template engine
     *
     * @return void
     */
    public function onTwigRegistration()
    {
        // your code
    }

    /**
     * Triggered before Pico renders the page
     *
     * @see    Pico::getTwig()
     * @see    DummyPlugin::onPageRendered()
     * @param  Twig_Environment &$twig          twig template engine
     * @param  array            &$twigVariables template variables
     * @param  string           &$templateName  file name of the template
     * @return void
     */
    public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
    {
        // your code
    }

    /**
     * Triggered after Pico has rendered the page
     *
     * @param  string &$output contents which will be sent to the user
     * @return void
     */
    public function onPageRendered(&$output)
    {
        // your code
    }
}
