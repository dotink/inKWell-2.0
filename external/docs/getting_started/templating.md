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

### Asset Combination {#asset_combination}

Assets of the same type will be combined based on the first argument to the `asset()` method.  If you add multiple javascript assets to the `'common'` element, they will be concatenated, cached, and served as a single javascript file; thereby reducing the number of HTTP requests.

If you need combine assets differently, you can add a component to the head view which will in turn group assets accordingly:

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
	$this->place('forums');
}
```

## Additional View Methods {#additional_view_methods}

There are a number of view methods which are designed to facilitate templating.  You can use these to perform common templating operations in a cleaner way than what would normally be provided by PHP.

### Each {#each_method}

The `each()` method will iterate over array data elements and passes them to a callback defined as the second argument of the method.  This is useful because closures can be used for output.

```php
<% $this->each('users', function($user, $i) {
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