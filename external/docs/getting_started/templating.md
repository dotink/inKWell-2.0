When a view object is compiled it iterates through all of its components and compiles them from the bottom up.  Each component, whether it is a direct template reference or an embedded view object will be compiled and stored for placement.  The compilation process essentially includes the template files with a standard PHP `include` call, so all the content of the template is executed as PHP.  This means you can do quite a bit inside of templates to construct rather complex views.  Here we will describe that functionality in full.

## Helpers {#helpers}

The `library/helpers/view` (by default) directory contains a list of view helpers named after the view type as derived from the first extension on a template's filename.  So for example, creating a view with the `main.html.php` template will automatically attempt to include `library/helpers/view/html.php`.

Helper classes and functions are created in the `Dotink\Inkwell\View\<TYPE>` namespace.  You can use them easily within your view templates by namespacing the template:

```php
<% namespace Dotink\Ikwell\View\HTML
{
	// code here

	%>
	<!-- HTML here -->
	<%
}
```

Using the HTML helpers as an example, we can see how we can easily encode special characters in view data using the `e()` function:

```php
<%= e($this['data']) ?: 'Default' %>
```

## Adding Assets {#adding_assets}

It is possible to add assets to [the head](./views#the_head_view), view object, or parent view object from within a template.  It is good practice to add all assets in the top block of a template.

```php
<% namespace Dotink\Ikwell\View\HTML
{
	$this->head->asset('common', 'scripts/jquery/jquery-1.9.1.js');
	$this->head->asset('common', 'styles/forums/main.css');

	%>
	<!-- HTML here -->
	<%
}
```

### Asset Combination and Preprocessing {#asset_combination}

Assets of the same type are combined and pre-processed by default.  That is, if you add multiple javascript assets to the `'common'` element (or any other element you create), they will be concatenated, cached, and served as a single javascript file; thereby reducing the number of HTTP requests.  To disable combination and preprocessing of assets simply add a `FALSE` argument when they are placed:

```php
$this->place('forums', FALSE);
```

Assets can be grouped differently by creating different templates and adding them to a pre-existing element being placed.  The example below shows how we can group our `'forums'` assets and use a custom head template which is attached to the `'common'` element.  This allows you to disable combining or pre-processing on certain elements.

```php
<% namespace Dotink\Ikwell\View\HTML
{
	$this->head->asset('common', 'scripts/jquery/jquery-1.9.1.js');
	$this->head->asset('common', 'styles/dotink/forums/main.css');

	$this->head->asset('forums', 'scripts/dotink/forums/ajax_posting.js');
	$this->head->asset('forums', 'scripts/dotink/forums/dynamic_threads.js');

	$this->head->add('common', 'dotink/forums/head.html');

	%>
	<!-- HTML here -->
	<%
}
```

Then within the `dotink/forums/head.html.php` file:

```php
<% namespace Dotink\Ikwell\View\HTML
{
	//
	// The previously set javascript assets will not be combined or pre-processed.
	//

	$this->place('forums', FALSE);
}
```

You may wish to do this if you're using a public CDN and there is a high liklihood your visitors will have common scripts or CSS already cached.

## Additional View Methods {#additional_view_methods}

There are a number of view methods which are designed to facilitate templating.  You can use these to perform common templating operations in a cleaner way than what would normally be provided by PHP.

### Each {#each_method}

The `each()` method will iterate over array data elements and passes them to a callback defined as the second argument of the method.  This is useful because closures can be used for output.

```php
<% $this->each('users', function($user, $i) { %>
	<section class="user">
		<h3><%= e($user->getName()) %></h3>
		<div class="bio">
			<%= $user->getBio() %>
		</div>
	</section>
<% }) %>
```

### Repeat {#repeat_method}

The repeat method allows you to repeat the previous emitter used in an `each()` call.  This is useful for things like navigation where you need to build nested structures using the same basic template.

```php
<% $this->each('nav_items', function($item, $i) { %>
	<% if ($i == 0) { %><ul><% } %>
		<li>
			<a href="<%= e($item->getLink()) %>"><%= $item->getName() %></a>
			<% $this->repeat($item->getChildren()) %>
		</li>
	<% if ($i == 0) { %><ul><% } %>
<% }) %>
```

## Accessing the App Container {#app_access}

View template are somewhat unique in that they have direct access to the application instance using the `$app` variable.  Aside from `$this` which references the view object, `$app` is the only predefined variable inside a template.

This is most commonly used in order to access the application router in order to compose links:

```php
<% namespace Dotink\Inkwell\View\HTML
{
	$username  = $this['user']->getUserName();
	$links     = [
		'edit_profile' => $app['router']->compose(
			'/users/[username]?action=edit', [
				'username' => $username
			]
		)
	];

	%>
	<a href="<%= $links['edit_profile'] %>">Edit Your Proile</a>
	<%
}
```

