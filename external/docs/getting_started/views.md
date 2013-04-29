The `View` class in inKWell uses PHP templates not only for speed but also for power and flexibility.  While there are good arguments for non-language specific templates, these generally increase the amount of code required to interface between controllers and views and/or models and views.  We've stuck with PHP to avoid this and a number of other common pitfalls.

## View Objects vs. View Templates {#object_vs_templates}

There's a tendency in the PHP MVC world to consider views to be solely templates, while we disagree with this approach, we'll leave the argument to the blogs and simply state that views are comprised of two parts in inKWell: the view object (which we will consider the view itself) and the view template.

View templates in inKWell should always follow the format of `<name>.<type>.<php>`.  Neither the `type` extension nor the `php` extension are optional.

## Creating a New View {#creating_a_view}

You can create a new main view simply by passing the type.

```php
View::create('html');
```

The above will create a view whose template is `main.html.php` in the configured default view root directory.

### Seeding Components {#seeding_components}

You can add additional sub-components to a view by adding an associative array as the second argument.  The key will be used to place the component inside the main view, and the value points to the template to be placed.

```php
Inkwell\View::create('html', [
	'staple' => 'users/show.html'
]);
```

### Seeding Data {#seeding_data}

A third argument to the `create()` method allows us to seed our view with data.  Using the examples as shown, all data and components will be shared across all the components.

```php
$view = Inkwell\View::create('html', [
	'staple' => 'users/show.html'
], [
	'id'    => 'view_user',
	'title' => 'Hello World!'
	'user'  => $user
]);
```

The `user` element in the data array can then be operated on in the `users/show.html.php` template while the `id` is used in the `main.html.php` template.

## Basic Templating {#basic_templating}

The view templates are included by the view object, so within their scope `$this` will always point to the view object which added them.  Let's examine a stripped down `main.html.php` template to get an idea on how we work with components and data within the template.

```php
<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	%>
	<!doctype html>
	<html>
		<head>
			<title><%= $this['title'] :? 'No Title' %></title>

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		</head>
		<body id="<%= $this['id'] ?: 'page' %>">

			<% $this->place('staple') %>

		</body>
	</html>
	<%
}
```

### Placing Components {#placing_components}

We're able to place our component using the name associated with it when we seeded it and the `place()` method.

```php
<% $this->place('staple') %>
```

### Accessing Data {#accessing_data}

Using `$this` as an array allows us to access our data:

```php
<%= $this['id'] ?: 'page' %>
```

If the data is not available, `NULL` will be returned instead.  You can use the compact ternary operate `?:` to establish a default in the event the data is missing.

## Embedding Views for Data Encapsulation {#embedded_views}

Each view object has its own set of components and data. So if you need to isolate data between two views you can embed another view object as a component.  The example below would mean that the main view would not be able to access the user data.

```php
$view = Inkwell\View::create('html', [
	'staple' => Inkwell\View::create('users/show.html', [], [
		'user' => $user
	])
], [
	'id'   => 'view_user'
]);
```

**Note: The embedded view will have the same root directory as its parent.**

### Accessing the Parent {#accessing_the_parent}

Although the above example shows how we can encapsulate the user data, there are some circumstances where the component's template may need to access more general information from the parent.  This can be done within by using the `parent` property.

```php
The parent's value for id is: <%= $this->parent['id'] %>
```

### The Head View {#the_head_view}

If you have multiple levels of embedded views you may also need to propagate data all the way to the top.  The `head` property is a special view which is common across the main view and all component templates regardless of how deeply they are embedded.  This is most commonly used (in HTML at least) to allow every level of embedded components to add scripts or styles to the `<head>` element, however, it can also be used for data.

```php
<% $this->head->push('title', 'My Title') %>
```

**Note: The head property is only available during compilation, so it is only available inside templates, i.e. you cannot work with the head from inside a controller.**

## Helpers, Assets, and More {#helpers_assets_more}

For more information about extended functionality inside view template including using helpers, adding assets, and additional view methods, check out the [templating documentation](./templating).




