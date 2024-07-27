# Setup

Run the following commands in the terminal:  
* docker compose up --detach  
* docker compose exec php bash
  * composer install
  * bin/console doctrine:migrations:migrate
  * bin/console app:crawl

Crawling only has to be done once and will take 4~5 minutes to complete due to built-in delays.  
Once completed, you'll have a running environment with a local copy of the Rick and Morty data.

# Usage

The following commands are created as per the assignment;
* meeseeks:character
* meeseeks:character:episode
* meeseeks:character:location
* meeseeks:character:dimension 

All of these commands support searching via name, API ID, and API url.
Use the `-h` or `--help` for more details and possible other search methods.
