require File.dirname(__FILE__) + '/lib_icon_rest_xml/scraper'

case ENV['MORPH_PERIOD']
  when 'lastmonth'
  	period = "lastmonth"
  when 'thismonth'
  	period = "thismonth"
  when
    period = "thisweek"
  else
    period = "last14days"
end
puts "Getting data in `" + period + "`, changable via MORPH_PERIOD environment"

scrape_icon_rest_xml("http://hscenquiry.hornsby.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx", "d=" + period + "&k=LodgementDate&o=xml", false)
