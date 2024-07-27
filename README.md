# Setup

Run the following commands in the terminal:  
* docker compose up --detach  
* docker compose run php bash
  * composer install
  * bin/console doctrine:migrations:migrate
  * bin/console app:crawl

Crawling only has to be done once and will take 4~5 minutes to complete due to built-in delays.  
Once completed, you'll have a running environment with a local copy of the Rick and Morty data.
