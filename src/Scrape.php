<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

require 'vendor/autoload.php';

class Scrape
{
	public const WEBSITE_PAGES = 3;
	
    private array $products = [];
	
    public function run(): void
    {
        //Iterate through all the website's pages
    	for($page = 1; $page <= Scrape :: WEBSITE_PAGES; $page++)
    	{
    		$url = 'https://www.magpiehq.com/developer-challenge/smartphones/?page='. $page;
    		
		    $document = ScrapeHelper::fetchDocument($url);
		    
		    //Split the products into individual nodes based on a common filter
		    $document = $document -> filter("div.bg-white.p-4.rounded-md");
	           
		    //Iterate through the nodes
		    foreach($document as $domElement)
		    {
			    $html = $domElement -> ownerDocument -> saveHTML($domElement);
			    $node = new Crawler($html, $url);
			    
			    //Get the colour array for the current node
			    $nodeColours = $node -> filter('div.px-2 > span') -> extract(['data-colour']);
			    
			    //Get the filtered content from the current node
			    $nodeContent = Product::extractContent($node);
			    
			    //Iterate through the colour array, update colour to be a separate product
			    foreach($nodeColours as $colour)
			    {
					    $nodeContent['colour'] = $colour;
					    
					    //Before adding the node's content to the products array,verify if it is a duplicate
					    if(!$this -> checkDuplicate($nodeContent))
					    {
						    $this -> products[] = $nodeContent;
						    
					    }
			    }
		    }
	}
	    
	    //Put all the elements from products array into the json file
	    file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    private function checkDuplicate(array $content)
    {
    	//Check the current content against all the element that are, so far, in the products array
    	foreach($this -> products as $product)
    	{
    		if(($content['title'] == $product['title']) and ($content['capacityMB'] == $product['capacityMB']) and ($content['colour'] == $product['colour']))
    		{
    			return true;
    			
    		}		
    	}
    	
    	return false;
    }
}

$scrape = new Scrape();
$scrape->run();
