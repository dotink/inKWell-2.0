


```php
$view = App\View::create('html.php');

$view->addAssets([
	'js'  => [

	],

	'css' => [

	]
]);


```

add

pack
push
pull
peal

has('users', TRUE)
has('users', [[$val3, $val4]]);

count('users')

insert
inject

each
join('users', '::')


digest('pagination', function($view){ ?>


<? });


render()
buffer()


<% namespace Dotink\Inkwell\View; %>

add('js', 'jquery.js')

<div>
	<% $this->each('users', function($user, $i) { %>
		<div class="user">
			<h2><%= e($user->getName()) %></h2>
		</div>
	<% }); %>
</div>




