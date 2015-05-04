Search Api Attachments

This module will extract the content out of attached files using the Tika
library or the build in Solr extractor and index it.
Search API attachments will index many file formats.

REQUIREMENTS
------------
Requires the ability to run java on your server and an installation of the
Apache Tika library if you don't want to use the Solr build in extractor.

MODULE INSTALLATION
-------------------
Copy search_api_attachments into your modules folder

Install the search_api_attachments module in your Drupal site

Go to the configuration: admin/config/search/search_api_attachments

Choose an extraction method and follow the instructions under the respective
heading below.


EXTRACTION CONFIGURATION (Tika)
-------------------------------
On Ubuntu 14.04

Install java
> sudo apt-get install openjdk-7-jdk

Download Apache Tika library: http://tika.apache.org/download.html
> wget http://mir2.ovh.net/ftp.apache.org/dist/tika/tika-app-1.8.jar

Enter the full path on your server where you downloaded the jar
e.g. /var/apache-tika/tika-app-1.8.jar.

EXTRACTION CONFIGURATION (Solr)
-------------------------------
Install and configure the search_api_solr module
https://www.drupal.org/project/search_api_solr
Make sure to configure it as explained in its README.txt
Create at least one solr server
Now you can choose it from /admin/config/search/search_api_attachments

Note: For Solr extraction to work, we need solarium in 3.3.0 or greater.