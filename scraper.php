<?php
### Hornsby Shire Council scraper

require_once 'vendor/autoload.php';
require_once 'vendor/openaustralia/scraperwiki/scraperwiki.php';

use PGuardiario\PGBrowser;

date_default_timezone_set('Australia/Sydney');

$url_base = "http://hscenquiry.hornsby.nsw.gov.au";
$comment_base = "mailto:GIPA@hornsby.nsw.gov.au?subject=Development Application Enquiry: ";
$date_format = 'Y-m-d';

# Default to 'thisweek', use MORPH_PERIOD to change to 'thismonth' or 'lastmonth' for data recovery
switch(getenv('MORPH_PERIOD')) {
    case 'thismonth' :
        $period = 'thismonth';
        break;
    case 'lastmonth' :
        $period = 'lastmonth';
        break;
    case 'thisweek' :
    default         :
        $period = 'thisweek';
        break;
}

$rss_feed = $url_base . "/Pages/XC.Track/SearchApplication.aspx?d=" .$period. "&k=LodgementDate&t=DA&o=rss";

$browser = new PGBrowser();
$browser->setUserAgent("Mozilla/5.0 (compatible; PlanningAlerts/0.1; +http://www.planningalerts.org.au/)");
$rss_response = $browser->get($rss_feed);
$rss = simplexml_load_string($rss_response->html);


// Iterate through each application
foreach ($rss->channel->item as $item)
{
    // RSS title appears to be the council reference
    $rss_title = explode(' - ', $item->title);
    $council_reference = trim($rss_title[0]);

    // RSS description appears to be the address followed by the actual description
    $rss_description = preg_split('/\./', $item->description, 2);
    $address = trim($rss_description[0]);
    $address = trim(preg_replace('/\s+/', ' ', $address));

    $description = trim($item->category . ' -' . $rss_description[1]);
    $description = trim(preg_replace('/\s+/', ' ', $description));

    $date_scraped = date($date_format);
    $date_received = date($date_format, strtotime($item->pubDate));

    $record = array(
        'council_reference' => $council_reference,
        'address' => $address,
        'description' => $description,
        'info_url' => $url_base . trim($item->link),
        'comment_url' => $comment_base . $council_reference,
        'date_scraped' => $date_scraped,
        'date_received' => $date_received
    );

    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $record['council_reference'] . "'");
    if (sizeof($existingRecords) == 0)
    {
//         var_dump($record);
        print ("Saving record " .$record['council_reference']. " - " .$record['address']. "\n");
        scraperwiki::save(array('council_reference'), $record);
    }
    else
    {
        print ("Skipping already saved record " . $record['council_reference'] . "\n");
    }
}
