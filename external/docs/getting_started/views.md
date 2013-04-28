The `View` class in inKWell uses PHP templates not only for speed but also for power and flexibility.  While there are good arguments for non-language specific templates, these generally increase the amount of code required to interface between controllers and views and/or models and views.  We've stuck with PHP to avoid this and a number of other common pitfalls.

## View Objects vs. View Templates {#object_vs_templates}

There's a tendency in the PHP MVC world to consider views to be solely templates, while we disagree with this approach, we'll leave the argument to the blogs and simply state that views are comprised of two parts in inKWell: the view object (which we will consider the view itself) and the view template.

## Template Resolution {#template_resolution}

View templates in inKWell should always follow the format of `<name>.<type>.<php>`.  Neither the `type` extension nor the `php` extension are optional.

## Creating a New View {#creating_a_view}

There are several ways to approach view creation, but for the sake of simplicity, here are some common things you'll see and a quick explanation of each.

```php
View::create('html');
```

The above will create a view whose template is `main.html.php` in the configured default view root directory.

```php
View::create('users/show.html')
```

This example would use the template `users/show.html.php`, however, will have a `NULL` root directory.  Views with a `NULL` root directory when compiled will use one of the following:

- The root directory of their parent view (if they have a parent)
- The current working directory (if they have no parent)

### Seeding Components {#seeding_components}

Using the above two examples, we can see how this makes a lot more sense when they are combined to form a single view:

```php
View::create('html', [
	'staple' => View::create('users/show.html')
]);
```

The second argument to view create shown above, an associative array, is a list of component assignments for the `main.html.php` view template.  If we look at this template we will see where the `'staple'` component gets placed:

```php
<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	$this->head->asset('common', 'http://dotink.github.io/inKLing/inkling.css');

	%>
	<!doctype html>
	<html>
		<head>
			<title><%= $this->head->join('title', '::', TRUE) %></title>

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

			<% $this->head->place('common') %>

		</head>
		<body id="<%= $this['id'] ?: 'page' %>">

			<% $this->place('header') %>
			<% $this->place('staple') %>
			<% $this->place('footer') %>

		</body>
	</html>
	<%
}
```

### Seeding Data {#seeding_data}

In addition to the components which you can see being placed (`place()`) in the main HTML view above, you will also note the use of the view as an array such as `$this['id']`.  This is how you access the view data inside the template.  Data, similar to components, can be added when creating the view:

```php
$view = View::create('html', [
	'staple' => View::create('users/show.html')
], [
	'id' => 'view_user'
]);
```

## Data Encapsulation {#data_encapsulation}

Each view object has it's own set of components and data.  Respectively, if you're placing a component or accessing data in a template loaded in a view which is a component of another view, the components and data are separate.  If the view template needs to access the same data, you can assign it directly to a component:

```php
$view = View::create('html', [
	'staple' => 'users/show.html'
], [
	'id' => 'view_user'
]);
```

