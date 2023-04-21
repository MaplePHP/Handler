# PHPFuse - Emitter 
PHPFuse is built upon **nikic/FastRoute**. It is possible you change it with you own preferences, but i do not really see why you would, becouse FastRoute works really great and uses regular expression for advanced users. You can add your routers in "app/Http/Routes/web.php".

```php
// Catch a GET response
$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);

// Catch a POST response
$routes->post("/{page:about}", ['Http\Controllers\Pages', "about"]);

// Catch a PUT response
$routes->put("/{page:about}", ['Http\Controllers\Pages', "about"]);

// Catch a DELETE response
$routes->delete("/{page:about}", ['Http\Controllers\Pages', "about"]);

// Catch a Custom response
$routes->map("CLI","/{page:about}", ['Http\Controllers\Pages', "about"]);

// Catch a Multiple responses
$routes->map(["GET", "POST"],"/{page:about}", ['Http\Controllers\Pages', "about"]);

// Take control over all the HTTP request errors (404, 403...). If removed, fuse will generically try to handle the error responses. 
$routes->map("*", '[/{any:.*}]', ['Http\Controllers\HttpRequestError', "handleError"]);

// Group 
$routes->group("/{lang:en}", function($routes) {
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);
    $routes->get("/{page:contact}", ['Http\Controllers\Pages', "contact"]);
});
```

#### Group routers by Pattern
you can very easily group routers under a patter. Bellow for example will show the page/method **about** if you vist **example.com/en/about**.
```php
// Group under language /en
$routes->group("/{lang:en}", function($routes) {
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);
    $routes->get("/{page:contact}", ['Http\Controllers\Pages', "contact"]);
});

// Group under language all possible languages
$routes->group("/{lang:[^/]+}", function($routes) {
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);
    $routes->get("/{page:contact}", ['Http\Controllers\Pages', "contact"]);
});
```
#### Group routers by Middlewares
Middleware is a software component that sits between the client and the server and acts as an intermediary.Middleware can perform various tasks such as authentication, authorization, caching, logging, error handling, and more.
```php
$routes->group(function($routes) {
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);
    $routes->get("/{page:contact}", ['Http\Controllers\Pages', "contact"]);
}, [
    Http\Middlewares\SessionStart::class,
	Http\Middlewares\DomManipulation::class
]);

```
#### Group routers by Pattern and Middlewares
Middleware is a software component that sits between the client and the server and acts as an intermediary.Middleware can perform various tasks such as authentication, authorization, caching, logging, error handling, and more.
```php
$routes->group("/{lang:en}", function($routes) {
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);
    $routes->get("/{page:contact}", ['Http\Controllers\Pages', "contact"]);
}, [
    Http\Middlewares\SessionStart::class,
	Http\Middlewares\DomManipulation::class
]);

```
#### Recommended router usage
```php
$routes->group(function($routes) {
    // Take control over all the HTTP request errors (404, 403...). If removed, fuse will generically try to handle the error responses.
	$routes->map("*", '[/{any:.*}]', ['Http\Controllers\HttpRequestError', "handleError"]);

    // Your routes
	$routes->get("/", ['Http\Controllers\Pages', "start"]);
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);

}, [
    Http\Middlewares\SessionStart::class,
	Http\Middlewares\DomManipulation::class
]);
```
