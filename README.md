# php-benchmarks-library

This is a library for frontends of https://hostingstabilitymeter.com/ 

It allows to perform benchmarks, make correct results arrays and send them to the backend. Duration of each benchmark is no more than 1 second.

Also it collects and sends some hardware information such as CPU family, number of cores, bogomips, amount of RAM, OS family.

## Files

* **class.hosting-stability-meter-benchmarks.php** - base class itself. It has been developed for WordPress plugin but it doesn't contain any WP-specific things. So you can use in for any CMS.
* **example-cli-hosting-stability-meter.php** - just an example.

## See also 
https://hostingstabilitymeter.com/about
