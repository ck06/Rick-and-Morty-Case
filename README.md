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
* meeseeks:character:character
* meeseeks:character:episode
* meeseeks:character:location

All of these commands support searching via name, API ID, and API url.
Use the `-h` or `--help` for more details and possible other search methods.

# Assignment

Here you quickly reference how to test the assignment requirements.

* Show all characters that exist (or are last seen) in a given dimension
  * `meeseeks:character:location --dimension "name of dimension"`
* Show all characters that exist (or are last seen) at a given location
  * `meeseeks:character:location --name "name of location"`
    * this will show you results of this location across ALL dimensions
    * if you want only those of a specific dimension, specify it in the search string
    * example: `"Earth (C-137)"`
  * `meeseeks:character:location --id "id of location"`
* Show all characters that partake in a given episode
  * `meeseeks:character:episode --name "name of episode"`
  * `meeseeks:character:episode --code "episode code"` 
    * episode code is SxxEyy where xx is a 2-digit season number and yy is a 2-digit episode number
    * example: "S01E4" fetches the 4th episode of the 1st season
  * `meeseeks:character:episode --id "episode number"`
    * episode number across all seasons correlates with the episode ID
    * example: episode 4 is episode 4 of season 1
    * example: episode 12 is episode 2 of season 2
* Showing all information of a character (Name, species, gender, last location, dimension, etc)
  * this has been implemented as the output of the above commands