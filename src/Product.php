<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;
use \Datetime;

class Product
{


    public static function extractContent(Crawler $node)
    {
        $content = array();
        
        //Get the title of the product
        $title = $node -> filter('h3 > span');

        //Get the capacity of the product
        $capacityGB = $title -> eq(1) -> text();
        $content['title'] = ($title -> eq(0) -> text()) . ' ' . $capacityGB;

        //Get the price of the product
        $price = $node -> filter('div.my-8.block.text-center.text-lg') -> text();
        $price = str_replace('£', '', $price);
        $priceInt = (float)$price;
        $content['price'] = $priceInt;

        $imageUrl = $node -> filter('.my-8.mx-auto') -> image() -> getUri();
        $content['imageUrl'] = $imageUrl;
        
        //Convert the capacity from GB into MB
        $capacityMB = (int)$capacityGB;
        $capacityMB = $capacityMB * 1000;

        $content['capacityMB'] = $capacityMB;

        //Set 'default' as value for the colour so it can be changed in the Scrape class (for loop)
        $content['colour'] = 'default';
        
        //Get the text of the availability text of the product 
        $available = $node -> filter('div.my-4.text-sm.block.text-center');
        $availabilityText = $available -> eq(0) -> text();
        $availabilityText = str_replace('Availability: ', '', $availabilityText);
        $content['availabilityText'] = $availabilityText;
        
        //Check if the product is available or not
        $isAvailable;
        
        if($availabilityText == 'Out of Stock')
        {
                $isAvailable = false;
                
        }
        else
        {
                $isAvailable = true;
                
        }
        
        $content['isAvailable'] = $isAvailable;
     
        //If the there is any shipping text, process it
        if(count($available) == 2)
        {
            //Get the shipping text of the product
            $shippingText = $available -> eq(1) -> text();
            $content['shippingText'] = $shippingText;
            
            //Format the date inside the shipping text
            $shippingDate = Product :: extractDate($shippingText);
            
            //If there is a data in the shipping text, add ot to the content array
            if($shippingDate !== null)
            {
                $content['shippingDate'] = $shippingDate; 
            
            }     
        }
        
        return $content;
        
    }
    
    private function extractDate(string $shippingText)
    {
              
              //A predefined array for mapping month values
              $monthMapping = ["Jan" => "01", "Feb" => "02", "Mar" => "03", "Apr" => "04", "May" => "05", "Jun" => "06",
                               "Jul" => "07", "Aug" => "08", "Sep" => "09", "Oct" => "10", "Nov" => "11", "Dec" => "12"];
              
              //A predefined array for mapping day values           
              $dayMapping = ["1" => "01", "2" => "02", "3" => "03", "4" => "04", "5" => "05",
                             "6" => "06", "7" => "07", "8" => "08", "9" => "09"];
              
              //If the shipping text contains 'tomorrow', return tomorrow's date
              if(str_contains($shippingText, 'tomorrow'))
              {
                    $tomorrowDate = new DateTime('tomorrow');
                    return $tomorrowDate->format('Y-m-d');
              
              }else
              {
                  //Check if the shipping text contains the date in the year-month-day format (e.g 2021-03-01)
                  $patternDate = '/\d{4}-\d{1,2}-\d{1,2}/';
                  preg_match($patternDate, $shippingText, $shippingDate);
                  
                  if(empty($shippingDate))
                  {
                     //If it does not contain the first date format, check for the day month year format (e.g. 01 May 2021, 3rd Jun 2021)
                     $patternDate = '/\d{1,2}([a-z]{2})?\s[A-Z][a-z]{2}\s\d{4}/';
                     preg_match($patternDate, $shippingText, $shippingDate);
                     
                     if(!empty($shippingDate))
                     {
                        //Split the shipping date into day, month and year 
                        $splitDate = explode(" ", $shippingDate[0]);
                        
                        //Replace any 'th', 'rd', 'st', 'nd' from day with nothing so it only remains the number of the day
                        $day = preg_replace('/[a-z]{2}/', '', $splitDate[0]);
                        
                        //If the day is '1', '2', etc. replace it with '01', '02' etc.
                        if(array_key_exists($day, $dayMapping))
                        {
                            $day = $dayMapping[$day];
                        
                        }
                        
                        //Replace the month from 'Jan', 'May' etc. to '01', '05' etc.
                        $month = $monthMapping[$splitDate[1]];
                        
                        //Concatenate all the elements to a single string, representing the formated date
                        $formatedDate = $splitDate[2] . "-" . $month . "-" . $day;
                        
                        return $formatedDate;
                        
                     }
                           
                  }
                  //If it contains the first format, just return it, without any formatting done
                  else
                  {
                     return $shippingDate[0];
                     
                  }
            }
    }
}
