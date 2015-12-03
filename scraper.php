<?php
### Hornsby Shire Council scraper

require 'scraperwiki.php'; 
require 'simple_html_dom.php';

date_default_timezone_set('Australia/Sydney');

$url_base = "http://hscenquiry.hornsby.nsw.gov.au";
$comment_base = "mailto:devmail@hornsby.nsw.gov.au?subject=Development Application Enquiry: ";

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

$da_page = $url_base . "/Pages/XC.Track/SearchApplication.aspx?d=" .$period. "&k=LodgementDate&t=DA";


$mainUrl = scraperWiki::scrape("$da_page");
$dom = new simple_html_dom();
$dom->load($mainUrl);

# Just focus on the a section of the web site
$dataset = $dom->find("div[id=hiddenresult] div[class=result]");

# The usual, look for the data set and if needed, save it
foreach($dataset as $record) {
    # Slow way to transform the date but it works
    $date_received = explode('<br />', trim($record->find("div", 2)->innertext));
    $date_received = preg_replace('/\s+/', ' ', $date_received[0]);
    $date_received = explode(' ', $date_received);
    $date_received = explode('/', trim($date_received[1]));
    $date_received = "$date_received[2]-$date_received[1]-$date_received[0]";
    
    # Prep some data before hand
    $council_reference = trim($record->find("a",0)->plaintext);
    if ($council_reference[strlen($council_reference)-1] === "/") {
        $council_reference = substr($council_reference, 0, -1);
    }
    $desc = explode('<br />', $record->find("div",1)->innertext);
    $desc = explode('-', $desc[1], 2);
    $desc = html_entity_decode($desc[1]);
    $desc = trim(preg_replace('/\s+/', ' ', $desc));
    $desc = ucwords(strtolower($desc));    

    # Put all information in an array
    $application = array (
        'council_reference' => $council_reference,
        'address' => trim(html_entity_decode($record->find("strong",0)->plaintext)) . "  AUSTRALIA",
        'description' => $desc,
        'info_url' => $url_base . substr($record->find("a",0)->href, 5),
        'comment_url' => $comment_base . $council_reference,
        'date_scraped' => date('Y-m-d'),
        'date_received' => date('Y-m-d', strtotime($date_received))
    );

    # Check if record exist, if not, INSERT, else do nothing
    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $application['council_reference'] . "'");
    if (count($existingRecords) == 0) {
        print ("Saving record " . $application['council_reference'] . "\n");
        # print_r ($application);
        scraperwiki::save(array('council_reference'), $application);
    } else {
        print ("Skipping already saved record " . $application['council_reference'] . "\n");
    }
}

?>
