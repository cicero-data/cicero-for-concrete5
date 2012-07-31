Azavea Cicero Package for Concrete5 CMS
===============

This package contains the Concrete5 blocks for [Cicero Live](http://www.azavea.com/products/cicero/live-demo/) and the [upcoming elections](http://www.azavea.com/cicero/) feed.

Each block takes advantage of Azavea's [Cicero API](http://www.azavea.com/cicero/) for political data and elected-official lookups by street address.

Important Note!
--------------

The Cicero Live block is still coded to use the SOAP version of the Cicero API. (Cicero Elections uses the RESTful API.). Azavea is discontinuing access to the SOAP API in April of 2013. An update to this block should arrive in the next few months of 2012. If you install this block, remember that you WILL need to install the future update to use Cicero Live after April 2013. 

Installation
------------

Copy the azavea_cicero folder into your Concrete5 packages directory. Then complete installation from your site's Dashboard (Dashboard>Extend concrete5, or Add Functionality). The "Azavea Cicero" add-on should be available and Awaiting Installation. 

Next, sign up for a 1,000 API credit / 90 day [free trial of Cicero](http://www.azavea.com/products/cicero/free-trial/). Each address lookup you perform from the Cicero Live block will count against these 1,000 credits. The Cicero Elections block does not cost any credits, but currently your free trial will still expire after 90 days. If you like Cicero, Azavea has affordable API-credit pricing starting at just $298 for 10,000 credits per year for government, non-profit, and education customers.

Additionally, to use the map feature of Cicero Live, you will need to sign up for a [Microsoft Bing Maps API key](https://www.bingmapsportal.com/).

Add either the Cicero Live or Cicero Elections blocks to a page on your Concrete5 site. In edit mode, click on the block and select "Edit" from the dropdown. In the popup that appears, you should be able to enter your Cicero account details and Bing API key.

Support
-------

Bug reports and feature requests can be reported to the [issue tracker](https://github.com/azavea/cicero-for-concrete5/issues).

Additionally, you may contact [Andrew Thompson](mailto:athompson@azavea.com), Azavea's Community Evangelist, and he will do his best to help you ;-).

