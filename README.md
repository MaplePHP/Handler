
# MaplePHP - Handler 
MaplePHP is built upon **nikic/FastRoute**. It is possible you change it with you own preferences, but i do not really see why you would, becouse FastRoute works really great and uses regular expression for advanced users. You can add your routers in "app/Http/Routes/web.php".


### Structrue

Group
```html
@routes->group([PATTERN], [CALLABLE], [MIDDELWARE]);
@routes->group([CALLABLE], [MIDDELWARE]);
@routes->group([CALLABLE]);
```

Router
```html
@routes->get([PATTERN], [ [CLASS], [METHOD] ]);
```

### Self explainable example
This is a self explainable example for advanced users
```php
$routes->group("[POSSIBLE_PATTERNS]", function($routes) {
    
    // Router data - will inherit the group pattern and middlewares
    $routes->get("[PATTERN]", ['CLASS', "METHOD"]);

	// You can nest groups in infinity
    $routes->group(function($routes) {
    	// Router data - will inherit group and group parents patterns and middlewares
		$routes->get("[PATTERN]", ['CLASS', "METHOD"]);
		$routes->post("[PATTERN]", ['CLASS', "METHOD"]);

    }, [
	    [Http\Middlewares\LoggedIn::class, "privateZone"],
	]);

}, [
    [
    	Http\Middlewares\SessionStart::class,
		Http\Middlewares\DomManipulation::class
    ]
]);

```

## How it works

### Different router responses
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

// Take control over all the HTTP request errors (404, 403...). If removed, MaplePHP will generically try to handle the error responses. 
$routes->map("*", '[/{any:.*}]', ['Http\Controllers\HttpRequestError', "handleError"]);

// Group 
$routes->group("/{lang:en}", function($routes) {
	$routes->get("/{page:about}", ['Http\Controllers\Pages', "about"]);
    $routes->get("/{page:contact}", ['Http\Controllers\Pages', "contact"]);
});
```

### Groups
You can easily group routers under a patter. Bellow for example will show the page/method **about** if you vist **example.com/en/about**.
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

### Middlewares
Middleware is a software component that sits between the client and the server and acts as an intermediary. Middleware can perform various tasks such as authentication, authorization, caching, logging, error handling, and more. In more simpler terms Middlewares will quickly and easily extend you applications functionallity accross specified routers.

```php
$routes->group(function($routes) {
	$routes->get("/{form:login}", ['Http\Controllers\Users\Login', "form"]);

	// You can nest groups in infinity
	$routes->group(function($routes) {
        // Router data - will inherit group and group parents patterns and middlewares
        $routes->get("/{profile:profile}", ['Http\Controllers\Examples\PrivatePage', "profile"]);
    }, [
        [Http\Middlewares\LoggedIn::class, "privateZone"]
    ]);

}, [
    Http\Middlewares\SessionStart::class,
	Http\Middlewares\DomManipulation::class
]);

```

### Patterns
It is possible to use Regular Expression (Regex). Form more information you can also [click here](https://github.com/nikic/FastRoute).

#### In very simple applications you could write a pattern like this
```html
/param1
/param1/param2/param3
```

#### But in more advanced applicaton it is good know some more advanced patterns:
Find all, strings and numbers (counts as one parameter)
```html
[^/]+
```

Allow only numbers
```html
\d+
```

Find everything for dynamic parameters 
```html
.+
```
IF match or else
```html
(?:match|elseMatch)
```

It is also highly recommended to attach a KEY to a pattern. With the example above you can write more complete routes like bellow.
```html
/{page:param1}
/{page:[^/]+}
OK: /about-us
NOT_FOUND: /about-us/environment
```

```html
/{page:[^/]+}/{subpagePage:[^/]+}
OK: /about-us/environment
```
```html
/{page:.+}
OK: /about-us/environment
```
```html
/{page-id:\d+}
OK: /5242
NOT_FOUND: /about-us
```
```html
/{product-id:\d+}/{slug:[^/]+}
OK: /5242/round-table
```
```html
/{cat:.+}[/{pagination:pagin-\d+}]
OK: /cat1/cat2/.../cat5
OK: /cat1/cat2/.../cat5/pagin-2
```

### Complete example
```php
$routes->group(function($routes) {
    // Will handle all HTTP request errors 
	$routes->map("*", '[/{any:.*}]', ['Http\Controllers\HttpRequestError', "handleError"]);

    // Your routes
	$routes->get("/", ['Http\Controllers\Examples\Pages', "start"]);
	$routes->get("/{page:about}", ['Http\Controllers\Examples\Pages', "about"]);

    
    $routes->group(function($routes) {
        // Regular page with form
        $routes->get("/{form:login}", ['Http\Controllers\Users\Login', "form"]);
        // Open form in a modal with ajax call
        $routes->get("/{form:login}/{model:model}", ['Http\Controllers\Users\Login', "formModel"]);
        // Login request
        $routes->post("/{form:login}", ['Http\Controllers\Users\Login', "login"]);

    }, [
        [Http\Middlewares\LoggedIn::class, "publicZone"],
    ]);

    $routes->group(function($routes) {
        // Will handle all HTTP request errors 
        $routes->get("/{profile:profile}", ['Http\Controllers\Examples\PrivatePage', "profile"]);

    }, [
        [Http\Middlewares\LoggedIn::class, "privateZone"]
    ]);

}, [
    Http\Middlewares\SessionStart::class,
	Http\Middlewares\DomManipulation::class
]);
```

