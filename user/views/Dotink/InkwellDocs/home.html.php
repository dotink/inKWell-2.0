<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	$this->head->push('title', 'A PHP Framework for PHP Developers');

	%>
	<header>
		<a href="/">
			<img class="logo" src="/assets/images/inkwell_logo_dark.png" />
		</a>
		<h1>A PHP Framework for PHP Developers</h1>
		<h2>
			 We've reached 2.0 Beta! and we couldn't have done it without you, the PHP community.
		</h2>
		<a class="action" href="http://www.github.com/dotink/inkwell-2.0">Visit Us On GitHub</a>
	</header>


	<div class="brochure group">
		<div>
			<section>
				<h3>Fast</h3>
				<object type="image/svg+xml" data="/assets/images/fast.svg" width="128">Your browser does not support SVG</object>
				<p>
					This page, from start to finish in &lt; 40ms on a Lenovo U310.
				</p>
			</section>
		</div>
		<div>
			<section>
				<h3>Fun</h3>
				<object type="image/svg+xml" data="/assets/images/fun.svg" width="128">Your browser does not support SVG</object>
				<p>
					Unique architecture and design gives tons of room to customize.
				</p>
			</section>
		</div>
		<div>
			<section>
				<h3>Flexible</h3>
				<object type="image/svg+xml" data="/assets/images/flexible.svg" width="128">Your browser does not support SVG</object>
				<p>
					Convention when you want, configuration when you need.
				</p>
			</section>
		</div>
	</div>

	<div class="group">
		<%= $this['doc'] %>
	</div>

	<%
}
