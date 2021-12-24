# Basic universal minified Nette API
Welcome to a little experiment over Nette Framework. The aim of this is to reduce the serverload while using Nette as API. Nette is great and complex thing. So is APItte. This project is very simple, minimalistic and lightweight (by design).
- replaces Nette\Application
- gets rid of unnecessary complicated stuff
    - presenters (turned into Endpoints)
    - router (simplified and merged with Request)
    - some other unnecessary autoloaded things in the container
- Gets data from JS/Fetch post data (that arrived using STDIN instead of $_POST)
- use Tracy to display error over XHR/Fetch requests
- Introduces minimalistic AJAX API security
- Connects with React App, run the whole development app (after preparing with `yarn` and `composer install`) using `yarn start` in the home directory
- If you dont like anything, just override that part and change the application in your bootstrap.php

## Dont get me wrong, I love Nette!
Nette, Latte etc. are great. It's IMHO just not efficient enough for modern "API" world (and maybe nor is this project :D).

## Clone and enjoy